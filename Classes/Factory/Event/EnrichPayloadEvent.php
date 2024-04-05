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

namespace Leuchtfeuer\SecureDownloads\Factory\Event;

use Leuchtfeuer\SecureDownloads\Domain\Transfer\Token\AbstractToken;

/**
 * You can use this event for extending or manipulating the payload of the JSON Web Token. This event is executed immediately
 * before the JSON Web token is generated.
 */
final class EnrichPayloadEvent
{
    /**
     * @var array
     */
    private $payload;

    /**
     * @var AbstractToken
     */
    private $token;

    /**
     * @param array         $payload This array contains the default payload of the JSON Web Token. You can enrich this data by
     *                               your own properties or manipulate the existing data.
     * @param AbstractToken $token   This property is read-only and contains the generated token object.
     */
    public function __construct(array $payload, AbstractToken $token)
    {
        $this->payload = $payload;
        $this->token = $token;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function setPayload(array $payload): void
    {
        $this->payload = $payload;
    }

    public function getToken(): AbstractToken
    {
        return $this->token;
    }
}
