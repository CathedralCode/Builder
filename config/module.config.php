<?php
namespace Cathedral\Builder;

use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;

return [
    'builderui' => [
        'namespace' => 'Application',
        'entitysingular' => true,
        'singularignore' => false
    ],
    'controllers' => [
        'factories' => [
            Controller\BuilderWebController::class => InvokableFactory::class,
            Controller\BuilderRestController::class => InvokableFactory::class,
            Controller\BuilderCLIController::class => InvokableFactory::class,
        ],
        'aliases' => [
            'Cathedral\Controller\Index' => \Cathedral\Builder\Controller\BuilderWebController::class
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
                            'controller' => Controller\BuilderCLIController::class,
                            'action' => 'table-list'
                        ]
                    ]
                ],
                'build' => [
                    'options' => [
                        'route' => 'build [datatable|abstract|entity|ALL]:class [<table>] [--write|-w]',
                        'defaults' => [
                            'controller' => Controller\BuilderCLIController::class,
                            'action' => 'build',
                            'class' => 'ALL',
                            'table' => 'ALL'
                        ]
                    ]
                ]
            ]
        ]
    ],
    'view_manager' => [
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
