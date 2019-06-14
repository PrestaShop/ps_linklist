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

namespace PrestaShop\Module\LinkList\Presenter;

use PrestaShop\Module\LinkList\Model\LinkBlock;
use Tools;

/**
 * Class LinkBlockPresenter.
 */
class LinkBlockPresenter
{
    private $link;
    private $language;

    /**
     * LinkBlockPresenter constructor.
     *
     * @param \Link $link
     * @param \Language $language
     */
    public function __construct(\Link $link, \Language $language)
    {
        $this->link = $link;
        $this->language = $language;
    }

    /**
     * @param LinkBlock $cmsBlock
     *
     * @return array
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function present(LinkBlock $cmsBlock)
    {
        return array(
            'id' => (int) $cmsBlock->id,
            'title' => $cmsBlock->name[(int) $this->language->id],
            'hook' => (new \Hook((int) $cmsBlock->id_hook))->name,
            'position' => $cmsBlock->position,
            'links' => $this->makeLinks($cmsBlock->content, $cmsBlock->custom_content),
        );
    }

    /**
     * Check the url if is an external link.
     *
     * @param $url
     *
     * @return bool
     */
    public function isExternalLink($url)
    {
        $baseLink = preg_replace('#^(http)s?://#', '', $this->link->getBaseLink());
        $url = Tools::strtolower($url);

        if (preg_match('#^(http)s?://#', $url) && !preg_match('#^(http)s?://' . preg_quote(rtrim($baseLink, '/'), '/') . '#', $url)) {
            return true;
        }

        return false;
    }

    /**
     * @param array $content
     * @param array $custom_content
     *
     * @return array
     */
    private function makeLinks($content, $custom_content)
    {
        $cmsLinks = $productLinks = $staticsLinks = $customLinks = $categoryLinks = array();

        if (isset($content['cms'])) {
            $cmsLinks = $this->makeCmsLinks($content['cms']);
        }

        if (isset($content['product'])) {
            $productLinks = $this->makeProductLinks($content['product']);
        }

        if (isset($content['static'])) {
            $staticsLinks = $this->makeStaticLinks($content['static']);
        }

        if (isset($content['category'])) {
            $categoryLinks = $this->makeCategoryLinks($content['category']);
        }


        $customLinks = $this->makeCustomLinks($custom_content);

        return array_merge(
            $cmsLinks,
            $productLinks,
            $staticsLinks,
            $customLinks,
            $categoryLinks
        );
    }

    /**
     * @param array $cmsIds
     *
     * @return array
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    private function makeCmsLinks($cmsIds)
    {
        $cmsLinks = array();
        foreach ($cmsIds as $cmsId) {
            $cms = new \CMS((int) $cmsId);
            if (null !== $cms->id && $cms->active) {
                $cmsLinks[] = array(
                    'id' => 'link-cms-page-' . $cms->id,
                    'class' => 'cms-page-link',
                    'title' => $cms->meta_title[(int) $this->language->id],
                    'description' => $cms->meta_description[(int) $this->language->id],
                    'url' => $this->link->getCMSLink($cms),
                );
            }
        }

        return $cmsLinks;
    }

    /**
     * @param array $productIds
     *
     * @return array
     */
    private function makeProductLinks($productIds)
    {
        $productLinks = array();
        foreach ($productIds as $productId) {
            if (false !== $productId) {
                $meta = \Meta::getMetaByPage($productId, (int) $this->language->id);
                $productLinks[] = array(
                    'id' => 'link-product-page-' . $productId,
                    'class' => 'cms-page-link',
                    'title' => $meta['title'],
                    'description' => $meta['description'],
                    'url' => $this->link->getPageLink($productId, true),
                );
            }
        }

        return $productLinks;
    }

    /**
     * @param array $staticIds
     *
     * @return array
     */
    private function makeStaticLinks($staticIds)
    {
        $staticLinks = array();
        foreach ($staticIds as $staticId) {
            if (false !== $staticId) {
                $meta = \Meta::getMetaByPage($staticId, (int) $this->language->id);
                $staticLinks[] = array(
                    'id' => 'link-static-page-' . $staticId,
                    'class' => 'cms-page-link',
                    'title' => $meta['title'],
                    'description' => $meta['description'],
                    'url' => $this->link->getPageLink($staticId, true),
                );
            }
        }

        return $staticLinks;
    }

    /**
     * @param array $customContent
     *
     * @return array
     */
    private function makeCustomLinks($customContent)
    {
        $customLinks = array();

        if (isset($customContent[$this->language->id])) {
            $customLinks = $customContent[$this->language->id];

            $self = $this;
            $customLinks = array_map(function ($el) use ($self) {
                return array(
                    'id' => 'link-custom-page-' . Tools::link_rewrite($el['title']),
                    'class' => 'custom-page-link',
                    'title' => $el['title'],
                    'description' => '',
                    'url' => $el['url'],
                    'target' => $self->isExternalLink($el['url']) ? '_blank' : '',
                );
            }, array_filter($customLinks));
        }

        return $customLinks;
    }

    /**
     * @param array $categoryIds
     *
     * @return array
     */
    private function makeCategoryLinks($categoryIds)
    {
        $categoryLinks = array();
        foreach ($categoryIds as $categoryId) {
            if (false !== $categoryId) {
                $meta = \Meta::getCategoryMetas($categoryId, (int) $this->language->id, null, null);
                $categoryLinks[] = array(
                    'id' => 'link-category-' . $categoryId,
                    'class' => 'category-link',
                    'title' => $meta['name'],
                    'description' => $meta['description'],
                    'url' => $this->link->getCategoryLink($categoryId, true),
                );
            }
        }

        return $categoryLinks;
    }
}
