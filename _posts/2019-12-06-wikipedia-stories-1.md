---
layout: post
title: "Missing partitions, disappearing audio players, and extreme packet loss"
tags: engineering-stories
redirect_from:
- /posts/2018/production-excellence-aug-2018/ # draft
- /posts/2018/wikipedia-stories-aug-2018/ # renamed
- /posts/2019/wikipedia-stories-01/ # renamed
plainwhite:
  also_url: https://phabricator.wikimedia.org/phame/blog/view/1/
  also_label: Production Excellence at Wikipedia
---

These are stories from bug hunts and incident investigations at Wikipedia.

<!--more-->

* [New database partition](#new-database-partition)
* [Mystery of Disappearing Audio Players](#mystery-of-disappearing-audio-players)
* [Losing packets on the way to Logstash](#losing-packets-on-the-way-to-logstash)
{:class="md-toc"}

## New database partition

A user reported a timeout error for certain queries from the [Public log viewer](https://en.wikipedia.org/wiki/Special:Log) on commons.wikimedia.org.

Database administrator [Manuel Aróstegui](https://phabricator.wikimedia.org/p/Marostegui/) investigated the underlying query and found that it was slow (and timing out) due to one of the database replicas having an unpartitioned `logging` table.

### Background
Our database servers carry labels that the MediaWiki application can ask for along with a query. This allows replicas to be finely tuned to specific kinds of of queries. In particular, when two useful optimisations strategies are mutually exclusive. The labelling system allows both strategies to be applied, on different database servers. MediaWiki then decides which one is most important for that query.

Partioning the [MediaWiki `logging` table](https://www.mediawiki.org/wiki/Manual:Logging_table) is one such optimisation strategy. For queries in the Public logs that focus on actions by a specific user, we route the query to replicas where the `logging` table is partioned by user ID. This is in addition to a regular index on the user ID column for that table, which we have on all replicas.

### Action
As first response, the faulty server was taken out of rotation. Re-partitioning was completed later that day.

– [Task #199790](https://phabricator.wikimedia.org/T199790 "Special:Log results in fatal exception of type DBQueryTimeoutError.")

-------

## Mystery of Disappearing Audio Players

Routine triaging of PHP errors led to discovery of the following:

```
[PHP Notice] Undefined index: 'c9ndx98du2.ogg'
at mediawiki/extensions/Score/includes/Score.php:L507
```

### Background
The [Score extension](https://www.mediawiki.org/wiki/Extension:Score) for MediaWiki provides a way to produce image and audio files from music notation (backed by LilyPond). The extension registers a wikitext tag that allows editors to create and embed music on Wikipedia pages.

The "Undefined index" warning from PHP happens when code tries to access a non-existent key
from an associative array. For example: `$x = array( 'foo' => 1 ); return $x['bar'];`. When this happens, no exception or run-time errors ocurrs. Instead, the PHP engine implicitly returns the `null` value. PHP also emits a notice to the error log channel. We feed that into Logstash and Kibana.

"PHP Notice" errors are not uncommon and can sometimes even cause (by accident) the correct behaviour. For example, if the code involves a condition like `if ($x['bar']) { … } else { … }`. Our error will produce the `null` value, which casts to `false`, and we proceed to the `else` branch. If the `bar` key is meant to be optional here, and if the `else` branch correctly handles the scenario for when it is not set, then this code might already behave correctly. A simple fix would then be to expand the condition to first assert that the key exists. Thus preventing the warning message, but otherwise behaving the same.

### Action
Back to our investigation; The response was led by volunteer [@Ebe123](https://phabricator.wikimedia.org/p/Ebe123/) who is also the lead maintainer of the Score extension.

First, we did some exploratory testing to see if there were any defects we could find with the feature. On the various Wikipedia articles we tested it on, the audio player seemed to work fine.

Back to the error we found on the backend, we traced it to the code responsible for adding the "duration" metadata (used by the audio player). The code for computing this duration stores it in an array, and other code later tries to access it. However, these two functions were not using the same logic to create their array key. As such, it was unable to find the duration, and did not add it to the audio player. While this is bad, it appeared to not affect the audio player. It worked and even displayed the correct duration!

Ebe123 wrote a patch that corrects the key string logic anyway. The duration value would then be found in the array and passed on as the code originally intended.

During code review, we also looked at why this code existed in the first place (because the player appeared to work fine without it). The (broken) code was introduced several years ago in an attempt to fix a bug where the player loaded very slowly for some users. The story is that our multimedia framework needs the duration information before it can start playing back audio. And, for most file types, the framework is able to compute this on its own in the backend and hand it to the audio player ahead of time. However, the framework does not support computing durations for files with the `audio/ogg` MIME-type (which the Score extension used).

When no duration is given ahead of time, web browsers have a fallback strategy. They attempt to download the track regardless, wait for it to fully arrive, then look at how many seconds it contains audio for, and use that as the duration value. This means the audio would not start playing until *after* it was fully downloaded. No streaming!

In our isolated testing we were playing relatively short audio clips using a high-bandwidth connection. Thus, the issue was not obvious to us.

We also found a separate bug report from a few months earlier where several users reported that when pressing "Play" the player would dissappear for 5-20 seconds before audio starts playing.

It all started to make sense.

– [Task #200835](https://phabricator.wikimedia.org/T200835 "PHP Notice: 'Undefined index' from Score.php:L507."), [Task #192550](https://phabricator.wikimedia.org/T192550 "Score audio player vanishes for a few seconds.")

-------

## Losing packets on the way to Logstash

I noticed that for recent bug reports with Error IDs, I was unable to find the
associated error report in Logstash. I could also reproduce this for bugs I had
reported myself.

### Background
In the event of an internal server error, the MediaWiki web server sends a detailed
error report to Logstash. MediaWiki then displays an error page to the user,
where it mentions the "Error ID".

### Action
[Tim Starling](https://tstarling.com/blog/) (Platform architect at Wikimedia) started investigating. He created a new Grafana
dashboard and the culprit was quickly identified. Over 3000 UDP packets were being dropped at the Logstash servers, every second. That's over 90% of its total packets – lost!

As first mitigation, he rebooted the server, quadrupled the default receive buffer size (`net.core.rmem_default` in the Linux kernel) to 4MB, and rebooted it again.

| ![Rate of succesfull Logstash packet reception increased from 50 pps to 300 pps](/assets/attachments/2018_augstories_1a_logstash_recv.png "Success rate goes up from 50 pps to 300 pps"){:class="md-box"} | ![Rate of Logstash packet loss decreased from 1200 pps to 950 pps.](/assets/attachments/2018_augstories_1b_logstash_loss.png "Failure rate went down from 1200 pps to 950 pps"){:class="md-box"}
{:class="md-block"}

The first reboot significantly improved throughput (from 10% success, to 25% success), but the receive buffer change didn't have any positive effect and we were still dropping the remaining 75% of packets.

To recap, the buffer was now large enough to accomodate 3 seconds worth of messages which should be enough margin for Logstash to process it. Short spikes aside, it's unlikely that allowing more stalling would help, because new packets are constantly added to the buffer as well.

[Filippo Giunchedi](https://phabricator.wikimedia.org/p/fgiunchedi/) (Site Reliability Engineering team) jumped in and noticed that the [`workers.pipeline` setting](https://www.elastic.co/guide/en/logstash/6.4/tuning-logstash.html) was explicitly set to `1`, thus allowing Logstash to only use a single thread to process all the messages. This was configured several years earlier ([commit](https://github.com/wikimedia/puppet/commit/011aa76f0af62c3d5160c9f5e821108323cc3f16)) to workaround a problem with the Logstash Multiline plugin; This plugin wasn't thread-safe and would corrupt logs if active in multiple threads.

Filippo determined we no longer needed this plugin, disabled it, and allowed the default `workers.pipeline` setting to take effect - which is to use the number of available CPU cores as the number of threads.

This, together with the 4MB receive buffer Kernel setting, let the packet loss rate drop to zero.

– [Task #200960](https://phabricator.wikimedia.org/T200960 "Logstash packet loss (August 2018)"), [Grafana dashboard: Logstash](https://grafana.wikimedia.org/dashboard/db/logstash)

-------

**Note**: I originally wrote about these incidents in the [August 2018 edition](https://lists.wikimedia.org/pipermail/wikitech-l/2018-August/090594.html) of the Production Excellence newsletter for Wikipedia's Engineering department. This article is an expanded version of that, with additional background information to make the stories suitable for a wider audience.
