<?php

namespace Leuchtfeuer\SecureDownloads\Factory;

use Leuchtfeuer\SecureDownloads\Domain\Transfer\Token\AbstractToken;
use Leuchtfeuer\SecureDownloads\Domain\Transfer\Token\DefaultToken;

class TokenFactory
{
    public function buildToken(?string $jsonWebToken = null): AbstractToken
    {
        $tokenClass = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['secure_downloads']['tokenClass'] ?? DefaultToken::class;

        if (!class_exists($tokenClass)) {
            // TODO: Exception
        }

        $token = new $tokenClass($jsonWebToken);

        if (!$token instanceof AbstractToken) {
            // TODO: Exception
        }

        return $token;
    }
}
