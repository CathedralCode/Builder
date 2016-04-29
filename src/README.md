# Inane Classes

Version: `0.11.0` 29 Apr 2016

## Components

### Config

### Debug

### Exception

### File

### Http

### String

### Type

#### Enum

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

### Version

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
