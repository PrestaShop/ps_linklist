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

namespace PrestaShop\Module\LinkList\Cache;

use PrestaShop\PrestaShop\Core\Addon\Module\ModuleRepository;
use Ps_Linklist;

/**
 * Class LegacyBlockCache.
 */
final class LegacyLinkBlockCache implements LinkBlockCacheInterface
{
    /**
     * @var ModuleRepository
     */
    private $moduleRepository;

    /**
     * LegacyLinkBlockCache constructor.
     *
     * @param ModuleRepository $moduleRepository
     */
    public function __construct(ModuleRepository $moduleRepository)
    {
        $this->moduleRepository = $moduleRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function clearModuleCache()
    {
        /** @var Ps_Linklist $module */
        $module = $this->moduleRepository->getInstanceByName(Ps_Linklist::MODULE_NAME);
        $module->_clearCache($module->templateFile);
    }
}
