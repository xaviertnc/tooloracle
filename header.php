<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?=$page->title?> - Tool Oracle</title>
  <base href="<?=$request->urlBase?>/">
  <link href="img/favicon.png" rel="shortcut icon">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans|Open+Sans+Condensed:300&display=swap" rel="stylesheet">
  <link href="css/font-awesome.min.css" rel="stylesheet">
  <link href="css/pure-select.css" rel="stylesheet">
  <link href="css/style.css" rel="stylesheet">
  <script async src="https://www.googletagmanager.com/gtag/js?id=UA-146283830-1"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'UA-146283830-1');
  </script>
</head>
<body>
<noscript>This page will not display correctly without Javascript enabled.</noscript>
<header id="site-header">
  <div class="content">
    <a id="brand" href="<?=$request->urlBase?>">
      <img id="site-logo" src="img/logo.png" alt="Site Logo">
      <?php if ( ! $auth->loggedIn): ?>

      <h1 id="site-name">Tool Oracle
        <small>The right tool for the job.</small>
      </h1>
      <?php endif; ?>

    </a>
    <button id="toggle-nav" type="button"
      onclick="this.nextElementSibling.classList.toggle('open')">
      <span></span>
      <span></span>
      <span></span>
    </button>
    <div id="site-nav">
      <nav id="main-nav">
        <a href="howto">How To Guides</a>
        <a href="answers">Q&amp;A's</a>
        <a href="support">Support</a>
        <a href="blog">Blog</a>
        <a href="contact">Contact</a>
        <?php if ($auth->loggedIn): ?>

        <a href="admin/tools">Admin</a>
        <?php endif; ?>

      </nav>
      <nav id="user-nav">
        <a href="subscribe" class="btn-cta">Subscribe</a>
        <?php if ($auth->loggedIn): ?>

        <form id="logout" action="login" method="POST">
          <button type="submit" class="btn btn-logout" name="logout">Logout</button>
        </form>
        <?php else:?>

        <a id="login" href="login" class="btn btn-login">Login</a>
        <?php endif; ?>

      </nav>
    </div>
  </div>
</header>
<main>
