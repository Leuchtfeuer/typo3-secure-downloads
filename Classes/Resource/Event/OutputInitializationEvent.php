<?php
declare(strict_types = 1);
namespace Leuchtfeuer\SecureDownloads\Resource\Event;

/***
 *
 * This file is part of the "Secure Downloads" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2020 Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 *
 ***/

use Leuchtfeuer\SecureDownloads\Domain\Transfer\Token\AbstractToken;

final class OutputInitializationEvent
{
    /**
     * @var AbstractToken
     */
    private $token;

    public function __construct(AbstractToken $token)
    {
        $this->token = $token;
    }

    public function getToken(): AbstractToken
    {
        return $this->token;
    }

    public function setToken(AbstractToken $token): void
    {
        $this->token = $token;
    }
}
