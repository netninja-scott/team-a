<?php
define('TEAMA_ROOT', \dirname(\dirname(__DIR__)));

require_once TEAMA_ROOT . '/vendor/autoload.php';

/* Define the routes here: */
$dispatcher = FastRoute\simpleDispatcher(
    function (FastRoute\RouteCollector $r) {
        $r->addRoute(
            'GET',
            '/',
            ['\\Netninja\\TeamA\\API\\Index', 'index']
        );
    }
);

$httpMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
$uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';

// Strip query string (?foo=bar) and decode URI
if (false !== $pos = \strpos($uri, '?')) {
    $uri = \substr($uri, 0, $pos);
}
$uri = \rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        \http_response_code(404);
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        \http_response_code(405);
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];
        $obj = new $handler[0];
        if (!empty($vars)) {
            $obj->{$handler[1]}(...$vars);
        } else {
            $obj->{$handler[1]}();
        }
        break;
}
