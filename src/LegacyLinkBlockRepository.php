<?php
/**
 * 2007-2020 PrestaShop SA and Contributors
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
 * needs please refer to https://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\Module\LinkList;

use PrestaShop\Module\LinkList\Model\LinkBlock;
use Symfony\Component\Translation\TranslatorInterface as Translator;
use Shop;
use Hook;
use DB;
use Language;
use Context;

/**
 * Class LegacyLinkBlockRepository.
 */
class LegacyLinkBlockRepository
{
    private $db;
    private $shop;
    private $db_prefix;
    private $translator;

    /**
     * @param DB $db
     * @param Shop $shop
     * @param Translator $translator
     */
    public function __construct(Db $db, Shop $shop, Translator $translator)
    {
        $this->db = $db;
        $this->shop = $shop;
        $this->db_prefix = $db->getPrefix();
        $this->translator = $translator;
    }

    /**
     * @param int $id_hook
     *
     * @return array
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function getByIdHook($id_hook)
    {
        $id_hook = (int) $id_hook;

        $sql = "SELECT cb.`id_link_block`
                    FROM {$this->db_prefix}link_block cb
                    WHERE `id_hook` = $id_hook
                    ORDER by cb.`position`
                ";
        $ids = $this->db->executeS($sql);

        $cmsBlock = array();
        foreach ($ids as $id) {
            $cmsBlock[] = new LinkBlock((int) $id['id_link_block']);
        }

        return $cmsBlock;
    }

    /**
     * @return bool
     */
    public function createTables()
    {
        $engine = _MYSQL_ENGINE_;
        $success = true;
        $this->dropTables();
        $queries = [
            "CREATE TABLE IF NOT EXISTS `{$this->db_prefix}link_block`(
    			`id_link_block` int(10) unsigned NOT NULL auto_increment,
    			`id_hook` int(1) unsigned DEFAULT NULL,
    			`position` int(10) unsigned NOT NULL default '0',
    			`content` text default NULL,
    			PRIMARY KEY (`id_link_block`)
            ) ENGINE=$engine DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `{$this->db_prefix}link_block_lang`(
    			`id_link_block` int(10) unsigned NOT NULL,
    			`id_lang` int(10) unsigned NOT NULL,
    			`name` varchar(40) NOT NULL default '',
    			`custom_content` text default NULL,
    			PRIMARY KEY (`id_link_block`, `id_lang`)
            ) ENGINE=$engine DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `{$this->db_prefix}link_block_shop` (
    			`id_link_block` int(10) unsigned NOT NULL auto_increment,
    			`id_shop` int(10) unsigned NOT NULL,
    			PRIMARY KEY (`id_link_block`, `id_shop`)
            ) ENGINE=$engine DEFAULT CHARSET=utf8",
        ];
        foreach ($queries as $query) {
            $success &= $this->db->execute($query);
        }

        return $success;
    }

    public function dropTables()
    {
        $sql = "DROP TABLE IF EXISTS
			`{$this->db_prefix}link_block`,
			`{$this->db_prefix}link_block_lang`,
			`{$this->db_prefix}link_block_shop`";

        return $this->db->execute($sql);
    }

    /**
     * @return bool
     */
    public function installFixtures()
    {
        $success = true;
        $id_hook = (int) Hook::getIdByName('displayFooter');
        $queries = [
            'INSERT INTO `' . $this->db_prefix . 'link_block` (`id_link_block`, `id_hook`, `position`, `content`) VALUES
                (1, ' . $id_hook . ', 0, \'{"cms":[false],"product":["prices-drop","new-products","best-sales"],"static":[false]}\'),
                (2, ' . $id_hook . ', 1, \'{"cms":["1","2","3","4","5"],"product":[false],"static":["contact","sitemap","stores"]}\');',
        ];
        foreach (Language::getLanguages(true, Context::getContext()->shop->id) as $lang) {
            $queries[] = 'INSERT INTO `' . $this->db_prefix . 'link_block_lang` (`id_link_block`, `id_lang`, `name`) VALUES
                (1, ' . (int) $lang['id_lang'] . ', "' . pSQL($this->translator->trans('Products', array(), 'Modules.Linklist.Shop', $lang['locale'])) . '"),
                (2, ' . (int) $lang['id_lang'] . ', "' . pSQL($this->translator->trans('Our company', array(), 'Modules.Linklist.Shop', $lang['locale'])) . '")'
            ;
        }
        foreach ($queries as $query) {
            $success &= $this->db->execute($query);
        }

        return $success;
    }
}
