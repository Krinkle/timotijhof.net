<div class="posts-row">
	<a class="post-link" href="<?php echo esc_url(get_permalink()); ?>"><h2 class="post-title"><?php the_title(); ?></h2></a>
	<a class="post-date" href="<?php echo esc_url(get_permalink()); ?>" tabindex="-1"><?php
		$timeHtml = '<time class="entry-date" datetime="%1$s">%2$s</time>';
		echo sprintf(
			$timeHtml,
			esc_attr(get_the_date('Y-m-d')),
			get_the_date('j M Y')
		);
	?></a>
	<div class="post"><?php the_excerpt(); ?></div>
</div>
