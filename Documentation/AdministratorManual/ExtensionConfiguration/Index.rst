.. include:: ../../Includes.txt

.. _configuration:

=======================
Extension Configuration
=======================

All configuration is made in the "Extension Configuration" section of the "Settings" module beneath the "Admin Tools".

Properties
==========

.. container:: ts-properties

	==================================== ==================================== ==================
	Property                             Tab                                  Type
	==================================== ==================================== ==================
	securedDirs_                         Parsing                              string
	securedFiletypes_                    Parsing                              string
	domain_ (legacy)                     Parsing                              string
	linkPrefix_                          Link Generation                      string
	tokenPrefix_                         Link Generation                      string
	cachetimeadd_                        Link Generation                      positive integer
	enableGroupCheck_                    Group Check                          boolean
	groupCheckDirs_                      Group Check                          string
	excludeGroups_                       Group Check                          string
	outputFunction_                      File Delivery                        options
	outputChunkSize_ (legacy)            File Delivery                        positive integer
	forcedownload_                       File Delivery                        boolean
	forcedownloadtype_                   File Delivery                        string
	additionalMimeTypes_ (legacy)        File Delivery                        string
	protectedPath_                       File Delivery                        string
	log_                                 Module                               boolean
	debug_ (legacy)                      Debug                                options
	==================================== ==================================== ==================

.. ### BEGIN~OF~TABLE ###

.. _admin-configuration-securedDirs:

securedDirs
-----------
.. container:: table-row

   Property
         securedDirs
   Data type
         string
   Default
         :code:`typo3temp|fileadmin`
   Description
         List of directories of your TYPO3 Server in that files should be secured, separated by pipe (|). Files in subdirectories
         will also be secured.
         You can use :ref:`regex:Regular Expressions` for this option.


.. _admin-configuration-securedFileTypes:

securedFiletypes
----------------
.. container:: table-row

   Property
         securedFiletypes
   Data type
         string
   Default
         :code:`pdf|jpe?g|gif|png|odt|pptx?|docx?|xlsx?|zip|rar|tgz|tar|gz`
   Description
         List of file types (file extensions) that should be protected. Multiple file extension patterns can be separated by a
         pipe (|). You can use an asterisk (*) if you want to protect all files within configured directories.
         You can use :ref:`regex:Regular Expressions` for this option.


.. _admin-configuration-domain:

domain
------
.. container:: table-row

   Property
         domain
   Data type
         string
   Default
         :code:`http://mydomain.com/|http://my.other.domain.org/`
   Description
         This is only required for absolute file links to your local server, e.g. :code:`https://example.com//fileadmin/image.jpg`.
         Not needed for internal (relative) links. Please note, that this configuration property is deprecated. Parsing the HTML
         output will no longer work with version 5. You should consider to use the TYPO3 API instead.
         You can use :ref:`regex:Regular Expressions` for this option.


.. _admin-configuration-linkPrefix:

linkPrefix
----------
.. container:: table-row

   Property
         linkPrefix
   Data type
         string
   Default
         :code:`downloads`
   Description
         Prefix for generated links (the `"download"` part in "https://example.com/download/sdl-[JWT]/image.png").


.. _admin-configuration-tokenPrefix:

tokenPrefix
-----------
.. container:: table-row

   Property
         linkPrefix
   Data type
         string
   Default
         :code:`sdl-`
   Description
         Prefix for generated token (the `"sdl-"` part in "https://example.com/download/sdl-[JWT]/image.png").


.. _admin-configuration-cacheTimeAdd:

cachetimeadd
------------
.. container:: table-row

   Property
         cachetimeadd
   Data type
         positive integer
   Default
         3600
   Description
         The secure link is only valid for a limited time, which is calculated from the cache time that is used for the page that
         carries the link plus this value (in seconds).


.. _admin-configuration-enableGroupCheck:

enableGroupCheck
----------------
.. container:: table-row

   Property
         enableGroupCheck
   Data type
         boolean
   Default
         false
   Description
         Allows forwarding a secure download link to others, who can access that file if they have at least one front-end user
         group in common. Enabling this makes the checks *less* restrictive!


