---
layout: post
title: "New: mwSnapshots"
tags: wiki-tools
redirect_from:
- /2012/new-mwsnapshots/ # former WordPress site
---

Ever since MediaWiki migrated their [source control management](https://en.wikipedia.org/wiki/Source_Control_Management "From Wikipedia: In software engineering, source control is system for managing changes to computer programs.") from Subversion to Git, the tool to download nightly snapshots has been froozen.

I’ve been waiting for a chance to learn more about Git’s command-line tools, so I took this opportunity to work on a new tool where I could do just that.

The new mwSnapshots tool is monitoring all branches of the mediawiki/core.git repository. Once an hour it will fetch all the new commits added to the repository and sync the HEAD positions of all branches. Then, for branches that have changed since the previous run, it will: check them out, create a new [`tar` archive](https://en.wikipedia.org/wiki/Tar_(file_format)) compressed by [gzip](https://en.wikipedia.org/wiki/gzip), and clean up the old one.

[→ Check it out: mwSnapshots](https://toolserver.org/~krinkle/mwSnapshots)
