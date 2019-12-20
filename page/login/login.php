<?php //page/login/login.php


if ( ! defined('__APP_START__')) die();


if ($request->method == 'POST')
{

  $goto = $request->back;

  if (isset($_POST['login']))
  {
    if ($_POST['username'] === $app->env->auth->username and
        $_POST['password'] === $app->env->auth->password)
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
$page->title = 'Login';


$message = array_get($app->state, 'message', null);
unset($app->state['message']);


include $app->env->rootPath . '/header.php';

?>
<div class="login content">
  <br>
  <form method="POST" class="text-center">

    <fieldset style="display:inline-block;padding:1em 2em 2em;text-align:left;">

      <legend>Tool Oracle Admin</legend>

      <div class="field">
        <label>Username:</label>
        <input type="text" name="username" autofocus required>
      </div>

      <div class="field">
        <label>Password:</label>
        <input type="password" name="password" required>
      </div>
      <?php if ($message) echo "<b class=\"red\">$message</b>"; ?>

      <div class="actionbar">
        <input type="submit" value="Login" name="login">
      </div>

    </fieldset>

  </form>
  <br>
</div>
<?php

include $app->env->rootPath . '/footer.php';


$_SESSION[$app->id] = $app->state;