.. _admin-configuration-groupCheckDirs:

groupCheckDirs
--------------
.. container:: table-row

   Property
         groupCheckDirs
   Data type
         string
   Description
         A list of directories for the less restrictive group check, separated by a pipe (|). Leave empty if you want to enable
         the group check for all directories.


.. _admin-configuration-excludeGroups:

excludeGroups
-------------
.. container:: table-row

   Property
         excludeGroups
   Data type
         string
   Description
         A comma separated list of groups that are excluded from the group check feature (if enabled).


.. _admin-configuration-outputFunction:

outputFunction
--------------
.. container:: table-row

   Property
         outputFunction
   Data type
         options
   Default
         readfile
   Description
         Due to possible restrictions in php and php settings, you probably need to adjust this value. By default "readfile" is
         used to deliver the file. If this function is disabled in your php settings, you can try "fpassthru". If you have
         problems with php `memory_limit` and big files to download, you need to set this to "stream", which delivers
         the files in small portions. The option "readfile_chunked" is deprecated, but does the same as "stream" for now.
         For nginx web servers, there is also the possibility to deliver the file directly from the server by setting this
         property to "x-accel-redirect".


.. _admin-configuration-outputChunkSize:

outputChunkSize
---------------
.. container:: table-row

   Property
         outputChunkSize
   Data type
         positive integer
   Default
         1048576
   Description
         Only applicable if you use "readfile_chunked" or "stream" as output function (see: outputFunction_). Specify the number
         of bytes, served as one chunk when delivering the file. Choosing this value too low is a performance killer. Please note,
         that this property is deprecated and will be removed in version 5.


.. _admin-configuration-forcedownload:

forcedownload
-------------
.. container:: table-row

   Property
         forcedownload
   Data type
         boolean
   Default
         false
   Description
         If this is checked some file types are forced to be downloaded (see: forcedownloadtype_) in contrast of being embedded
         in the browser window.


.. _admin-configuration-forcedownloadtype:

forcedownloadtype
-----------------
.. container:: table-row

   Property
         forcedownloadtype
   Data type
         string
   Default
         :code:`odt|pptx?|docx?|xlsx?|zip|rar|tgz|tar|gz`
   Description
         A list of file types that should not be opened inline in a browser, separated by a pipe. Only used if "forcedownload"
         (see: forcedownload_) is enabled. You can use an asterisk (*) if you want to force download for all file types.
         You can use :ref:`regex:Regular Expressions` for this option.


.. _admin-configuration-additionalMimeTypes:

additionalMimeTypes
-------------------
.. container:: table-row

   Property
         additionalMimeTypes
   Data type
         string
   Default
         :code:`txt|text/plain,html|text/html`
   Description
         Comma separated list of additional MIME types (file extension / mime type pairs, in which file extension and MIME type
         is separated by a pipe symbol). Can be used to override existing MIME type settings of the extension as well. Please
         note, that this property is deprecated and will be removed in version 5. You should use the TYPO3 API for adding
         additional MIME types.


.. _admin-configuration-protectedPath:

protectedPath
-------------
.. container:: table-row

   Property
         protectedPath
   Data type
         string
   Description
         Only applicable if you use x-accel-redirect (see: outputFunction_). Specify the protected path used in your nginx
         location directive. A matching nginx `location` directive needs to be added.
   Example
         ::

            location /internal {
                internal;
                alias /path/to/your/protected/storage;
            }


.. _admin-configuration-log:

log
---
.. container:: table-row

   Property
         log
   Data type
         boolean
   Default
         false
   Description
         Each file access will be logged to database, this could be a performance issue, if you have a high traffic site. If you
         decide to turn it on, a backend module will be activated to see the traffic caused by user/ file


.. _admin-configuration-debug:

debug
-----
.. container:: table-row

   Property
         debug
   Data type
         options
   Default
         0
   Description
         For developing only. This configuration is deprecated. Please consider to use PSR-3 Logger.

.. ### END~OF~TABLE ###
