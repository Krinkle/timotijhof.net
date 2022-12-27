<?php

define('TTNET_GITHUB', 'Krinkle');
define('TTNET_MASTO_USER', 'krinkle');
define('TTNET_MASTO_INSTANCE', 'timotijhof.net');
define('TTNET_BIO', 'Principal Engineer, Wikimedia&nbsp;Foundation.');

define('TTNET_VERSIONED', 1);
define('TTNET_UNVERSIONED', 2);

// Action firing order:
// https://codex.wordpress.org/Plugin_API/Action_Reference
add_action('after_setup_theme', function () {
	// Let wp_head() handle <title>
	add_theme_support('title-tag');

	// Let wp_head() use HTML5
	add_theme_support('html5',['search-form', 'gallery', 'caption', 'style', 'script']);

	// Let wp-admin associate a featured image with a post
	add_theme_support('post-thumbnails');

	// But only the main post feed, no comments feed
	add_filter('feed_links_show_comments_feed', '__return_false');
});

add_action('init', function () {
	// Remove stuff from wp_head
	remove_action('wp_head', 'feed_links_extra', 3);
	remove_action('wp_head', 'rsd_link');
	remove_action('wp_head', 'wlwmanifest_link');
	remove_action('wp_head', 'rest_output_link_wp_head');
	add_action('wp_enqueue_scripts', function () {
		wp_dequeue_style('wp-block-library');
		wp_dequeue_style('classic-theme-styles');
		wp_dequeue_style('global-styles');
	});
});

// https://github.com/westonruter/syntax-highlighting-code-block
add_filter('syntax_highlighting_code_block_styling', '__return_false');

/**
 * Based on "Add IDs to Header Tags"
 * - URI: http://wordpress.org/plugins/add-ids-to-header-tags/
 * - Version: 1.0
 * - Author: George Stephanis <http://stephanis.info>
 * - License: WTFPL <http://www.wtfpl.net/txt/copying/>
 *
 * Modifications:
 * - Change is_single to is_singular, to cover both posts and pages.
 */
add_filter('the_content', 'krinkle_on_content_add_heading_ids');
function krinkle_on_content_add_heading_ids($content) {
	if (!is_singular()) {
		return $content;
	}

	$pattern = '#(?P<full_tag><(?P<tag_name>h\d)(?P<tag_attr>[^>]*)>(?P<tag_content>[^<]*)</h\d>)#i';
	if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
		$find = [];
		$replace = [];
		foreach ($matches as $match) {
			if (str_contains($match['tag_attr'] ?? '', 'id=' )) {
				continue;
			}
			$find[] = $match['full_tag'];
			$id = sanitize_title( $match['tag_content'] );
			$id_attr = sprintf(' id="%s"', esc_attr($id));
			$replace[] = sprintf('<%1$s%2$s%3$s>%4$s</%1$s>',
				$match['tag_name'],
				$match['tag_attr'],
				$id_attr,
				$match['tag_content']
			);
		}
		$content = str_replace($find, $replace, $content);
	}

	return $content;
}

/**
 * Strip "Code language" label. Upstream hides these visually by default
 * but not in a semantic way that e.g. Firefox reader view, Safari Reader,
 * or RSS would know to hide.
 *
 * Ref https://github.com/westonruter/syntax-highlighting-code-block/tree/1.3.1
 */
add_filter('the_content', 'krinkle_on_content_remove_shcb');
function krinkle_on_content_remove_shcb($content) {
	$pattern = '/<small[^>]*\sclass="shcb-language"[^>]*>.*?<\/small>/s';
	$content = preg_replace($pattern, '', $content);

	$pattern = '/aria-describedby="[^"]+"/';
	$content = preg_replace($pattern, '', $content);

	return $content;
}

add_filter('the_content_feed', 'krinkle_on_feed_content_add_footer');
function krinkle_on_feed_content_add_footer($content) {
	$content .= '<hr/><p>'
		. 'This post appeared on <a href="' . esc_attr(get_permalink()) . '">'
		. esc_html(parse_url(get_site_url(), PHP_URL_HOST))
		. '</a>'
		. '. '
		// Inspired by Ru Singh (2022)
		// <https://rusingh.com/adding-a-comment-via-email-convenience-link/>
		. '<a target="_blank" href="mailto:'
		. get_the_author_meta('user_email')
			. '?subject='
			// Avoid get_the_title() as titles can contain HTML markup,
			// and are otherwise html-escaped. We prefer tagless plain text,
			// and not html-escaped, to (first) URL-encode.
			. rawurlencode('RE: ' . the_title_attribute([ 'echo' => false ]))
			. '&body='
			. rawurlencode("\n\n\nPermalink: " . get_permalink())
		. '">Reply via email</a>'
		. '.</p>';

	return $content;
}

