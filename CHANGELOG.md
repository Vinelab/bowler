# Changelog
All notable changes to `bowler` will be documented in this file.

## [v0.5.3] - 2019-08-27
### Fixed
- Fix service binding for exception handler changed in v0.5.0

## [v0.5.2] - 2019-08-27
### Added
- Enable package auto-discovery

## [v0.5.1] - 2019-08-27
### Fixed
- Fix config path for `vendor:publish` command

## [v0.5.0] - 2019-08-27
### Added
- Introduce lifecycle hooks
- Introduce configuration management
- Added default error reporting behavior for Bowler with message included in error context

### Changed
- Bowler doesn't fetch RabbitMQ credentials from `config/queue.php` file anymore, use package config instead
- App exception handler no longer needs to implement BowlerExceptionHandler contract unless you want to override default behavior

## [0.4.4] - 2019-02-20
### Changed
- Update to `php-amqplib v2.8.1`

## [0.4.3] - 2019-02-04
### Added
- Configurable connection `vhost` param @cerpusoddarne.

## [0.4.2] - 2019-01-14
### Added
- Configurable connection `connection_timeout`, `heartbeat` and `read_write_timeout` params.

### Removed
- Drop support for Laravel 5.3 and earlier.
   - Tests backward incompatibility.
