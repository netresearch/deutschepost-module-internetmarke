# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## 2.4.0

### Added

- Compatibility for Magento 2.4.7.

### Changed

- Use escaper instead of block for escaping in templates

### Removed

- PHP7.x support
- PHP8.1 support

## 2.3.3

### Changed

- Establish compatibility to `dhl/module-carrier-paket:2.8.0`: Deselect new
  DHL Paket services for INTERNETMARKE shipments.

## 2.3.2

### Changed

- Establish compatibility to `netresearch/module-shipping-core:2.9.0`.
- Establish compatibility to `dhl/module-carrier-paket:2.7.0`: Deselect new
  DHL Paket services for INTERNETMARKE shipments.

## 2.3.1

### Fixed

- Consider database table prefix when updating products lists.

## 2.3.0

Magento 2.4.4 compatibility release

### Added

- Support for Magento 2.4.4

### Removed

- Support for PHP 7.1

## 2.2.0

### Changed

- Establish compatibility with shipping core 2.7.

## 2.1.1

### Fixed

- Skip product list update if no API credentials are configured.

## 2.1.0

### Added

- Interactive batch processing for Internetmarke shipping products. 

## 2.0.3

### Changed

- Establish compatibility with shipping core 2.4.

## 2.0.2

### Fixed

- Roll back multi-package label requests on error.

## 2.0.1

### Changed

- Use `netresearch/module-shipping-core` package for three-letter country code calculation.

## 2.0.0

### Changed

- Replace shipping core package dependency.

## 1.1.2

### Fixed

- Use ISO 3166 ALPHA-3 country code in label requests.

## 1.1.1

### Fixed

- Prevent `PageFormatCollection does not exist` exception, caused by wrong import statement.

## 1.1.0

### Added

- Update shipping products weekly via cron job.
- Define web service log settings in module configuration.

### Fixed

- Show web service error messages in packaging popup.

## 1.0.0

Initial release
