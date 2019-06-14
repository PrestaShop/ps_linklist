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

namespace PrestaShop\Module\LinkList\Form;

use PrestaShop\Module\LinkList\Cache\LinkBlockCacheInterface;
use PrestaShop\Module\LinkList\Model\LinkBlock;
use PrestaShop\Module\LinkList\Repository\LinkBlockRepository;
use PrestaShop\PrestaShop\Core\Addon\Module\ModuleRepository;
use PrestaShop\PrestaShop\Core\Form\FormDataProviderInterface;
use Hook;
use Ps_Linklist;

/**
 * Class LinkBlockFormDataProvider.
 */
class LinkBlockFormDataProvider implements FormDataProviderInterface
{
    /**
     * @var int|null
     */
    private $idLinkBlock;

    /**
     * @var LinkBlockRepository
     */
    private $repository;

    /**
     * @var LinkBlockCacheInterface
     */
    private $cache;

    /**
     * @var ModuleRepository
     */
    private $moduleRepository;

    /**
     * @var array
     */
    private $languages;

    /**
     * @var int
     */
    private $shopId;

    /**
     * LinkBlockFormDataProvider constructor.
     *
     * @param LinkBlockRepository $repository
     * @param LinkBlockCacheInterface $cache
     * @param ModuleRepository $moduleRepository
     * @param array $languages
     * @param int $shopId
     */
    public function __construct(
        LinkBlockRepository $repository,
        LinkBlockCacheInterface $cache,
        ModuleRepository $moduleRepository,
        array $languages,
        $shopId
    ) {
        $this->repository = $repository;
        $this->cache = $cache;
        $this->moduleRepository = $moduleRepository;
        $this->languages = $languages;
        $this->shopId = $shopId;
    }

    /**
     * @return array
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function getData()
    {
        if (null === $this->idLinkBlock) {
            return [];
        }

        $linkBlock = new LinkBlock($this->idLinkBlock);

        $arrayLinkBlock = $linkBlock->toArray();

        //The form and the database model don't have the same data hierarchy
        //Transform array $custom[en][1][name] to $custom[1][en][name]
        $arrayCustom = [];
        foreach ($arrayLinkBlock['custom_content'] as $idLang => $customs) {
            if (!is_array($customs)) {
                continue;
            }

            foreach ($customs as $i => $custom) {
                $arrayCustom[$i][$idLang] = $custom;
            }
        }

        return ['link_block' => [
            'id_link_block' => $arrayLinkBlock['id_link_block'],
            'block_name' => $arrayLinkBlock['name'],
            'id_hook' => $arrayLinkBlock['id_hook'],
            'cms' => $arrayLinkBlock['content']['cms'],
            'product' => $arrayLinkBlock['content']['product'],
            'static' => $arrayLinkBlock['content']['static'],
            'category' => $arrayLinkBlock['content']['category'],
            'custom' => $arrayCustom,
        ]];
    }

    /**
     * @param array $data
     *
     * @return array
     *
     * @throws \PrestaShop\PrestaShop\Adapter\Entity\PrestaShopDatabaseException
     */
    public function setData(array $data)
    {
        $linkBlock = $data['link_block'];
        $errors = $this->validateLinkBlock($linkBlock);
        if (!empty($errors)) {
            return $errors;
        }

        $customContent = [];
        if (!empty($linkBlock['custom'])) {
            foreach ($linkBlock['custom'] as $customLanguages) {
                if ($this->isEmptyCustom($customLanguages)) {
                    continue;
                }

                foreach ($customLanguages as $idLang => $custom) {
                    $customContent[$idLang][] = $custom;
                }
            }
        }
        $linkBlock['custom_content'] = $customContent;

        if (empty($linkBlock['id_link_block'])) {
            $linkBlockId = $this->repository->create($linkBlock);
            $this->setIdLinkBlock($linkBlockId);
        } else {
            $linkBlockId = $linkBlock['id_link_block'];
            $this->repository->update($linkBlockId, $linkBlock);
        }
        $this->updateHook($linkBlock['id_hook']);
        $this->cache->clearModuleCache();

        return [];
    }

    /**
     * @return int
     */
    public function getIdLinkBlock()
    {
        return $this->idLinkBlock;
    }

    /**
     * @param int $idLinkBlock
     *
     * @return LinkBlockFormDataProvider
     */
    public function setIdLinkBlock($idLinkBlock)
    {
        $this->idLinkBlock = $idLinkBlock;

        return $this;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function validateLinkBlock(array $data)
    {
        $errors = [];
        if (!isset($data['id_hook'])) {
            $errors[] = [
                'key' => 'Missing id_hook',
                'domain' => 'Admin.Catalog.Notification',
                'parameters' => [],
            ];
        }

        if (!isset($data['block_name'])) {
            $errors[] = [
                'key' => 'Missing block_name',
                'domain' => 'Admin.Catalog.Notification',
                'parameters' => [],
            ];
        } else {
            foreach ($this->languages as $language) {
                if (empty($data['block_name'][$language['id_lang']])) {
                    $errors[] = [
                        'key' => 'Missing block_name value for language %s',
                        'domain' => 'Admin.Catalog.Notification',
                        'parameters' => [$language['iso_code']],
                    ];
                }
            }
        }

        if (!isset($data['custom'])) {
            return $errors;
        }

        foreach ($data['custom'] as $customIndex => $custom) {
            if ($this->isEmptyCustom($custom)) {
                continue;
            }
            foreach ($this->languages as $language) {
                if (!isset($custom[$language['id_lang']])) {
                    $errors[] = [
                        'key' => 'Missing block_name value for language %s',
                        'domain' => 'Admin.Catalog.Notification',
                        'parameters' => [$language['iso_code']],
                    ];
                } else {
                    $langCustom = $custom[$language['id_lang']];
                    $fields = ['title', 'url'];
                    foreach ($fields as $field) {
                        if (empty($langCustom[$field])) {
                            $errors[] = [
                                'key' => 'Missing %s value in custom[%s] for language %s',
                                'domain' => 'Admin.Catalog.Notification',
                                'parameters' => [$field, $customIndex, $language['iso_code']],
                            ];
                        }
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * @param array $custom
     *
     * @return bool
     */
    private function isEmptyCustom(array $custom)
    {
        $fields = ['title', 'url'];
        foreach ($custom as $langCustom) {
            foreach ($fields as $field) {
                if (!empty($langCustom[$field])) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Register the selected hook to this module if it was not registered yet.
     *
     * @param int $hookId
     *
     * @throws \PrestaShopException
     */
    private function updateHook($hookId)
    {
        $hookName = Hook::getNameById($hookId);
        $module = $this->moduleRepository->getInstanceByName(Ps_Linklist::MODULE_NAME);
        if (!Hook::isModuleRegisteredOnHook($module, $hookName, $this->shopId)) {
            Hook::registerHook($module, $hookName);
        }
    }
}
