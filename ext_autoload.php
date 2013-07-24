<?php

$extensionPath = t3lib_extMgm::extPath('naw_securedl');
return array(
'Bm\Securedl\Service\SecuredlService' => $extensionPath . '/Classes/Service/SecuredlService.php',
'Bm\Securedl\Driver\Xclass\LocalDriver' => $extensionPath . '/Classes/Driver/Xclass/LocalDriver.php',

);



?>