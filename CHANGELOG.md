# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.2] - 2025-10-16

### Fixed
- **cli:** release generation fixes


## [1.2.1] - 2025-10-16

### Added
- **client:** added non-identifiable client detection
- **ci:** change php action source

### Fixed
- **migration:** resolves issue with related model references
- **core:** pass by reference warning
- **docs:** return type to fix undefined lint errors


## [1.2.0] - 2025-10-16

🐘 Updated required PHP version from 8.3 to 8.4

### Added
- **jwt:** added jwt refresh token generator
- **core:** use native php 8.4 functions
- **core:** update php version requirement to 8.4

### Changed
- **docs:** fix formatting
- **docs:** update docs to not show redundant examples
- Update github action

### Fixed
- **core:** use explicit nullable types
- **jwt:** cleanup jwt handling
- **db:** add enum to migration generation


## [1.1.0] - 2025-10-15

Fixes some breaking issues from command line usage

### Changed
- **docs:** update generics documentation

### Fixed
- **cli:** append custom release message to commit message
- **cli:** upgrade summary cleanup
- **cli:** cleanup for routes postman generation
- **schema:** silently ignore unknown validators
- **db:** safe ssignment to models
- **db:** timestamp types inconsistencies


## [1.0.3] - 2025-10-15

### Fixed
- **cli:** reset output between git status checks


## [1.0.2] - 2025-10-15

### Fixed
- **critical:** env constant name
- **db:** namespace issues


## [1.0.1] - 2025-10-15

### Fixed
- **db:** allow for text validates in migration generation


## [1.0.0] - 2025-10-15

We're finally on a stable 1.0 release! 🥳

### Added
- **cli:** validate changes before making releases

### Changed
- Update version


## [0.5.0] - 2025-10-15

### Added
- **cli:** allow custom message to release command
- **cli:** improved and safer upgrade command

### Fixed
- **cli:** only generate changelog from latest release


## [0.4.0] - 2025-10-14

### Added
- **cli:** added release command
- **cli:** extracted versioning functions to keep commands clean
- **cli:** added versioning functionality

