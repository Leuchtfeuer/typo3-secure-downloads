<?php
declare(strict_types = 1);
namespace Leuchtfeuer\SecureDownloads\Registry;

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
use Leuchtfeuer\SecureDownloads\Exception\ClassNotFoundException;
use Leuchtfeuer\SecureDownloads\Exception\InvalidClassException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TokenRegistry extends AbstractRegistry
{
    private static $token;

    public static function register(string $identifier, string $className, int $priority = 0, bool $overwriteExisting = false): void
    {
        if (self::$token instanceof AbstractToken && $overwriteExisting === false) {
            // Do nothing. Maybe log this in future.
            return;
        }

        self::$token[$identifier] = [
            'class' => self::getTokenFromClassName($className),
            'priority' => $priority
        ];

        self::sortByPriority(self::$token);
    }

    public static function getToken(): AbstractToken
    {
        return reset(self::$token)['class'];
    }

    private static function getTokenFromClassName(string $className): AbstractToken
    {
        if (!class_exists($className)) {
            throw new ClassNotFoundException(
                sprintf('Class "%s" not found.', $className),
                1588840845
            );
        }

        $token = GeneralUtility::makeInstance($className);

        if (!$token instanceof AbstractToken) {
            throw new InvalidClassException(
                sprintf('Class "%s" must extend "%s"', $className, AbstractToken::class),
                1588840850
            );
        }

        return $token;
    }
}
