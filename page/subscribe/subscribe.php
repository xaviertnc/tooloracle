<?php //page/login/login.php


if ( ! defined('__APP_START__')) die();


if ($request->method == 'POST')
{

  $goto = $request->back;

  if (isset($_POST['login']))
  {
    if ($_POST['username'] == $auth->username and
        $_POST['password'] == $auth->password)
    {
      $goto = 'admin/tools';
      $app->state['loggedIn'] = true;
    }
    else
    {
      $app->state['message'] = 'Login failed! Try again...';
      unset($app->state['loggedIn']);
    }
  }

  elseif (isset($_POST['logout']))
  {
    $goto = 'home';
    $app->state['loggedIn'] = false;
    unset($app->state['loggedIn']);
  }

  $_SESSION[$app->id] = $app->state;

  header('location:' . $goto);
  exit();
}


$page = new stdClass();
$page->title = 'Subscribe';


$message = array_get($app->state, 'message', null);
unset($app->state['message']);


include $app->env->rootPath . '/header.php';

?>
<div class="subscribe content">
  <br>
  <form method="POST" class="text-center">

    <fieldset style="display:inline-block;padding:1em 2em 2em;text-align:left;">

      <legend>Tool Oracle Subscribe</legend>

      <div class="field">
        <label>Username:</label>
        <input type="text" name="username" autofocus required>
      </div>

      <div class="field">
        <label>Password:</label>
        <input type="password" name="password" required>
      </div>

      <div class="field">
        <label>Repeat Password:</label>
        <input type="password" name="password2" required>
      </div>
      <?php if ($message) echo "<b class=\"red\">$message</b>"; ?>

      <div class="actionbar">
        <input type="submit" value="Subscribe" name="login">
      </div>

    </fieldset>

  </form>
  <br>
</div>
<?php

include $app->env->rootPath . '/footer.php';


$_SESSION[$app->id] = $app->state;