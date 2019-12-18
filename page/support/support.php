<?php //pages/about/about.php


if ( ! defined('__APP_START__')) die(); // Silence is golden


$page = new stdClass();
$page->title = 'Support';


$message = array_get($app->state, 'message', null);
unset($app->state['message']);


include $app->env->rootPath . '/header.php';

?>
<div class="support content">

  <h2>Support</h2>

  <section>
    Content goes here...
  </section>

</div>
<?php

include $app->env->rootPath . '/footer.php';


$_SESSION[$app->id] = $app->state;