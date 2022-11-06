<article>
<header class="post-meta">
    <h1><?php the_title(); ?></h2>
    <?php
    /**
    * The `div.byline` element exists solely to please Safari Reader.
    *
    * I tried dozens of variations but without it, Safari will only hoist
    * the `H1` and `time` element directly to its header but leave "• Tag"
    * behind, as if it was the first paragraph of the post content.
    *
    * The `byline` class is internally hardcoded by Safari as way to mark
    * whatever is left as to be ignored.
    */
    edit_post_link( 'Edit', '<div class="byline">', '</div>' );
    ?>
</header>
<div class="post">
<?php the_content(); ?>
</div>
</article>
