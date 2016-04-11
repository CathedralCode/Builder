<?php
use Sami\Sami;
use Symfony\Component\Finder\Finder;

$iterator = Finder::create()
    ->files()
    ->name('*.php')
    ->exclude('Resources')
    ->exclude('Tests')
    ->in('../src')
;

$options = [
	'theme'                => 'default',
    'title'                => 'API Docs',
    'build_dir'            => __DIR__.'/build/default',
    'cache_dir'            => __DIR__.'/cache/default',
    'default_opened_level' => 2,
    'template_dirs'        => ['/Users/philip/Servers/Web/Sami/themes/'],
];
