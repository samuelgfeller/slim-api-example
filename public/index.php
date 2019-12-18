<?php
// @todo remove that
ini_set('xdebug.var_display_max_depth', '10');
ini_set('xdebug.var_display_max_children', '256');
ini_set('xdebug.var_display_max_data', '1024');

$GLOBALS['time']['start'] = hrtime(true);

use Slim\Factory\AppFactory;
use DI\ContainerBuilder;

// todo validation - return error
// todo login / calls via token jsfw
// todo add posts and user roles
// todo testing junit
// todo translation
// todo use frontend framework

// Time in ms
$GLOBALS['time']['untilBeforeAutoload'] = (hrtime(true) - $GLOBALS['time']['start'])/1e+6;

require __DIR__ . '/../vendor/autoload.php';
$GLOBALS['time']['untilAfterAutoload'] = (hrtime(true) - $GLOBALS['time']['start'])/1e+6;


/*
 * Instantiate App
 */
// Instantiate PHP-DI ContainerBuilder
$containerBuilder = new ContainerBuilder();

// Set up settings
$settings = require __DIR__ . '/../app/settings.php';
$settings($containerBuilder);

// Set up dependencies
$dependencies = require __DIR__ . '/../app/dependencies.php';
$dependencies($containerBuilder);

// Set up repositories
$repositories = require __DIR__ . '/../app/repositories.php';
$repositories($containerBuilder);

// Build PHP-DI Container instance
$container = $containerBuilder->build();


// Set container to c<reate App with on AppFactory
AppFactory::setContainer($container);
// Instantiate the app
$app = AppFactory::create();

// Register middleware
$middleware = require __DIR__ . '/../app/middleware.php';
$middleware($app);

// Access Control headers
/*$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
//		->withHeader('Access-Control-Allow-Origin', 'http://www.test-cors.org/')
        ->withHeader('Access-Control-Allow-Origin', '*')
//		->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});*/

/*$container->set('LocationController', function (ContainerInterface $c) {
//    return new LocationController($c);
});


$container->set('LocationService', function (ContainerInterface $c) {
    return new LocationService($c);
});*/


// testing cors http://www.test-cors.org/#?client_method=POST&client_credentials=false&server_url=http%3A%2F%2Fdev.slim_first_app%2Flocations&server_enable=true&server_status=200&server_credentials=false&server_tabs=remote
// serv http://slimfirstapp.masesselin.ch/
// doc https://www.slimframework.com/docs/v4/objects/routing.html#how-to-create-routes
// Route group best practices: https://stackoverflow.com/questions/34502856/slim-3-framework-should-i-be-using-route-groups-for-my-api

// Add Routing Middleware
$app->addRoutingMiddleware();

$GLOBALS['time']['untilBeforeRoute'] = (hrtime(true) - $GLOBALS['time']['start'])/1e+6;

// Routing
$routes = require __DIR__ . '/../app/routes.php';
$routes($app);
$GLOBALS['time']['untilAfterRouting'] = (hrtime(true) - $GLOBALS['time']['start'])/1e+6;

// Frontend routing todo remove when separating frontend
$routesFrontend = require __DIR__ . '/../app/routes_frontend.php';
$routesFrontend($app);




/*
 * Add Error Handling Middleware
 *
 * @param bool $displayErrorDetails -> Should be set to false in production
 * @param bool $logErrors -> Parameter is passed to the default ErrorHandler
 * @param bool $logErrorDetails -> Display error details in error log
 * which can be replaced by a callable of your choice.

 * Note: This middleware should be added last. It will not handle any exceptions/errors
 * for middleware added after it.
 */
$errorMiddleware = $app->addErrorMiddleware(true, true, true);






$app->run();
$GLOBALS['time']['untilAfterRun'] = round(microtime(true) - $GLOBALS['time']['start'],5);
//var_dump($GLOBALS['time']);
