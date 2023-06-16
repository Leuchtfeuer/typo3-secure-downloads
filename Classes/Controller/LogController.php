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

use Doctrine\DBAL\Exception;
use Leuchtfeuer\SecureDownloads\Domain\Repository\LogRepository;
use Leuchtfeuer\SecureDownloads\Domain\Transfer\Filter;
use Leuchtfeuer\SecureDownloads\Domain\Transfer\Statistic;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\Components\Menu\Menu;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Pagination\ArrayPaginator;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

class LogController extends ActionController
{
    const FILTER_SESSION_KEY = 'sdl-filter';

    /**
     * @var LogRepository $logRepository
     */
    protected LogRepository $logRepository;

    /**
     * @var ModuleTemplateFactory
     */
    protected ModuleTemplateFactory $moduleTemplateFactory;

    public function __construct(LogRepository $logRepository, ModuleTemplateFactory $moduleTemplateFactory)
    {
        $this->logRepository = $logRepository;
        $this->moduleTemplateFactory = $moduleTemplateFactory;
    }

    /**
     * @return ResponseInterface
     * @throws NoSuchArgumentException
     */
    public function initializeAction(): ResponseInterface
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
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addCssFile('EXT:secure_downloads/Resources/Public/Styles/Styles.css');
        return $this->htmlResponse('');
    }

    /**
     * @param Filter|null $filter The filter object
     * @return ResponseInterface
     * @throws Exception
     */
    public function listAction(?Filter $filter = null): ResponseInterface
    {
        $filter = $filter ?? unserialize($GLOBALS['BE_USER']->getSessionData(self::FILTER_SESSION_KEY)) ?? (new Filter());
        $filter->setPageId(0);
        $logEntries = $this->logRepository->findByFilter($filter);

        // Store filter data in session of backend user (used for pagination)
        $GLOBALS['BE_USER']->setSessionData(self::FILTER_SESSION_KEY, serialize($filter));

        $itemsPerPage = 20;
        $currentPage = GeneralUtility::_GP('currentPage') ? (int)GeneralUtility::_GP('currentPage') : 1;

        $paginator = new ArrayPaginator($logEntries->toArray(), $currentPage, $itemsPerPage);
        $pagination = new SimplePagination($paginator);

        $this->view->assignMultiple([
            'logs' => $paginator->getPaginatedItems(),
            'users' => $this->getUsers(),
            'fileTypes' => $this->getFileTypes(),
            'filter' => $filter,
            'statistic' => new Statistic($logEntries),
            'paginator' => $paginator,
            'pagination' => $pagination,
            'totalResultCount' => count($logEntries),
        ]);
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $this->createMenu();
        $moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($moduleTemplate->renderContent());
    }

    /**
     * @return array Array containing all users that have downloaded files
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
            ->fetchAll();
    }

    /**
     * @return array Array containing all used file types
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
            ->fetchAll();
    }

    /**
     * @param Filter|null $filter The filter object
     * @return ResponseInterface
     * @throws Exception
     */
    public function showAction(?Filter $filter = null): ResponseInterface
    {
        $pageId = (int)GeneralUtility::_GP('id');

        if ($pageId === 0) {
            return $this->redirect('list');
        }

        $filter = $filter ?? unserialize($GLOBALS['BE_USER']->getSessionData(self::FILTER_SESSION_KEY)) ?? (new Filter());
        $filter->setPageId($pageId);
        $logEntries = $this->logRepository->findByFilter($filter);

        // Store filter data in session of backend user (used for pagination)
        $GLOBALS['BE_USER']->setSessionData(self::FILTER_SESSION_KEY, serialize($filter));

        $itemsPerPage = 20;
        $currentPage = GeneralUtility::_GP('currentPage') ? (int)GeneralUtility::_GP('currentPage') : 1;

        $paginator = new ArrayPaginator($logEntries->toArray(), $currentPage, $itemsPerPage);
        $pagination = new SimplePagination($paginator);

        $this->view->assignMultiple([
            'logs' => $paginator->getPaginatedItems(),
            'page' => BackendUtility::getRecord('pages', $pageId),
            'users' => $this->getUsers(),
            'fileTypes' => $this->getFileTypes(),
            'filter' => $filter,
            'statistic' => new Statistic($logEntries),
            'paginator' => $paginator,
            'pagination' => $pagination,
            'totalResultCount' => count($logEntries),
        ]);

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $this->createMenu();
        $moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($moduleTemplate->renderContent());
    }

    /**
     * Set up the doc header properly here
     */
    public function initializeView(): void
    {
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addCssFile('EXT:secure_downloads/Resources/Public/Styles/Styles.css');
        $this->createMenu();
    }

    /**
     * Create menu
     */
    private function createMenu(): void
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $menu = $moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('secure_downloads');

        if ((int)GeneralUtility::_GP('id') !== 0) {
            $this->addMenuItems($menu);
        }

        $this->view->assign('action', $this->request->getControllerActionName());
        $moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
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
