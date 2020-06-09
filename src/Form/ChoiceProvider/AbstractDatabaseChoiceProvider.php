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

namespace PrestaShop\Module\LinkList\Form\ChoiceProvider;

use Doctrine\DBAL\Connection;
use PrestaShop\PrestaShop\Core\Form\FormChoiceProviderInterface;

/**
 * Class AbstractDatabaseChoiceProvider.
 */
abstract class AbstractDatabaseChoiceProvider implements FormChoiceProviderInterface
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var string
     */
    protected $dbPrefix;

    /**
     * @var int
     */
    protected $idLang;

    /**
     * @var array
     */
    protected $shopIds;

    /**
     * AbstractDatabaseChoiceProvider constructor.
     *
     * @param Connection $connection
     * @param string $dbPrefix
     * @param int|null $idLang
     * @param array|null $shopIds
     */
    public function __construct(Connection $connection, $dbPrefix, $idLang = null, array $shopIds = null)
    {
        $this->connection = $connection;
        $this->dbPrefix = $dbPrefix;
        $this->idLang = $idLang;
        $this->shopIds = $shopIds;
    }

    /**
     * {@inheritdoc}
     */
    abstract public function getChoices();
}
