<?php

/**
*
*/
class RouterTest extends PHPUnit_Framework_TestCase
{

    public function FactoryRequest($method, $path)
    {
        $get_params = array();
        $post_params = array();

        if (in_array($method, array('DELETE','PUT','PACTH'))) {
            $post_params['_method'] = $method;
            $method = 'POST';
        }

        $server = array(
            'REQUEST_METHOD' => $method,
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => $path
        );

        return new \Drumon\Request($server, $get_params, $post_params);
    }

    public function FactoryRoutes()
    {
        $routes = array();
        $routes['GET']['/'] = 'home';
        $routes['GET']['/page'] = 'get_page';
        $routes['POST']['/page'] = 'post_page';
        $routes['PUT']['/page'] = 'put_page';
        $routes['DELETE']['/page'] = 'delete_page';
        $routes['PACTH']['/page'] = 'pacth_page';
        $routes['*']['/any'] = 'any';
        $routes['GET']['/cursosonline'] = 'cursosonline';
        $routes['GET']['/cursos'] = 'cursos';
        $routes['*']['/article/{id}'] = array('article_id');
        $routes['*']['/article/car/{id}'] = array(
            'article_car_id',
            'params' => array('category' => 'car')
        );
        $routes['*']['/article/{id}/{slug}'] = array('article_title');
        $routes['*']['/posts/{name}'] = array(
            'post_name',
            'conditions' => array('{name}' => '[a-z]')
        );
        $routes['*']['/posts/{id}'] = array(
            'post_id',
            'conditions' => array('{id}' => '[0-9]')
        );

        return $routes;
    }

    public function FactoryMatch($method, $path)
    {
        $request = $this->FactoryRequest($method, $path);
        $router = new \Drumon\Router($request->getMethod(), $request->getPath());
        $router->match($this->FactoryRoutes());

        return $router;
    }

    public function testMatchRoot()
    {
        $router = $this->FactoryMatch('GET', '/');
        $this->assertEquals('home', $router->getData());
    }

    public function testMatchGetStatic()
    {
        $router = $this->FactoryMatch('GET', '/page');
        $this->assertEquals('get_page', $router->getData());
    }

    public function testMatchPostStatic()
    {
        $router = $this->FactoryMatch('POST', '/page');
        $this->assertEquals('post_page', $router->getData());
    }

    public function testMatchDeleteStatic()
    {
        $router = $this->FactoryMatch('DELETE', '/page');
        $this->assertEquals('delete_page', $router->getData());
    }

    public function testMatchPutStatic()
    {
        $router = $this->FactoryMatch('PUT', '/page');
        $this->assertEquals('put_page', $router->getData());
    }

    public function testMatchPacthStatic()
    {
        $router = $this->FactoryMatch('PACTH', '/page');
        $this->assertEquals('pacth_page', $router->getData());
    }

    public function testMatchAnyStatic()
    {
        $router = $this->FactoryMatch('GET', '/any');
        $this->assertEquals('any', $router->getData());
    }

    public function testMatchNone()
    {
        $router = $this->FactoryMatch('GET', '/not_found');
        $this->assertNull($router->getData());
    }

    public function testMatchSimilarRoute()
    {
        $router = $this->FactoryMatch('GET', '/cursos');
        $this->assertEquals('cursos', $router->getData());
    }

    public function testMatchWithOneParam()
    {
        $router = $this->FactoryMatch('GET', '/article/1');

        $this->assertEquals(array(
            'article_id',
            'params' => array('id' => 1)
        ), $router->getData());
    }

    public function testMatchWithOneParamAndOneDefault()
    {
        $router = $this->FactoryMatch('GET', '/article/car/1');

        $this->assertEquals(array(
            'article_car_id',
            'params' => array(
                'id' => 1,
                'category' => 'car'
            )
        ), $router->getData());
    }

    public function testMatchWithTwoParam()
    {
        $router = $this->FactoryMatch('GET', '/article/1/title-one');

        $this->assertEquals(array(
            'article_title',
            'params' => array(
                'id' => 1,
                'slug' => 'title-one'
            )
        ), $router->getData());
    }

    public function testMatchWithRegexParam()
    {
        $router = $this->FactoryMatch('GET', '/posts/1');

        $this->assertEquals(array(
            'post_id',
            'conditions' => array('{id}' => '[0-9]'),
            'params' => array(
                'id' => 1
            )
        ), $router->getData());
    }

    public function testMatchModuleLoadFile()
    {
        $routes = array();
        $routes['MODULE']['/module'] = 'module_routers.php';

        $module_routes = array();
        $module_routes['GET']['/'] = 'module';

        $request = $this->FactoryRequest('GET', '/module');

        $router = $this->getMock('Drumon\Router',
            array('includeModuleRoutes'),
            array($request->getMethod(), $request->getPath())
        );

        $router->expects($this->once())
               ->method('includeModuleRoutes')
               ->with($this->equalTo('module_routers.php'));

        $route_data = $router->match($routes);
    }

    public function testMatchModule()
    {
        $routes = array();
        $routes['MODULE']['/module'] = 'module_routers.php';

        $module_routes = array();
        $module_routes['GET']['/erro'] = 'module_erro';
        $module_routes['GET']['/'] = 'module';

        $request = $this->FactoryRequest('GET', '/module');

        $router = $this->getMock('Drumon\Router',
            array('includeModuleRoutes'),
            array($request->getMethod(), $request->getPath())
        );

        $router->expects($this->once())
                     ->method('includeModuleRoutes')
                     ->will($this->returnValue($module_routes));

        $route_data = $router->match($routes);

        $this->assertEquals('module', $route_data);
    }

    public function testMatchModuleTwoModules()
    {
        $routes = array();
        $routes['MODULE']['/module'] = 'module_routers.php';
        $routes['MODULE']['/module_other'] = 'module_other_routers.php';
        $routes['MODULE']['/module_other_2'] = 'module_other_2_routers.php';

        $module_routes = array();
        $module_routes['GET']['/'] = 'module';

        $request = $this->FactoryRequest('GET', '/module_other');

        $router = $this->getMock('Drumon\Router',
            array('includeModuleRoutes'),
            array($request->getMethod(), $request->getPath())
        );

        $router->expects($this->once())
               ->method('includeModuleRoutes')
               ->with($this->equalTo('module_other_routers.php'))
               ->will($this->returnValue($module_routes));

        $route_data = $router->match($routes);

        $this->assertEquals('module', $route_data);
    }
}
