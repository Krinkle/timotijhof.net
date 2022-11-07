<?php

if (is_home()) :
  $tagLinks = [];
  foreach (get_tags() as $tag) {
    $tagLinks[] = '<a class="post-tag" href="'
      . esc_attr(get_tag_link($tag->term_id))
      . '">'
      . esc_html($tag->name)
      . '</a>';
  }
  if ($tagLinks) {
    echo '<p class="posts-label">tags</p>';
    echo implode(', ', $tagLinks);
  }
endif;

?>
</main>
<?php

$hideIntro = is_page() || is_tag();
if (!$hideIntro):
?>
<aside class="about <?php echo (is_home() ? 'about--home': 'about--foot'); ?>">
  <a href="/" class="about-sitelink" title="Visit the home page"><?php
    if ( is_home() ) :
  ?><img src="<?php echo esc_attr(krinkle_get_resource_uri('/images/profile.jpg', TTNET_UNVERSIONED)); ?>" alt="" width="192" height="192"><?php
    endif;
  ?><span>Timo Tijhof</span></a>
  <p class="about-tagline"><?php echo esc_html(TTNET_BIO); ?></p>
  <ul class="about-social">
    <li><a href="<?php echo esc_attr('https://' . TTNET_MASTO_INSTANCE . '/@' . TTNET_MASTO_USER); ?>" title="Fediverse: <?php echo esc_attr('@' . TTNET_MASTO_USER . '@' . TTNET_MASTO_INSTANCE ); ?>"><i class="icon icon-mastodon"></i></a></li>
    <li><a href="<?php echo esc_attr(get_bloginfo('rss2_url')); ?>" title="RSS Feed"><i class="icon icon-rss-squared"></i></a></li>
    <li><a href="https://github.com/<?php echo esc_attr(TTNET_GITHUB); ?>" title="GitHub: <?php echo esc_attr(TTNET_GITHUB); ?>"><i class="icon icon-github-circled"></i></a></li>
  </ul>
</aside>
<?php endif; ?>
</div>
<script type="module">
if (document.body.matches) {
  document.body.addEventListener("ontouchstart" in window ? "dblclick" : "click", e => {
    if (e.target.nodeName === "IMG" && !e.target.matches("a img")) {
        if (e.altKey || e.metaKey) { window.open(e.target.src); }
        else { location.href = e.target.src; }
    }
  });
}
</script>
<?php wp_footer(); ?>
</body>
</html>
