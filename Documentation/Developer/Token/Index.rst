.. include:: ../../Includes.txt

.. _developer-token:

=====
Token
=====
You can simply add your own token or override existing ones and change their priorities. Your token has to extend the
`AbstractToken` class.

.. _developer-token-registerToken:

Register Token
==============
You can add following method call to your `ext_localconf.php` file:

.. code-block:: php

   \Leuchtfeuer\SecureDownloads\Registry\TokenRegistry::register(
       'tx_securedownloads_default',
       \Leuchtfeuer\SecureDownloads\Domain\Transfer\Token\DefaultToken::class,
       0,
       false
   );

Instead of `tx_securedownloads_default` you can use your own unique identifier. The second argument of that method contains the
class of your token. The third one mirrors the priority of your token and you can override existing tokens when you set the fourth
argument of this method to `true`.

.. _developer-token-example:

Example
=======
An example of how to register your own token can be found in the
`example extension <https://github.com/flossels/even-more-secure-downloads#create-your-own-token>`__. This example token uses an
RSA key pair to sign the token.

.. _developer-token-example-registerTheToken:

Register the Token
------------------
**ext_localconf.php**

.. code-block:: php

   \Leuchtfeuer\SecureDownloads\Registry\TokenRegistry::register(
       'tx_evenmoresecuredownloads_rsa',
       \Flossels\EvenMoreSecureDownloads\Domain\Transfer\Token\RsaToken::class,
       50,
       false
   );

.. _developer-token-example-theToken:

The Token
---------
**Classes/Domain/Transfer/Token/RsaToken.php**

.. code-block:: php

   class RsaToken extends AbstractToken
   {
       const PRIVATE_KEY_FILE = 'EXT:secure_downloads_example/Resources/Private/Keys/private.key';

       const PUBLIC_KEY_FILE = 'EXT:secure_downloads_example/Resources/Private/Keys/public.key';

       const CLAIMS = ['user', 'groups', 'file', 'page'];

       protected $extensionConfiguration;

       public function __construct()
       {
           parent::__construct();

           $this->extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
       }

       public function encode(?array $payload = null): string
       {
           $builder = new Builder();
           $builder->issuedBy($this->getIssuer());
           $builder->permittedFor($this->getPermittedFor());
           $builder->issuedAt($this->getIat());
           $builder->canOnlyBeUsedAfter($this->getIat());
           $builder->expiresAt($this->getExp());

           foreach (self::CLAIMS as $claim) {
               $getter = 'get' . ucfirst($claim);
               $builder->withClaim($claim, $this->$getter());
           }

           $signer = new Sha256();
           $key = new Key('file://' . GeneralUtility::getFileAbsFileName(self::PRIVATE_KEY_FILE));
           (new ClaimRepository())->addClaim($this);

           return (string)$builder->getToken($signer, $key);
       }

       public function getHash(): string
       {
           return md5(parent::getHash() . $this->getExp());
       }

       public function log(array $parameters = []): void
       {
           // TODO: Implement log() method.
       }

       public function decode(string $jsonWebToken): void
       {
           if (empty($jsonWebToken)) {
               throw new \Exception('Token is empty.', 1588852881);
           }

           $parsedToken = (new Parser())->parse($jsonWebToken);

           if (!$parsedToken->validate($this->getValidationData())) {
               throw new \Exception('Could not validate data.', 1588852940);
           }

           $signer = new Sha256();
           $key = new Key('file://' . GeneralUtility::getFileAbsFileName(self::PUBLIC_KEY_FILE));

           if (!$parsedToken->verify($signer, $key)) {
               throw new \Exception('Could not verify data.', 1588852970);
           }

           foreach ($parsedToken->getClaims() ?? [] as $claim) {
               /** @var $value Claim */
               if (property_exists(__CLASS__, $claim->getName())) {
                   $property = $claim->getName();
                   $this->$property = $claim->getValue();
               }
           }
       }

       protected function getIssuer(): string
       {
           $environmentService = GeneralUtility::makeInstance(EnvironmentService::class);

           if ($environmentService->isEnvironmentInFrontendMode()) {
               try {
                   $pageId = (int)$GLOBALS['TSFE']->id;
                   $base = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($pageId)->getBase();

                   if ($base->getScheme() !== null) {
                       $issuer = sprintf('%s://%s', $base->getScheme(), $base->getHost());
                   } else {
                       // Base of site configuration might be "/" so we have to retrieve the domain from the ENV
                       $issuer = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST');
                   }
               } catch (SiteNotFoundException $exception) {
                   $issuer = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST');
               }
           } elseif ($environmentService->isEnvironmentInBackendMode()) {
               $issuer = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST');
           }

           return $issuer ?? '';
       }

       protected function getPermittedFor(): string
       {
           return $this->extensionConfiguration->getDocumentRootPath() . $this->extensionConfiguration->getLinkPrefix();
       }

       protected function getValidationData(): ValidationData
       {
           $validationData = new ValidationData();
           $validationData->setIssuer($this->getIssuer());
           $validationData->setAudience($this->getPermittedFor());

           return $validationData;
       }
   }