add_filter('excerpt_more', 'krinkle_excerpt_more');
function krinkle_excerpt_more($more) {
	return '…';
}

function krinkle_get_proper_excerpt($post = null) {
	// get_the_excerpt() normally has the following chain:
	//
	// 1. post_excerpt (if defined),
	// 2. post_content upto the `<!--more-->` marker (if present),
	// 3. post_content trimmed to threshold with ellipsis (fallback).
	//
	// But, this only works when called on index/home/archive/etc.
	//
	// When calling get_the_excerpt() from requests for a singular 'page' or 'post',
	// WP_Query internally sets $more=1 for unknown reasons, and thus skips option 2,
	// with no apparent way to get at the more-based excerpt that is displayed everywhere
	// else in WordPress by default. This has the ugly side-effect that if you output
	// a meta description based on the excerpt, the options are to either:
	//
	// A. Manually copy the first paragraph into the excerpt in wp-admin, or;
	// B. Call it anyway and accept that you get the uglier option 3 with an arbitrary
	//    cut into the content.
	//
	// So... let's use the unrelated get_extended() function intended for WP-XMLRPC
	// which appears to be the only other built-in function apart from get_the_content()
	// that contains logic for `<!--more-->`, except in a way that is reusable.
	//
	// --krinkle 2022-11-20
	$post = $post ?? get_post();
	$excerpt = $post->post_excerpt ?: '';
	if ($excerpt === '') {
		$extended = get_extended($post->post_content);
		if ($extended['main'] && $extended['extended']) {
			$excerpt = $extended['main'];
		} else {
			// If there is no "more" marker, get_extended places the entire content
			// in "main". Fallback to option 3 (trimmed content) in that case.
			$excerpt = wp_trim_excerpt('', $post);
		}
	}
	return $excerpt;
}

function krinkle_get_resource_query($filepath) {
	$hash = @md5_file($filepath);
	return $hash ? '?v=' . substr($hash, 0, 7) : '';
}

// Resources with TTNET_UNVERSIONED should be specified in .htaccess
function krinkle_get_resource_uri($path, $flags = TTNET_VERSIONED) {
	$uri = krinkle_relpath(get_stylesheet_directory_uri() . $path);
	if ($flags === TTNET_VERSIONED) {
		$filepath = __DIR__ . $path;
		$uri .= krinkle_get_resource_query($filepath);
	}
	return $uri;
}
function krinkle_relpath($uri) {
	static $siteurl;
	if (!$siteurl) {
		$siteurl = set_url_scheme(get_site_url());
	}
	if (!str_starts_with($uri, $siteurl)) {
		return $uri;
	}
	return substr($uri, strlen($siteurl)) ?: '/';
}


class Krinkle_Plugin_Footnotes {
	private $refs = [];
	private $offset = 0;

	public function __construct() {
		add_shortcode('ref', [$this, 'on_shortcode_ref']);

		// built-in do_shortcode filter runs on the_content with priority 11,
		// we must run after that
		add_filter('the_content', [$this, 'on_the_content'], 20);
		add_filter('the_content', [$this, 'on_the_content_clear'], 100);
	}

	/**
	 * @param array $attrs
	 * @param string $content
	 */
	public function on_shortcode_ref($attrs, $content = null) {
		if (is_home() || is_search() || is_archive() || is_category()) {
			return '';
		}

		$this->offset++;
		$offset = $this->offset;
		$content = do_shortcode($content);
		$this->refs[] = [ 'offset' => $offset, 'content' => $content ];

		$href = '#fn' . $offset;
		if (!is_singular()) {
			$href = get_permalink(get_the_ID()) . $href;
		}

		return sprintf(
			'<sup id="fnr%s" class="footnote">'
			. '<a rel="footnote" role="doc-noteref" href="%s" title="Jump to footnote %s">[%s]</a>'
			. '</sup>',
			esc_attr($offset),
			esc_attr($href),
			esc_attr($offset),
			esc_html($offset),
		);
	}

	public function on_the_content($content) {
		if (!$this->refs) {
			return $content;
		}

		$itemsHtml = '';

		foreach ($this->refs as $ref) {
			$itemsHtml .= sprintf(
				'<li id="fn%1$s" role="doc-endnote">'
					. '%2$s'
					. ' <a href="#fnr%1$s" role="doc-backlink" title="Jump back">↩︎</a>'
					. '</li>',
				esc_attr($ref['offset']),
				$ref['content']
			);
		}

		$footnotesHtml = sprintf(
			'<hr><div class="footnotes" role="doc-endnotes">Footnotes:<ol>%s</ol></div>',
			$itemsHtml
		);

		return $content . "\n" . $footnotesHtml;
	}

	public function on_the_content_clear($content) {
		$this->offset = 0;
		$this->refs = [];

		return $content;
	}
}

$krinkle_footnotes = new Krinkle_Plugin_Footnotes();
