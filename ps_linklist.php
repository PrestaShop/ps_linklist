<?php
/**
 * 2007-2018 PrestaShop.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2018 PrestaShop SA
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */
if (!defined('_CAN_LOAD_FILES_')) {
    exit;
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;
use PrestaShop\Module\LinkList\LegacyLinkBlockRepository;
use PrestaShop\Module\LinkList\Presenter\LinkBlockPresenter;
use PrestaShop\Module\LinkList\Model\LinkBlockLang;
use PrestaShop\Module\LinkList\Repository\LinkBlockRepository;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use PrestaShop\PrestaShop\Adapter\LegacyContext;
use PrestaShop\PrestaShop\Adapter\Shop\Context;

/**
 * Class Ps_Linklist.
 */
class Ps_Linklist extends Module implements WidgetInterface
{
    const MODULE_NAME = 'ps_linklist';

    protected $_html;
    protected $_display;
    /**
     * @var LinkBlockPresenter
     */
    private $linkBlockPresenter;
    /**
     * @var LegacyLinkBlockRepository
     */
    private $legacyBlockRepository;
    /**
     * @var LinkBlockRepository
     */
    private $repository;

    public $templateFile;

    public function __construct()
    {
        $this->name = 'ps_linklist';
        $this->author = 'PrestaShop';
        $this->version = '3.0.4';
        $this->need_instance = 0;
        $this->tab = 'front_office_features';
        $this->tabs = [
            [
                'class_name' => 'AdminLinkWidget',
                'visible' => true,
                'name' => 'Link Widget',
                'parent_class_name' => 'AdminParentThemes',
            ],
        ];

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->trans('Link List', array(), 'Modules.Linklist.Admin');
        $this->description = $this->trans('Adds a block with several links.', array(), 'Modules.Linklist.Admin');
        $this->secure_key = Tools::encrypt($this->name);

        $this->ps_versions_compliancy = array('min' => '1.7.5.0', 'max' => _PS_VERSION_);
        $this->templateFile = 'module:ps_linklist/views/templates/hook/linkblock.tpl';

        $this->linkBlockPresenter = new LinkBlockPresenter(new Link(), $this->context->language);
        $this->legacyBlockRepository = new LegacyLinkBlockRepository(Db::getInstance(), $this->context->shop, $this->context->getTranslator());
    }

    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        if (null !== $this->getRepository()) {
            $installed = $this->installFixtures();
        } else {
            $installed = $this->installLegacyFixtures();
        }

        if ($installed
            && $this->registerHook('displayFooter')
            && $this->registerHook('actionUpdateLangAfter')
            && $this->installTab()) {
            return true;
        }

        $this->uninstall();

        return false;
    }

    public function enable($force_all = false)
    {
        if (!$this->installTab()) {
            return false;
        }

        return parent::enable($force_all);
    }

    /**
     * @return bool
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function installFixtures()
    {
        $installed = true;
        $errors = $this->getRepository()->createTables();
        if (!empty($errors)) {
            $this->addModuleErrors($errors);
            $installed = false;
        }

        $errors = $this->getRepository()->installFixtures();
        if (!empty($errors)) {
            $this->addModuleErrors($errors);
            $installed = false;
        }

        return $installed;
    }

    /**
     * @return bool
     */
    private function installLegacyFixtures()
    {
        return $this->legacyBlockRepository->createTables() && $this->legacyBlockRepository->installFixtures();
    }

    public function uninstall()
    {
        $uninstalled = true;
        $errors = $this->getRepository()->dropTables();
        if (!empty($errors)) {
            $this->addModuleErrors($errors);
            $uninstalled = false;
        }

        return $uninstalled && parent::uninstall();
    }

    /**
     * The Core is supposed to register the tabs automatically thanks to the getTabs() return.
     * However in 1.7.5 it only works when the module contains a AdminLinkWidgetController file,
     * this works fine when module has been upgraded and the former file is still present however
     * for a fresh install we need to install it manually until the core is able to manage new modules.
     *
     * @return bool
     */
    public function installTab()
    {
        if (Tab::getIdFromClassName('AdminLinkWidget')) {
            return true;
        }

        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminLinkWidget';
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Link Widget';
        }
        $tab->id_parent = (int) Tab::getIdFromClassName('AdminParentThemes');
        $tab->module = $this->name;

        return $tab->add();
    }

    public function hookActionUpdateLangAfter($params)
    {
        if (!empty($params['lang']) && $params['lang'] instanceof Language) {
            Language::updateMultilangFromClass(_DB_PREFIX_ . 'link_block_lang', LinkBlockLang::class, $params['lang']);
        }
    }

    public function _clearCache($template, $cache_id = null, $compile_id = null)
    {
        parent::_clearCache($this->templateFile);
    }

    public function getContent()
    {
        Tools::redirectAdmin(
            $this->context->link->getAdminLink('AdminLinkWidget')
        );
    }

    public function renderWidget($hookName, array $configuration)
    {
        $key = 'ps_linklist|' . $hookName;

        if (!$this->isCached($this->templateFile, $this->getCacheId($key))) {
            $this->smarty->assign($this->getWidgetVariables($hookName, $configuration));
        }

        return $this->fetch($this->templateFile, $this->getCacheId($key));
    }

    public function getWidgetVariables($hookName, array $configuration)
    {
        $id_hook = Hook::getIdByName($hookName);

        $linkBlocks = $this->legacyBlockRepository->getByIdHook($id_hook);

        $blocks = array();
        foreach ($linkBlocks as $block) {
            $blocks[] = $this->linkBlockPresenter->present($block);
        }

        return array(
            'linkBlocks' => $blocks,
            'hookName' => $hookName,
        );
    }

    /**
     * @param array $errors
     */
    private function addModuleErrors(array $errors)
    {
        foreach ($errors as $error) {
            $this->_errors[] = $this->trans($error['key'], $error['parameters'], $error['domain']);
        }
    }

    /**
     * @return LinkBlockRepository|null
     */
    private function getRepository()
    {
        if (null === $this->repository && $this->isSymfonyContext()) {
            try {
                $this->repository = $this->get('prestashop.module.link_block.repository');
            } catch (\Exception $e) {
                //Module is not installed so its services are not loaded
                /** @var LegacyContext $context */
                $legacyContext = $this->get('prestashop.adapter.legacy.context');
                /** @var Context $shopContext */
                $shopContext = $this->get('prestashop.adapter.shop.context');
                $this->repository = new LinkBlockRepository(
                    $this->get('doctrine.dbal.default_connection'),
                    SymfonyContainer::getInstance()->getParameter('database_prefix'),
                    $legacyContext->getLanguages(true, $shopContext->getContextShopID()),
                    $this->get('translator')
                );
            }
        }

        return $this->repository;
    }
}
