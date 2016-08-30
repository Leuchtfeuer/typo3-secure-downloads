.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt


What does it do?
================

In TYPO3, assets like PDFs, TGZs or JPGs etc. are normally just referenced by a URL e.g. to “fileadmin/...”. The file itself is delivered directly by the web server, and is therefore not part of the TYPO3 access control scheme – files remain unprotected, since URLs can be re-used, emailed, Google-included or even guessed.

The "Secure Download" extension (“secure_downloads”) changes this behavior: Files will now be accessed through a eID script that honors TYPO3 access rights. The converted URL's will then look like this:

::

	index.php?eID=tx_securedownloads&u=1&file=fileadmin/secure/test.jpg&hash=306a7839647a68caf24b50870a59d3fc

This works regardless of where the files come from, is not limited to special plugins etc.

Since in most cases you will not want to protect everything (which means that everything undergoes rather performance-consuming  access right checking), Secure Download is highly configurable. You may choose:

* what directories to protect (e.g. you can include typo3temp or not,)
* what file types to protect (do you want to protect JPGs or not? etc.)
* what domains are considered local

As a complementary measure, you will of course need to configure your web server not to deliver these things directly (e.g. using .htaccess settings).