<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?=$page->title?> - Tool Oracle</title>
  <base href="<?=$request->urlBase?>/">
  <link href="img/favicon.png" rel="shortcut icon">
  <link href="css/font-awesome.min.css" rel="stylesheet">
  <link href="css/pure-select.css" rel="stylesheet">
  <link href="css/style.css" rel="stylesheet">
</head>
<body>
  <noscript>This page will not display correctly without Javascript enabled.</noscript>
  <div id="page">
    <header id="site-header">
      <a id="brand" href="<?=$request->urlBase?>">
        <img id="site-logo" src="img/logo.png" alt="Site Logo">
        <h1 id="site-name">Tool Oracle
          <small>The right tool for the job.</small></h1>
      </a>
      <div id="site-nav">
        <nav id="main-nav">
          <a href="home">Home</a>
          <a href="about">About</a>
          <a href="contact">Contact Us</a>
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
    </header>
    <main>
