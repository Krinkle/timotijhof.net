<?php get_header(); ?>
<h1><?php

if (is_tag()) {
	echo sprintf('Tag: %s', single_tag_title('', false));
} elseif (is_day()) {
	echo sprintf('Daily Archives: %s', get_the_date());
} elseif (is_month()) {
	echo sprintf('Monthly Archives: %s', get_the_date('F Y'));
} elseif (is_year()) {
	echo sprintf('Yearly Archives: %s', get_the_date('Y'));
} else {
	echo sprintf('Blog Archives', 'twentyeleven' );
}

?></h1>
<p class="posts-label"><?php
global $wp_query;
$size = (int)$wp_query->found_posts;

echo ($size === 1) ? '1 post' : "$size posts";
?> • <a href="/" title="Back to the home page">View all posts</a></p>
<div class="posts">
<?php

while ( have_posts() ) :
	the_post();
	get_template_part( 'content', 'archive' );
endwhile;

?>
</div>
<?php get_footer(); ?>
