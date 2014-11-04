<?php

namespace CathedralTest\Controller;

use CathedralTest\Bootstrap;
use Zend\Mvc\Router\Http\TreeRouteStack as HttpRouter;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;
use PHPUnit_Framework_TestCase;
use Zend\Debug\Debug;
use Cathedral\Controller\BasicUIController;

class BasicUIControllerTest extends \PHPUnit_Framework_TestCase {
	
	protected $controller;
	protected $request;
	protected $response;
	protected $routeMatch;
	protected $event;
	
	protected function setUp() {
		$serviceManager = Bootstrap::getServiceManager();
		$this->controller = new BasicUIController();
		$this->request = new Request();
		$this->routeMatch = new RouteMatch(array('controller' => 'BasicUI'));
		$this->event = new MvcEvent();
		$config = $serviceManager->get('Config');
		$routerConfig = isset($config['router']) ? $config['router'] : array();
		$router = HttpRouter::factory($routerConfig);
		
		$this->event->setRouter($router);
		$this->event->setRouteMatch($this->routeMatch);
		$this->controller->setEvent($this->event);
		$this->controller->setServiceLocator($serviceManager);
		
		//$abapter = $serviceManager->get('Zend\Db\Adapter\Adapter');
	}
	
	public function testIndexActionCanBeAccessed() {
	    $this->routeMatch->setParam('action', 'index');
	
	    $result   = $this->controller->dispatch($this->request);
	    $response = $this->controller->getResponse();
	
	    $this->assertEquals(200, $response->getStatusCode());
	}
}