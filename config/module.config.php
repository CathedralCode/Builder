<?php
namespace Cathedral;

use Laminas\ServiceManager\Factory\InvokableFactory;

return [
    'builderui' => [
        'namespace' => 'Application',
        'entitysingular' => true,
        'singularignore' => false
    ],
    'controllers' => [
        'invokables' => [
            'Cathedral\Controller\BuilderCLI' => Controller\BuilderCLIController::class,
            'Cathedral\Controller\BuilderRest' => Controller\BuilderRestController::class,
            'Cathedral\Controller\BuilderWeb' => Controller\BuilderWebController::class
        ],
        'factories' => [
            Controller\BuilderWebController::class => \Cathedral\Controller\ControllerFactory::class
        ],
        'aliases' => [
            'Cathedral\Controller\Index' => \Cathedral\Controller\BuilderWebController::class
        ]
    ],
    'router' => [
        'routes' => [
            'builder' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/builder',
                    'defaults' => [
                        'controller' => Controller\BuilderWebController::class,
                        'action' => 'index'
                    ]
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'build' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/:table/:type[/:write]',
                            'constraints' => [
                                'table' => '[a-zA-Z0][a-zA-Z0-9_-]*',
                                'type' => '[0-2]',
                                'write' => '[0-1]'
                            ],
                            'defaults' => [
                                'action' => 'build'
                            ]
                        ]
                    ]
                ]
            ],
            'builderrest' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/builder/rest[/[:table[/[:id]]]]',
                    'constraints' => [
                        'id' => '[a-zA-Z0-9_-]*',
                        'table' => '[a-zA-Z][a-zA-Z0-9_-]*'
                    ],
                    'defaults' => [
                        '__NAMESPACE__' => 'Cathedral\Controller',
                        'controller' => 'BuilderRest'
                    ]
                ]
            ]
        ]
    ],
    'console' => [
        'router' => [
            'routes' => [
                'table-list' => [
                    'options' => [
                        'route' => 'table list',
                        'defaults' => [
                            '__NAMESPACE__' => 'Cathedral\Controller',
                            'controller' => 'BuilderCLI',
                            'action' => 'table-list'
                        ]
                    ]
                ],
                'build' => [
                    'options' => [
                        'route' => 'build (datatable|abstract|entity|ALL):class <table> [--write|-w]',
                        'defaults' => [
                            '__NAMESPACE__' => 'Cathedral\Controller',
                            'controller' => 'BuilderCLI',
                            'action' => 'build'
                        ]
                    ]
                ]
            ]
        ]
    ],
    'view_manager' => [
        'template_map' => include __DIR__ . '/../template_map.php',
        'template_path_stack' => [
            'Cathedral' => __DIR__ . '/../view'
        ],
        'strategies' => [
            'ViewJsonStrategy'
        ]
    ]
];
//@formatter:off
//@formatter:on
