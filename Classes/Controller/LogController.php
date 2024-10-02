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

namespace Leuchtfeuer\SecureDownloads\Controller;

use Doctrine\DBAL\Exception;
use Leuchtfeuer\SecureDownloads\Domain\Repository\LogRepository;
use Leuchtfeuer\SecureDownloads\Domain\Transfer\Filter;
use Leuchtfeuer\SecureDownloads\Domain\Transfer\Statistic;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class LogController extends ActionController
{
    public function __construct(
        protected ModuleTemplateFactory $moduleTemplateFactory,
        protected LogRepository $logRepository,
    ) {}

    public function initializeAction(): ResponseInterface
    {
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addCssFile('EXT:secure_downloads/Resources/Public/Styles/Styles.css');
        return $this->htmlResponse('');
    }

    /**
     * @param Filter|null $filter The filter object
     * @return ResponseInterface
     * @throws Exception
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     */
    public function listAction(?Filter $filter = null): ResponseInterface
    {
        if ($this->request->hasArgument('reset') && (bool)$this->request->getArgument('reset')) {
            $filter = new Filter();
        } elseif (!$filter instanceof Filter) {
            $filter = $this->getFilterFromBeUserData();
        }

        $extensionConfigurationLogging = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('secure_downloads', 'log') ?? 0;

        $pageId = (int)(array_key_exists('id', $this->request->getQueryParams()) ? $this->request->getQueryParams()['id'] : 0);
        $filter->setPageId($pageId);

        $this->persistFilterInBeUserData($filter);
        $this->resetFilterOnMemoryExhaustionError();

        $itemsPerPage = 20;
        $currentPage = (int)(array_key_exists('currentPage', $this->request->getQueryParams()) && $this->request->getQueryParams()['currentPage'] > 0 ? $this->request->getQueryParams()['currentPage'] : 1);
        $logEntries = $this->logRepository->findByFilter($filter, $currentPage, $itemsPerPage);

        $totalResultsCount = $this->logRepository->countByFilter($filter);
        $totalPages = (int)(ceil($totalResultsCount / $itemsPerPage));

        $statistic = new Statistic();
        $statistic->calc($filter, $this->logRepository);

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->assignMultiple([
            'loggingEnabled' => $extensionConfigurationLogging,
            'logs' => $logEntries,
            'page' => BackendUtility::getRecord('pages', $pageId),
            'users' => $this->getUsers(),
            'fileTypes' => $this->getFileTypes(),
            'filter' => $filter,
            'statistic' => $statistic,
            'pagination' => [
                'totalPages' => $totalPages,
                'currentPage' => $currentPage,
                'previousPage' => max($currentPage - 1, 0),
                'nextPage' => $totalPages > $currentPage ? $currentPage + 1 : 0,
            ],
            'totalResultCount' => $totalResultsCount,
            'isRoot' => $pageId == 0,
        ]);
        return $moduleTemplate->renderResponse('List');
    }

    /**
     * @return list<array<string,mixed>> Array containing all users that have downloaded files
     * @throws Exception
     */
    private function getUsers(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_securedownloads_domain_model_log');

        return $queryBuilder
            ->select('users.uid as uid', 'users.username as username')
            ->from('tx_securedownloads_domain_model_log', 'log')
            ->join('log', 'fe_users', 'users', $queryBuilder->expr()->eq('users.uid', 'log.user'))
            ->where($queryBuilder->expr()->neq('user', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)))
            ->groupBy('users.uid')
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * @return list<array<string,mixed>> Array containing all used file types
     * @throws Exception
     */
    private function getFileTypes(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_securedownloads_domain_model_log');

        return $queryBuilder
            ->select('media_type')
            ->from('tx_securedownloads_domain_model_log')
            ->groupBy('media_type')->orderBy('media_type', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * Get module states (the filter object) from user data
     */
    protected function getFilterFromBeUserData(): Filter
    {
        $serializedConstraint = $this->request->getAttribute('moduleData')->get('filter');
        $filter = null;
        if (is_string($serializedConstraint) && !empty($serializedConstraint)) {
            $filter = @unserialize($serializedConstraint, ['allowed_classes' => [Filter::class, \DateTime::class]]);
        }
        return $filter ?: GeneralUtility::makeInstance(Filter::class);
    }

    /**
     * Save current filter object in be user settings (uC)
     */
    protected function persistFilterInBeUserData(Filter $filter): void
    {
        $moduleData = $this->request->getAttribute('moduleData');
        $moduleData->set('filter', serialize($filter));
        $this->getBackendUser()->pushModuleData($moduleData->getModuleIdentifier(), $moduleData->toArray());
    }

    /**
     * In case the script execution fails, because the user requested too many results
     * (memory exhaustion in php), reset the filters in be user settings, so
     * the belog can be accessed again in the next call.
     */
    protected function resetFilterOnMemoryExhaustionError(): void
    {
        $reservedMemory = new \SplFixedArray(187500); // 3M
        register_shutdown_function(function () use (&$reservedMemory): void {
            $reservedMemory = null; // free the reserved memory
            $error = error_get_last();
            if (str_contains($error['message'] ?? '', 'Allowed memory size of')) {
                $filter = GeneralUtility::makeInstance(Filter::class);
                $this->persistFilterInBeUserData($filter);
            }
        });
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
