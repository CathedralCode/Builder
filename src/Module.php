<?php

/**
 * This file is part of the Cathedral package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * PHP Version 8
 *
 * @author Philip Michael Raab <peep@inane.co.za>
 * @package Cathedral\Builder
 *
 * @license MIT
 * @license https://raw.githubusercontent.com/CathedralCode/Builder/develop/LICENSE MIT License
 *
 * @copyright 2013-2022 Philip Michael Raab <peep@inane.co.za>
 */
declare(strict_types=1);

namespace Cathedral\Builder;

use Cathedral\Builder\Config\BuilderConfigAwareInterface;

use function array_keys;

/**
 * Module loader for Cathedral Builder
 *
 * @package Cathedral\Builder
 *
 * @version 1.2.0
 */
class Module {
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
