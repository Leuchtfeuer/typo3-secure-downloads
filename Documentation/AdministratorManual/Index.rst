.. include:: Includes.txt

.. _admin:

==============
Administration
==============

This chapter describes how to configure this extension within the settings module of your TYPO3 instance.

You need to secure all the directories and file types by your server configuration. This can be done with `.htaccess` files.
You find some example `.htaccess` files below and in the `Resources/Private/Examples <https://github.com/bitmotion/typo3-secure-downloads/tree/master/Resources/Private/Examples>`__
directory of this extension.

.. important::
   This extension cannot secure links to files that you include in CSS, PDF, ... files.

Example Files
=============

Apache ≥ 2.4
------------

**.htaccess allow**
::
   <FilesMatch "\.([Pp][Dd][Ff]|[Jj][Pp][Ee]?[Gg]|[Gg][Ii][Ff]|[Pp][Nn][Gg]|[Dd][Oo][Cc]|[Xx][Ll][Ss]|[Rr][Aa][Rr]|[Tt][Gg][Zz]|[Tt][Aa][Rr]|[Gg][Zz])">
       Require all granted
   </FilesMatch>

**.htaccess deny**
::
   <FilesMatch "\.([Pp][Dd][Ff]|[Jj][Pp][Ee]?[Gg]|[Gg][Ii][Ff]|[Pp][Nn][Gg]|[Dd][Oo][Cc]|[Xx][Ll][Ss]|[Rr][Aa][Rr]|[Tt][Gg][Zz]|[Tt][Aa][Rr]|[Gg][Zz])">
       Require all denied
   </FilesMatch>


Apache < 2.4
------------

**.htaccess allow**
::
   <FilesMatch "\.([Pp][Dd][Ff]|[Jj][Pp][Ee]?[Gg]|[Gg][Ii][Ff]|[Pp][Nn][Gg]|[Dd][Oo][Cc]|[Xx][Ll][Ss]|[Rr][Aa][Rr]|[Tt][Gg][Zz]|[Tt][Aa][Rr]|[Gg][Zz])">
       Order deny,allow
       Allow from all
   </FilesMatch>

**.htaccess deny**
::
   <FilesMatch "\.([Pp][Dd][Ff]|[Jj][Pp][Ee]?[Gg]|[Gg][Ii][Ff]|[Pp][Nn][Gg]|[Dd][Oo][Cc]|[Xx][Ll][Ss]|[Rr][Aa][Rr]|[Tt][Gg][Zz]|[Tt][Aa][Rr]|[Gg][Zz])">
       Order deny,allow
       Deny from all
       Allow from none
   </FilesMatch>



.. toctree::
    :maxdepth: 3
    :titlesonly:

    ExtensionConfiguration/Index

