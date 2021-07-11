---
layout: post
title: "Profiling PHP in production at scale"
tags:
- wikipedia
- performance
plainwhite:
  original_url: https://calendar.perfplanet.com/2020/profiling-php-in-production-at-scale/
  original_label: Performance Calendar 2020
  also_url: https://techblog.wikimedia.org/2021/03/03/profiling-php-in-production-at-scale/
---

At Wikipedia, we built an efficient sampling profiler for PHP, and use it to instrument live requests. The trace logs and flame graphs are powered by a simple setup that involves only free open-source software, and runs at low infrastructure cost.

<!--more-->

I'd like to demonstrate that profiling doesn't have to be expensive, and can even be performant enough to run continually in production! The principles in this article should apply to most modern programming languages. We developed [Excimer](https://github.com/wikimedia/php-excimer/), a sampling profiler for PHP; and [Arc Lamp](https://github.com/wikimedia/arc-lamp/) for processing stack traces and generating flame graphs.

<figure markdown="block">
![Flame Graph example](/assets/attachments/2020_profiling_figure1_flamegraph_intro.png)
<figcaption markdown="span">
Figure 1: A daily flame graph, from [performance.wikimedia.org](https://performance.wikimedia.org/php-profiling/).
</figcaption>
</figure>

## Exhibit A: The Flame Graph

Our goal is to help developers understand the performance characteristics of their application through flame graphs. Flame graphs visually describe how and where an application spends its time. You may have seen them while using the browser's developer tools, or after running an application via a special tool from the command-line.

Profilers often come with a cost – code may run much more slowly when a profiler is active. This cost is fine when investigating something locally or ad-hoc, but it's not something we always want to apply to live requests.

To generate flame graphs, we sample stack traces from web servers that are serving live traffic. This is achieved through a sampling profiler. We then send the stack traces to a stream, which is then turned into a flame graph.

Our target was to add less than **1 millisecond** to user-facing web requests that complete within 50ms or 200ms, and add under 1% to long-running processes that run for several minutes.
And so our journey begins, with the quest for an efficient sampling profiler.

## How profiling can be expensive

### Internal entry and exit hooks

<span id="fnr1"></span>XHProf is a native extension for PHP. It intercepts the start and end of every function call, and may record function hierarchy, call count, memory usage, etc. When used as a debugger to trace an entire request, it can slow down your application by 3X (+200%). <sup>[[1]](#fn1 "Jump to footnote 1")</sup>

It has a sampled mode in which its entry-exit hooks are reduced to no-ops most of the time, and otherwise records only a stack trace. But this could still run code [10-30% slower](https://phabricator.wikimedia.org/T176916#4293822). The time spent within these hooks for "no-op" cases was fairly small. But, the act of switching to and from such a hook has a cost as well. And, when we intercept every single function in an application, those costs quickly add up.

We also found that the mere presence of these entry-exit hooks prevented the PHP engine from using certain [optimisations](https://www.mediawiki.org/wiki/Excimer#Background). When evaluating performance, compare not only a plugin being used vs not, but also compare to a system with the plugin being entirely uninstalled!

We also looked at external ways to capture stack trace samples, using GDB, or `perf_events`.

### External interrupts

<span id="fnr2"></span>GDB unlocks the full power of the Linux kernel to halt a process in mid-air, break into it, run your code in its local state, and then gets out to let the process resume – all without the process' awareness. <sup>[[2]](#fn2 "Jump to footnote 2")</sup>

[GDB](https://en.wikipedia.org/wiki/GNU_Debugger) does this through `ptrace`, which comes with a relatively high interrupt cost. But, the advantage of this approach is that there is no overhead when the profiling is inactive. Initial exploration showed that taking a single sample could delay the process by [a whole second](https://phabricator.wikimedia.org/T176916#4301180) while GDB attached and detached itself. There was some room for improvement here (such as GDB preloading), but it seemed inevitable that the cost would be magnitudes too high.

### perf_events

`perf_events` is a Linux tool that can inspect a process and read its current stack trace. As with GDB, when we're not looking, the process runs as normal. `perf_events` takes samples relatively quickly, has growing ecosystem support, and its cost can be [greatly minimised](http://www.brendangregg.com/perf.html).

If your application runs as its own compiled program, such as when using C or Rust, then this solution might be ideal. <span id="fnr3"></span><span id="fnr4"></span>But, runtimes that use a [virtual machine](https://en.wikipedia.org/wiki/Application_virtual_machine) (like PHP, Node.js, or Java), act as an intermediary process with their own way of managing an application's call stack. All that `perf_events` would see is the time spent inside the runtime engine itself. This might tell you how internal operations like "assign_variable" work, but is not what we are after. <sup>[[3]](#fn3 "Jump to footnote 3")</sup><sup>[[4]](#fn4 "Jump to footnote 4")</sup>

## Introducing: Excimer

Excimer is a small C program, with a binding for PHP 7. Its binding can be used to collect sampled stack traces. It leverages two low-level concepts that I'll briefly describe on their own: POSIX timers, and graceful interrupts.

### POSIX timers

With a POSIX timer, we directly ask the operating system to notify us after a given amount of time has elapsed. It can notify us in one of several ways. The timer can deliver signal events to a particular process or thread (which we could poll for). Or, the timer can respond by spawning a new concurrent thread in the process, and run a callback there. This last option is known as `SIGEV_THREAD`.

### Graceful interrupts

There is a `vm_interrupt` global flag in the PHP engine that the virtual machine checks during code execution. It's not a very precise feature, but it is checked at least once before the end of any userland function, which is enough for our purpose.

If during such a check the engine finds that the flag is raised (set to `1` instead of `0`), it resets the flag and runs any registered callbacks. The engine uses the same feature for enforcing request timeouts, and thus no overhead is added by using it to facilitate our sampling.

## At last, we can start sampling!

When the Excimer profiler starts, it starts a little POSIX timer, with `SIGEV_THREAD` as the notification type. To give all code an equal chance of being sampled, the first interval is staggered by a random fraction of the sampling interval.

We'll also give the timer the raw memory address where the `vm_interrupt` flag is located (you'll understand why in a moment). The code to set up this timer is negligible and happens only once for a given web request. After that, the process is left to run as normal.

When the sampling interval comes around, the operating system spawns a new thread and runs Excimer's timer handler. There isn't a whole lot we can do from here since we're in a thread alongside the PHP engine which is still running. We don't know what the engine is up to. For example, we can't safely and non-blockingly read the stack trace from here. Its memory may mutate at any time. What we do have is the raw address to the `vm_interrupt` flag, and we can boldly write a `1` there! No matter where the engine is at, that much is safe to do.

Not long after, PHP will reach one of its checkpoints and find the flag is raised. It resets the flag and makes a direct inline call to Excimer's profiling code. Excimer simply reads out a copy of the stack trace, optionally flushing or sending it out, and then PHP resumes as normal.

If the process runs long enough to cover more than one sampling interval, the timer will notify us once more and the above cycle repeats.

## Putting it all together

It's time to put our sampling profiler to use!

* Collect – start the profiler and set a flush destination.
* Flush – send the traces someplace nice.
* Flame graphs – combine the traces and generate flame graphs.

<figure markdown="block">
![Architecture diagram for Arc Lamp](/assets/attachments/2020_profiling_figure2_arclamp.png)
<figcaption markdown="span">
Figure 2: Web servers send stack traces to a Redis stream. This is independently read into a rotated log file and periodically converted to a Flame Graph.
</figcaption>
</figure>

### Collect

The application can start the Excimer profiler with a sampling interval and flush callback.

```php
static $prof = new ExcimerProfiler();
$prof->setPeriod(60); // seconds
$prof->setFlushCallback(function ($log) { ArcLamp::flush($log); });
$prof->start();
```

The above snippet is from Arc Lamp, as used on Wikipedia. This code would be placed in the early setup phase of your application. In PHP, this could also be placed in an [`auto_prepend_file`](https://www.php.net/manual/en/ini.core.php#ini.auto-prepend-file) that automatically applies to your web entry points, without needing any code or configuration inside the application.

### Flush

Next we need to flush these traces to a place where we can find them later. This place needs to be reachable from all web servers, accept concurrent input at low latencies, and have a fast failure mode. I subscribe to the ["boring technology"](https://mcfunley.com/choose-boring-technology) ethos, and so if you have existing infrastructure in use for something like this, I'd start with that. (e.g. ZeroMQ, or rsyslog/Kafka.)

At Wikimedia Foundation, we choose Redis for this. We ingest about 3 million samples daily from a cluster of 150 Apache servers in any given data centre, using a 60s sample interval. These are all received by a single Redis instance.

### Flame Graphs

Arc Lamp consumes the Redis stream and writes the trace logs in batches to locally rotated files. You can configure how to split and join these. For example, we split incoming samples by "web", "api", or "job queue" entry point; and join by the hour, and by full day.

<figure markdown="block">
![Wikimedia Foundation, production flame graph for 2020-12-06. Generated by Arc Lamp using 2.8 million stack trace samples as collected from Excimer PHP](/assets/attachments/2020_profiling_figure3_flamegraph_summary.png)
</figure>

You can browse our daily flame graphs on [performance.wikimedia.org](https://performance.wikimedia.org/php-profiling/), or check out the [Arc Lamp](https://github.com/wikimedia/arc-lamp/) and [Excimer](https://github.com/wikimedia/php-excimer/) projects.

_Thanks to: Tim Starling who single-handedly developed Excimer, Stas Malyshev for his insights on PHP internals, Kunal Mehta as Debian developer and fellow Wikimedian who packaged Excimer, and Ori Livneh who originally created Arc Lamp and got me into all this._

## Further reading

* _[How editing Wikipedia became twice as fast on HHVM](https://diff.wikimedia.org/2014/12/29/how-we-made-editing-wikipedia-twice-as-fast/)_, Ori Livneh, 2015.
* _[How Wikimedia Foundation successfully migrated to PHP 7](https://launchdarkly.com/blog/how-the-wikimedia-foundation-successfully-migrated-to-php7/)_, Effie Mouzeli, 2019.
* _[WikimediaDebug for PHP is here](https://techblog.wikimedia.org/2019/12/16/wikimediadebug-v2-is-here/#how-does-it-all-work)_, Timo Tijhof, 2019.
* _[PHP Virtual Machine: vm_interrupt](https://nikic.github.io/2017/04/14/PHP-7-Virtual-machine.html)_, Nikita Popov, 2017.
* [Flame Graphs](http://www.brendangregg.com/flamegraphs.html) by Brendan Gregg.
* [perf_events (Linux)](https://en.wikipedia.org/wiki/Perf_%28Linux%29) on Wikipedia.
* [PHP Internals Handbook: All about hooks](http://www.phpinternalsbook.com/php7/extensions_design/hooks.html).
* [POSIX timer: notify options](https://man7.org/linux/man-pages/man2/timer_create.2.html), Linux manual pages.
* [Wikimedia Foundation: Site infrastructure](https://wikitech.wikimedia.org/wiki/Wikimedia_infrastructure).

Foonotes:

1. {:#fn1} We already used XHProf as a debugger for capturing complete and unsampled profiles over a single web request. The original [php-xhprof](https://github.com/phacility/xhprof) targeted PHP 5. When we migrated to HHVM, we continued using its built-in port of XHProf. We since migrated to PHP 7 and use [php-tideways](https://github.com/tideways/php-xhprof-extension), which is a maintained alternative with PHP 7 support. The original xhprof has since published an [experimental](https://github.com/phacility/xhprof/tree/dab44f76da5c8a0d4f1339f7d2ea2bc42408e8e9) branch with tentative PHP 7 support. [↩︎](#fnr1 "Jump back")
2. {:#fn2} See also _[Poor man’s contention profiling](https://dom.as/2009/02/15/poor-mans-contention-profiling/)_ (Domas Mituzas, 2009), in which GDB is used to profile a MySQL server. [↩︎](#fnr2 "Jump back")
3. {:#fn3} If the VM runtime includes a JIT compiler, then perf_events could be used still. With a [JIT compiler](https://en.wikipedia.org/wiki/Just-in-time_compilation), the runtime compiles your source code into machine code, which then becomes a native part of the VM's process. The VM would call these unnamed chunks of machine code directly by their memory address. This is a bit like how "eval" can create functions in a scripting language. You then need a `perf.map` file so that `perf_events` can turn these unnamed addresses back into the names of classes and methods from which a chunk of code originated. This is known as symbol translation. There [is support](http://www.brendangregg.com/perf.html#JIT_Symbols) for perf map files in Node.js and Java. [↩︎](#fnr3 "Jump back")
4. {:#fn4} PHP 8.0 was [announced](https://www.php.net/releases/8.0/en.php) last week, and includes [a new JIT](https://wiki.php.net/rfc/jit) with perf.map support. I look forward to exploring this over the coming year! [↩︎](#fnr4 "Jump back")

