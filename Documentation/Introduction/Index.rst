.. include:: ../Includes.txt

.. _introduction:

============
Introduction
============

This chapter gives you a basic introduction about the TYPO3 CMS extension "*secure_downloads*".

What does it do?
================

In TYPO3, assets like PDFs, TGZs or JPGs etc. are normally just referenced by a URL e.g. to `fileadmin/...`. The file itself is
delivered directly by the web server, and is therefore not part of the TYPO3 access control scheme – files remain unprotected,
since URLs can be re-used, emailed, Search engine included or even guessed.

The "Secure Downloads" extension (`EXT:secure_downloads`) changes this behavior: Files will now be accessed through a script that
honors TYPO3 access rights. The converted URL's will then look like this:

::

   /download/sdl-eyJ0eXAiOiJKV1QiLCJhbGciO[...]vcM5rWxIulg5tQ/protected_image.jpg

This works regardless of where the files come from and is not limited to special plugins, etc.

Since in most cases you will not want to protect everything (which means that everything undergoes rather performance-consuming
access right checking), Secure Downloads is highly configurable. You may choose:

* what directories to protect (e.g. you can include typo3temp or not)
* what file types to protect (do you want to protect JPGs or not? etc.)

As a complementary measure, you will of course need to configure your web server not to deliver these things directly (e.g. using
.htaccess settings).


Identify protected files
========================

You can easily identify protected files in the file list, because all protected files and directories are marked with a dedicated
icon:

   .. figure:: Filelist.png
      :class: with-shadow
      :alt: Identify protected files within the "Filelist" module.

      Identify protected files within the "Filelist" module.
