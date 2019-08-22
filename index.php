<?php // Tool Oracle - Front Controller


define('__APP_START__', true);


function array_get(array $array, $key, $default = null) {
  return isset($array[$key]) ? $array[$key] : $default;
}


register_shutdown_function(function() {
  if (error_get_last() !== null) {
    http_response_code(500);
    echo '<div class="error server-error"><h3>Oops, something went wrong!</h3>', PHP_EOL;
    if (__DEBUG__) { echo '<hr><pre>', print_r(error_get_last(), true), '</pre>'; }
    echo PHP_EOL, '</div>';
  }
});


session_start();


// GLOBALS
$log     = new stdClass();
$app     = new stdClass();
$auth    = new stdClass();
$request = new stdClass();


require '.env-local';


// APP SERVICES
require $app->servicesPath . '/Logger.php';
require $app->servicesPath . '/Database.php';
require $app->servicesPath . '/Format.php';
require $app->servicesPath . '/View.php';


// GET PAGE CONTROLLER
$app->currentPage = $request->parts[count($request->parts)-1];
$app->controllerPath = $app->pagesPath . '/' . $request->pageRef;
$app->controller = $app->controllerPath . '/' . $app->currentPage . '.php';

if ( ! file_exists($app->controller)) {
  $app->controllerPath = $app->pagesPath . '/error/404';
  $app->controller = $app->controllerPath . '/404.php';
}


require $app->controller;