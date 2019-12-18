<?php //pages/error/404/404.php

  if ( ! defined('__APP_START__')) die(); // Silence is golden

  $page = new stdClass();
  $page->title = 'Error 404 (Page Not Found)';

?>
<style>
  h3 { color: red; }
  small { color: silver; }
  .error-container { padding: 1em; }
  .error {
    font-family: arial, verdana, sans-serif;
    max-width: 320px;
    margin: 1.5em auto;
  }
</style>
<div class="error-container">
  <div class="error server-error content">
    <h3>Oops, are you lost?</h3>
    <hr>
    <h4>The page you requested is not available.</h4>
    <h5>Click here to goto: <a class="pagelink" href="<?=$app->env->siteUrl?>">Home Page</a></h5>
  </div>
</div>