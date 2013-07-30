<?php
/**
 * Created by JetBrains PhpStorm.
 * User: heise
 * Date: 22.07.13
 * Time: 13:36
 * To change this template use File | Settings | File Templates.
 */

include_once('SecuredlService.php');

/**
 * @deprecated
 */
class Tx_NawSecuredlService extends \Bm\Securedl\Service\SecuredlService {

}

// Include extension?
// Deprecated, only used for TYPO3 < 6.0
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/naw_securedl/Classes/Driver/Xclass/Tx_NawSecuredlService.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/naw_securedl/Classes/Driver/Xclass/Tx_NawSecuredlService.php']);
}
?>