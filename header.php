<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?=$page->title?> - Tool Oracle</title>
  <base href="/">
  <link href="img/favicon.png" rel="shortcut icon">
  <link href="css/font-awesome.min.css" rel="stylesheet">
  <link href="css/pure-select.css" rel="stylesheet">
  <link href="css/style.css" rel="stylesheet">
</head>
<body>
  <noscript>This page will not display correctly without Javascript enabled.</noscript>
  <div id="page">
    <header id="site-header">
      <div id="brand">
        <img id="site-logo" src="img/logo.png" alt="Site Logo">
        <h1 id="site-name">Tool Oracle
          <small>The right tool for the job.</small></h1>
      </div>
      <div id="site-nav">
        <nav id="main-nav">
          <a href="/">Home</a>
          <a href="/pages/about">About</a>
          <a href="/pages/contact">Contact Us</a>
        </nav>
        <nav id="user-nav">
          <?php if ($app->auth->loggedIn): ?>
          <form action="/pages/login/index.php" method="POST">
            <button type="submit" class="btn login" name="logout">Logout</button>
          </form>
          <?php else:?>
          <a href="/pages/login" class="btn login">Login</a>
          <?php endif; ?>
        </nav>
      </div>
    </header>
    <main>
