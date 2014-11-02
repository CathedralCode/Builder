<?php
namespace Cathedral\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\EventManager\EventManagerInterface;
use Zend\Console\Request;
use Cathedral\Builder\BuilderManager;
use Cathedral\Builder\NameManager;

class BuilderToolController extends AbstractActionController {
	
	private $dataNamespace = 'Application';
	private $entitysingular = true;
	private $singularignore = false;
	
	private $_namemanager = null;

	public function setEventManager(EventManagerInterface $events) {
		$config = $this->getServiceLocator()->get('Config')['builderui'];
		$manager = $this->getServiceLocator()->get('ModuleManager');
	
		$modules = $manager->getLoadedModules();
		if ($modules[$config['namespace']]) {
			$this->dataNamespace = $config['namespace'];
		}
		if ($config['entitysingular']) {
			$this->entitysingular = $config['entitysingular'];
		}
		if ($this->entitysingular) {
			if ($config['singularignore']) {
				$this->singularignore = $config['singularignore'];
			}
		}
	
		parent::setEventManager($events);
	}
	
	/**
	 * Creates and returns a NameManager
	 *
	 * @return \Cathedral\Builder\NameManager
	 */
	private function getNameManager() {
		if (!$this->_namemanager) {
			$nm = new NameManager($this->dataNamespace);
			if (!$this->entitysingular) {
				$nm->entitySingular(false);
			} else {
				$nm->setEntitySingularIgnores($this->singularignore);
			}
			$this->_namemanager = $nm;
		}
		return $this->_namemanager;
	}
	
	private function getConsoleRequest() {
		$request = $this->getRequest();
		if (!$request instanceof Request){
			throw new \RuntimeException('You can only use this action from a console!');
		}
		return $request;
	}
	
    public function tableListAction() {
    	$request = $this->getConsoleRequest();
    	
    	$status=[-1 => 'None', 0 => 'Outdated', 1 => 'Ok'];
    	$bm = new BuilderManager($this->getNameManager());
    	
    	$body = '';
    	while ($bm->nextTable()) {
    		$body .= $bm->getTableName()."\n";
    		$body .= "\tDataTable     :".$status[$bm->existsDataTable()]."\n";
    		$body .= "\tEntityAbstract:".$status[$bm->existsEntityAbstract()]."\n";
    		$body .= "\tEntity        :".$status[$bm->existsEntity()]."\n";
    	}
    	
    	$response = <<<MBODY
Listing of tables
$body
MBODY;
    	return "$response\n";
    }
    
    public function buildAction() {
    	$request = $this->getConsoleRequest();
    	
    	$types = ['datatable' => 'DataTable', 'abstract' => 'EntityAbstract', 'entity' => 'Entity'];
    	
    	$class = $request->getParam('class');
    	$table = $request->getParam('table');
    	$write = $request->getParam('write') || $request->getParam('w');
    	
    	$type = $types[$class];
    	$getFunc = "get{$type}Code";
		$writeFunc = "write{$type}";
    	
    	$body = "#Generating $type for $table\n";
    	
    	if ($table == 'ALL') {
    		$bm = new BuilderManager($this->getNameManager());
    		
    		while ($bm->nextTable()) {
    			if ($bm->$writeFunc(true)) {
    				$body .= "\tWritten {$bm->getTableName()}\n";
    			}
    		}
    	} else {
    		$bm = new BuilderManager($this->getNameManager(), $table);
    		$code = $bm->$getFunc();
    		
    		if ($write) {
    			if ($bm->$writeFunc(true)) {
    				$body .= "\tWritten to file\n";
    			}
    		} else {
    			$body .= $code;
    		}
    	}
    	return "$body\n";
    }
    		
}

