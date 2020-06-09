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

namespace PrestaShop\Module\LinkList\Model;

/**
 * Class LinkBlock.
 */
class LinkBlock extends \ObjectModel
{
    /**
     * @var int
     */
    public $id_link_block;

    /**
     * @var string
     */
    public $name;

    /**
     * @var int
     */
    public $id_hook;

    /**
     * @var int
     */
    public $position;

    /**
     * @var array
     */
    public $content;

    /**
     * @var array
     */
    public $custom_content;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'link_block',
        'primary' => 'id_link_block',
        'multilang' => true,
        'fields' => array(
            'name' => array('type' => self::TYPE_STRING, 'lang' => true, 'required' => true, 'size' => 128),
            'id_hook' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),
            'position' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true),
            'content' => array('type' => self::TYPE_STRING, 'validate' => 'isJson'),
            'custom_content' => array('type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isJson'),
        ),
    );

    public function __construct($id = null, $id_lang = null, $id_shop = null)
    {
        parent::__construct($id, $id_lang, $id_shop);

        if ($this->id) {
            $this->content = json_decode($this->content, true);
            if ($this->custom_content) {
                $this->custom_content = array_map(
                    function ($el) {
                        return json_decode($el, true);
                    },
                    $this->custom_content
                );
            }
        }

        if (is_null($this->content)) {
            $this->content = array(
                'cms' => array(),
                'product' => array(),
                'static' => array(),
            );
        }
    }

    public function add($auto_date = true, $null_values = false)
    {
        if (is_array($this->content)) {
            $this->content = json_encode($this->content);
        }

        if (!$this->position) {
            $this->position = 1;
        }

        $return = parent::add($auto_date, $null_values);
        $this->content = json_decode($this->content, true);

        return $return;
    }

    public function update($auto_date = true, $null_values = false)
    {
        if (is_array($this->content)) {
            $this->content = json_encode($this->content);
        }

        $return = parent::update($auto_date, $null_values);
        $this->content = json_decode($this->content, true);

        return $return;
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'id_link_block' => $this->id_link_block,
            'name' => $this->name,
            'id_hook' => $this->id_hook,
            'position' => $this->position,
            'content' => $this->content,
            'custom_content' => $this->custom_content,
        ];
    }
}
