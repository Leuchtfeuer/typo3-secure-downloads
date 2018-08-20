<?php
namespace Bitmotion\SecureDownloads\Controller;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2016 Bitmotion GmbH (typo3-ext@bitmotion.de)
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use Bitmotion\SecureDownloads\Domain\Model\Filter;
use Bitmotion\SecureDownloads\Domain\Model\Statistic;
use Bitmotion\SecureDownloads\Domain\Repository\LogRepository;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Class LogController
 * @package Bitmotion\SecureDownloads\Controller
 */
class LogController extends ActionController
{
    /**
     * @var BackendTemplateView
     */
    protected $view;

    /**
     * @var BackendTemplateView
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * logRepository
     *
     * @var LogRepository
     */
    protected $logRepository;

    /**
     * pageRepository
     *
     * @var \TYPO3\CMS\Frontend\Page\PageRepository
     */
    protected $pageRepository = null;

    /**
     * @param LogRepository $logRepository
     */
    public function injectLogRepository(LogRepository $logRepository)
    {
        $this->logRepository = $logRepository;
    }

    /**
     * @param PageRepository $pageRepository
     */
    public function injectPageRepository(PageRepository $pageRepository)
    {
        $this->pageRepository = $pageRepository;
    }

    public function initializeAction()
    {
        parent::initializeAction();

        if ($this->arguments->hasArgument('filter')) {
            $this->arguments->getArgument('filter')->getPropertyMappingConfiguration()->allowAllProperties();
        }
    }

    /**
     * action list
     *
     * @param Filter $filter
     *
     * @return void
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

    /**
     * @return array
     */
    private function getUsers()
    {
        $users = [];
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'user',
            'tx_securedownloads_domain_model_log', 'user != 0',
            'user'
        );

        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
            $getUserRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'fe_users', 'uid = ' . $row['user']);
            $users[] = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($getUserRes);
        }

        return $users;
    }

    /**
     * @return array
     */
    private function getFileTypes()
    {
        $fileTypes = [];

        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'media_type',
            'tx_securedownloads_domain_model_log',
            '',
            'media_type',
            'media_type ASC'
        );

        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
            $fileTypes[] = ['title' => $row['media_type']];
        }

        return $fileTypes;
    }

    /**
     * action show
     *
     * @param Filter $filter
     *
     * @return void
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
     * @param ViewInterface $view
     *
     * @throws \InvalidArgumentException
     */
    public function initializeView(ViewInterface $view)
    {
        /** @var BackendTemplateView $view */
        parent::initializeView($view);

        /** @var PageRenderer $pageRenderer */
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addCssFile(ExtensionManagementUtility::extRelPath('secure_downloads') . 'Resources/Public/Styles/Styles.css');
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
                $item = $menu->makeMenuItem()->setTitle($action['label'])->setHref($this->getUriBuilder()->reset()->uriFor($action['action'],
                    [], $action['controller']))->setActive($isActive);
                $menu->addMenuItem($item);
            }
        }

        $this->view->assign('action', $this->request->getControllerActionName());

        $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
    }

    /**
     * @return UriBuilder
     */
    protected function getUriBuilder()
    {
        /** @var UriBuilder $uriBuilder */
        $uriBuilder = $this->objectManager->get(UriBuilder::class);
        $uriBuilder->setRequest($this->request);

        return $uriBuilder;
    }

}