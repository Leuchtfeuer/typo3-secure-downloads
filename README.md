[![Latest Stable Version](https://poser.pugx.org/bitmotion/secure-downloads/v/stable)](https://packagist.org/packages/bitmotion/secure-downloads)
[![Total Downloads](https://poser.pugx.org/bitmotion/secure-downloads/downloads)](https://packagist.org/packages/bitmotion/secure-downloads)
[![Latest Unstable Version](https://poser.pugx.org/bitmotion/secure-downloads/v/unstable)](https://packagist.org/packages/bitmotion/secure-downloads)
[![Code Climate](https://codeclimate.com/github/Leuchtfeuer/typo3-secure-downloads/badges/gpa.svg)](https://codeclimate.com/github/Leuchtfeuer/typo3-secure-downloads)
[![License](https://poser.pugx.org/bitmotion/secure-downloads/license)](https://packagist.org/packages/bitmotion/secure-downloads)

# TYPO3 Extension "Secure Downloads"

In TYPO3, assets like PDFs, TGZs or JPGs etc. are normally just referenced by a URL e.g. to `fileadmin/...`. The file itself is 
delivered directly by the web server, and is therefore not part of the TYPO3 access control scheme – files remain unprotected, 
since URLs can be re-used, emailed, Search engine included or even guessed.

The "Secure Downloads" extension (`EXT:secure_downloads`) changes this behavior: Files will now be accessed through a script that 
honors TYPO3 access rights. The converted URL's will then look like this:

    https://www.example.com/securedl/sdl-eyJ0eXAiOiJKV1QiLCJhbGciO[...]vcM5rWxIulg5tQ/protected_image.jpg

This works regardless of where the files come from and is not limited to special plugins, etc.

Since in most cases you will not want to protect everything (which means that everything undergoes rather performance-consuming 
access right checking), Secure Downloads is highly configurable. You may choose:

* what directories to protect (e.g. you can include typo3temp or not)
* what file types to protect (do you want to protect JPGs or not? etc.)

As a complementary measure, you will of course need to configure your web server not to deliver these things directly (e.g. using 
.htaccess settings).

## Requirements

We are currently supporting following TYPO3 versions:<br><br>

| Extension Version                                                              | TYPO3 v10 | TYPO3 v9 |
| ------------------------------------------------------------------------------ | --------- | -------- |
| [5.x](https://github.com/Leuchtfeuer/typo3-secure-downloads) <sup>1</sup>      | x         | -        |
| [4.x](https://github.com/Leuchtfeuer/typo3-secure-downloads/tree/release-4.x)  | x         | x        |

* <sup>1)</sup> Upcoming release as `leuchtfeuer/secure-downloads` (vendor name changed).

### Outdated Versions

For the following versions no more free bug fixes and new features will be provided by the authors:

| Extension Version | TYPO3 v9 | TYPO3 v8 | TYPO3 v7 | TYPO3 v6.2 | TYPO3 v4.5 |
| ----------------- | -------- | -------- | -------- | ---------- | ---------- |
| 3.x               | x        | x        | -        | -          | -          |
| 2.0.4 - 2.x       | -        | x        | x        | -          | -          |
| 2.0.0 - 2.0.3     | -        | -        | x        | x          | -          |
| 1.x<sup>2</sup>   | -        | -        | -        | x          | x          |

* <sup>2)</sup> As [`EXT:naw_securedl`](https://extensions.typo3.org/extension/naw_securedl) bzw. `typo3-ter/naw-securedl`.

## Installation
There are several ways to require and install this extension. We recommend getting this extension via 
[composer](https://getcomposer.org/).

### Via Composer
If your TYPO3 instance is running in composer mode, you can simply require the extension by running:

    composer req bitmotion/secure-downloads:^4.0

### Via Extension Manager
Open the extension manager module of your TYPO3 instance and select "Get Extensions" in the select menu above the upload button. 
There you can search for `secure_downlaods` and simply install the extension. Please make sure you are using the latest version 
of the extension by updating the extension list before installing the Secure Downloads extension.

### Via ZIP File
You need to download the Secure Downloads extension from the 
[TYPO3 Extension Repository](https://extensions.typo3.org/extension/secure_downloads/) and upload the ZIP file to the extension 
manager of your TYPO3 instance and activate the extension afterwards.
You can also download an archive from [GitHub](https://github.com/Leuchtfeuer/typo3-secure-downloads/releases/latest) and put its
content directly into the `typo3conf/ext` directory of your TYPO3 instance. But please keep in mind, that the name of the folder 
must be `secure_downloads` (the repository name will be default).

## Configuration
After installation, you need to configure this extension. Take a look at the corresponding section of the official 
[manual](https://docs.typo3.org/p/bitmotion/secure-downloads/4.1/en-us/AdministratorManual/ExtensionConfiguration/Index.html).

### Best Practice
You can configure this extension to fit your specific needs. However, here are some "best practices" that may help you when first
using Secure Downloads:

* Install this extension as described above
* Create a new "File Storage" of type "Local filesystem" on page 0 of your TYPO3 instance and set the "Is publicly available?" 
  option to false
* Create a directory on your filesystem which matches the previously configured "Base Path"
* Put an `.htaccess` file into that folder that denies the access to all files within and underneath this path
* Configure the extension in the admin section of your TYPO3 Backend to match all files (use an astrix for the 
  [securedFiletypes](https://docs.typo3.org/p/bitmotion/secure-downloads/4.1/en-us/AdministratorManual/ExtensionConfiguration/Index.html#securedfiletypes)
  option) in your newly created file storage (use the path for the 
  [securedDirs](https://docs.typo3.org/p/bitmotion/secure-downloads/4.1/en-us/AdministratorManual/ExtensionConfiguration/Index.html#securedfiletypes)
  option).

### Access Configuration
You also need to secure all the directories and file types by your server configuration. This can be done with `.htaccess` files.
Some example .htaccess files can be found in the 
[Resources/Private/Examples](https://github.com/Leuchtfeuer/typo3-secure-downloads/tree/release-4.x/Resources/Private/Examples) 
folder.

**Note**: This extension cannot secure links to files that you include in your CSS file. For example you can secure `/fileadmin` 
with the default `.htaccess_deny` file by putting the file in `/fileadmin`. You can allow `/fileadmin/templates/` with the
default `.htaccess_allow` file by putting this file to `/fileadmin/template/`.

## Documentation
A detailed documentation can be found in the 
[official TYPO3 documentation](https://docs.typo3.org/p/bitmotion/secure-downloads/master/en-us/Index.html)
of this extension.

## Changelog
The changelog can be found in the changelog chapter of the
[official TYPO3 documentation](https://docs.typo3.org/p/bitmotion/secure-downloads/master/en-us/Miscellaneous/ChangeLog/Index.html)
of this extension.

## Contributing
You can contribute by making a **pull request** to the master branch of this repository, by using the "❤️ Sponsor" button on the 
top of this page, or just send us some **beers** 🍻...
