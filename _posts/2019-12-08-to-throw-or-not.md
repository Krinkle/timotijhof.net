---
layout: post
title: "To throw or not to throw, that is the question"
tags: engineering-stories
redirect_from:
- /posts/2019/wikipedia-stories-3/ # renamed
plainwhite:
  index: minor
---

Why does software accept invalid data? And, at what software layer should we reject it? Also, what are "namespaces" and "special pages" on Wikipedia?

<!--more-->

## Premise
One day, our server monitoring was reporting a high frequency of fatal errors from web servers. Over 10,000 an hour. The majority shared a single root cause – The program attempted to find the discussion space for a page that didn't support discussions.

Why was the program trying to do this? And how should the software behave when asked to do something it cannot?

-------

## Background

### Namespaces and Special pages
The MediaWiki software that powers Wikipedia has a concept of titles and namespaces. Each article (or "wiki page") has a title. And each title can belong to one of several namespaces.

The pages that contain the encyclopaedic content you're familiar with, exist under the Article namespace. These are accessed via URLs such as `/wiki/Some_subject`.

Each Article also has an associated wiki page under the so-called "Talk" namespace. For example, `Talk:Some_subject`. This is a place where conversations about the article take place. (Questions, concerns, suggestions, and other discussion threads.)

Beyond this, there are many more namespaces. "File" pages represent an uploaded multimedia file, "User" pages represent individual contributors and their profile pages, and so on. Each of these namespaces has an associated talk namespace as well ("File talk", "User talk", etc.).

Lastly, there is the "Special" namespace of pages. These do not represent things that can be created or edited by contributors. Instead, this space is reserved for software features. For example, the account sign up page is a "special" page (at `Special:Create_account`). These do not have a discussion space. That is, there is no "Special talk" namespace.

### Special:Contributions
The special page we'll take a closer look at today is "User contributions" (at `Special:Contributions`). This is where you can see the contribution history of a specific editor. Besides the mandatory username field, there are date filters, and namespace filters. The namespace filter also allows one to search through any associated  namespaces.

Because the “Special” namespace does not contain wiki pages, and thus no contributions, it is not listed in this dropdown menu.

![The Special:Contributions form contains a "Namespace" dropdown menu with options such as "Article", "Talk", "User", and "File". It also has a checkbox for "Include associated namespace".](/assets/attachments/2019_stories3_form.png)

-------

## The Problem

Some users browsed URLs to Special:Contributions with the namespace ID of "Special" selected. While this wasn't an option in the user interface, the request handler did not reject it. After all, it _is_ a valid namespace. Just one that contains no user contributions.

By itself, such query would actually succeed. In so far, that it simply yields no results. It works as well as could be expected.

Where it went wrong is if one would also tick the "Include associated namespace" checkbox.

This forced the software to filter the query to one of two possible namespace IDs. The ID of the "Special" namespace, and the ID of its associated namespace. Except, there is no associated namespace for Special! The code in charge of associating namespaces had no choice but to abort. The question it was asked demanded a specific answer, but it could not give any.

![Users were shown an "Internal error" page, stating a fatal exception had ocurred, with an Error Code next to it.](/assets/attachments/2019_stories3_error.png "The error page shown to users"){:style="max-height:200px"}

The error report reads as follows ([task 150324](https://phabricator.wikimedia.org/T150324 "Wikimedia Phabricator: Fatal MWNamespace exception on Special:Contributions")):

```
Exception: getAssociated is not valid for the Special namespace.

at Namespace.php: Namespace::isMethodValidFor()
at pagers/ContribsPager.php: Namespace::getAssociated()
at pagers/ContribsPager.php: ContribsPager->getNamespaceCond()
…
at MediaWiki.php: SpecialContributions->execute()
at index.php: MediaWiki->run()
```

## The Investigation

### Accepting invalid data
Do we need to change anything, or is the program already good enough?

There are no contributions under the Special namespace. And, there is also no talk space for discussions about these non-existent contributions. The desired outcome isn’t for there to be results, as there can't be any.

But, we also can't prevent our editors (or their apps) from asking for results. Perhaps an older app version did list "Special" as option, or another system mistakenly opens the form the wrong way. Or, someone may be intentionally manipulating the system via its URL. It can happen. And when it does, the server has to respond in some way.

So far, the server was responding by crashing… If that happens a lot, alarm bells will ring about a potential outage being underway. When we crash without explanation, end-users (or developers working on an app) can't tell what's wrong. Were our servers malfunctioning? Or did the user do something wrong?

### Rejecting invalid data
I sometimes think about software as an onion. At its outer layer, anything can happen. We don't control what end-users and external systems try to do. If we encounter invalid input, we generally prefer to respond clearly. For example, by explaining the nature of the problem so that users may correct it, and carry on.

At this outer layer, bad input is not unexpected and should not cause our software to crash. And, to avoid false alarms in the backend, we need to distinguish end-user mistakes from real bugs in our code. Ideally crashes only happen if there is a bug in the program. It may be worth measuring in the backend when an end-user mistake happens. (For example, it might help you understand that the user-interface is confusing to users.) But, such instrumentation should stand separate from the technical question of whether the system is in full working order.

### Who is in charge, and who is responsible?
Once past the outer layer, there are many more layers to our "onion". Each layer gets closer to core business logic.

A question like "What are recent contributions by user X?" is subdivided into many small instructions and questions (or "functions"). One such function will answer to "_What is the talk namespace for a given title?_". This would answer "Talk" for "Article", and "File_talk" for "File".

The "Associated namespaces" option on Special:Contributions, uses that function.

If one of the contributions is for a page that has no discussion namespace, what should we do? Show no results at all? Skip that one edit and tell the user "1 edit was hidden"? Or show it anyway, but without the "talk" portion? This is a decision the inner layer cannot make. It only knows the small question being asked. It should not be aware of what the outer layer wants to do (sometimes known as "global state"). The outer layer has to decide how to handle this problem. If the outer layer believes this kind of edit should never show up under normal conditions, then it could show an error message. Something like "_Error: Unsupported namespace selection._"

Alternatively, the canundrum can be avoided by structuring the program differently. The outer layer could ask a different question instead. A question that cannot fail. A question that leaves room for unexpected outcomes. Such as "_Does namespace X have a talk space?_", instead of "_I need the talk space of X, what is it?_". The outer layer then recognises that the question can be answered with “No”, and could then have logic for displaying those contributions in a different way.
