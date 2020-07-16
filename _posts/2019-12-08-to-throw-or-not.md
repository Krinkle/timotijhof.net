---
layout: post
title: "To throw or not to throw, that is the question"
tags: engineering-stories
redirect_from:
- /posts/2019/wikipedia-stories-3/ # renamed
---

What are namespaces and special pages on Wikipedia? Why do we accept invalid data? And, at what layer should we reject it?

<!--more-->

## Premise
One day, our server monitoring was reporting a high frequency of fatal errors from web servers. Over 10,000 an hour. The majority shared a single root cause – The program attempted to find the discussion space of a page that doesn't support discussions.

Why was the program trying to do this? And how should the software behave when asked to do something it cannot?

-------

## Background

### Namespaces and Special pages
The MediaWiki software that powers Wikipedia has a concept of titles and namespaces. Every article (or "wiki page") has a title. And every title can belong to one of several namespaces.

The pages that contain the encyclopaedic content you're familiar with, exist under the Article namespace. These are accessed via URLs such as `/wiki/Some_subject`.

Each Article also has an associated wiki page under the so-called "Talk" namespace. For example, `Talk:Some_subject`. This is a place where conversations about the article take place. (Questions, concerns, and other discussion threads.)

Beyond this, there are many more namespaces. "File" pages represent an uploaded multimedia file, "User" pages  represent profiles of user accounts, and so on. Each of these has an associated talk space as well ("File talk", "User talk", etc.).

Lastly, there is the "Special" namespace of pages. These do not represent things that users can create or edit. Instead, it is reserved for software features. For example, the sign up page is a "special" page (at `Special:Create_account`). These don't have one-to-one associated discussion spaces. That is, there is no "Special talk" or some such.

### Special:Contributions
The special page we'll take a closer look at today is "User contributions" (at `Special:Contributions`). This is where you can see the contribution history of a specific editor. Besides the mandatory username field, there are date filters, and namespace filters. The namespace filter also allows one to search through any associated  namespaces.

Because the “Special” namespace does not contain wiki pages, it is not listed in this namespace filter.

![The Special:Contributions form contains a "Namespace" dropdown menu with options such as "Article", "Talk", "User", and "File". It also has a checkbox for "Include associated namespace".](/assets/attachments/2019_stories3_form.png)

-------

## The Problem

Some users browsed URLs to Special:Contributions with the namespace ID of "Special" selected. While this wasn't an option in the user interface, the request handler did not reject it. After all, it _is_ a valid namespace. Just one that contains no user contributions.

By itself, such query would actually succeed. In so far, that it simply yields no results. It works as well as could be expected.

Where it went wrong is if one would also tick the "Include associated namespace" checkbox.

This forced the software to filter the query to one of two possible namespace IDs. The ID of the "Special" namespace, and the ID of its associated namespace. Except, there is no associated namespace for Special! The code in charge of associating namespaces had no choice but to abort. The question it was asked demanded a specific answer, but it could not give any.

![Users were shown an "Internal error" page, stating a fatal exception had ocurred, with an Error Code next to it.](/assets/attachments/2019_stories3_error.png "The error page shown to users"){:style="max-height:200px"}

The error code is used to look up the trace report, which looks like this ([task 150324](https://phabricator.wikimedia.org/T150324 "Fatal MWNamespace exception on Special:Contributions")):

```
[MWException]
getAssociated() is not valid for Special namespace.

at Namespace.php: Namespace::isMethodValidFor()
at pagers/ContribsPager.php: Namespace::getAssociated()
at pagers/ContribsPager.php: ContribsPager->getNamespaceCond()
…
at MediaWiki.php: SpecialContributions->execute()
at index.php: MediaWiki->run()
```

## The Investigation

### Accepting invalid data
Do we need to change anything, or is the program already good enough? There are no contributions under the Special namespace. And, there is also no talk space for discussions about these non-existent contributions. The desired outcome isn’t for there to be results, as there can't be any.

But, we also can't prevent our editors (or their apps) from asking for results. Perhaps an older app version did list "Special" as option, or another system mistakenly opens the form the wrong way. Or, someone may be intentionally abusing the system. It can happen. And when it does, the server has to respond in some way.

So far, the server was responding by crashing… If that happens a lot, alarm bells will ring about a potential outage being underway. When we crash without explanation, end-users (or developers working on an app) can't tell what's wrong. Were our servers malfunctioning? Or did the user do something wrong?

### Rejecting invalid data
I sometimes think about software as an onion. At its outer layer, anything can happen. We don't control what end-users and external systems try to do. If we encounter invalid input, we should respond clearly. For example, by explaining the nature of the problem so that users may correct it, and carry on.

In the outer layer, bad input is not unexpected and should not cause our software to crash. We need a way to distinguish end-user mistakes from real bugs in our code. When crashes happen, it means there is a mistake in the program. It can still be interesting to measure when end-user mistakes happen. For example, it might mean that our user-interface is confusing to users. But, that is separate from the technical question of whether the system is in full working order.

### Who is in charge, and who is responsible?
Once past the outer layer, there are many more layers to our "onion". Each layer gets closer to core business logic.

A question like "What are recent edits by user X?" is subdivided into many smaller commands and questions (or "functions"). One such function will answer to "_What is the talk namespace for a given title?_". This would answer "Talk" for "Article", and "File_talk" for "File".

The "Associated namespaces" option on Special:Contributions, uses that function.

If one of your edits is for a page that has no discussion namespace, what should we do? Show no edits at all? Skip that one edit and tell the user "1 edit was hidden"? Or show it anyway, but without the "talk" portion? This is a decision the inner layer cannot make. All it knows is the small question being asked. It should not be aware of what the outer layer wants to do. The outer layer has to decide how to handle this problem. If the outer layer believes this kind of edit should never show up under normal conditions, then it could show an error message. Something like "_Error: Unsupported namespace selection._"

Or, the outer layer can avoid the error by asking a different question. A question that cannot fail. A question that leaves room for unexpected outcomes. Such as "_Does namespace X have a talk space?_", instead of "_I need the talk space of X, what is it?_". The outer layer then recognises that the question can be answered with “No”, and could then have logic for displaying those edits in a different way.
