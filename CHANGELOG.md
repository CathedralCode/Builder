# CHANGELOG

Basically a TODO of things I would like to maybe do,  
once done the move to history and there is my change log :)

## TODO

- Option: use foreignKey OR fieldPattern OR neither to build relationship lookups
- fieldPattern for relationships to be configurable
- Add support for compound primary keys
- Bash around the name spaces so that all classes fall under builder
- Drop tbl prefix for classes
- Ignore list for tables
- If a fk_{table} reference is invalid ignore it

### Rest

- Rest: Generate rest controllers
- Rest interface configuration (enable/disable functions global or by table)
  - create($data) - Triggered by POST request to a resource collection.
  - delete($id) - Triggered by DELETE request to a resource entity.
  - deleteList($data) - Triggered by DELETE request to a resource collection.
  - fetch($id) - Triggered by GET request to a resource entity.
  - fetchAll($params = []) - Triggered by GET request to a resource collection.
  - patch($id, $data) - Triggered by PATCH request to resource entity.
  - patchList($data) - Triggered by PATCH request to a resource collection.
  - update($id, $data) - Triggered by PUT request to a resource entity.
  - replaceList($data) - Triggered by PUT request to a resource collection.

## Version Numbers

So the version number makes more since, the 2nd number is now the version of the
generated files. Yeah, this is much better.

## History

### 0.27.1 (2020 May 19)

- Add SharedEventManager (which was missing after the change)
- Added const for events

### 0.27.0 (2020 May 11)

- Event now using FeatureEvents from Laminas

### 0.26.0 (2020 May 05)

- Console back and better than before

### 0.25.0 (2020 Mar 24)

- Default values handled better
- Relations retrieved by model or entity name

### 0.23.2 (2020 Mar 18)

- Module got selectPaginated function
- Some adjustments to code comments

### 0.22.0 (2020 Feb 05)

- Moved to Laminas
- AbstractEntity - get method sets the identity property
- Update Hydrator to ReflectionHydrator

### 0.21.2 (2019 Feb 05)

- DataTableBuilder - DocBlock generated with more comments
- DataTableBuilder - DocBlock for trigger function fixed, argv => data

### 0.21.1 (2018 Oct 14)

- AbstractEntity: Fixed comment for __(get/set) functions
- AbstractEntity: __set now returns $this for chaining (version bump 21)
- EntityAbstractBuilder: Cleaned to code up little

### 0.20.1 (2018 Oct 12)

- Add: DataTables->selectUsing($order, $where, $limit)

### 0.19.3 (2018 Jul xx)

- UI: Add a link to server root on overview page.
- AbstractEntity: exchangeArray function simpler
- DataTableBuilder->setEventManager now also creates a SharedEventManager
- DataTables event Identifiers now work on class with or without namespace
- Insert does array_filter to only insert fields with values

### 0.19.2 (2017 Nov 24)

- Update: ZF3 compatible
- Update: BuilderManager->verifyModuleStructure() - Tries to create any missing directories

### 0.18.12 (2016 Sep 07)

- Add: more phpdoc in generated files

### 0.18.11 (2016 Jun 29)

- Fix: trigger - should be strings quotes where missing not $
- Update: Metadata using Feature\MetadataFeature
- Update: Simplified some functions

### 0.18.10 (2016 Jun 23)

- Fix missing $ for variables in DataTable Trigger

### 0.18.9 (2016 May 27)

- Added @property tags to EntityAbstract Classes
- Getters cast properties of type int

### 0.18.8 (2016 Apr 15)

- Fixed CLI config error.

### 0.18.5 (2016 Apr 15)

- Verify the module set in config is valid, else use Application.

### 0.18.4 (2016 Apr 15)

- Removed use of deprecated ServiceLocatorAwareInterface.

### 0.18.1 (2015 Nov 11)

- CLI ALL and ALL, add ALL option for class. One cmd and every done.
- Fixed, default for singularignore was not set in config.

### 0.18.0 (2015 Aug 31)

- Fixed, saving blank values
- Added warnings to files that get replaced by builder

### 0.17.0 (2015 Aug 17)

- Fixed, now passing correct array to exchangeArray

### 0.16.1 (2015 Aug 1)

- Improved on the error handling and messages
- DataTable now has getColumnDefaults, which gives you an array of cols and their default values
- ExchangeArray, now fills in the column default if a value is missing

