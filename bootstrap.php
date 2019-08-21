<?php // Bootstrap App

define('__DEBUG__', true);
define('__ENV_PROD__', false);


function array_get(array $array, $key, $default = null) {
  return isset($array[$key]) ? $array[$key] : $default;
}

register_shutdown_function(function() {
  if (error_get_last() !== null) {
    ob_clean();
    http_response_code(500);
    echo '<div class="error server-error"><h3>Oops, something went wrong!</h3>', PHP_EOL;
    if (__DEBUG__) { echo '<hr><pre>', print_r(error_get_last(), true), '</pre>'; }
    echo PHP_EOL, '</div>';
  }
});


session_start();


$app = new stdClass();

$app->id           = 'tooloracle';

$app->uriBase      = '/';
$app->rootPath     = array_get($_SERVER, 'DOCUMENT_ROOT', __DIR__);
$app->appPath      = $app->rootPath . '/app';
$app->vendorsPath  = $app->appPath  . '/vendors';
$app->servicesPath = $app->appPath  . '/services';
$app->storagePath  = $app->appPath  . '/storage';
$app->timezone     = 'Africa/Johannesburg';

$app->dbConnection = [
  'DBHOST' => 'localhost',
  'DBNAME' => 'tooloracle',
  'DBUSER' => 'root',
  'DBPASS' => 'root'
];


date_default_timezone_set($app->timezone);


// HTTP REQUEST
$request = new stdClass();
$request->uri = $_SERVER['REQUEST_URI'];
$request->protocol = isset($_SERVER['HTTPS']) ? 'https' : 'http';
$request->host = array_get($_SERVER, 'HTTP_HOST', 'tooloricle.localhost');
$request->urlBase = $request->protocol . '://' . $request->host . $app->uriBase;
$request->method = $_SERVER['REQUEST_METHOD'];
$request->back = array_get($_SERVER, 'HTTP_REFERER');
$request->parts = explode('?', $request->uri);
$request->query = isset($request->parts[1]) ? $request->parts[1] : '';
$request->parts = explode('/', $request->parts[0]);
$app->request = $request;


// RESTORE APP STATE
$app->state = array_get($_SESSION, $app->id, []);


$app->auth = new stdClass();
$app->auth->loggedIn = array_get($app->state, 'loggedIn', false);
$app->auth->username = 'admin';
$app->auth->password = 'admin';


// APP SERVICES
require $app->servicesPath . '/Logger.php';
require $app->servicesPath . '/Session.php';
require $app->servicesPath . '/Database.php';
require $app->servicesPath . '/Format.php';
require $app->servicesPath . '/View.php';

// PAGE
$page = new stdClass();
$page->title = 'Home';