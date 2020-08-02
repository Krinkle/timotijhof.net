---
layout: post
title: How to protect yourself from npm
excerpt: <p>What’s the worst that could happen after npm install?</p>
tags: wikipedia javascript testing
plainwhite:
  og_image_url: /assets/attachments/2019_npm_LouisXIV.jpg
  og_image_alt: King Louis XIV with a red blindfold over his eyes.
  original_url: https://medium.com/@timotijhof/how-to-protect-yourself-from-vulnerable-npm-packages-c03f85249651
  original_label: Medium.com
---

> What's the worst that could happen after `npm install`?

When you open an app or execute a program from the terminal,
that program can do anything that you can do.

**In a nutshell**: Imagine if your computer were to disappear in front of your eyes and re-appear in front of mine. Still open. Still unlocked. What could I do from this moment on? _That_ is what an unknown program could do.

Upon running `npm install`, you may be downloading and executing hundreds of unknown programs.

1. What is at stake?
2. How does it compare to other package managers?
3. What can you do about it?
{:class="md-toc"}


---

<figure class="md-block-left" markdown="block">
![Two surveillance cameras on a lamppost with a clear blue sky behind them.](/assets/attachments/2019_npm_BlueSkyCCTV13.jpg){:style="max-width:380px"}
<figcaption markdown="span">
Photo by [Raysonho](https://commons.wikimedia.org/wiki/File:BlueSkyCCTV13.jpg#firstHeading)
</figcaption>
</figure>

Programs from nice people sometimes ask for your permission. This is because a developer choose to do so.

There may also be laws that could punish them if they get caught choosing differently.


---

What about programs of which the authors choose differently? Well, such program could do quite a bit.

* It could access any of your files, modify them, delete them, or upload them. This also applies to the internal files used by other applications.
* It could install other programs in the background.
* It could talk to other devices linked to your home network.

## What is at stake

Files you might not be thinking about:

* The cookies in your web browser.
* Desktop applications. Chat history, password managers, todo lists, etc. They all use files to store the text and media you send or receive.
* Digital media. Your photo albums, home videos, and voice memos.
* SSH private keys, GPG key rings, and other crypto files used by developers.

<figure markdown="block">
![A red face in a white rectangle made of nanoblocks, resting on a silver Apple keyboard.](/assets/attachments/2019_npm_FamilyComputer.jpg)
<figcaption markdown="span">
Photo by [DaraKero_F](https://commons.wikimedia.org/wiki/File:Family_Computer_(6914313766).jpg#firstHeading) / CC BY 2.0
</figcaption>
</figure>

## Browser cookies

Browsers cookies make it so you're immediately logged-in when you open a new tab for Gmail, or Twitter. An evil program can copy the browser's cookies file and share it with the attacker.

They could then read any e-mail you've ever received or sent stored there. It could also delete any. (Got a backup?) They can naturally access future e-mails as well. Like the ones you get from "Forgot password" buttons. They could also hide any trace of these (e.g. filter rules).

This affects any website you use. Social network? Access to any post or DM — regardless of privacy setting. Company e-mail, Google Drive? That too.

## Sleeper programs

The evil program may configure itself to always start in the background when you open your laptop. A new friend for life!

It could also add local command-line programs that wrap the popular `sudo` and `ssh` commands, to make them do a little extra behind the scenes. Next time you run `sudo <something>` to perform an administrator action and enter your password—you may have given away full system access. Deploying some code? Running `ssh cloud.someplace.special` might let the attacker tailgate along with you, opening one shell for itself and another for you.

<figure markdown="block">
![Statue of King Louis XIV on a horse with a red blindfold over his eyes. Taken in Paris, France.](/assets/attachments/2019_npm_LouisXIV.jpg)
<figcaption markdown="span">
Photo by [BikerNormand](https://commons.wikimedia.org/wiki/File:Louis_XIV_with_a_red_mask,_Paris_20_August_2015.jpg#firstHeading) / CC BY-SA 2.0
</figcaption>
</figure>

## Local web server

These background programs could also affect you in a myriad of other ways. I won't detail those today, except to mention they can keep a local web server running. Spotify and Zoom have been seen in the news doing [questionable things](https://medium.com/bugbountywriteup/zoom-zero-day-4-million-webcams-maybe-an-rce-just-get-them-to-visit-your-website-ac75c83f4ef5) with their local web servers.


---

## Is this an npm problem?

Maybe. Technically these concerns apply to any method of executing unknown code. Running `npm install` isn't very different from pasting a command like `curl url… | bash`. They both execute a downloaded program from your terminal. The difference is in user expectation.

Upon seeing the url and the `bash` invocation, you have a choice: Trust the publisher (the url), or trust the script (download, review, then decide whether to run). The result is generally predictable and without hidden dependencies.

### Other package managers

What about Debian (apt-get) or Homebrew? Like npm, code published there is unknown to most of us and hard to review. But, there is an important difference: Peer-review. These traditional repositories are curated by a central authority. You don't have to trust the script or original authors of each package, so long as you trust the publishers and their curation process.

<figure markdown="block">
![Earth is small compared to Jupiter. Jupiter is roughly 11 times larger.](/assets/attachments/2019_npm_Jupiter.jpg)
<figcaption markdown="span">
Image by NASA / Public domain
</figcaption>
</figure>

### The scale has changed the game

What about PyPI or Packagist (Composer)? These are like npm. Anyone can publish anything. There is however a difference in scale. PyPI has 194K projects. Packagist is host to 237K packages with 0.5 billion downloads a month. npm has over 1.3 million packages and 30 _billion_ downloads a month. This makes it a much more popular target. [[1]](https://pypi.org) [[2]](https://packagist.org/statistics) [[3]](https://blog.npmjs.org/post/180868064080/this-year-in-javascript-2018-in-review-and-npms)

### Dependency graphs

There is also a difference in habit: PyPI packages have 7 dependencies on average, with typically 1 indirect dependency. And, I would expect most dependencies there to be from authors the user has trusted before. [[4]](https://snyk.io/blog/how-much-do-we-really-know-about-how-packages-behave-on-the-npm-registry/) Snyk.io published in April that the average npm package has a whopping 86 dependencies, with a 4+ levels of indirect dependencies. [[4]](https://snyk.io/blog/how-much-do-we-really-know-about-how-packages-behave-on-the-npm-registry/)

The ESLint package has 118 npm dependencies [[5]](https://npm.anvaka.com/#/view/2d/eslint/6.3.0). Eleventy, a popular static site generator, requires 555 dependencies ([Explore dependency graph](https://npm.anvaka.com/#/view/2d/%254011ty%252Feleventy/0.9.0)). Each one of these may run [arbitrary shell commands](https://blog.alexwendland.com/2018-11-20-npm-install-scripts-intro/) from the terminal both during the installation process, after later when using the tool.

## I get it. Now, what can we do about it?

There isn't a magic bullet to make everything perfectly safe. But, there are a number of things you can do to reduce risk.

### Isolation

For the past year, I've been using disposable Docker containers as a way to reduce the risk of compromise. It has controls for network access, and for which directories can be exposed. Docker isn't a perfect safety net by any means, but it's a step in the right direction.

<figure markdown="block">
![Earth is small compared to Jupiter. Jupiter is roughly 11 times larger.](/assets/attachments/2019_npm_Servers.jpg)
<figcaption markdown="span">
Image by [Victor Grigas](https://commons.wikimedia.org/wiki/Category:Wikimedia_servers_in_Carrollton#/media/File:Wikimedia_Foundation_Servers_2015-88.jpg) / CC BY-SA 3.0
</figcaption>
</figure>

My base image uses Debian and comes with Node.js, npm, and a few other utilities (such as headless browsers, for automated tests). I use a bash script to launch a temporary container, based on that image. It runs as the unprivileged `nobody` user, and mounts only the current working directory.

From there, I would run `npm install` and such. The only thing it interacts with is the source code and local `node_modules` directory for that specific project. It isn't given access to any other Git repos, desktop apps, browser cookies, or crypto files. And, once that terminal tab is closed, the container is destroyed.

I've published the script I use at [github.com/wikimedia/fresh](https://github.com/wikimedia/fresh#start-of-content). I don't recommend using it outside Wikimedia, however. Create your own instead. The repository explains [how it works](https://github.com/wikimedia/fresh/blob/19.10.1/Tutorial.md).

Other options for isolating your environment:

* Speed and flexibility: Use `systemd-nspawn` or `chroot`. This takes more work to setup, but provides a faster environment than Docker. In terms of security it is comparable to Docker. Read more systemd-nspan on [ArchWiki](https://wiki.archlinux.org/index.php/Systemd-nspawn).

* Security and ease of use: Use a virtual machine (e.g. VirtualBox/Vagrant). This is more secure by default and offers a GUI for controlling what to expose. The downside is that VMs are significantly slower.

### Fewer dependencies

Finally, you can reduce risk by reducing the number of packages you depend on in your projects (and then shrink-wrap them). Especially development dependencies, as these tend to be explicitly aimed at executing from the CLI.

Question yourself and question others before introducing new dependencies. Perhaps even encourage maintainers of your favourite packages to [Reduce the size of their dependency graph](https://github.com/qunitjs/qunit/issues/1342#show_issue)!


---

## See also

* [An idea for improving npm package permissions](https://medium.com/hackernoon/npm-package-permissions-an-idea-441a02902d9b), by David Gilbertson.
* [When packages go bad](https://jakearchibald.com/2018/when-packages-go-bad/), by Jake Archibald.

### Further reading

* [Malicious code found on npm](https://snyk.io/blog/malicious-code-found-in-npm-package-event-stream/), Danny Grander, Snyk.io.
* [Shell execution vulnerability in iTerm 2](https://blog.mozilla.org/security/2019/10/09/iterm2-critical-issue-moss-audit/), Tom Ritter, Mozilla Security Blog.
* [Deconstructing Spotify's local server](https://cgbystrom.com/articles/deconstructing-spotifys-builtin-http-server/), Carl Byström.
* [Apple removes Zoom's hidden server](https://nakedsecurity.sophos.com/2019/07/15/apple-quietly-removes-zooms-hidden-web-server-from-macs/), John E Dunn, Naked Security.
* [Zoom 0-day: 4+ Million Webcams](https://medium.com/bugbountywriteup/zoom-zero-day-4-million-webcams-maybe-an-rce-just-get-them-to-visit-your-website-ac75c83f4ef5), Jonathan Leitschuh.
* [Detect piped curl on the server-side](https://www.idontplaydarts.com/2016/04/detecting-curl-pipe-bash-server-side/), Phil from idontplaydarts.com.
* [npm Install Hook Scripts](https://blog.alexwendland.com/2018-11-20-npm-install-scripts-intro/), Alex Wendland.
