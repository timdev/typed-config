# timdev/typed-config Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## Unreleased

### Added

### Changed

### Deprecated

### Removed

### Fixed

### Security

## 0.2.0 - 2025-05-28

### Added

* Added support for PHP 8.2
* Added PHPStan for additional static analysis

### Changed

* Modernized dependencies and supported PHP versions.
  * Moved from Psalm 5.x to 6.x 

### Removed

* Removed support for PHP <8.3

## 0.1.3 - 2024-10-30

### Added 

* Test suite now complains more helpfully if you run it with
  `zend.assertions` set to something other than `1`.

## 0.1.2 - 2021-12-19

### Added

* Added support for PHP 8.1.

### Changed

* Minor code-style fixes.

## 0.1.1 - 2021-09-22

### Added

* Added `Config::toArray()` which simply returns the original array.
* Added `Config::toFlatArray(string $delimiter = '.')`

## 0.1.0 - 2021-09-11

* Initial public release of this library
