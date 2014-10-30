<?php
return array(
    'builderui' => array(
        'namespace' => 'Application'
    ),
    'controllers' => array(
        'invokables' => array(
            'Cathedral\Controller\BasicUI' => 'Cathedral\Controller\BasicUIController',
        ),
    ),
    'router' => array(
        'routes' => array(
            'builder' => array(
                'type'    => 'Literal',
                'options' => array(
                    // Change this to something specific to your module
                    'route'    => '/builder',
                    'defaults' => array(
                        // Change this value to reflect the namespace in which
                        // the controllers for your module are found
                        '__NAMESPACE__' => 'Cathedral\Controller',
                        'controller'    => 'BasicUI',
                        'action'        => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    // This route is a sane default when developing a module;
                    // as you solidify the routes for your module, however,
                    // you may want to remove it and replace it with more
                    // specific routes.
                    'default' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            //'route'    => '/[:controller[/:action]]',
                            'route'    => '/[:action]',
                            'constraints' => array(
                                //'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'controller'    => 'BasicUI',
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                            'defaults' => array(
                                'action' => 'index'
                            ),
                        ),
                        'may_terminate' => true,
                        'child_routes' => array(
                            'builder' => array(
                                'type' => 'Segment',
                                'options' => array(
                                    'route' => '/[:table[/:type[/:write]]]',
                                    'constraints' => array(
                                        'table' => '[a-zA-Z0][a-zA-Z0-9_-]*',
                                        'type' => '[0-2]',
                                        'write' => '[0-1]'),
                                    'defaults' => array(
                                        'table' => '',
                                        'type' => '',
                                        'write' => '0'
                                    )
                                )
                            )
                        ),
                    ),
                ),
            ),
        ),
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            'Cathedral' => __DIR__ . '/../view',
        ),
    ),
);
