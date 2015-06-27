Cathedral Builder
=================

Zend framework 2 database layer builder with a simple Web & Console UI with many great features.

For a quick list of fixes and changes see [CHANGELOG.md](CHANGELOG.md)

Creates classes based on:

<http://framework.zend.com/manual/2.3/en/user-guide/database-and-models.html>

Requirements
------------

-   PHP \>= 5.4

-   Zend Framework 2 (latest master)

Installing
----------

I’m sure most of you can do this, but those that need a little help.

#### With composer

1.  Add this project in your composer.json:

    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    "require": {
        "cathedral/builder": "dev-master"
    }
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

2.  Now tell composer to update by running the command:

    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    $ php composer.phar update
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

### Post installation (Optional)

Enabling BuilderUI in your `application.config.php` file


	return array(
	    'modules' => array(
	        // ...
	        'Cathedral',
	    ),
	    // ...
	);


BuilderUI has some options to allow you to quickly customise the basic
functionality. After Enabling BuilderUI, copy
`./vendor/cathedral/builder/config/builderui.global.php.dist` to
`./config/autoload/builderui.global.php` and change the values as desired.

The following options are available:

-   **namespace** - Module where files will be created and the namespace of the
    created files. Default is `Application`.
    
-   **entitysingular** - On/Off switch for this feature.

-   **singularignore** - A | (pipe) delimited list of tables to ignore for EntitySingular. 

Basic Usage
-----------

Builder is only used to generate the classes, after that the classes are only
dependent on zf2, so no need to have builder on your production machine as a
dependency.

From v0.12.0 BuilderUI is part of Builder.

### BuilderUI

	Open http://yoursite/builder

If you want builder to save files to disk the directories for Namespace/Entity
and Namespace/Model must be writable by php.

And enjoy.

### Console

And just for kicks there is even console support.  
The console UI uses the same config as the Web UI.  
In the root of your project run `php index.php` and see the Cathedral options:

Get info with: `php index.php table list`

	Listing of tables
	basics
		DataTable     :Ok
		EntityAbstract:Outdated
		Entity        :None

Generate with `build (datatable|abstract|entity) <table> [--write|-w]`  
You can redirect to a file ` > path/to/file.php`  
Or simple use the -w option and builder does it for you.

### Code

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

	//Echo to screen
	echo $buildManager->getDataTableCode();
	echo $buildManager->getEntityAbstractCode();
	echo $buildManager->getEntityCode();
	
	//Write to file
	$buildManager->writeDataTable();
	$buildManager->writeEntityAbstract();
	$buildManager->writeEntity();

Thats it for the table :)

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


Thats it for all tables :)

### Restful

Builder now has some a simple Restful interface to tables.

Supported so far:

-	getList (List tables & list rows in table)

-	get (individual row from table)

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

Features/Conventions (Assumptions)
----------------------------------

### EntitySingular

If a table name is plural, builder will try create the entity as the singular
version.

Most common plural/singular conventions are supported.

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
	$nm->setEntitySingularIgnores(array('table1s','table2s'));


### Relations

If a field name uses the format fk\_{table}, I’ll assume it stores the primary
key of table {table}.

Class for table containing fk\_{table}:

This will add a new method fetch{Table} that returns an Entity of type {Table}.

E.g.: Get the User related to a Group


	Table groups which contains users
	Field groups.fk_users
	Method:$group->fetchUser()
	Entity: User
	...


Class for {table}

This will add a new methods gather(fk\_{table}’s Table) that returns Entities of
type (fk\_{table}’s Table).
You can also pass an optional array ['column' => 'value'] to futher restrict the result.

E.g.: Get all Groups related to a User


	...
	Method: $user->gatherGroups()
	Entities: Group
	OR
	Method: $user->gatherGroups(['active' => 1])
	Entities: Group that also have active set to 1


### Events

The DataTable triggers events pre & post of insert, update and delete queries.

Trigger Events

-   insert.pre

-   insert.post & commit

-   update.pre

-   update.post & commit

-   delete.pre

-   delete.post & commit

As you can see a commit event is only triggered at any post, also post is only
triggered if successful.

 

How to attach to the event?

Make these changes to:

Module.php


	...
	use Zend\EventManager\Event;


onBootstrap()


	public function onBootstrap(MvcEvent $e) {
	    ...
	    $e->getApplication()->getEventManager()->getSharedManager()->attach('Dossier\Model\TechniquesTable', 'commit', function(Event $e) {
	        Debug::dump($e->getName());
	        Debug::dump(get_class($e->getTarget()));
	        Debug::dump($e->getParams());
	    });
	    ...
	}


And that’s how easy it is :)

But also keep in mind you can call the enableEvents/disableEvents methods on the
DataTable to turn events off for a while :)

 

The Generated Files
-------------------

### Entity

This files is created for you to add any custom stuff you may want for that
table.

On a users table it might be a function that formats the full name to some crazy
standard.

So this file is **NEVER** replaced by the builder.

So use it for what ever you need and rest assured the code will not disappear.

### EntityAbstract

This is the basic Entity file.

If newer version of Builder may replace this with fixes/features/etc

Don’t edit this file, your changes will be lost!

### DataTable

Basically this is a TableGateway, it does the database lifting and returns the
Entities.

Again, Builder checks the version of this and it will be replaced with newer
versions.

Don’t edit.

Requirements: Runtime
---------------------

### Module & Directories (Only if you want to write to file)

The namespace passed to a manger needs to be an existing module.

It also needs to have the directories Entity and Model in the src/{ModuleName}/
directory

These 2 dirs need to be writable by your web server

E.G.


	$buildManager = new BuilderManager(‘DBLayer');


Will try create the files in:

module/DBLayer/src/DBLayer/Entity

module/DBLayer/src/DBLayer/Model

### Global Adapter

Builder uses the Global Adapter feature

Simplest way to get set it (I'm my opinion) is to modify the Module.php in the
module where the Data object will be created.

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

Feedback
--------

Hey, got any ideas or suggestions to help improve this generator let me.

Email me <code@cathedral.co.za>
