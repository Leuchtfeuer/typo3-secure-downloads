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

namespace Leuchtfeuer\SecureDownloads\ViewHelpers;

use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class GetUsernameByUidViewHelper extends AbstractViewHelper
{
    public function __construct(private readonly ConnectionPool $connectionPool) {}

    public function initializeArguments(): void
    {
        $this->registerArgument('uid', 'int', 'Uid of fe_user.', true);
    }

    #[\Override]
    public function render(): string
    {
        if (isset($this->arguments['uid']) && (int)$this->arguments['uid'] > 0) {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable('fe_users');

            $username = $queryBuilder
                ->select('username')
                ->from('fe_users')
                ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter((int)$this->arguments['uid'], ParameterType::INTEGER)))
                ->executeQuery()
                ->fetchAssociative()['username'];

            return is_string($username) ? $username : '';
        }
        return '';
    }
}
