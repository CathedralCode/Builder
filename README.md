# Cathedral Builder

Zend framework 2 database layer generator

## Basic Usage

Figure out what module will house your db code e.g. DBLayer

create the Entity & Model namespace dirs
(module/DBLayer/src/DBLayer/{Entity|Model}

Create a BuilderManager:

    $buildManager = new BuilderManager('DBLayer', ’mytable');

If you don’t leave off the table argument you can use the nextTable
method to loop through all the tables. Handy for batch runs. And
probably the most common use.

With either a table specified or loaded via nextTable, write the files
to disk or display to screen.

    # Echo to screen
    echo $buildManager->getDataTableCode();
    echo $buildManager->getEntityAbstractCode();
    echo $buildManager->getEntityCode();

    # Write to file
    echo $buildManager->writeDataTable();
    echo $buildManager->writeEntityAbstract();
    echo $buildManager->writeEntity();

Thats it :)

They based on:

<http://framework.zend.com/manual/2.3/en/user-guide/database-and-models.html>

With one little difference, the Entity file is an empty class that
inherits from and abstract class.

You can make what ever changes you want in the Entity file for custom
stuff.

If the table changes and new files are generated only the Model and
abstract class get replaced.

The Entity does not get touched, leaving your customisations A-Ok.

## Requirements

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
