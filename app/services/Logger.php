<?php

require $app->env->vendorsPath . '/OneFile/Logger.php';

$log = new \OneFile\Logger($app->env->storagePath . '/logs');
