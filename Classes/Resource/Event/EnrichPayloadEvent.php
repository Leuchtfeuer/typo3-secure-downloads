<?php
declare(strict_types = 1);
namespace Leuchtfeuer\SecureDownloads\Resource\Event;

use Leuchtfeuer\SecureDownloads\Domain\Transfer\Token\AbstractToken;

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
