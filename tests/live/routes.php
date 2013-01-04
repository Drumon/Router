<?php

$routes = array();

$routes['NAME']['home'] = $routes['GET']['/'] = array('index');

$routes['GET']['/contact'] = array('contact');
$routes['GET']['/posts/{id}'] = array('post id');

$routes['MODULE']['/admin'] = 'admin/routes.php';
$routes['MODULE']['/blog'] = 'blog/routes.php';

return $routes;
