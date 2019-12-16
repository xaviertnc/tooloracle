<?php //pages/about/about.php


if ( ! defined('__APP_START__')) die(); // Silence is golden


$page = new stdClass();
$page->title = 'Q&A';


$message = array_get($app->state, 'message', null);
unset($app->state['message']);


include $app->rootPath . '/header.php';

?>
<div class="answers content">

  <h2>Q&A's</h2>

  <section>
    Content goes here...
  </section>

</div>
<?php

include $app->rootPath . '/footer.php';


$_SESSION[$app->id] = $app->state;