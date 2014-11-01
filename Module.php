<?php
namespace Cathedral;

use Zend\ModuleManager\Feature\ConsoleBannerProviderInterface;
use Zend\ModuleManager\Feature\ConsoleUsageProviderInterface;
use Zend\Console\Adapter\AdapterInterface as Console;

class Module implements ConsoleBannerProviderInterface, ConsoleUsageProviderInterface {
    
    /**
     * This method is defined in ConsoleBannerProviderInterface
     */
    public function getConsoleBanner(Console $console) {
    	return 'BuilderTool 0.0.1';
    }
    
    /**
     * This method is defined in ConsoleUsageProviderInterface
     */
    public function getConsoleUsage(Console $console) {
    	return array(
    		'Listing tables and status of related class files',
    		'show (all|outdated|missing) tables'             => 'Show table information',
    		['all', 'All tables'],
    		['outdated', 'Tables with outdated class files'],
    		['missing', 'Tables without class files'],
    	);
    }
    
    public function getConfig() {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig() {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__.'/src/'.__NAMESPACE__)));
    }
}
