# Inane Classes

Version: `0.12.7.0` XX Oct 2018

View [CHANGELOG](CHANGELOG.md)

## Installing Inane Classes

### Requirements

- PHP \>= 7.0
- zendframework/zend-http >= 2.8

### Installation

```shell
php composer.phar require inanepain/inane
```

#### OR

Stuff to add to `composer.json`

```json
"require": {
    "inanepain/inane" : ">=0",
}
```

then simply run:

```shell
php composer.phar update
```

### Feedback

Hey, got any ideas or suggestions.

Email me <philip@inane.co.za>

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

- FileServer v0.7.2

### Observer

- InaneObserver v0.1.0
- InaneSubject v0.1.0

### String

- Capitalisation v0.1.1
- Str v0.0.5

#### Str

Some usfull string tools called static or on object:

- replace last occurrence of search with replace string
- check if str contains
- append
- prepend
- toCase

### Type

- Enum v0.2.1
- Flag v0.1.0
- Once v0.2.1

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

- Version v0.1.0
