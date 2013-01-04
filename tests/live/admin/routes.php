<?php

$routes = array();

$routes['NAME']['home'] = $routes['GET']['/'] = array('admin');
$routes['GET']['/contact'] = array('admin contact');

//$routes['MODULE']['/gallery'] = array('/admin/gallery/routes.php');

return $routes;
