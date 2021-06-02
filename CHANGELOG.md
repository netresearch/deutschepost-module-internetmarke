# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## Unreleased

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
