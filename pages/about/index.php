<?php // Tool Oricle - Front Controller

include $_SERVER['DOCUMENT_ROOT'] . '/bootstrap.php';


$page = new stdClass();
$page->title = 'About Us';


$message = array_get($app->state, 'message', null);
unset($app->state['message']);


include $app->rootPath . '/header.php';

?>
<div class="page about">

  <h2>About TOOL ORACLE</h2>

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