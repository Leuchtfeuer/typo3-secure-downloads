<?php

declare(strict_types=1);

/*
 * This file is part of the "Secure Downloads" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) Dev <dev@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Leuchtfeuer\SecureDownloads\Resource\Event;

use Leuchtfeuer\SecureDownloads\Domain\Transfer\Token\AbstractToken;

/**
 * This event is executed after the JSON Web Token has been decoded and before the access checks take place.
 */
final class OutputInitializationEvent
{
    /**
     * @param AbstractToken $token This property contains the decoded token object. You can manipulate the properties. The edited
     *                             token is then used in the further process.
     */
    public function __construct(private AbstractToken $token) {}

    public function getToken(): AbstractToken
    {
        return $this->token;
    }

    public function setToken(AbstractToken $token): void
    {
        $this->token = $token;
    }
}
