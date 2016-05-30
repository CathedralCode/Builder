# Inane Classes

Version: `0.12.0-beta2` 30 May 2016

## Components Overview

### Config

- ConfigAwareInterface v0.2.0
- ConfigAwareTrait v0.1.0

### Debug

 - Logger v0.3.0

### Exception

### File

 - FileInfo v0.4.0

### Http

 - FileServer v0.6.0

### String

 - Capitalisation v0.1.1

### Type

 - Enum v0.2.0
 - Flag v0.1.0
 - Once v0.2.0

### Version

 - Version v0.1.0

---

## Documentation

### Type

#### Once

 - Variable that holds its value until used at which point it loses its value

##### More (Need a name still)

 - Basicaly Once v2
 - Default works like Once
 - Optionaly specify read limit at creation 

#### Flag

 - Each Flag represents an option for a property.
 - Flags are not mutually exclusive like Enums.
 - Binary bit

---

## Requirements

-   PHP \>= 5.4
-   zendframework/zend-http >= 2.5

## Installation

Stuff to add to `composer.json`

```
"repositories" : [{
        "type" : "composer",
        "url" : "https://packages.inane.co.za",
        "name" : "Inane"
    }
],
"require": {
    "inane/inaneclasses" : ">=0",
}
```

then simply run:

```
php composer.phar update
```

## Feedback

Hey, got any ideas or suggestions.

Email me <philip@inane.co.za>
