<?php
/**
 * This file is part of the Cathedral package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Philip Michael Raab <peep@cathedral.co.za>
 * @package Cathedral\Builder
 *
 * @license MIT
 * @license https://raw.githubusercontent.com/CathedralCode/Builder/develop/LICENSE MIT License
 *
 * @copyright 2013-2014 Philip Michael Raab <peep@cathedral.co.za>
 */

namespace Cathedral\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\EventManager\EventManagerInterface;
use Cathedral\Builder\BuilderManager;
use Cathedral\Builder\NameManager;
use Cathedral\Config\ConfigAwareInterface;

/**
 * BuilderWebController
 * Web UI for Builder
 * @package Cathedral\Builder\Controller\Web
 */
class BuilderWebController extends AbstractActionController implements ConfigAwareInterface {
	
	private $dataNamespace = 'Application';
	private $entitysingular = true;
	private $singularignore = false;
	
	private $_namemanager = null;
	
	protected $config;

	/**
	 * {@inheritDoc}
	 * @see \Cathedral\Config\ConfigAwareInterface::setConfig()
	 */
	public function setConfig($config) {
		$this->config = $config;
	}

	public function setEventManager(EventManagerInterface $events) {
		if (in_array($this->config['namespace'], $this->config['modules']))
			$this->dataNamespace = $this->config['namespace'];
		
		if ($this->config['entitysingular'])
			$this->entitysingular = $this->config['entitysingular'];
		
		if ($this->entitysingular)
			if ($this->configconfig['singularignore'])
				$this->singularignore = $this->configconfig['singularignore'];
		
		parent::setEventManager($events);
		$controller = $this;
		$events->attach('dispatch', function ($e) use ($controller) {
			$controller->layout('layout/cathedral/builder');
		}, 100);
	}

	/**
	 * Creates and returns a NameManager
	 * 
	 * @return \Cathedral\Builder\NameManager
	 */
	private function getNameManager() {
		if (! $this->_namemanager) {
			$nm = new NameManager($this->dataNamespace);
			if (! $this->entitysingular) {
				$nm->entitySingular(false);
			} else {
				$nm->setEntitySingularIgnores($this->singularignore);
			}
			$this->_namemanager = $nm;
		}
		return $this->_namemanager;
	}

	public function indexAction() {
		$bm = new BuilderManager($this->getNameManager());
		return new ViewModel([
			'title' => 'Overview',
			'builderManager' => $bm,
			'namespace' => $this->dataNamespace]);
	}

	public function buildAction() {
		$types = [
			'DataTable',
			'EntityAbstract',
			'Entity'];
		$table = $this->params()->fromRoute('table', null);
		$typeIndex = $this->params()->fromRoute('type', null);
		$write = (bool) $this->params()->fromRoute('write', false);
		
		$type = $types[$typeIndex];
		$getFunc = "get{$type}Code";
		$writeFunc = "write{$type}";
		
		if ($table == '0') {
			$code = '';
			$bm = new BuilderManager($this->getNameManager());
			
			while ( $bm->nextTable() ) {
				$code .= "{$bm->getTableName()}... ";
				if ($bm->$writeFunc(true)) {
					$code .= "Saved\n";
				} else {
					$code .= "Failed\n";
				}
			}
			$table = 'Tables';
		} else {
			$bm = new BuilderManager($this->getNameManager(), $table);
			$code = $bm->$getFunc();
			
			if ($write) {
				if ($bm->$writeFunc(true)) {
					$saved = 'Saved';
				} else {
					$saved = "Error saving file";
				}
			}
		}
		return new ViewModel([
			'title' => 'Code View',
			'table' => $table,
			'saved' => $saved,
			'type' => $type,
			'code' => $code,
			'namespace' => $this->dataNamespace]);
	}

}

