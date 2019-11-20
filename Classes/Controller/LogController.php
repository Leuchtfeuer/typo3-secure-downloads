<?php
declare(strict_types=1);
namespace Bitmotion\SecureDownloads\Controller;

/***
 *
 * This file is part of the "Secure Downloads" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2019 Florian Wessels <f.wessels@bitmotion.de>, Bitmotion GmbH
 *
 ***/

use Bitmotion\SecureDownloads\Domain\Model\Filter;
use Bitmotion\SecureDownloads\Domain\Model\Statistic;
use Bitmotion\SecureDownloads\Domain\Repository\LogRepository;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Frontend\Page\PageRepository;

class LogController extends ActionController
{
    /**
     * @var BackendTemplateView
     */
    protected $view;

    protected $defaultViewObjectName = BackendTemplateView::class;

    protected $logRepository;

    protected $pageRepository = null;

    public function __construct(LogRepository $logRepository, PageRepository $pageRepository)
    {
        $this->logRepository = $logRepository;
        $this->pageRepository = $pageRepository;

        parent::__construct();
    }

    /**
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     */
    public function initializeAction()
    {
        parent::initializeAction();

        if ($this->arguments->hasArgument('filter')) {
            $this->arguments->getArgument('filter')->getPropertyMappingConfiguration()->allowAllProperties();
        }
    }

    /**
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    public function listAction(Filter $filter = null)
    {
        $logEntries = $this->logRepository->findByFilter($filter);

        $this->view->assignMultiple([
            'logs' => $logEntries,
            'users' => $this->getUsers(),
            'fileTypes' => $this->getFileTypes(),
            'filter' => $filter,
            'statistic' => new Statistic($logEntries),
        ]);
    }

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
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
     */
    public function showAction(Filter $filter = null)
    {
        $pageId = (int)GeneralUtility::_GP('id');

        if ($pageId == 0) {
            $this->redirect('list');
        }

        if ($filter === null) {
            $filter = new Filter();
        }

        $filter->setPageId($pageId);

        $logEntries = $this->logRepository->findByFilter($filter);

        $this->view->assignMultiple([
            'logs' => $logEntries,
            'page' => $this->pageRepository->getPage($pageId),
            'users' => $this->getUsers(),
            'fileTypes' => $this->getFileTypes(),
            'filter' => $filter,
            'statistic' => new Statistic($logEntries),
        ]);
    }

    /**
     * Set up the doc header properly here
     *
     * @throws \InvalidArgumentException
     */
    public function initializeView(ViewInterface $view)
    {
        parent::initializeView($view);

        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addCssFile('EXT:secure_downloads/Resources/Public/Styles/Styles.css');
        $this->createMenu();
    }

    /**
     * Create menu
     * @throws \InvalidArgumentException
     */
    private function createMenu()
    {
        $menu = $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('secure_downloads');

        if ((int)GeneralUtility::_GP('id') !== 0) {
            $actions = [
                ['controller' => 'Log', 'action' => 'show', 'label' => 'Show by Page'],
                ['controller' => 'Log', 'action' => 'list', 'label' => 'Overview'],
            ];

            foreach ($actions as $action) {
                $isActive = $this->request->getControllerName() === $action['controller'] && $this->request->getControllerActionName() === $action['action'];
                $item = $menu->makeMenuItem()->setTitle($action['label'])->setHref($this->getUriBuilder()->reset()->uriFor(
                    $action['action'],
                    [],
                    $action['controller']
                ))->setActive($isActive);
                $menu->addMenuItem($item);
            }
        }

        $this->view->assign('action', $this->request->getControllerActionName());

        $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
    }

    protected function getUriBuilder(): UriBuilder
    {
        $uriBuilder = $this->objectManager->get(UriBuilder::class);
        $uriBuilder->setRequest($this->request);

        return $uriBuilder;
    }
}
