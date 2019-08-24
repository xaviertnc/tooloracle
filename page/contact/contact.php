<?php //pages/contact/contact.php


if ( ! defined('__APP_START__')) die(); // Silence is golden


$page = new stdClass();
$page->title = 'Contact Us';


$message = array_get($app->state, 'message', null);
unset($app->state['message']);


include $app->rootPath . '/header.php';

?>
<div class="contact-page">

  <h2>Contact Us</h2>

  <section>
    <br>
    <form method="POST">

      <div class="field">
        <label>Name:</label>
        <input type="text" name="username" autofocus required>
      </div>

      <div class="field">
        <label>Email:</label>
        <input type="email" name="email" required>
      </div>

      <div class="field">
        <label>Subject:</label>
        <input type="text" name="subject" placeholder="The short version..." required>
      </div>

      <div class="field">
        <label>Message:</label>
        <textarea name="message" rows="5"></textarea>
      </div>

      <?php if ($message) echo "<b class=\"red\">$message</b>"; ?>

      <div class="actionbar">
        <input type="submit" value="Submit" name="login">
      </div>

    </form>
    <br>
  </section>

</div>
<?php

include $app->rootPath . '/footer.php';


$_SESSION[$app->id] = $app->state;