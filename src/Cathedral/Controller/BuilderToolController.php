<?php
namespace Cathedral\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Console\Request;

class BuilderToolController extends AbstractActionController {

    public function indexAction() {
        $request = $this->getRequest();
        
        if (!$request instanceof Request){
        	throw new \RuntimeException('You can only use this action from a console!');
        }
        
        return "Done!\n";
    }
    
    public function showTablesAction() {
    	$request = $this->getRequest();
    	if (!$request instanceof Request){
    		throw new \RuntimeException('You can only use this action from a console!');
    	}
    	
    	// Check verbose flag
    	$verbose = $request->getParam('verbose') || $request->getParam('v');
    	
    	// Check mode
    	$mode = $request->getParam('mode', 'all'); // defaults to 'all'
    	
    	return "Showing $mode tables\n";
    }
    		
}

