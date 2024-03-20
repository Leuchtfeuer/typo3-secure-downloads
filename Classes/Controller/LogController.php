<?php

declare(strict_types=1);
namespace Leuchtfeuer\SecureDownloads\Controller;

/***
 *
 * This file is part of the "Secure Downloads" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2019 Dev <dev@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 *
 ***/

use Leuchtfeuer\SecureDownloads\Domain\Repository\LogRepository;
use Leuchtfeuer\SecureDownloads\Domain\Transfer\Filter;
use Leuchtfeuer\SecureDownloads\Domain\Transfer\Statistic;
use TYPO3\CMS\Backend\Template\Components\Menu\Menu;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Pagination\ArrayPaginator;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

class LogController extends ActionController
{
    const FILTER_SESSION_KEY = 'sdl-filter';
    /**
     * @var BackendTemplateView
     */
    protected $view;

    protected $defaultViewObjectName = BackendTemplateView::class;

    protected $logRepository;

    public function __construct(LogRepository $logRepository)
    {
        $this->logRepository = $logRepository;
    }

    /**
     * @throws NoSuchArgumentException
     */
    public function initializeAction(): void
    {
        parent::initializeAction();

        if ($this->arguments->hasArgument('filter')) {
            $this->arguments->getArgument('filter')->getPropertyMappingConfiguration()->allowAllProperties();
        }

        if ($this->request->hasArgument('reset') && (bool)$this->request->getArgument('reset') === true) {
            $GLOBALS['BE_USER']->setSessionData(self::FILTER_SESSION_KEY, serialize(new Filter()));
        }

        if ($GLOBALS['BE_USER']->getSessionData(self::FILTER_SESSION_KEY) === null) {
            $GLOBALS['BE_USER']->setSessionData(self::FILTER_SESSION_KEY, serialize(new Filter()));
        }
    }

    /**
     * @param Filter|null $filter The filter object
     */
    public function listAction(?Filter $filter = null): void
    {
        $filter = $filter ?? unserialize($GLOBALS['BE_USER']->getSessionData(self::FILTER_SESSION_KEY)) ?? (new Filter());
        $filter->setPageId(0);
        $currentPage = GeneralUtility::_GP('currentPage') ? (int)GeneralUtility::_GP('currentPage') : 1;
        $itemsPerPage = 20;

        $logEntries = $this->logRepository->findByFilter($filter, $currentPage, $itemsPerPage);
        $totalResultsCount = $this->logRepository->countByFilter($filter);
        $totalPages = (int)(ceil($totalResultsCount / 20));
        $statistics = new Statistic($logEntries);
        $statistics->setTraffic($this->logRepository->getTrafficSumByFilter($filter));

        // Store filter data in session of backend user (used for pagination)
        $GLOBALS['BE_USER']->setSessionData(self::FILTER_SESSION_KEY, serialize($filter));

        $this->view->assignMultiple([
            'logs' => $logEntries,
            'users' => $this->getUsers(),
            'fileTypes' => $this->getFileTypes(),
            'filter' => $filter,
            'statistic' => $statistics,
            'pagination' => [
                'totalPages' => $totalPages,
                'currentPage' => $currentPage,
                'previousPage' => ($currentPage - 1) > 0 ? $currentPage - 1 : null,
                'nextPage' => $totalPages > $currentPage ? $currentPage + 1 : null,
            ],
            'totalResultCount' => $totalResultsCount,
        ]);
    }

    /**
     * @return array Array containing all users that have downloaded files
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
            ->execute()
            ->fetchAll();
    }

    /**
     * @return array Array containing all used file types
     */
    private function getFileTypes(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_securedownloads_domain_model_log');

        return $queryBuilder
            ->select('media_type')
            ->from('tx_securedownloads_domain_model_log')
            ->groupBy('media_type')
            ->orderBy('media_type', 'ASC')
            ->execute()
            ->fetchAll();
    }

    /**
     * @param Filter|null $filter The filter object
     * @throws StopActionException
     */
    public function showAction(?Filter $filter = null): void
    {
        $pageId = (int)GeneralUtility::_GP('id');

        if ($pageId === 0) {
            $this->redirect('list');
        }

        $filter = $filter ?? unserialize($GLOBALS['BE_USER']->getSessionData(self::FILTER_SESSION_KEY)) ?? (new Filter());
        $filter->setPageId($pageId);

        // Store filter data in session of backend user (used for pagination)
        $GLOBALS['BE_USER']->setSessionData(self::FILTER_SESSION_KEY, serialize($filter));

        $itemsPerPage = 20;
        $currentPage = GeneralUtility::_GP('currentPage') ? (int)GeneralUtility::_GP('currentPage') : 1;

        $logEntries = $this->logRepository->findByFilter($filter, $currentPage, $itemsPerPage);
        $totalResultsCount = $this->logRepository->countByFilter($filter);
        $totalPages = (int)(ceil($totalResultsCount / 20));
        $statistics = new Statistic($logEntries);
        $statistics->setTraffic($this->logRepository->getTrafficSumByFilter($filter));

        $this->view->assignMultiple([
            'logs' => $logEntries,
            'page' => BackendUtility::getRecord('pages', $pageId),
            'users' => $this->getUsers(),
            'fileTypes' => $this->getFileTypes(),
            'filter' => $filter,
            'statistic' => $statistics,
            'pagination' => [
                'totalPages' => $totalPages,
                'currentPage' => $currentPage,
                'previousPage' => ($currentPage - 1) > 0 ? $currentPage - 1 : null,
                'nextPage' => $totalPages > $currentPage ? $currentPage + 1 : null,
            ],
            'totalResultCount' => $totalResultsCount,
        ]);
    }

    /**
     * Set up the doc header properly here
     */
    public function initializeView(ViewInterface $view): void
    {
        parent::initializeView($view);

        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addCssFile('EXT:secure_downloads/Resources/Public/Styles/Styles.css');
        $this->createMenu();
    }

    /**
     * Create menu
     */
    private function createMenu(): void
    {
        $menu = $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('secure_downloads');

        if ((int)GeneralUtility::_GP('id') !== 0) {
            $this->addMenuItems($menu);
        }

        $this->view->assign('action', $this->request->getControllerActionName());
        $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
    }

    /**
     * Adds menu options to the select menu
     *
     * @param Menu $menu The Menu object
     */
    protected function addMenuItems(Menu &$menu): void
    {
        $controllerName = $this->request->getControllerName();
        $controllerActionName = $this->request->getControllerActionName();
        $actions = [
            ['controller' => 'Log', 'action' => 'show', 'label' => 'Show by Page'],
            ['controller' => 'Log', 'action' => 'list', 'label' => 'Overview'],
        ];

        foreach ($actions as $action) {
            $isActive = $controllerName === $action['controller'] && $controllerActionName === $action['action'];

            $href = $this->getUriBuilder()->reset()->uriFor(
                $action['action'],
                [],
                $action['controller']
            );

            $item = $menu->makeMenuItem()->setTitle($action['label'])->setHref($href)->setActive($isActive);
            $menu->addMenuItem($item);
        }
    }

    /**
     * @return UriBuilder The URI builder
     */
    protected function getUriBuilder(): UriBuilder
    {
        $uriBuilder = $this->objectManager->get(UriBuilder::class);
        $uriBuilder->setRequest($this->request);

        return $uriBuilder;
    }
}
