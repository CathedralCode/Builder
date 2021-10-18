# Cathedral Builder

CURRENTLY SPLITTING

Laminas 3 database layer builder with a simple Web & Console UI with many great features.

For a quick list of fixes and changes see [CHANGELOG.md](CHANGELOG.md)

Creates classes based on:

<http://framework.zend.com/manual/current/en/user-guide/database-and-models.html>

## Requirements

- PHP \>= 8
- Laminas 3 (latest master)

## Installing

I’m sure most of you can do this, but those that need a little help.

### With composer

Command line:

```shell
composer require --dev cathedral/builder
```

OR edit composer.json manually:

Add builder to composer.json require:  

```json
    "require-dev": {
        "cathedral/builder": "dev-master"
    }
```

Then update composer:  

```
    $ php composer.phar update
```

### Post installation (Optional)

Enabling Builder GUI in your `development.config.php` file:

```php
    return [
        'modules' => [
            // ...
            'Cathedral\Builder',
        ],
        // ...
    ];
```

Builder GUI has some options to allow you to quickly customise the basic functionality. After Enabling Builder GUI:

1. copy `./vendor/cathedral/builder/config/cathedral-builder.global.php.dist` to `./config/autoload/cathedral-builder.global.php.php`
2. change settings as desired.
    - **namespace** - Module where files will be created and the namespace of the
    created files. Default is `Application`.
    - **entity_singular** - On/Off switch for this feature.
    - **singular_ignore** - A | (pipe) delimited list of tables to ignore for EntitySingular.

## Build Your Data Layer

Builder is only used to generate the classes, after that the classes are only dependent on laminas, so no need to have builder on your production machine as a dependency.

### First things first

* Make sure any custom module is 100% functional before running builder or it will revert back to Application.
* Using WebUI: check web user has write access to modules `src/{Model,Entity}` folders
* Using Console: check you have write access to `src/{Model,Entity}` folders

### WebUI

`Open http://yoursite/builder`

If you want builder to save files to disk the directories for Namespace/Entity
and Namespace/Model must be writeable by php.  
And enjoy.

### Console

And just for kicks there is even console support.  
The console UI uses the same config as the Web UI.  
In the root of your project run `php index.php` and see the Cathedral options:

Get info with: `php index.php table list`

```
    Listing of tables
    basics
        DataTable     :Ok
        EntityAbstract:Outdated
        Entity        :None
```

Generate with `build (datatable|abstract|entity|ALL) <table|ALL> [--write|-w]`  
You can redirect to a file ` > path/to/file.php`  
Or simple use the -w option and builder does it for you.

just use `build ALL ALL -w`
and everything's done.

#### Quick console tips

* `builder build -w` # this creates all the files for all the tables
* `builder build ALL logs -w` # build all files for logs table
* `builder build datatable ALL -w` # builds the datatable file for all tables

### Custom Builder

Use builder in your own way.

#### Single Table

Figure out what module will house your db code e.g. DBLayer

