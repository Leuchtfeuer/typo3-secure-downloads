# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [4.0.0]
### Added
* Dedicated DTO for extension configuration
* Use JSON Web Tokens for URL generation
* Support for TYPO3 10
* Support for PHP 7.3

### Changed

### Deprecated
* Class `\Bitmotion\SecureDownloads\Configuration\ConfigurationManager` is now deprecated. You can use the newly introduced DTO instead.
* Generation of secured links via URL get parameters is marked as deprecated. You should use JWTs and PSR-15 middleware instead.
* Parameters `hash` and `calculatedHash` of `$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['bitmotion']['secure_downloads']['output']['init']` hook.

### Removed
* Support for TYPO3 8 LTS
* Apache delivery
* Deprecated properties "bytesDownloades" and "typo3Mode" from log model
* Deprecated hook `$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/secure_downloads/class.tx_securedownloads.php']['makeSecure']` was removed. You can use the hook `$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/secure_downloads/Classes/Service/SecureDownloadService.php']['makeSecure']` instead.

[4.0.0]: https://github.com/bitmotion/typo3-secure-downloads/compare/3.0.1..4.0.0

## [3.0.1]
TODO: add

[3.0.1]: https://github.com/bitmotion/typo3-secure-downloads/compare/3.0.0..3.0.1

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
