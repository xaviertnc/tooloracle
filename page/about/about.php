<?php //pages/about/about.php


if ( ! defined('__APP_START__')) die(); // Silence is golden


$page = new stdClass();
$page->title = 'About Us';


$message = array_get($app->state, 'message', null);
unset($app->state['message']);


include $app->rootPath . '/header.php';

?>
<div class="about-page">

  <h2>About Us</h2>

  <?php if ($message):?>
  <h1><?=$message?></h1>
  <?php endif; ?>

  <section>
    Content goes here...
  </section>

</div>
<?php

include $app->rootPath . '/footer.php';


$_SESSION[$app->id] = $app->state;