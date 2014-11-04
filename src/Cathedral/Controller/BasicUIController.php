<?php

namespace Cathedral\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\EventManager\EventManagerInterface;
use Cathedral\Builder\BuilderManager;
use Cathedral\Builder\NameManager;
use Dossier\Entity\Setting;

class BasicUIController extends AbstractActionController {
	
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
    
    public function indexAction() {
    	$setting = new Setting();
    	$setting->get('version');
    	
    	$bm = new BuilderManager($this->getNameManager());
        return new ViewModel(['title' => 'Overview', 'builderManager' => $bm, 'namespace' => $this->dataNamespace]);
    }

    public function buildAction() {
		$types = ['DataTable', 'EntityAbstract', 'Entity'];
		$table = $this->params()->fromRoute('table', null);
		$typeIndex = $this->params()->fromRoute('type', null);
		$write = (bool)$this->params()->fromRoute('write', false);
		
		$type = $types[$typeIndex];
		$getFunc = "get{$type}Code";
		$writeFunc = "write{$type}";
		
		if ($table == '0') {
			$code = '';
			$bm = new BuilderManager($this->getNameManager());
	
			while ($bm->nextTable()) {
				if ($bm->$writeFunc(true)) {
					$code .= "{$bm->getTableName()}\n";
				}
				$table = 'Tables';
			}
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
		return new ViewModel(['title' => 'Code View', 'table' => $table, 'saved' => $saved, 'type' => $type, 'code' => $code, 'namespace' => $this->dataNamespace]);
	}


}

