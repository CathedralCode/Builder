CHANGELOG
=========

Basically a todo of things I would like to maybe do,

once done the move to history and there is my change log :)

TODO
----

-   Features (Relations and EntitySingular) should be optional

-   Some more convenience methods, like write [file(s) for table(s)\|all] just
    simper

-   Improve code comments (getting there)

Version Numbers
---------------

So the version number makes more since, the 2nd number is now the version of the
generated files. Yeah, this is much better.

History
-------

### 0.12.0 (2014 Nov 02)

-   Paginator: Adding support for paginator on fetchAll

-   Merged BuilderUI into Builder

-   Event args includes table name

-   Events args send primary key only (NOT insert.pre)

-   Fix: Bug in getting related tables

-   UI got some (very little) style

-   Fix: Get related function name

-   Routes for UI optimised

-	Settings: The UI got 2 more settings, EntitySingular On/Off and Singular Ignore list

-	Related Tables: function changed to fetch for Single and gather for Many

-	Console: in the root of your project run zftool and see the Cathedral commands

### 0.11.0 (2014 Oct 24)

-   Events: DataTables now trigger events pre & post insert/update/delete

### 0.10.0 (2014 Oct 21)

-   Change: Entity-\>get now returns false if no record found rather then
    exception

### 0.9.1 (2014 Oct 16)

-   Fix Bug: New singular function bypassed ignore list

-   Fix Bug: Entity files where being replaced

### 0.9.0 (2014 Oct 15)

-   EntitySingular: now supports most plural to singular conventions

### 0.8.0 (2014 Oct 06)

-   Clean up of phpDoc

-   EntityAbstract: get(\$id) function now loads into current object

### 0.7.1 (2014 Jun 26)

-   Error function access error fixed

-   EntitySingular: Ignore list only stores unique values now

-   readme fixes

### 0.1.9 - 7 (2014 Jun 25)

-   NameManager: improvements to SQL & speed

-   NameManager: Properties reduced in size

-   EntityAbstractBuilder: Code Comments

-   EntityAbstractBuilder: Generate: Property types, getter & setter Code
    comments

-   Exceptions: Code Comments

-   Option: EntitySingular - Enable/Disable

-   Option: EntitySingular - Ignore list, if only 1 or 2 names need to be
    skipped

### 0.1.8 - 6 (2014 Jun 17)

-   New: Relational Methods returning Entities based on fields using fk\_{table}
    name format

-   Fix: All data types where assigned string (data types not used yet, but
    still)

-   Entity properties changed to protected, with \_\_get/\_\_set calling the
    correct getter/setter

-   When updating a table row, the DataTable now only updates changed fields

-   Minor code clean ups

-   README.md updated

### 0.1.7 - 5 (2014 Jun 04)

-   Added Getter and Setters to Abstract Entity

### 0.1.6 - 4 (2014 Jun 04)

-   Save updates object id if auto increment

-   Clean up some redundant code

### 0.1.5 - 3 (2014 May 29)

-   Model: Insert: clear out null properties before doing an insert

### 0.1.3 - 2 (2014 May 28)

-   Entity: now returns entity on get() rather then load into self

-   Entity: dataTable property now protected

-   Entity: Improved generated comments

-   Model: Uses HydratingResultSet

### 0.1.2 (2014 May 25)

-   Minor code clean ups

-   Fix version checking code

### 0.1.1 (2014 May 25)

-   Added a generator version, if the improvements made to classes old version
    get replaced

-   Proper feedback on path permission errors

### 0.1.0 (2014 May 24)

-   Start a change log
