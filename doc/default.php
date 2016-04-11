<?php
use Sami\Sami;
use Sami\RemoteRepository\GitHubRemoteRepository;
use Symfony\Component\Finder\Finder;

include "config.php";

$options['title'] = 'InaneClasses (Default)';

return new Sami($iterator, $options);
