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
    
    /*
     // First we create a NameManger
     $nm = new NameManager('Dossier');
    
     // EntitySingular is enabled by default
     // To check the status use:
     if ($nm->entitySingular()) {
     // If enabled
     // To disable it:
     $nm->entitySingular(false);
     } else {
     // If disabled
     // To disable it:
     $nm->entitySingular(true);
     }
    
     // Lets keep it enabled
     $nm->entitySingular(true);
    
     // But lets tell it that a few tables ending in 's' should be ignored
     // To reset the ignore list pass FALSE
     $nm->setEntitySingularIgnores(false);
    
     // Now lets add our ignore tables
     // adding cities
     $nm->setEntitySingularIgnores('cities');
     // you can add them as an array or | delimited string as well
     $nm->setEntitySingularIgnores('cities|smartees');
     // OR
     $nm->setEntitySingularIgnores(array('cities','smartees'));
    
     // Righty let var_dump and we should have array('cities','smartees')
     var_dump($nm->getEntitySingularIgnores());
    
     die();
     */
    
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

