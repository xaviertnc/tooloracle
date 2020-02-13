<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?=$page->title?> - Tool Oracle</title>
  <base href="<?=$app->env->siteUrl?>/">
  <link href="img/favicon.png" rel="shortcut icon">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans|Open+Sans+Condensed:300&display=swap" rel="stylesheet">
  <link href="css/font-awesome.min.css" rel="stylesheet">
  <link href="css/pure-select.css" rel="stylesheet">
  <link href="css/style.css" rel="stylesheet">
<!--   <script async src="https://www.googletagmanager.com/gtag/js?id=UA-146283830-1"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'UA-146283830-1');
  </script> -->
</head>
<body>
<noscript>This page will not display correctly without Javascript enabled.</noscript>
<header id="site-header">
  <div class="container-fixed">
    <a id="site-brand" href="<?=$app->env->siteUrl?>">
      <img id="site-logo" src="img/logo.png" alt="Site Logo">
      <?php if ( ! $auth->loggedIn): ?>

      <h1 id="site-name">Tool Oracle
        <small>The right tool for the job.</small>
      </h1>
      <?php endif; ?>

    </a>
    <div class="navbar">
      <button class="nav-toggle" type="button"
        onclick="this.nextElementSibling.classList.toggle('open')">
        <span></span>
        <span></span>
        <span></span>
      </button>
      <nav>
        <ul id="site-nav">
          <li><a href="howto">How To Guides</a></li>
          <li><a href="answers">Q&amp;A's</a></li>
          <li><a href="support">Support</a></li>
          <li><a href="blog">Blog</a></li>
          <li><a href="contact">Contact</a></li>
          <?php if ($auth->loggedIn): ?>

          <li><a href="admin/tools">Admin</a></li>
          <?php endif; ?>

        </ul>
        <ul id="auth-nav">
          <?php if ($auth->loggedIn): ?>
          <li>
            <form action="login" method="POST">
              <button type="submit" class="btn btn-logout" name="logout">Logout</button>
            </form>
          </li>
          <?php else:?>

          <li><a href="subscribe" class="btn-cta btn-subscribe">Subscribe</a></li>
          <li><a href="login" class="btn btn-login">Login</a></li>
          <?php endif; ?>

        </ul>
      </nav>
    </div>
  </div>
</header>
<main>
