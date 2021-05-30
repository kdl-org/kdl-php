# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.4.0] - 2021-05-30

### Changed
- add a benchmark task in dev environment (`task bench`) using [PHPBench](https://phpbench.readthedocs.io/)
- use Parsica release 0.8.1, giving an approx 2x speed-up vs previous release!

## [0.3.0] - 2021-02-24

### Changed
- using Parsica release 0.7, with new `Parsica` PHP namespace and new package name (`parsica-php/parsica`)

## [0.2.0] - 2021-01-13

### Added
- clear memoised parsers at end of parse operation

### Changed
- adopted `Kdl` vendor PHP namespace (as per [PSR-4](https://www.php-fig.org/psr/psr-4/#2-specification)) as repository passed to kdl-org ownership, and `kdl/kdl` as package name for e.g. Packagist

## [0.1.1] - 2021-01-09

### Added
- internal memoisation of instantiated Parsica parser objects, giving ~3% speed-up

## [0.1.0] - 2021-01-08

### Added
- initial implementation using [Parsica](https://parsica.verraes.net), passing tests migrated from both JS and Rust libraries