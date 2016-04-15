<?php
namespace Cathedral;

use Zend\ModuleManager\Feature\ConsoleBannerProviderInterface;
use Zend\ModuleManager\Feature\ConsoleUsageProviderInterface;
use Zend\Console\Adapter\AdapterInterface as Console;
use Cathedral\Config\ConfigAwareInterface;

class Module implements ConsoleBannerProviderInterface, ConsoleUsageProviderInterface {

	/**
	 * This method is defined in ConsoleBannerProviderInterface
	 */
	public function getConsoleBanner(Console $console) {
		$version = \Cathedral\Builder\Version::VERSION;
		$version_date = \Cathedral\Builder\Version::VERSION_DATE;
		
		return "Cathedral/BuilderCLI $version ($version_date)";
	}

	/**
	 * This method is defined in ConsoleUsageProviderInterface
	 */
	public function getConsoleUsage(Console $console) {
		return [
			'Table information',
			'table list' => 'list all tables',
			'Class generation',
			'build (datatable|abstract|entity|ALL):class <table|ALL> [--write|-w]' => 'Print or (-w )write class(es) file(s) for table(s)',
			[
				'class',
				'file to generate. ALL generates all'],
			[
				'<table>',
				'table used for generation. Use ALL for all tables'],
			[
				'--write|-w',
				'Write file to module, Otherwise use > path/to/file.php. If ALL used look for //TODO:NEWCLASS']];
	}

	public function getConfig() {
		return include __DIR__ . '/config/module.config.php';
	}

	public function getAutoloaderConfig() {
		return array(
			'Zend\Loader\ClassMapAutoloader' => array(
				__DIR__ . '/autoload_classmap.php'),
			'Zend\Loader\StandardAutoloader' => array(
				'namespaces' => array(
					__NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__)));
	}

	public function getServiceConfig() {
		return array(
			'initializers' => array(
				function ($instance, $sm) {
					if ($instance instanceof ConfigAwareInterface) {
						$config = $sm->get('Config');
						$instance->setConfig($config['builderui']);
					}
				}));
	}

	public function getControllerConfig() {
		return array(
			'initializers' => array(
				function ($instance, $sm) {
					if ($instance instanceof ConfigAwareInterface) {
						$locator = $sm->getServiceLocator();
						$config = $locator->get('Config');
						$instance->setConfig($config['builderui']);
					}
				}));
	}
}
