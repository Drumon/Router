<?php

require __DIR__.'/../../vendor/autoload.php';


$request = new \Drumon\Request($_SERVER, $_POST, $_GET);
echo 'aa';
// $routes = include 'routes.php';

// $request = new \Drumon\Request($_SERVER, $_POST, $_GET);
// $router = new \Drumon\Router($request->getMethod(), $request->getPath());

// if ($router->match($routes)) {
//     $request->addParams($router->getParams());
// } else {
// 	die('not_found');
// }