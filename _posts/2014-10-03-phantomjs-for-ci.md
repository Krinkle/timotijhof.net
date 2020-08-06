---
layout: post
title: PhantomJS for CI (anno 2014)
tags: javascript testing
redirect_from:
- /2014/phantomjs-for-ci-anno-2014/ # former WordPress site (deleted page)
plainwhite:
  original_url: https://codepen.io/Krinkle/post/phantomjs-anno-2014
  original_label: CodePen
  also_url: https://medium.com/@timotijhof/phantomjs-for-ci-anno-2014-589a3f0dec6b
  also_label: Medium.com
---

How did Apple create Safari, and what is PhantomJS?

<!--more-->

## Safari
In January 2003 Apple announced Safari, their new web browser for Mac. [[1]](https://dot.kde.org/2003/01/08/apple-announces-new-safari-browser)  The Safari team had just spent 2002 building Safari atop KHTML and KJS, [[2]](http://lists.kde.org/?l=kfm-devel&m=104197092318639&w=2)[[3]](https://web.archive.org/web/20070310215550/http://www.opendarwin.org/pipermail/kde-darwin/2002-June/000034.html)  the KDE layout and javascript engines developed for Konqueror. The Safari team kept the codebase somewhat modular. This allowed Apple-branding and other proprietary features to stay separate whilst also having a sustainable open-source project (WebKit) that is standalone and compilable into a fully functional GUI application. The Mac OS version of WebKit is composed of WebCore and JavaScriptCore – the frameworks that encapsulate the OSX ports of KHTML and KJS respectively. Apple developed the JavaScriptCore library previously for use in Sherlock. [[4]](https://web.archive.org/web/20070310215550/http://www.opendarwin.org/pipermail/kde-darwin/2002-June/000034.html)

## Chromium
In 2008, Google introduced Chrome and started the open-source project Chromium. Chromium was composed of WebKit's **WebCore** and the **V8** javascript engine (instead of JavaScriptCore). Google later forked WebCore into **Blink** in 2013, thus abandoning any upstream connection with WebKit.

While Chromium is a single code-base with bindings for multiple platforms, WebKit is not. Instead, WebKit is based around the concept of ports.

These ports are manually kept in sync. Some maintained by third parties (e.g. not by webkit.org or Apple). Some ports are better than others. "WebKit", as such, has also become an abstract API, rather than just a framework.

## WebKit
A few popular ports:

* Safari for Mac.
* Mobile Safari for iOS.
* Safari for Windows (abandoned).
* QtWebKit (by Nokia; due to it being implemented atop Qt, it works on Mac/Linux/Windows).
* Android browser (abandoned, uses Chromium now).
* Chromium (abandoned, uses Blink now).
* WebKitGTK+.

WebKit itself doesn't do much when it comes to network, GPU, javascript, or text rendering. Those are not "WebKit". Each port binds those to something present in the OS - or another application layer. E.g. QtWebKit defers to Qt, which in turn binds to the platform.

## PhantomJS
PhantomJS is a headless browser using the **QtWebKit** engine at its core.

The current release cycle of PhantomJS (1.9.x) is based on Qt 4.8.5, which bundles QtWebKit 2.2.4, which was branched off of upstream WebKit in May 2011. Due to the many layers in between, it will take a long time for PhantomJS to get anywhere near the feature-set of current Safari 8. PhantomJS by design is nothing like Safari but, if anything, it is probably like an alpha version (branched from SVN trunk) of Safari 4. Which is why, contrary to Safari 5.0, PhantomJS has only partial support for ES5.

Chromium has its abstraction layer at a higher level (platform independent). When run headless, it is exactly like an actual instance of Chrome on the same platform. When used in a virtual machine on a remote server, one doesn't even need to be "headless". We can use regular Chromium (under Xvfb). In theory the visual rendering through Xvfb and VM hypervisor could be different, however.

## Further reading
* [Konqueror](https://en.wikipedia.org/wiki/Konqueror) on Wikipedia
* [Safari](https://en.wikipedia.org/wiki/Safari_(web_browser)) on Wikipedia
* [WebKit](https://en.wikipedia.org/wiki/WebKit) on Wikipedia
* [Sherlock](https://en.wikipedia.org/wiki/Sherlock_(software)) on Wikipedia
* [V8](https://en.wikipedia.org/wiki/V8_(JavaScript_engine)) on Wikipedia
* [Blink](https://en.wikipedia.org/wiki/Blink_(layout_engine)) on Wikipedia
* [phantomjs.org](http://phantomjs.org/) (Official website)
* [WebKit for Developers](https://www.paulirish.com/2013/webkit-for-developers/) by Paul Irish

-------

**Update (September 2018)**: I recently read *[Creative Selection](http://creativeselection.io/)*, which talks about the engineering choices behind Safari and iPhone, how some of its features came to be, and the role of Steve Jobs day-to-day. It is written by Ken Kocienda, an engineer who worked on both projects. The book was a good and quick read.

I previously read *Steve Jobs* by Walter Isaacson. The biography was great, but it didn't cover much of Apple's internal practices. *Creative Selection* covers this gap.
