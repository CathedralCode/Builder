<?php
return array(
	'builderui' => array(
		'namespace' => 'Application'),
	'controllers' => array(
		'invokables' => array(
			'Cathedral\Controller\BasicUI' => 'Cathedral\Controller\BasicUIController',
			'Cathedral\Controller\BuilderCLI' => 'Cathedral\Controller\BuilderCLIController',)),
	'router' => array(
		'routes' => array(
			'builder' => array(
				'type' => 'Literal',
				'options' => array(
					'route' => '/builder',
					'defaults' => array(
						'__NAMESPACE__' => 'Cathedral\Controller',
						'controller' => 'BasicUI',
						'action' => 'index')),
				'may_terminate' => true,
				'child_routes' => array(
					'build' => array(
						'type' => 'Segment',
						'options' => array(
						    'route' => '/:table/:type[/:write]',
							'constraints' => array(
								'table' => '[a-zA-Z0][a-zA-Z0-9_-]*',
								'type' => '[0-2]',
								'write' => '[0-1]'),
							'defaults' => array(
								'action' => 'build')))),
			))),
    'console' => array(
    	'router' => array(
    		'routes' => array(
    			'table-list' => array(
    				'options' => [
    					'route'    => 'table list',
    					'defaults' => [
    						'__NAMESPACE__' => 'Cathedral\Controller',
    						'controller' => 'BuilderCLI',
    						'action' => 'table-list'
    					]
    				]
    			),
    			'build' => array(
    				'options' => [
    					'route'    => 'build (datatable|abstract|entity):class <table> [--write|-w]',
    					'defaults' => [
    						'__NAMESPACE__' => 'Cathedral\Controller',
    						'controller' => 'BuilderCLI',
    						'action' => 'build'
    					]
    				]
    			),
    		))),
	'view_manager' => array(
		'template_path_stack' => array(
			'Cathedral' => __DIR__ . '/../view')));
//@formatter:off
//@formatter:on
