# Cathedral Builder

Zend framework 2 database layer generator

Creates classes based on:

<http://framework.zend.com/manual/2.3/en/user-guide/database-and-models.html>

## Requirements

-   PHP \>= 5.3.3

-   [Zend Framework 2][] (latest master)

## Intalling

I’m sure most of you can do this, but those that need a little help.

#### With composer

1.  Add this project in your composer.json:

        "require": {
            "cathedral/builder": "dev-master"
        }

2.  Now tell composer to update by running the command:

        $ php composer.phar update

## Basic Usage

### Single Table

Figure out what module will house your db code e.g. DBLayer

create the Entity & Model namespace dirs
(module/DBLayer/src/DBLayer/{Entity|Model}

User BuilderManager:

    use Cathedral\Builder\BuilderManager;

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
    $buildManager->writeDataTable();
    $buildManager->writeEntityAbstract();
    $buildManager->writeEntity();

Thats it for the table :)

### Loop through Tables

Handy for updating classes to new version etc… And for many tables a lot
less painfull then 3 lines of code per tables :)

User BuilderManager:

    use Cathedral\Builder\BuilderManager;

Create a BuilderManager NO table specified:

    $buildManager = new BuilderManager('DBLayer');

Write while loop overwriting current DataTable And EntityAbstract, only
create Entity if not found:

    while ($bm->nextTable()) {
        $buildManager->writeDataTable();
        $buildManager->writeEntityAbstract();
        $buildManager->writeEntity();
    }

Thats it for all tables :)

## Features/Conventions (Assumptions)

### EntitySingular

If a table name ends in an s, I assume its a plural.

So the DataTable will keep the s, but the Entity will drop it.

E.g.

    Table users
    DataTable: UsersTable
    Ebtity: User

### Relations

If a field name uses the format fk\_{table}, I’ll assume it stores the
primary key of table {table}.

Class for table containing fk\_{table}:

This will add a new method get{Table} that returns an Entity of type
{Table}.

E.g.: Get the User related to a Group

    Table groups which contains users
    Field groups.fk_users
    Method:$group->getUser()
    Entity: User
    ...

Class for {table}

This will add a new methods get(fk\_{table}’s Table) that returns an
Entites of type (fk\_{table}’s Table).

E.g.: Get all Groups related to a User

    ...
    Method: $user->getGroups()
    Entities: Group

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

Don’t edit this file, your changes will be lost!

### DataTable

Basicaly this is a TableGateway, it does the database lifting and
returns the Entities.

Again, Builder checks the version of this and it will be replaced with
newer versions.

Don’t edit.

## Requirements: Runtime

### Module & Directories (Only if you want to write to file)

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

  [Zend Framework 2]: mailto:code@cathedral.co.za
