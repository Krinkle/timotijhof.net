---
layout: post
title: "Many dots, do not a query make"
tags: engineering-stories
author_role: editor
---

How a long sequence of dots allowed a regex to reach its internal stack limit.

<!--more-->

## Premise

Wikipedia's production error logs were reporting an increase in app crashes from the search results page. The internal [Logstash](/posts/2019/wikipedia-stories-1/#losing-packets-on-the-way-to-logstash) error report looked as follows:

```
[RuntimeException]
Cannot consume query at offset 0 (need to go to 7296)

at mediawiki/…/CirrusSearch: QueryStringRegexParser->nextToken
at mediawiki/…/CirrusSearch: QueryStringRegexParser->parse
at mediawiki/…/CirrusSearch: SearchQueryBuilder::newFTSearchQueryBuilder
```

What caused this?

-------

## Background

Wikipedia's search experience is provided by the [CirrusSearch](https://www.mediawiki.org/wiki/Extension:CirrusSearch) plugin for MediaWiki. It is internally backed by an Elasticsearch cluster.

There are a number of custom operators supported in the search field, such as wildcards, excluded words, and things like `incategory:` and `intitle:`. These are parsed by the plugin's middleware and turned into a structured query sent to the Elastic API.

While each error report had a different URL and search query, I noticed most of them had something in common: the search query consisted mostly of dots. For example:


```
https://de.wikipedia.org/w/?search=.................. (3000 dots)
```

Such an odd query might not need to yield a useful response, but it is important that it not crash the application. Doing so leaves the user stranded with an unhelpful "Internal server error" page. It can also interfere with on-going deployments as raised error levels usually indicate that a recent software update caused a problem.

-------

## Investigation


[David Causse](https://phabricator.wikimedia.org/p/dcausse/) (Search Platform team) led the investigation ([task #236419](https://phabricator.wikimedia.org/T236419 "[CirrusSearch] Fatal RuntimeException: Cannot consume query at offset 0")).

This RuntimeException has been added as a safeguard in the parser for incoming search queries. This check exists toward the end of the parsing code, and should never be reached. It is an indication that a problem appeared previously. The problem was narrowed down to a failure executing the following regex

```js
/\G(?<negated>[-!](?=[\w]))?(?<word>(?:\\\\.|[!-](?!")|[^"!\pZ\pC-])+)/u
```

This regex looks complex, but it can actually be simplified to:
```js
/(?:ab|c)+/
```

This regex still triggers the problematic behavior in PHP. It fails with a `PREG_JIT_STACKLIMIT_ERROR`, when given a long string. Below is a reduced test case:

```php
$ret = preg_match('/(?:ab|c)+/', str_repeat('c', 8192));
if ($ret === false) {
    print("failed with: " . preg_last_error());
}
```

* Fails when given 1365 contiguous `c` on PHP 7.0.
* Fails with 2731 characters on PHP 7.2, PHP 7.1, and PHP 7.0.13.
* Fails with 8192 characters on PHP 7.3. (Might be due to [php-src@bb2f1a6](https://github.com/php/php-src/commit/bb2f1a683003559ada1c70166557bd7ac2845a11)).

In the end, the fix we applied was to split the regex into two separate ones, and remove the non-capturing group with a quantifier, and loop through at the PHP level ([patch 546209](https://gerrit.wikimedia.org/r/c/mediawiki/extensions/CirrusSearch/+/546209)).

The lesson learned here is that the code did not properly check the return value of `preg_match`, this is even more important as the size allowed for the JIT stack changes between PHP versions.

For future reference, David concluded: The regex could be optimized to support more chars (~3 times more) by using atomic groups, like so `/(?>ab|c)+/`.
