---
layout: post
title: Should I substr(), substring(), or slice()?
tags: javascript
plainwhite:
  index: minor
---

What's the deal with these string methods, and how are they different?

<!--more-->

<span class="screen-reader-text">Table of contents</span>

1. [String substr](#string-substr)
2. [String substring](#string-substring)
3. [String slice (recommended)](#string-slice)
{:class="md-toc"}

---

## String substr()

```
str.substr(start[, length])
```

This method takes a start index, and optionally a number of characters to read _from_ that start index with the default being to read until the end of the string.

```js
'foobar'.substr(2, 3); // "oba"
```

The `start` parameter may be a negative number, for starting relative from the end.

Note that only the first parameter of `substr()` supports negative numbers. This in contrast to most methods you may be familiar with that support negative offsets, such as `String#slice()` or `Array#slice()`. The second parameter may not be negative. In fact, it isn't an end index at all. Instead, it is the (maximum) number of characters to return.

But, in Internet Explorer 8 (and earlier IE versions), the `substr()` method deviates from the ECMAScript spec. Its `start` parameter doesn't support negative numbers. Instead, these are **silently ignored** and treated as zero. (I noticed this in 2014, shortly before Wikimedia disabled JavaScript for IE 8.)

IE 8:
```js
'faux'.substr( -1 ); // "faux"
```

Standard behaviour:
```js
'faux'.substr( -1 ); // "x"
```

And, the name and signature of `substr()` are deceptively similar to those of the `substring()` method.

## String substring()

```js
str.substring(start[, end])
```

This method takes a start index, and optionally an end index. At glance, a very simple and low-level method. No relative lengths, negative offsets, or any other trickery. Right?

<span id="fnr1"></span>Behold! The two parameters **automatically swap** if `start` is larger than `end`. <sup>[[1]](#fn1 "Jump to footnote 1")</sup>

```js
'foobar'.substring(1, 4); // "oob"
'foobar'.substring(4, 1); // "oob", also!
```

Unexpected values such as `null`, `undefined`, or `NaN` are silently treated as zero. For `substring()` this also applies to negative numbers.

And, of course, the name and signature of `substring()` are deceptively similar to `substr()`.

## String slice()

```js
str.slice(start[, end])
```

This method takes a start index, and optionally an end index that defaults to the end of the string. Either parameter may be a negative number, which is interpreted as a relative offset from the end of the string.

I found no defects in browsers or JavaScript engines implementing this method. And it has been around since the [beginning of time](https://developer.mozilla.org/en-US/docs/Archive/Web/JavaScript/New_in_JavaScript/1.2).

Its only weakness is also its greatest strength — full support for negative numbers.

One might think this can be ignored for cases where you only intend to work with positive numbers. You'd be right, until you write code like the following:

```js
start = something.indexOf(needle); // returns -1 if needle not found.
remainder = str.slice(start); // oops, -1 means something else here!
```

The notion of negative offsets was confusing to me when I first learned it. But, over the years, I've come to appreciate it and it actually became second nature to think about offsets in this way. If you're unfamiliar, see the examples below.

## Conclusion

Let's compare these methods once more:

```js
str = 'foobarb…z';

// Strip start "foo" > "barb…z"
str.slice(3);
str.substring(3);
str.substr(3);

// Strip end "z" > "foobarb…"
str.slice(0, -1);
str.substring(0, str.length - 1);
str.substr(0, str.length - 1);

// Strip "foo" and "z" > "barb…"
str.slice(3, -1);
str.substring(3, str.length - 1);
str.substr(3, str.length - 3 - 1); // 👀

// Extract start > "foo"
str.slice(0, 3);
str.substring(0, 3);
str.substr(0, 3);

// Extract end > "z"
str.slice(-1);
str.substring(str.length - 1);
str.substr(str.length - 1); // Compat
str.substr(-1); // Modern

// Extract 4 chars at [3] > "barb"
str.slice(3, 3 + 4);
str.substring(3, 3 + 4);
str.substr(3, 4); // 👀
```

None of these seem unreasonable, in isolation. It's nice that `slice()` allows negative offsets. It's nice that `substring()` may limit the damage of accidentally negative offsets. It's nice that `substr()` allows extracting a specific number of characters without needing to add to the start index.

But having all three? That can incur a very real cost on development in the form of doubt, confusion, and — inevitably — mistakes. I don't think any of these is worth that cost over some minute localised benefit.

I find `substr()` or `substring()` cast doubt on surrounding code. I need to second-guess the author's intentions when reviewing or debugging such code. Which is wasteful even, or especially, when they (or I) use them correctly.

_But what about unit tests?_ Well, there's sufficient overlap between the three that a couple of good tests may very well pass. It's easy to forget exercising every possible value for a parameter, especially one that is passed through to a built-in. We usually don't question whether the built-in method works. The question is – did we use the right method?

This ubiquitous signature of `slice()` is well-understood. It is a de facto standard in technology, seen in virtually all programming languages. It is applies to strings, arrays, and sequences of all sorts. As such, that's the one I tend to prefer.

<span id="fnr2"></span>But more important than which one you choose, I think, is the act of choosing itself. Eliminating the others from your work environment reduces cognitive overhead in development, with one less worry whilst reading code, and one less decision when writing it. <sup>[[2]](#fn2 "Jump to footnote 2")</sup>

-------

1. {:#fn1} This "argument swapping" behaviour in `substring()` has existed since the original JavaScript 1.0 as implemented in Netscape 2 (1996), and reverse-engineered by Microsoft in IE 3. The behaviour was [briefly removed](https://web.archive.org/web/19971015223714/http://developer.netscape.com/library/documentation/communicator/jsguide/js1_2.htm) by Netscape 4 with [JavaScript 1.2](https://developer.mozilla.org/en-US/docs/Archive/Web/JavaScript/New_in_JavaScript/1.2#Changed_functionality_in_JavaScript_1.2) in June 1997, but that same month the misfeature finished its [fast-tracked standardisation](https://www.ecma-international.org/archive/ecmascript/1996/index.html) as part of [ECMAScript 1](https://www.ecma-international.org/publications/standards/Ecma-262-arch.htm). Thus, the misfeature returned in 1998 with the release of Netscape 4.5 and JavaScript 1.3, which aligned itself with the new specification. [↩︎](#fnr1 "Jump back")
2. {:#fn2} In 2014, I wrote [a lengthy code review](https://gerrit.wikimedia.org/r/c/mediawiki/core/+/158108) about the string methods which, after much delay, I used as the basis for this article. [↩︎](#fnr2 "Jump back")
