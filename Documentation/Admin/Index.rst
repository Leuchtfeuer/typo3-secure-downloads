.. include:: ../Includes.txt

.. _admin:

==================
For Administrators
==================

This chapter describes how to install and how to configure this extension within the settings module of your TYPO3 instance.

.. important::
   This extension cannot secure links to files that you include in CSS, PDF, ... files.

.. _admin-installation:

Installation
============
There are several ways to require and install this extension. We recommend getting this extension via
`composer <https://getcomposer.org/>`__.

.. _admin-installation-viaComposer:

Via Composer
------------
If your TYPO3 instance is running in composer mode, you can simply require the extension by running:

.. code-block:: bash

   composer req bitmotion/secure-downloads:^4.0

.. _admin-installation-viaExtensionManager:

Via Extension Manager
---------------------
Open the extension manager module of your TYPO3 instance and select "Get Extensions" in the select menu above the upload button.
There you can search for `secure_downlaods` and simply install the extension. Please make sure you are using the latest version
of the extension by updating the extension list before installing the Secure Downloads extension.

.. _admin-installation-viaZipFile:

Via ZIP File
------------
You need to download the Secure Downloads extension from the
`TYPO3 Extension Repository <https://extensions.typo3.org/extension/secure_downloads/>`__ and upload the ZIP file to the extension
manager of your TYPO3 instance and activate the extension afterwards.
You can also download an archive from `GitHub <https://github.com/Leuchtfeuer/typo3-secure-downloads/releases/latest>`__ and put
its content directly into the `typo3conf/ext` directory of your TYPO3 instance. But please keep in mind, that the name of the
folder must be `secure_downloads` (the repository name will be default).

.. _admin-bestPractices:

Best Practices
==============
You can configure this extension to fit your specific needs. However, here are some "best practices" that may help you when first
using Secure Downloads:

* Install this extension as described above
* Create a new `File Storage <https://docs.typo3.org/m/typo3/reference-coreapi/master/en-us/ApiOverview/Fal/Administration/Storages.html>`__
  of type "Local filesystem" on page 0 of your TYPO3 instance and set the "Is publicly available?" option to false
* Create a directory on your filesystem which matches the previously configured "Base Path"
* Put an `.htaccess` file into that folder that denies the access to all files within and underneath this path
* Configure the extension in the admin section of your TYPO3 Backend to match all files (use an astrix for the
  :ref:`admin-extensionConfiguration-securedFiletypes` option) in your newly created file storage (use the path for the
  :ref:`admin-extensionConfiguration-securedDirs` option)

.. hint::

   From version 5 on, it is possible to automatically generate a file storage in which all contained files are protected from
   direct access.

.. _admin-accessConfiguration:

Access Configuration
====================

You need to secure all the directories and file types by your server configuration. This can be done with `.htaccess` files.
You find some example `.htaccess` files below and in the
`Resources/Private/Examples <https://github.com/Leuchtfeuer/typo3-secure-downloads/tree/master/Resources/Private/Examples>`__
directory of this extension.

.. _admin-accessConfiguration-exampleConfiguration:

Example Configuration
---------------------
Please make sure to adapt the file match pattern as configured in :ref:`admin-extensionConfiguration-securedFiletypes`.

**.htaccess deny**
::
   # Apache 2.4
   <IfModule mod_authz_core.c>
     <FilesMatch "\.(pdf|jpe?g|gif|png|odt|pptx?|docx?|xlsx?|zip|rar|tgz|tar|gz)$">
       Require all denied
     </FilesMatch>
   </IfModule>

   # Apache 2.2
   <IfModule !mod_authz_core.c>
     <FilesMatch "\.(pdf|jpe?g|gif|png|odt|pptx?|docx?|xlsx?|zip|rar|tgz|tar|gz)$">
       Order Allow,Deny
       Deny from all
     </FilesMatch>
   </IfModule>

**.htaccess allow**
::
   # Apache 2.4
   <IfModule mod_authz_core.c>
     <FilesMatch "\.(pdf|jpe?g|gif|png|odt|pptx?|docx?|xlsx?|zip|rar|tgz|tar|gz)$">
       Require all granted
     </FilesMatch>
   </IfModule>

   # Apache 2.2
   <IfModule !mod_authz_core.c>
     <FilesMatch "\.(pdf|jpe?g|gif|png|odt|pptx?|docx?|xlsx?|zip|rar|tgz|tar|gz)$">
       Order Deny,Allow
       Allow from all
     </FilesMatch>
   </IfModule>

.. toctree::
    :maxdepth: 3
    :hidden:

    ExtensionConfiguration/Index
    RegularExpressions/Index

