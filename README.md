# Cathedral Builder

Zend framework 2 database layer generator

## Requierments

### Module & Directories

The namespace passed to a manger needs to be an existing module.

It also needs to have the directories Entity and Model in the
src/{ModuleName}/ directory

These 2 dirs need to be writable by your web server

E.G.

    $buildManager = new BuilderManager(‘DBLayer');

Will try create the entities and models in:

module/DBLayer/src/DBLayer/Entity

module/DBLayer/src/DBLayer/Model

### Global Adapter

Builder uses the Global Adapter feature

Simplest way to get set it (i’m my opinion) is to modify the Module.php
in the module where the Data object will be created.

module/DBLayer/Module.php

    public function onBootstrap(MvcEvent $e) {
    ...
      $adapter = $e->getApplication()
        ->getServiceManager()
        ->get('Zend\Db\Adapter\Adapter');

      \Zend\Db\TableGateway\Feature\GlobalAdapterFeature::setStaticAdapter($adapter);
    ...
    }

A make sure that this Module is before any other in the list the use the
DBLayer.

## Resources
