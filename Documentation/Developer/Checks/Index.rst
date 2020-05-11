.. include:: ../../Includes.txt

.. _developer-securityChecks:

===============
Security Checks
===============
You can simply add your own security check or override existing ones and change their priorities. Your check has to extend the
`AbstractCheck` class.

.. _deveolper-securityChecks-registerSecurityCheck:

Register Security Check
========================
You can add following method call to your `ext_localconf.php` file:

.. code-block:: php

   \Leuchtfeuer\SecureDownloads\Registry\CheckRegistry::register(
       'tx_securedownloads_group',
       \Leuchtfeuer\SecureDownloads\Security\UserGroupCheck::class,
       10,
       true
   );

Instead of `tx_securedownloads_group` you can use your own unique identifier. The second argument of that method contains the
class of your check. The third one mirrors the priority of your check and you can override existing checks when you set the fourth
argument of that method to `true`.

.. _deveolper-securityChecks-example:

Example
=======
An example of how to register your own security check can be found in the
`example extension <https://github.com/flossels/even-more-secure-downloads#add-your-own-security-check>`__. This example check
allows only a single access to a file. On the second call, the link is identified as invalid and the server returns a 403 status
code.

.. _deveolper-securityChecks-example-registerTheCheck:

Register the Check
------------------
**ext_localconf.php**

.. code-block:: php

   \Leuchtfeuer\SecureDownloads\Registry\CheckRegistry::register(
       'tx_evenmoresecuredownloads_once',
       \Flossels\EvenMoreSecureDownloads\Security\OneTimeCheck::class,
       50,
       true
   );

.. _deveolper-securityChecks-example-theSecurityCheck:

The Security Check
------------------
**Classes/Security/OneTimeCheck.php**

.. code-block:: php

   class OneTimeCheck extends AbstractCheck
   {
       /**
        * @var RsaToken
        */
       protected $token;

       public function hasAccess(): bool
       {
           $claimRepository = new ClaimRepository();

           if (!$claimRepository->isClaimed($this->token)) {
               $claimRepository->setClaimed($this->token);

               return true;
           }

           return false;
       }
   }
