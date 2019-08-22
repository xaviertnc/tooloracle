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
      $goto = 'admin';
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
    $goto = 'login';
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


include $app->rootPath . '/header.php';

?>
<div class="page about">

  <br>

  <form method="POST">
    <fieldset style="padding:1em 2em 2em">
      <legend>Tool Oracle Admin</legend>
      <div class="field">
        <label>Username:</label>
        <input type="text" name="username" autofocus required>
      </div>
      <div class="field">
        <label>Password:</label>
        <input type="password" name="password" required>
      </div>
      <?php if ($message):?>
      <h4 class="red"><?=$message?></h4>
      <?php endif; ?>
      <div class="actionbar">
        <input type="submit" value="Login" name="login">
      </div>
    </fieldset>
  </form>

  <br>

</div>
<?php

include $app->rootPath . '/footer.php';


$_SESSION[$app->id] = $app->state;