<?php
declare(strict_types = 1);
namespace Bitmotion\SecureDownloads\Domain\Model;

/***
 *
 * This file is part of the "Secure Downloads" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2019 Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 *
 ***/

/**
 * @deprecated Will be removed with Version 5. Use Filter DTO instead.
 */
class Filter extends \Bitmotion\SecureDownloads\Domain\Transfer\Filter
{
    public function __construct()
    {
        trigger_error('Class Filter is deprecated. Use Filter DTO instead.', E_USER_DEPRECATED);

        parent::__construct();
    }
}
