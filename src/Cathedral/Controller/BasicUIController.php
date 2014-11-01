<?php

namespace Cathedral\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\EventManager\EventManagerInterface;
use Cathedral\Builder\BuilderManager;

class BasicUIController extends AbstractActionController {

    private $dataNamespace = 'Application';
    
    const VERSION = '0.1.0';
	
	/**
	 * Date of the release
	 */
	const VERSION_DATE = '2014 Oct 21';
    
    public function setEventManager(EventManagerInterface $events) {
        $config = $this->getServiceLocator()->get('Config')['builderui'];
        $manager = $this->getServiceLocator()->get('ModuleManager');
        
        $modules = $manager->getLoadedModules();
        if ($modules[$config['namespace']]) {
            $this->dataNamespace = $config['namespace'];
        }
        
        parent::setEventManager($events);
        $controller = $this;
        $events->attach('dispatch', function ($e) use ($controller) {
            $controller->layout('layout/cathedral/builder');
        }, 100);
    }
    
    public function indexAction() {
        $bm = new BuilderManager($this->dataNamespace);
        
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
			$bm = new BuilderManager($this->dataNamespace);
	
			while ($bm->nextTable()) {
				if ($bm->$writeFunc(true)) {
					$code .= "{$bm->getTableName()}\n";
				}
				$table = 'Tables';
			}
		} else {
			$bm = new BuilderManager($this->dataNamespace, $table);
			$code = $bm->$getFunc();
			
			if ($write) {
				if ($bm->$writeFunc(true)) {
					$saved = ':written';
				}
			}
			$canSave = true;
		}
		return new ViewModel(['title' => "Generator:{$table}:{$type}{$saved}", 'type' => $type, 'code' => $code, 'canSave' => $canSave, 'namespace' => $this->dataNamespace]);
	}


}