create the Entity & Model namespace dirs
(module/DBLayer/src/DBLayer/{Entity|Model}

Use BuilderManager:

    use Cathedral\Builder\BuilderManager;

Create a BuilderManager:

    $buildManager = new BuilderManager('DBLayer', ’mytable');

If you don’t leave off the table argument you can use the nextTable method to
loop through all the tables. Handy for batch runs. And probably the most common
use.

With either a table specified or loaded via nextTable, write the files to disk
or display to screen.

```php
    //Echo to screen
    echo $buildManager->getDataTableCode();
    echo $buildManager->getEntityAbstractCode();
    echo $buildManager->getEntityCode();

    //Write to file
    $buildManager->writeDataTable();
    $buildManager->writeEntityAbstract();
    $buildManager->writeEntity();
```

That's it for the table :)

#### Loop through Tables

Handy for updating classes to new version etc… And for many tables a lot less
painful then 3 lines of code per tables :)

Use BuilderManager:

    use Cathedral\Builder\BuilderManager;

Create a BuilderManager NO table specified:

    $buildManager = new BuilderManager('DBLayer');

Write while loop overwriting current DataTable And EntityAbstract, only create
Entity if not found:

    while ($buildManager->nextTable()) {
        $buildManager->writeDataTable();
        $buildManager->writeEntityAbstract();
        $buildManager->writeEntity();
    }

That's it for all tables :)

### Restful

Builder now has some a simple Restful interface to tables.

Supported so far:

- getList (List tables & list rows in table)
- get (individual row from table)

To get a list of tables use:

    get http://yoursite/builder/rest

result:

    {
        "code": 401,
        "message": "Tabels",
        "data": [
            "cities",
            "countries",
            "currencies",
            "settings",
            "users"
        ]
    }

To list rows in table showing primary key field and value

    get http://yoursite/builder/rest/settings

result:

    {
        "code": 0,
        "message": "SettingsTable List",
        "data": [
            {
                "name": "currency"
            },
            {
                "name": "db_version"
            }
        ]
    }

List a row:

    get http://yoursite/builder/rest/settings/db_version

result:

    {
        "code": 0,
        "message": "Setting",
        "data": {
            "name": "db_version",
            "value": "1",
            "created": "2014-11-08 05:28:31",
            "modified": null
        }
    }

## Features/Conventions (Assumptions)

### EntitySingular

If a table name is plural, builder will try create the entity as the singular
version.

Most **common** plural/singular conventions are supported.

E.g.

    Table countries
    DataTable: CountriesTable
    Entity: Country

    Table catches
    DataTable: CatchesTable
    Entity: Catch

    Table users
    DataTable: UsersTable
    Entity: User

#### Disable

But if you want you can also disable it totally.

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
        // To enable it:
        $nm->entitySingular(true);
    }

    // Lets keep it enabled
    $nm->entitySingular(true);

#### Ignore List

Or add tables to an ignore list to skip a table or two.

    // But lets tell it that a few tables ending in a plural should be ignored
    // To reset the ignore list pass FALSE
    $nm->setEntitySingularIgnores(false);

    // Now lets add our ignore tables
    // adding table1s
    $nm->setEntitySingularIgnores('table1s');

    // you can add them as an array or | (pipe) delimited string as well
    $nm->setEntitySingularIgnores('table1s|table2s');
    // OR
    $nm->setEntitySingularIgnores(['table1s','table2s']);

### Relations

Builder checks the MySQL info tables to relate tables to one another.

To get related records either use the table name in plural for referenced tables or the singular for a referenced table.

E.g.: Get the User related to a Group

    ...
    Table groups which contains users
    Method: $group->User()
    Entity: User
    ...

E.g.: Get all Groups related to a User

    ...
    Method: $user->Groups()
    Entities: Group
    OR
    Method: $user->Groups(['active' => 1])
    Entities: Group that also have active set to 1
    ...

### Events

