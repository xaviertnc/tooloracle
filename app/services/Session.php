<?php

require $app->vendorsPath . '/OneFile/Session.php';

$app->session = new \OneFile\Session();
$app->session->start($app->id);