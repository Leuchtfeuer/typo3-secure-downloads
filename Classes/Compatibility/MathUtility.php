<?php
/**
 * Created by JetBrains PhpStorm.
 * User: helmut
 * Date: 10.09.13
 * Time: 12:53
 * To change this template use File | Settings | File Templates.
 */

namespace Bitmotion\NawSecuredl\Compatibility;


class MathUtility {
	/**
	 * Forces the integer $theInt into the boundaries of $min and $max. If the $theInt is FALSE then the $defaultValue is applied.
	 *
	 * @param integer $theInt Input value
	 * @param integer $min Lower limit
	 * @param integer $max Higher limit
	 * @param integer $defaultValue Default value if input is FALSE.
	 * @return integer The input value forced into the boundaries of $min and $max
	 */
	static public function forceIntegerInRange($theInt, $min, $max = 2000000000, $defaultValue = 0) {
		return \t3lib_div::intInRange($theInt, $min, $max, $defaultValue);
	}
}