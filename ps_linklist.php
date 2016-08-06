<?php
/*
 * 2007-2016 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
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
 *  @author PrestaShop SA <contact@prestashop.com>
 *  @copyright  2007-2016 PrestaShop SA
 *  @version  Release: $Revision: 7060 $
 *  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

 use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

if (!defined('_CAN_LOAD_FILES_')) {
    exit;
}

include_once(__DIR__ . '/src/LinkBlockRepository.php');
include_once(__DIR__ . '/src/LinkBlock.php');
include_once(__DIR__ . '/src/LinkBlockPresenter.php');

class Ps_LinkList extends Module implements WidgetInterface
{
    protected $_html;
    protected $_display;
    private $linkBlockPresenter;
    private $linkBlockRepository;

    public function __construct()
    {
        $this->name = 'ps_linklist';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'PrestaShop';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->getTranslator()->trans('Link List', array(), 'Modules.LinkList');
        $this->description = $this->getTranslator()->trans('Adds a block with several links.', array(), 'Modules.LinkList');
        $this->secure_key = Tools::encrypt($this->name);
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);

        $this->linkBlockPresenter = new LinkBlockPresenter(
            new Link(),
            $this->context->language
        );
        $this->linkBlockRepository = new LinkBlockRepository(
            Db::getInstance(),
            $this->context->shop
        );
    }

    public function install()
    {
        return parent::install()
            && $this->installTab()
            && $this->linkBlockRepository->createTables()
            && $this->linkBlockRepository->installFixtures()
            && $this->registerHook('displayFooter');
    }

    public function uninstall()
    {
        return parent::uninstall()
            && $this->uninstallTab()
            && $this->linkBlockRepository->dropTables();
    }

    public function installTab()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = "AdminLinkWidget";
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = "Link Widget";
        }
        $tab->id_parent = (int)Tab::getIdFromClassName('AdminParentThemes');
        $tab->module = $this->name;
        return $tab->add();
    }

    public function uninstallTab()
    {
        $id_tab = (int)Tab::getIdFromClassName('AdminLinkWidget');
        $tab = new Tab($id_tab);
        return $tab->delete();
    }

    public function getContent()
    {
        Tools::redirectAdmin(
            $this->context->link->getAdminLink('AdminLinkWidget')
        );
    }

    public function renderWidget($hookName, array $configuration)
    {
        $this->context->smarty->assign([
            'linkBlocks' => $this->getWidgetVariables($hookName, $configuration)
        ]);

        return $this->context->smarty->fetch('module:ps_linklist/views/templates/hook/linkblock.tpl');
    }

    public function getWidgetVariables($hookName, array $configuration)
    {
        $id_hook = Hook::getIdByName(isset($hookName) ? $hookName : $configuration['hook']);
        $linkBlocks = $this->linkBlockRepository->getByIdHook($id_hook);

        $blocks = array();
        foreach ($linkBlocks as $block) {
            $blocks[] = $this->linkBlockPresenter->present($block);
        }

        return $blocks;
    }
}
