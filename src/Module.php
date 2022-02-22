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
declare(strict_types=1);

namespace Cathedral\Builder;

use Cathedral\Builder\Config\BuilderConfigAwareInterface;
use Laminas\Console\Adapter\AdapterInterface as Console;

use function array_keys;

use Laminas\ModuleManager\Feature\{
	ConsoleBannerProviderInterface,
	ConsoleUsageProviderInterface
};

/**
 * Module loader for Cathedral Builder
 *
 * @package Cathedral\Builder
 *
 * @version 1.0.0
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
			'Table Information',
			'tables [<filter>]' => 'lists tables and if their files are outdated or missing.',
			[
				'filter','simple text match, if table name contains filter it is listed'
			],
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
					if ($instance instanceof BuilderConfigAwareInterface) {
						$config = $sm->get('Config');

						try {
							\Laminas\Db\TableGateway\Feature\GlobalAdapterFeature::getStaticAdapter();
						} catch (\Throwable $th) {
							\Laminas\Db\TableGateway\Feature\GlobalAdapterFeature::setStaticAdapter($sm->get('Laminas\Db\Adapter\Adapter'));
						}

						$instance->setBuilderConfig($config['cathedral']['builder']);
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
					if ($instance instanceof BuilderConfigAwareInterface) {
						$moduleManager = $container->get('ModuleManager');
						$config = $container->get('Config');

						$loadedModules = array_keys($moduleManager->getLoadedModules());
						$config['cathedral']['builder']['modules'] = $loadedModules;
						$instance->setBuilderConfig($config['cathedral']['builder']);
					}
				}
			]
		];
	}
}
