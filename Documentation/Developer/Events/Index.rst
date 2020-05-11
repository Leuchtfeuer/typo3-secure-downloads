.. include:: ../../Includes.txt

.. _developer-events:

======
Events
======
This extension comes up with two different categories of
`PSR-14 Events <https://docs.typo3.org/m/typo3/reference-coreapi/master/en-us/ApiOverview/Hooks/EventDispatcher/Index.html>`__ you
can listen to. The first category deals with the generation of token and payload, the second one with the retrieval of assets.

.. _developer-events-linkGeneration:

Link Generation
===============
This event is executed in the
`Secure Link <https://github.com/Leuchtfeuer/typo3-secure-downloads/blob/master/Classes/Factory/SecureLinkFactory.php>`__ factory.

.. _developer-events-linkGeneration-enrichPayload:

Enrich Payload
--------------
You can use this event for extending or manipulating the payload of the JSON Web Token. This event is executed immediately before
the JSON Web token is generated. The name of the event is `Leuchtfeuer\SecureDownloads\Factory\Event\EnrichPayloadEvent`.

.. container:: table-row

   Property
        payload
   Data Type
        array
   Description
        This array contains the default payload of the JSON Web Token. You can enrich this data by your own properties or
        manipulate the existing data.

.. container:: table-row

   Property
        token
   Data Type
        `AbstractToken <https://github.com/Leuchtfeuer/typo3-secure-downloads/blob/master/Classes/Domain/Transfer/Token/AbstractToken.php>`__
   Description
        This property is read-only and contains the generated token object.

.. _developer-events-fileRetrieving:

File Retrieving
===============
These events are executed in the
`FileDelivery <https://github.com/Leuchtfeuer/typo3-secure-downloads/blob/master/Classes/Resource/FileDelivery.php>`__ class.

.. _developer-events-fileRetrieving-outputInitialization:

Output Initialization
---------------------
This event is executed after the JSON Web Token has been decoded and before the access checks take place. The name of the event is
`Leuchtfeuer\SecureDownloads\Resource\Event\OutputInitializationEvent`.

.. container:: table-row

   Property
        token
   Data Type
        `AbstractToken <https://github.com/Leuchtfeuer/typo3-secure-downloads/blob/master/Classes/Domain/Transfer/Token/AbstractToken.php>`__
   Description
        This property contains the decoded token object. You can manipulate the properties. The edited token is then used in the
        further process.

.. _developer-events-fileRetrieving-afterFileRetrieved:

After File Retrieved
--------------------
This event is executed after the access checks has been performed and both the file and the file name have been read from the
token. Afterwards, the check is made whether the file is available on the file system. The name of the event is
`Leuchtfeuer\SecureDownloads\Resource\Event\AfterFileRetrievedEvent`.

.. container:: table-row

   Property
        file
   Data Type
        string
   Description
        Contains the absolute path to the file on the file system. You can change this property.

.. container:: table-row

   Property
        fileName
   Data Type
        string
   Description
        Contains the name of the file. You can change this so that another file name is used when downloading this file.

.. _developer-events-fileRetrieving-beforeReadDeliver:

Before Read Deliver
-------------------
This event is executed just before the file is sent to the browser. It is the last chance to influence both the output function
and the headers sent.  The name of the event is `Leuchtfeuer\SecureDownloads\Resource\Event\BeforeReadDeliverEvent`.

.. container:: table-row

   Property
        outputFunction (deprecated)
   Data Type
        string
   Description
        Contains the output function as string. This property is deprecated and will be removed in further releases since the
        output function can only be one of `x-accel-redirect` or `stream`.

.. container:: table-row

   Property
        header
   Data Type
        array
   Description
        An array of header which will be sent to the browser. You can add your own headers or remove default ones.

.. container:: table-row

   Property
        fileName
   Data Type
        string
   Description
        The name of the file. This property is read-only.

.. container:: table-row

   Property
        mimeType
   Data Type
        string
   Description
        The mime type of the file. This property is read-only.

.. container:: table-row

   Property
        forceDownload
   Data Type
        boolean
   Description
        Information whether the file should be forced to download or not. This property is read-only.
