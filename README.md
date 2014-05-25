# Cathedral Builder

Zend framework 2 database layer generator

Creates classes based on:

<http://framework.zend.com/manual/2.3/en/user-guide/database-and-models.html>

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

## The Generated Files

### Entity

This files is created for you to add any custom stuff you may want for
that table.

On a users table it might be a function that formats the full name to
some crazy standard.

So this file is **NEVER** replaced by the builder.

So use it for what ever you need and rest assured the code will not
disappear.

### EntityAbstract

This is the basic Entity file.

If newer version of Builder may replace this with fixes/features/etc

Don’t edit this file, you changes will be lost!

### ModelTable

Basicaly this is a TableGateway, it this the database lifting and
returns the Entities.

Again, Builder checks the version of this and it will be reokaced for
newer versions.

Don’t edit.

### Class Name Conventions

If a table name ends in an s, I assume its a plural.

So the model will keep the s, but the Entity will drop it.

E.g.

    Table users
    Model = UsersTable
    Ebtity = User

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

## Feedback

Hey, got any ideas or suggestions to help improve this generator let me.

Email me <code@cathedral.co.za>