### 0.16.0 (2015 Jun 27)

- DataTable->getColumns() now returns an array of columns
- Entity->getArrayCopy reworked to use getColumns so that no extra properties slip threw
- AbstractEntity had two incomplete method comments
- gather{Entity} now has an optional array param for extra filtering

### 0.15.0 (2015 May 23)

- Entities are now Serialisable
- RestController fix incorrect function call featchAll (oi, grin)
- setProperties and be chained

### 0.14.0 (2015 Feb 07)

- BasicUI renamed to Web Interface
- Reduce database access when building
- GatherChildren return type fix
- Fix spelling FetchAll on DataTable
- Fixed return type FetchAll DataTable
- Set permissions on created files to a+rw
- Minor code cleanups
- Minor improvements to README.md

### 0.13.0 (2014 Nov 27)

- DataTable: new method getEntity, returns new empty entity
- Insert Update: When inserting and key exists it will do an update
- Default namespace Application, use builder with giving a namespace in code as well
- WebUI: Code display page got some work done to it.
- Display user friendly (how to fix) messages for things like permissions problems.
- Restful controller to access tables added

### 0.12.1 (2014 Nov 02)

- Fix: Passing table name to BuilderManager with NameManager was dropping the table name.

### 0.12.0 (2014 Nov 02)

- Paginator: Adding support for paginator on fetchAll
- Merged BuilderUI into Builder
- Event args includes table name
- Events args send primary key only (NOT insert.pre)
- Fix: Bug in getting related tables
- UI got some (very little) style
- Fix: Get related function name
- Routes for UI optimised
- Settings: The UI got 2 more settings, EntitySingular On/Off and Singular Ignore list
- Related Tables: function changed to fetch for Single and gather for Many
- Console: in the root of your project run zftool and see the Cathedral commands

### 0.11.0 (2014 Oct 24)

- Events: DataTables now trigger events pre & post insert/update/delete

### 0.10.0 (2014 Oct 21)

- Change: Entity-\>get now returns false if no record found rather then
    exception

### 0.9.1 (2014 Oct 16)

- Fix Bug: New singular function bypassed ignore list
- Fix Bug: Entity files where being replaced

### 0.9.0 (2014 Oct 15)

- EntitySingular: now supports most plural to singular conventions

### 0.8.0 (2014 Oct 06)

- Clean up of phpDoc
- EntityAbstract: get(\$id) function now loads into current object

### 0.7.1 (2014 Jun 26)

- Error function access error fixed
- EntitySingular: Ignore list only stores unique values now
- readme fixes

### 0.1.9 - 7 (2014 Jun 25)

- NameManager: improvements to SQL & speed
- NameManager: Properties reduced in size
- EntityAbstractBuilder: Code Comments
- EntityAbstractBuilder: Generate: Property types, getter & setter Code
    comments
- Exceptions: Code Comments
- Option: EntitySingular - Enable/Disable
- Option: EntitySingular - Ignore list, if only 1 or 2 names need to be
    skipped

### 0.1.8 - 6 (2014 Jun 17)

- New: Relational Methods returning Entities based on fields using fk\_{table}
    name format
- Fix: All data types where assigned string (data types not used yet, but
    still)
- Entity properties changed to protected, with \_\_get/\_\_set calling the
    correct getter/setter
- When updating a table row, the DataTable now only updates changed fields
- Minor code clean ups
- README.md updated

### 0.1.7 - 5 (2014 Jun 04)

- Added Getter and Setters to Abstract Entity

### 0.1.6 - 4 (2014 Jun 04)

- Save updates object id if auto increment
- Clean up some redundant code

### 0.1.5 - 3 (2014 May 29)

- Model: Insert: clear out null properties before doing an insert

### 0.1.3 - 2 (2014 May 28)

- Entity: now returns entity on get() rather then load into self
- Entity: dataTable property now protected
- Entity: Improved generated comments
- Model: Uses HydratingResultSet

### 0.1.2 (2014 May 25)

- Minor code clean ups
- Fix version checking code

### 0.1.1 (2014 May 25)

- Added a generator version, if the improvements made to classes old version
    get replaced
- Proper feedback on path permission errors

### 0.1.0 (2014 May 24)

- Start a change log
