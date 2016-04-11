<?php
/**
 * Create Inane Documentation
 * sami.phar update inane.php --force
 */

use Sami\Sami;
use Sami\RemoteRepository\GitHubRemoteRepository;
use Symfony\Component\Finder\Finder;

include "config.php";

$options['theme'] = 'inane';
$options['title'] = 'InaneClasses (Inane)';
$options['build_dir'] = __DIR__.'/build/inane';
$options['cache_dir'] = __DIR__.'/cache/inane';

return new Sami($iterator, $options);
