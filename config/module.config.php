<?php
namespace Cathedral;

return array(
	'builderui' => array(
		'namespace' => 'Application',
		'entitysingular' => true,
		'singularignore' => false),
	'controllers' => array(
		'invokables' => array(
			'Cathedral\Controller\BuilderCLI' => Controller\BuilderCLIController::class,
			'Cathedral\Controller\BuilderRest' => Controller\BuilderRestController::class,
			'Cathedral\Controller\BuilderWeb' => Controller\BuilderWebController::class)),
	'router' => array(
		'routes' => array(
			'builder' => array(
				'type' => 'Literal',
				'options' => array(
					'route' => '/builder',
					'defaults' => array(
						'__NAMESPACE__' => 'Cathedral\Controller',
						'controller' => 'BuilderWeb',
						'action' => 'index')),
				'may_terminate' => true,
				'child_routes' => [
					'build' => array(
						'type' => 'Segment',
						'options' => [
							'route' => '/:table/:type[/:write]',
							'constraints' => array(
								'table' => '[a-zA-Z0][a-zA-Z0-9_-]*',
								'type' => '[0-2]',
								'write' => '[0-1]'),
							'defaults' => array(
								'action' => 'build')])]),
			'builderrest' => [
				'type' => 'Segment',
				'options' => [
					'route' => '/builder/rest[/[:table[/[:id]]]]',
					'constraints' => [
						'id' => '[a-zA-Z0-9_-]*',
						'table' => '[a-zA-Z][a-zA-Z0-9_-]*'],
					'defaults' => [
						'__NAMESPACE__' => 'Cathedral\Controller',
						'controller' => 'BuilderRest']]])),
	'console' => array(
		'router' => array(
			'routes' => array(
				'table-list' => array(
					'options' => [
						'route' => 'table list',
						'defaults' => [
							'__NAMESPACE__' => 'Cathedral\Controller',
							'controller' => 'BuilderCLI',
							'action' => 'table-list']]),
				'build' => array(
					'options' => [
						'route' => 'build (datatable|abstract|entity|ALL):class <table> [--write|-w]',
						'defaults' => [
							'__NAMESPACE__' => 'Cathedral\Controller',
							'controller' => 'BuilderCLI',
							'action' => 'build']])))),
	'view_manager' => array(
		'template_map' => include __DIR__ . '/../template_map.php',
		'template_path_stack' => array(
			'Cathedral' => __DIR__ . '/../view'),
		'strategies' => array(
			'ViewJsonStrategy')));
//@formatter:off
//@formatter:on
