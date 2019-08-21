<?php

require $app->vendorsPath . '/OneFile/Logger.php';

$app->log = new \OneFile\Logger($app->storagePath . '/logs');
