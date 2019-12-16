<?php //pages/about/about.php


if ( ! defined('__APP_START__')) die(); // Silence is golden


$page = new stdClass();
$page->title = 'Blog';


$message = array_get($app->state, 'message', null);
unset($app->state['message']);


include $app->rootPath . '/header.php';

?>
<div class="blog-page content">

  <h2>Blog</h2>
  <?php if ($message) echo "<b>$message</b>"; ?>

  <section>
  	<article>
  		<h2>Article #1 Title</h2>
  		<span>Author:</span>
  		<span>Date:</span>
  		<span>Tags:</span>
  		<div>Content...</div>
  	</article>
		<hr>
  	<article>
  		<h2>Article #2 Title</h2>
  		<span>Author:</span>
  		<span>Date:</span>
  		<span>Tags:</span>
  		<div>Content...</div>
  	</article>
		<hr>
  	<article>
  		<h2>Article #3 Title</h2>
  		<span>Author:</span>
  		<span>Date:</span>
  		<span>Tags:</span>
  		<div>Content...</div>
  	</article>
		<hr>
  	<article>
  		<h2>Article #4 Title</h2>
  		<span>Author:</span>
  		<span>Date:</span>
  		<span>Tags:</span>
  		<div>Content...</div>
  	</article>
  </section>

</div>
<?php

include $app->rootPath . '/footer.php';


$_SESSION[$app->id] = $app->state;