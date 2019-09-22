---
layout: post
title: Tools moving to Git
tags: wiki-tools
redirect_from:
- /2012/tools-moving-from-svn-to-git/ # former WordPress site
---

As part of the spring cleaning this year I want to get all my Toolserver tools into public and easy to use version control. Right now most tools are either on Toolserver’s internal SVN somewhere, or not in source control at all.

Each tool will be getting its own Git repository. I will be (one-way) mirroring all of those repositories under [github.com/Krinkle](https://github.com/Krinkle).

<!--more-->

As a start I’ve migrated the following sources to Git, mirrored on GitHub:

* [BaseTool](https://github.com/Krinkle/ts-krinkle-basetool)
* [OrphanTalk2](https://github.com/Krinkle/ts-krinkle-OrphanTalk2)
* [wmfBugZillaPortal](https://github.com/Krinkle/ts-krinkle-wmfBugZillaPortal)

The base class now has a new feature that can add a “Source code” and “Issue tracker” link in the header. For SVN or Git hosted tools the “Source code” link will point to an online viewer of that repository. And for tools mirrored on GitHub, the “Issue tracker” link will point to the Issue tracker of the GitHub project.

Last but not least, the header now shows the revision hash of the deployed version of the tool. Check [OrphanTalk2](https://toolserver.org/~krinkle/OrphanTalk2/) for example.

