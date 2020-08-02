---
layout: post
title: "Tomorrow, may be sooner than you think"
tags: engineering-stories
redirect_from:
- /posts/2019/wikipedia-stories-02/ # draft
- /posts/2019/wikipedia-stories-2/ # renamed
plainwhite:
  also_url: https://phabricator.wikimedia.org/phame/live/1/post/119/production_excellence_september_2018/
  also_label: Production Excellence at Wikipedia
---

These are stories from bug hunts and incident investigations at Wikipedia.

<!--more-->


### Impact
After developers submit code to Gerrit, they eagerly await the result from Jenkins, an automated test runner.

Every day during the 15 minute window before 5 PM in San Francisco, code changes submitted for code review would have mysteriously failing tests. Jenkins would wrongly inform developers that their proposed changes cause a problem with the MergeHistory feature of MediaWiki.

### Background
The test in question assumed that it would finish by "_tomorrow_". At first glance, it seems fair to assume that by tomorrow, a given test will have finished. We know our our test suite generally only take a few minutes to run (with a time limit of 30 minutes, to ensure tests report back even if they are stuck).

### Investigation
Unfortunately…, the programming utility `strtotime` in PHP, does not interpret "tomorrow" as "this time tomorrow".

Instead, it takes it to mean "the start of tomorrow". In other words, the next strike of midnight!

For example, on 14 August 23:59:59, `strtotime("tomorrow")` would evaluate to a timestamp merely one second into the future — 15 August 00:00:00.

This meant that whenever a test started running shortly before midnight, it would fail. The test server uses UTC as its timezone. As such, a test suite that started less than 15 minutes before 5 PM in San Francisco (which is midnight in UTC), it would mysteriously fail!

– [Task #201976](https://phabricator.wikimedia.org/T201976 "Flaky unit test MergeHistoryTest::testIsValidMerge.")

– [Changeset 452873](https://gerrit.wikimedia.org/r/452873 "MergeHistory: Fix flaky test due to relative timestamp.")

-------

**Note**: I originally wrote about this bug in the [September 2018 edition](https://phabricator.wikimedia.org/phame/live/1/post/119/production_excellence_september_2018/) of the Production Excellence newsletter for Wikipedia's Engineering department. This article is an expanded version of that.
