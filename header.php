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
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
  <script>
    (adsbygoogle = window.adsbygoogle || []).push({
      google_ad_client: "ca-pub-4708631205118863",
      enable_page_level_ads: true
    });
  </script>
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
        <h1 id="site-name">Tool Oracle
          <small>The right tool for the job.</small></h1>
      </a>
      <div id="site-nav">
        <nav id="main-nav">
          <a href="home">Home</a>
          <a href="about">About</a>
          <a href="contact">Contact</a>
          <a href="blog">Blog</a>
        </nav>
        <nav id="user-nav">
          <?php if ($auth->loggedIn): ?>
          <form action="login" method="POST">
            <button type="submit" class="btn login" name="logout">Logout</button>
          </form>
          <a href="admin">Admin</a>
          <?php else:?>
          <a href="login" class="btn login">Login</a>
          <?php endif; ?>
        </nav>
      </div>
    </div>
  </header>
  <main class="content">
