<?php //pages/about/about.php


if ( ! defined('__APP_START__')) die(); // Silence is golden


$page = new stdClass();
$page->title = 'How To Guides';


$message = array_get($app->state, 'message', null);
unset($app->state['message']);


include $app->env->rootPath . '/header.php';

?>
<div class="howto container-fixed content">

  <h2>How To Guides</h2>

  <section>
    Content goes here...
  </section>

</div>
<?php

include $app->env->rootPath . '/footer.php';


$_SESSION[$app->id] = $app->state;