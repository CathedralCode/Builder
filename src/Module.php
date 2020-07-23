<?php
/**
 * This file is part of the Cathedral package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Philip Michael Raab <peep@inane.co.za>
 * @package Cathedral\Builder
 *
 * @license MIT
 * @license https://raw.githubusercontent.com/CathedralCode/Builder/develop/LICENSE MIT License
 *
 * @copyright 2013-2019 Philip Michael Raab <peep@inane.co.za>
 */
namespace Cathedral;

use Laminas\ModuleManager\Feature\ConsoleBannerProviderInterface;
use Laminas\ModuleManager\Feature\ConsoleUsageProviderInterface;
use Laminas\Console\Adapter\AdapterInterface as Console;
use Cathedral\Config\ConfigAwareInterface;

/**
 * Module loader for Cathedral Builder
 *
 * @package Cathedral\Builder
 */
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
			'build [datatable|abstract|entity|ALL] [table|ALL] [--write|-w]' => 'Print or (-w )write class(es) file(s) for table(s)',
			[
				'class',
				'file to generate. ALL generates all'
			],
			[
				'<table>',
				'table used for generation. Use ALL for all tables'
			],
			[
				'--write|-w',
				'Write file to module, Otherwise use > path/to/file.php. If ALL used look for //MARK:NEWCLASS'
			]
		];
	}

    /**
     * get config
     *
     * @return void
     */
	public function getConfig() {
		return include __DIR__ . '/../config/module.config.php';
	}

    /**
     * get autoloader config
     *
     * @return void
     */
	public function getAutoloaderConfig() {
		return [
			'Laminas\Loader\StandardAutoloader' => [
				'namespaces' => [
					__NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__
				]
			]
		];
	}

    /**
     * get service config
     *
     * @return void
     */
	public function getServiceConfig() {
		return [
			'initializers' => [
				function ($sm, $instance) {
					if ($instance instanceof ConfigAwareInterface) {
						$config = $sm->get('Config');
						$instance->setConfig($config['builderui']);
					}
				}
			]
		];
	}

    /**
     * get controller config
     *
     * @return void
     */
	public function getControllerConfig() {
		return [
			'initializers' => [
				function ($container, $instance) {
					if ($instance instanceof ConfigAwareInterface) {
						$moduleManager = $container->get('ModuleManager');
						$config = $container->get('Config');

						$loadedModules = array_keys($moduleManager->getLoadedModules());
						$config['builderui']['modules'] = $loadedModules;
						$instance->setConfig($config['builderui']);
					}
				}
			]
		];
	}
}
