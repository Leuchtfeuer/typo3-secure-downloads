.. include:: ../Includes.txt

.. _about:

=====
About
=====

In TYPO3, assets like PDFs, TGZs or JPGs etc. are normally just referenced by a URL e.g. to `fileadmin/...`. The file itself is
delivered directly by the web server, and is therefore not part of the TYPO3 access control scheme – files remain unprotected,
since URLs can be re-used, emailed, Search engine included or even guessed.

The "Secure Downloads" extension (`EXT:secure_downloads`) changes this behavior: Files will now be accessed through a script that
honors TYPO3 access rights. The converted URL's will then look like this:

::

   /seduredl/sdl-eyJ0eXAiOiJKV1QiLCJhbGciO[...]vcM5rWxIulg5tQ/protected_image.jpg

This works regardless of where the files come from and is not limited to special plugins, etc.

Since in most cases you will not want to protect everything (which means that everything undergoes rather performance-consuming
access right checking), Secure Downloads is highly configurable. You may choose:

* what directories to protect (e.g. you can include typo3temp or not)
* what file types to protect (do you want to protect JPGs or not? etc.)

As a complementary measure, you will of course need to configure your web server not to deliver these things directly (e.g. using
.htaccess settings).

.. _about-compatibility:

Compatibility
=============
We are currently supporting following TYPO3 versions:

.. csv-table:: Version Matrix - Supported Versions
   :header: "Extension Version", "TYPO3 v12", "TYPO3 v11", "TYPO3 v10"
   :align: center

        "6.x", "yes", "no", "no"
        "5.x", "no", "yes", "yes"

.. _about-compatibility-outdatedVersions:

Outdated Versions
-----------------
For the following versions no more free bug fixes and new features will be provided by the authors:

.. csv-table:: Version Matrix - Outdated Versions
   :header: "Extension Version", "TYPO3 v10", "TYPO3 v9", "TYPO3 v8", "TYPO3 v7", "TYPO3 v6.2", "TYPO3 v4.5"
   :align: center

        "4.x", "yes", "yes", "no", "no", "no", "no"
        "3.x", "no", "yes", "yes", "no", "no", "no"
        "2.0.4 - 2.x", "no", "no", "yes", "yes", "no", "no"
        "2.0.0 - 2.0.3", "no", "no", "yes", "yes", "yes", "no"
        "1.x", "no", "no", "no", "no", "no", "yes"

Version 1 was released as `EXT:naw_securedl <https://extensions.typo3.org/extension/naw_securedl>`__ or `typo3-ter/naw-securedl`.
Its package name has been changed to `leuchtfeuer/secure-downloads` since Version 5.

.. _about-links:

Links
=====

:TYPO3 Extension Repository:
   https://extensions.typo3.org/extension/secure_downloads/

:Source Code and Git Repository:
   https://github.com/Leuchtfeuer/typo3-secure-downloads/

.. _about-knownLimitations:

Known Limitations
=================

* Files inside Direct Mail newsletters do not work correctly with this extension 🥺

.. toctree::
    :maxdepth: 3
    :hidden:

    ChangeLog/Index
