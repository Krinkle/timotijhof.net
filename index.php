<?php get_header(); ?>
<?php
// The `div.posts` element exists in order for the
// `.posts-row:last-child` selector to work as expected.
?>
<div class="posts">
<?php

while ( have_posts() ) :
	the_post();
	get_template_part( 'content', 'index' );
endwhile;

?>
</div>
<?php get_footer(); ?>
