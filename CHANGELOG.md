# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.0.0]
### Added
* Support for TYPO3 v9
* Support for PHP 7.2

### Changed
* Respect `$GLOBALS['TSFE']->absRefPrefix` when parsing HTML output
* Renamed protected method `\Bitmotion\SecureDownloads\Resource\FileDelivery::readfile_chunked` to `readFileFactional`

### Deprecated
* Member properties of `\Bitmotion\SecureDownloads\Domain\Model\Log`:
    * `$bytesDownloaded`
    * `$typo3Mode`

### Removed
* Support for TYPO3 v7
* Support for PHP 5
* Protected method `\Bitmotion\SecureDownloads\Request\RequestContext::initializeBackendContext`
* Protected member property `\Bitmotion\SecureDownloads\Resource\FileDelivery::$logRowUid`

[3.0.0]: https://github.com/bitmotion/typo3-secure-downloads/compare/2.0.6..3.0.0
