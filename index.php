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


// GLOBALS
$app       = new stdClass();
$auth      = new stdClass();
$request   = new stdClass();


// APP ID
$app->id = 'tooloracle';


// APP STATE
session_start();
$app->state = array_get( $_SESSION, $app->id, [] );


// APP LOCAL ENVIRONMENT
require '.env-local';


// HTTP REQUEST
$request->uri        = array_get( $_SERVER, 'REQUEST_URI'    );
$request->method     = array_get( $_SERVER, 'REQUEST_METHOD' );
$request->back       = array_get( $_SERVER, 'HTTP_REFERER'   );
$request->parts      = explode  ( '?',       $request->uri   );
$request->pageRef    = trim(str_replace($env->siteUrl, '', $request->parts[0]), '/');
$request->query      = isset($request->parts[1]) ? $request->parts[1] : '';
$request->pageRef    = $request->pageRef?:$app->env->homeUri;
$request->parts      = explode('/', $request->pageRef);
$request->partsCount = $request->parts ? count($request->parts) : 0;
$request->lastPart   = $request->parts[$request->partsCount - 1];
$request->itemId     = (int) $request->lastPart;
if ( $request->itemId > 0 ) {
	$request->partsCount--;
	array_pop($request->parts);
	$request->pageRef  = implode($request->parts, '/');
  $request->lastPart = $request->parts[$request->partsCount - 1];
} else {
	$request->itemId = null;
}


// AUTH STATUS
$auth->loggedIn = array_get( $app->state, 'loggedIn', false );


// APP SERVICES
require $app->env->servicesPath . '/Logger.php';
require $app->env->servicesPath . '/Database.php';
require $app->env->servicesPath . '/Format.php';
require $app->env->servicesPath . '/View.php';


// GET PAGE CONTROLLER
$app->currentPage = $request->lastPart;
$app->controllerPath = $app->env->pagesPath . '/' . $request->pageRef;
$app->controller = $app->controllerPath . '/' . $app->currentPage . '.php';
if ( ! file_exists($app->controller)) {
  $app->controllerPath = $app->env->pagesPath . '/error/404';
  $app->controller = $app->controllerPath . '/404.php';
}


require $app->controller;