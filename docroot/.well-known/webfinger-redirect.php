<?php
/**
 * Copyright 2022 Timo Tijhof <https://timotijhof.net>
 * Copyright 2022 Jamie Zawinski <jwz@jwz.org>
 *
 * Permission to use, copy, modify, distribute, and sell this software and its
 * documentation for any purpose is hereby granted without fee, provided that
 * the above copyright notice appear in all copies and that both that
 * copyright notice and this permission notice appear in supporting
 * documentation.  No representations are made about the suitability of this
 * software for any purpose.  It is provided "as is" without express or
 * implied warranty.
 *
 * ---------
 *
 * webfinger-redirect.php
 *
 * Allows you to be discoverable on Mastodon and the wider Fediverse through
 * your own domain name, without needing to run an ActivityPub server.
 *
 * SPDX-License-Identifier: MIT
 *
 * Changelog:
 *
 * - 2023-02-20 (krinkle), reject direct PHP file requests.
 * - 2022-12-27 (krinkle), use HTTP 307 instead of HTTP 301.
 * - 2022-12-27 (krinkle), code refactoring and forked to timotijhof.net.
 * - 2022-11-08 (jwz), updated at https://www.jwz.org/hacks/mastodon-webfinger.php.
 * - 2022-11-07 (jwz), published at https://www.jwz.org/blog/2022/11/using-your-own-domain-as-a-mastodon-handle/.
 *
 * Installation:
 *
 * - Edit ACCOUNTS below.
 * - Copy this script to "/.well-known/webfinger-redirect.php"
 * - Rewrite requests from standard "/.well-known/webfinger" (no trailing slash),
 *   e.g. via the following in "/.htaccess":
 *   ```
 *   RewriteEngine On
 *   RewriteRule ^\.well-known/webfinger$ /.well-known/webfinger-redirect.php [QSA,L]
 *   Redirect 307 /@krinkle https://fosstodon.org/@krinkle
 *   Redirect 307 /@krinkle.rss https://fosstodon.org/@krinkle.rss
 *   ```
 */

const ACCOUNTS = [
	// Alias account => Destination account
	'krinkle@timotijhof.net' => 'krinkle@fosstodon.org',
];

/**
 * WebFinger protocol, as per <https://www.rfc-editor.org/rfc/rfc7033>
 */
function webfinger_route() {
	// Allow query parameters, which are expected.
	$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
	if ($path !== '/.well-known/webfinger') {
		webfinger_error('Invalid route.');
		return;
	}

	$acct = $_GET['resource'] ?? null;
	if ($acct === null) {
		webfinger_error('Missing resource parameter.');
		return;
	}

	$m = null;
	// Consider "acct:" scheme optional (Mastodon uses it).
	// Consider leading "@" optional (Mastodon doesn't use it in WebFinger queries).
	if (!preg_match('/^(?:acct:)?@?(.*)$/s', $acct, $m)) {
		webfinger_error('Invalid resource.');
		return;
	}
	$acct = $m[1] ?? '';
	$dest = ACCOUNTS[strtolower($acct)] ?? null;
	if (!$dest) {
		webfinger_error('Unknown resource.');
		return;
	}

	$m = null;
	if (!preg_match('/@(.+)$/s', $dest, $m)) {
		webfinger_error('Internal error: Unknown destination domain.');
		return;
	}
	$domain = $m[1];
	$url = "https://$domain/.well-known/webfinger?resource=acct:$dest";

	http_response_code(307);
	header("Location: $url");
}

function webfinger_error($err) {
	header('HTTP/1.1 400 Bad Request');
	header('Content-Type: text/plain');
	print "$err\n";
	exit;
}

webfinger_route();