See Laminas Events: [Laminas-db](https://docs.laminas.dev/laminas-db/table-gateway/)

#### TableGateway LifeCycle Events

When the EventFeature is enabled on the TableGateway instance, you may attach to any of the following events, which provide access to the parameters listed.

* **preInitialize** (no parameters)
* **postInitialize** (no parameters)
* **preSelect**, with the following parameters:
  * *select*, with type Laminas\Db\Sql\Select
* **postSelect**, with the following parameters:
  * *statement*, with type Laminas\Db\Adapter\Driver\StatementInterface
  * *result*, with type Laminas\Db\Adapter\Driver\ResultInterface
  * *resultSet*, with type Laminas\Db\ResultSet\ResultSetInterface
* **preInsert**, with the following parameters:
  * *insert*, with type Laminas\Db\Sql\Insert
* **postInsert**, with the following parameters:
  * *statement* with type Laminas\Db\Adapter\Driver\StatementInterface
  * *result* with type Laminas\Db\Adapter\Driver\ResultInterface
* **preUpdate**, with the following parameters:
  * *update*, with type Laminas\Db\Sql\Update
* **postUpdate**, with the following parameters:
  * *statement*, with type Laminas\Db\Adapter\Driver\StatementInterface
  * *result*, with type Laminas\Db\Adapter\Driver\ResultInterface
* **preDelete**, with the following parameters:
  * *delete*, with type Laminas\Db\Sql\Delete
* **postDelete**, with the following parameters:
  * *statement*, with type Laminas\Db\Adapter\Driver\StatementInterface
  * *result*, with type Laminas\Db\Adapter\Driver\ResultInterface
  
Listeners receive a Laminas\Db\TableGateway\Feature\EventFeature\TableGatewayEvent instance as an argument. Within the listener, you can retrieve a parameter by name from the event using the following syntax:

#### Examples:

A Quick Example:

```php
// Create Settings & User table objects
$settingaTable = new SettingsTable();
$usersTable = new UsersTable();

// Two simple select events to see it in action
$settingaTable->getEventManager()->attach('postSelect', function(TableGatewayEvent $event) {
    Logger::dump($event->getParam('result')->current(), 'TableGatewayEvent::Setting', false);
});

$usersTable->getEventManager()->attach('postSelect', function(TableGatewayEvent $event) {
    Logger::dump($event->getParam('result')->current(), 'TableGatewayEvent::User', false);
});

// More useful, if a user is added we can run some init setup tasks
$usersTable->getEventManager()->attach('postInsert', function (TableGatewayEvent $event) {
    /** @var ResultInterface $result */
    $result = $event->getParam('result');
    $generatedId = $result->getGeneratedValue();

    // do something with the generated identifier...
});

// More useful, if a setting is updated rebuild cache or ...
$settingaTable->getEventManager()->attach('postUpdate', function (TableGatewayEvent $event) {
    /** @var ResultInterface $result */
    $result = $event->getParam('result');
    $generatedId = $result->getGeneratedValue();

    // do something with the generated identifier...
});

$user = $usersTable->getUser(1);
$pagination = $settingaTable->getSetting('pagination');
$dbVersion = (new Setting())->get('dbVersion');
```

## The Generated Files

### Entity

This files is created for you to add any custom stuff you may want for that table.  
On a users table it might be a function that formats the full name to some crazy standard.  
So this file is **NEVER** replaced by the builder.  
So use it for what ever you need and rest assured the code will not disappear.

### EntityAbstract

This is the basic Entity file.  
If newer version of Builder may replace this with fixes/features/etc  
Don't edit this file, your changes will be lost!

### DataTable

Basically this is a TableGateway, it does the database lifting and returns the Entities.  
Again, Builder checks the version of this and it will be replaced with newer versions.  
Don't edit.

## Requirements: Runtime

### Module & Directories (Only if you want to write to file)

The namespace passed to a manger needs to be an existing module.  
It also needs to have the directories Entity and Model in the `src/{ModuleName}/directory`

These 2 dirs need to be writeable by your web server

E.G.

    $buildManager = new BuilderManager(‘DBLayer');

Will try create the files in:

`module/DBLayer/src/DBLayer/Entity`

`module/DBLayer/src/DBLayer/Model`

### Global Adapter

Builder uses the Global Adapter feature

Simplest way to get set it (I'm my opinion) is to modify the Module.php in the
module where the Data object will be created.

`module/DBLayer/Module.php`

    public function onBootstrap(MvcEvent $e) {
    ...
      $adapter = $e->getApplication()
        ->getServiceManager()
        ->get('Laminas\Db\Adapter\Adapter');

      \Laminas\Db\TableGateway\Feature\GlobalAdapterFeature::setStaticAdapter($adapter);
    ...
    }

A make sure that this Module is before any other in the list the use the
DBLayer.


## Tips

### Write Permission Error

Try shell line bellow (replace DBLayer with your data module)

```shell
sudo chmod -R a+rwX module/DBLayer/src/{Entity,Model}
```

### Quick console tips

* `builder build -w` # this creates all the files for all the tables
* `builder build ALL logs -w` # build all files for logs table
* `builder build datatable ALL -w` # builds the datatable file for all tables

## Feedback

Hey, got any ideas or suggestions to help improve this generator let me.  
Email me <code@cathedral.co.za>
