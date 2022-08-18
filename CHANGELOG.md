# Changelog
All notable changes to this project will be documented in this file.

## [0.10.0] - 2022-08-18
## Fixed
- PHP 8 compatibility

## [0.9.0] - 2022-08-01
### Added
- Cleanup Task added to automatically clean up log database table after a given time.

## [0.8.0] - 2020-10-22
### Added
- Privacy Provider added

## [0.7.0] - 2020-10-19
### Changed
- Log table modified
  - Dropped columns: userid, objectid, os, browser
  - Added columns: device (combined value for os and browser)
- This means roughly 20% less space is used
### Added
- Import script: Option added to import only the last X weeks

## [0.6.0] - 2020-06-22
### Added
- Support for `guest` role is added
  - Previously, events triggered by guests were never logged
  - `guest` is now a valid identifier for whitelists and blacklists

## [0.5.0] - 2020-05-13
### Fixed
- Regular expressions for browser detection were off, especially "iOS" devices were counted as "macOS"
### Added
- Added a CLI tool to make it easier to test user agents and the corresponding regular expressions

## [0.4.0] - 2020-04-03
### Added
- Setting `tracking_roles` added to be used as whitelist for roles.
- Setting `log_scope` added to decide what should be logged
### Changed
- UI of setting `course_ids` changed to `textarea` for easier input

## [0.3.0] - 2020-03-31
### Changed
- Plugin only loads and calls `lalog` subplugins if there is data to log.

## [0.2.1] - 2020-03-24
### Fixed
- Added missing language strings

## [0.2.0] - 2020-03-24
First public release.
