.. include:: ../../Includes.txt

.. _developer-securedLinks:

=============
Secured Links
=============
Following examples clarifies, how to retrieve secured links from different data type sources.

.. _developer-securedLinks-fileObject:

File Object
===========
Instance of :php:`\TYPO3\CMS\Core\Resource\File` given:

.. code-block:: php

   /** @var \TYPO3\CMS\Core\Resource\File $file */
   $securedUrl = $file->getPublicUrl();

.. _developer-securedLinks-fileReference:

File Reference
==============
Instance of :php:`\TYPO3\CMS\Core\Resource\FileReference` given:

.. code-block:: php

   /** @var \TYPO3\CMS\Core\Resource\FileReference $fileReference */
   $securedUrl = $fileReference->getPublicUrl();

.. _developer-securedLinks-fluidTemplates:

Fluid Templates
===============
Getting secured links within a fluid template is a no-brainer; you don't have to pay attention to anything here:

.. code-block:: html

   <f:image image="{image}" class="img-fluid img-thumbnail" />
   <f:image image="{image}" treatIdAsReference="TRUE" class="img-fluid img-thumbnail" />
   <img src="{image.publicUrl}" class="img-fluid img-thumbnail" />

.. _developer-securedLinks-api:

API
===
Get a link that is only valid for user `29`, with user group `12`, is generated on page `89` and expires on `2022/05/08`:

.. code-block:: php

   $publicUrl = '/fileadmin/secured/invoice.pdf';
   $secureDownloadService = GeneralUtility::makeInstance(SecureDownloadService::class);

   if ($secureDownloadService->pathShouldBeSecured($publicUrl)) {
       $securedUrl = GeneralUtility::makeInstance(SecureLinkFactory::class)
           ->withResourceUri(rawurlencode($publicUrl))
           ->withUser(29)
           ->withPage(89)
           ->withGroups([12])
           ->withTimeout(1659650400)
           ->getUrl();
   }
