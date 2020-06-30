---
layout: post
title: QUnit anti-patterns
tags: javascript testing
redirect_from:
- /2015/qunit-anti-patterns/ # former WordPress site (deleted page)
plainwhite:
  original_url: https://codepen.io/Krinkle/post/qunit-anti-patterns
  original_label: CodePen
  also_url: https://medium.com/@timotijhof/qunit-anti-patterns-237ffef5e12f
  also_label: Medium.com
---

Today, I’d like to challenge the `assert.ok` and `assert.not*` methods. I believe they may’ve become an anti-pattern.

<!--more-->

## assert.ok

Using `assert.ok()` indicates one of two problems:

* The software, or testing strategy, is unreliable. (Unsure what value to expect.)
* The author is using it as shortcut for a proper comparison.

The former necessitates improvement to the code being tested. The latter comes with two additional caveats:

1. Less debug information. (Inaccurate actual/expected diff). Without an expected value provided, one can’t determine what’s wrong with the value.
2. Masking regressions. Even if the API being tested returns a proper boolean and `ok` is just a shortcut, the day the API breaks (e.g. returns a number, Promise, or other object) the test will not be able to catch this regression.

Common examples:

```js
// Meh...
assert.ok( result );
assert.ok( obj.fn );

// Better.
assert.equal( typeof obj.fn, 'function' );
assert.strictEqual( result, true );
```

## assert.not

Using `assert.not*()` indicates one of three problems:

* The software is unreliable. (Value is indeterministic.)
* The test uses an unreliable environment. (E.g. the input data is dynamic or variable, insufficient isolation or mocking.)
* The author is using it as shortcut for a proper comparison.

Common example:

```js
var index = list.indexOf( item );

// Meh...
assert.notEqual( index, -1 );

// Better.
assert.equal( index, 2 );

// Even better?
assert.propEqual( list, [
  'foo',
  'bar',
] );
```

I’ve yet to see the first use of these assert methods that wouldn’t be improved by writing it a different way. I admit there are limited scenarios where [`assert.notEqual`](https://api.qunitjs.com/assert/notEqual) can’t be avoided in the short-term, for example when the intent is to detect a difference between two unpredictable return values.

When calling a method such as `Math.random()` twice, one could use `notEqual` to assert the two return values differ. I still have my doubts about the value of such test, though. It’ll certainly be annoying when it randomly does produce the same value twice and cause a test failure. In the mission of test coverage, my recommendation would be to instead assert that calling the method did not throw an exception, and perhaps assert the type and length of the return value, without comparing the string content.
