<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php
	wp_head();

	// Output RSS discovery
	//
	// Same as built-in `feed_links()` from theme support 'automatic-feed-links',
	// but without the odd "- Feed" suffix in the title.
	printf(
		'<link rel="alternate" type="%s" href="%s">' . "\n",
		feed_content_type(),
		esc_url(get_feed_link())
	);

	if (is_single()) {
		/**
		 * Twitter Card (as of 9 Oct 2019).
		 *
		 * https://cards-dev.twitter.com/validator.
		 * https://developer.twitter.com/en/docs/tweets/optimize-with-cards/overview/summary-card-with-large-image
		 *
		 * - og:title: Displayed.
		 *   Trimmed at 40 characters for summary cards (placeholder box on the side),
		 *   Trimmed at 55 characters for summary_large_image cards (image on top, title full width).
		 * - og:description: Displayed.
		 * - og:site_name: Ignored, URL domain is displayed instead.
		 * - og:author: Ignored.
		 * - og:image: Displayed, but only if og:image:alt is set as well (wow!).
		 *   Twitter will generate a small thumbnail for you, however the original
		 *   must still be no larger than 4096x4096 pixels or 5MB in size.
		 * - og:image:alt: Used as image alt, naturally (must be under 420 characters).
		 */

		$postTitle = single_post_title('', false);
		$authorName = get_the_author_meta('display_name', get_post_field('post_author'));
		echo sprintf(
			'<meta property="og:type" content="article">'
				. '<meta property="og:title" content="%1$s">'
				. '<meta name="author" content="%2$s">'
				. '<meta name="description" content="%4$s">'
				. '<meta property="og:description" content="%4$s">'
				. '<meta property="article:published_time" content="%3$s">'
				. '<meta name="fediverse:creator" content="%5$s">',
			esc_attr($postTitle),
			esc_attr($authorName),
			esc_attr(mysql2date('Y-m-d\TH:i:s\Z', get_post_field('post_date_gmt'))),
			esc_attr(wp_strip_all_tags(krinkle_get_proper_excerpt(), true)),
			esc_attr(TTNET_MASTO_USER . '@' . TTNET_MASTO_INSTANCE)
		);

		$thumbURL = get_the_post_thumbnail_url(null, 'full');
		$attachmentID = $thumbURL ? get_post_thumbnail_id() : null;
		$thumbAlt = $attachmentID ? get_post_meta($attachmentID,'_wp_attachment_image_alt', true) : null;
		if ($thumbURL && $thumbAlt) {
			echo sprintf(
				'<meta property="twitter:card" content="summary_large_image">'
				. '<meta property="og:image" content="%s">'
				. '<meta property="og:image:alt" content="%s">',
				esc_attr($thumbURL),
				esc_attr($thumbAlt)
			);
		}
	}

	// Preload
	$links = [];
	foreach ([
		krinkle_get_resource_uri('/images/icon-github-circled.svg', TTNET_UNVERSIONED) => [ 'as' => 'image' ],
		krinkle_get_resource_uri('/images/icon-mastodon.svg', TTNET_UNVERSIONED )=> [ 'as' => 'image' ],
		krinkle_get_resource_uri('/images/icon-rss-squared.svg', TTNET_UNVERSIONED) => [ 'as' => 'image' ],
	] as $url => $attribs) {
		$link = "<{$url}>;rel=preload";
		foreach ($attribs as $key => $val) {
			$link .= ";{$key}={$val}";
		}
		$links[] = $link;
	}
	if ($links) {
		header('Link: ' . implode(',', $links));
	}
?>
<link rel="stylesheet" href="<?php echo krinkle_get_resource_uri('/style.css'); ?>">
<link rel="icon" href="<?php echo krinkle_get_resource_uri('/images/favicon.png'); ?>">
<link rel="me" href="<?php echo esc_attr('https://' . TTNET_MASTO_INSTANCE . '/@' . TTNET_MASTO_USER); ?>">
<body<?php if (is_home()): ?> class="layout--home-intro"<?php endif; ?>>
<header class="nav"><div class="nav-container">
	<a href="/" class="nav-sitelink" title="Visit the home page">Timo Tijhof</a>
	<p><a href="/about/">About</a> &bull; <a href="/about/#colophon">Colophon</a></p>
</div></header>
<div class="wrap">
<main class="content">
