<article>
<header class="post-meta">
	<a class="post-link" href="<?php echo esc_url( get_permalink() ); ?>">
		<h1><?php the_title(); ?></h2>
	</a>
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
  ?>
  <div class="byline"><?php
		$timeHtml = '<time class="post-date" datetime="%1$s">%2$s</time>';
		echo sprintf(
			$timeHtml,
			esc_attr( get_the_date( 'Y-m-d' ) ),
			get_the_date( 'j M Y' )
		);

        $sep = ' • ';
        $join = ', ';
        $tags_list = get_the_tag_list( '', $join );
        if ( $tags_list ) {
            echo $sep . $tags_list;
        }

        edit_post_link( 'Edit', $sep );
	?>
  </div>
</header>
<div class="post">
<?php the_content(); ?>
</div>
</article>
