<?php //pages/contact/contact.php


if ( ! defined('__APP_START__')) die(); // Silence is golden


$page = new stdClass();
$page->title = 'Contact Us';


if ($request->method == 'POST') {
  $app->state['message'] = 'Thank you for your message!';
  $_SESSION[$app->id] = $app->state;
  header('location:' . $request->back);
  exit();
}


$message = array_get($app->state, 'message', null);
unset($app->state['message']);


include $app->env->rootPath . '/header.php';

?>
<div class="contact-page container-fixed content">

  <section class="align-center" style="max-width:320px">
    <h2>Contact Us</h2>
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

      <?php if ($message) echo "<b class=\"green\">$message</b>"; ?>

      <div class="actionbar">
        <input type="submit" value="Submit" name="login">
      </div>

    </form>
    <br>
  </section>

</div>

<script type="text/javascript">(function(o){var b="https://fasttiger.io/anywhere/",t="5662ef736e0f4658bf47d2f8ded0c5072c20b58239144ccaafa2d4f3eef9295e",a=window.AutopilotAnywhere={_runQueue:[],run:function(){this._runQueue.push(arguments);}},c=encodeURIComponent,s="SCRIPT",d=document,l=d.getElementsByTagName(s)[0],p="t="+c(d.title||"")+"&u="+c(d.location.href||"")+"&r="+c(d.referrer||""),j="text/javascript",z,y;if(!window.Autopilot) window.Autopilot=a;if(o.app) p="devmode=true&"+p;z=function(src,asy){var e=d.createElement(s);e.src=src;e.type=j;e.async=asy;l.parentNode.insertBefore(e,l);};y=function(){z(b+t+'?'+p,true);};if(window.attachEvent){window.attachEvent("onload",y);}else{window.addEventListener("load",y,false);}})({});</script>
<?php

include $app->env->rootPath . '/footer.php';


$_SESSION[$app->id] = $app->state;