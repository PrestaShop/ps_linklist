<?php
/**
 * 2007-2018 PrestaShop.
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
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2018 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\Module\LinkList\Controller\Admin\Improve\Design;

use PrestaShop\Module\LinkList\Core\Grid\LinkBlockGridFactory;
use PrestaShop\Module\LinkList\Core\Search\Filters\LinkBlockFilters;
use PrestaShop\Module\LinkList\Form\LinkBlockFormDataProvider;
use PrestaShop\Module\LinkList\Repository\LinkBlockRepository;
use PrestaShop\PrestaShop\Adapter\Entity\PrestaShopDatabaseException;
use PrestaShop\PrestaShop\Core\Form\FormHandlerInterface;
use PrestaShop\PrestaShop\Core\Grid\Position\Exception\PositionDataException;
use PrestaShop\PrestaShop\Core\Grid\Position\Exception\PositionUpdateException;
use PrestaShop\PrestaShop\Core\Grid\Position\GridPositionUpdaterInterface;
use PrestaShop\PrestaShop\Core\Grid\Position\PositionDataHandler;
use PrestaShop\PrestaShop\Core\Grid\Position\PositionDefinition;
use PrestaShop\PrestaShop\Core\Grid\Position\PositionUpdate;
use PrestaShop\PrestaShop\Core\Grid\Presenter\GridPresenter;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopBundle\Security\Annotation\AdminSecurity;
use PrestaShopBundle\Security\Annotation\ModuleActivated;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class LinkBlockController.
 *
 * @ModuleActivated(moduleName="ps_linklist", redirectRoute="admin_module_manage")
 */
class LinkBlockController extends FrameworkBundleAdminController
{
    /**
     * @AdminSecurity("is_granted('read', request.get('_legacy_controller'))", message="Access denied.")
     * @Template("@Modules/ps_linklist/views/templates/admin/link_block/list.html.twig")
     *
     * @param Request $request
     *
     * @return array
     */
    public function listAction(Request $request)
    {
        //Get hook list, then loop through hooks setting it in in the filter
        /** @var LinkBlockRepository $repository */
        $repository = $this->get('prestashop.module.link_block.repository');
        $hooks = $repository->getHooksWithLinks();

        $filtersParams = $this->buildFiltersParamsByRequest($request);

        /** @var LinkBlockGridFactory $linkBlockGridFactory */
        $linkBlockGridFactory = $this->get('prestashop.module.link_block.grid.factory');
        $grids = $linkBlockGridFactory->getGrids($hooks, $filtersParams);

        /** @var GridPresenter $gridPresenter */
        $gridPresenter = $this->get('prestashop.core.grid.presenter.grid_presenter');
        $presentedGrids = [];
        foreach ($grids as $grid) {
            $presentedGrids[] = $gridPresenter->present($grid);
        }

        return [
            'grids' => $presentedGrids,
            'enableSidebar' => true,
            'layoutHeaderToolbarBtn' => $this->getToolbarButtons(),
            'help_link' => $this->generateSidebarLink($request->attributes->get('_legacy_controller')),
        ];
    }

    /**
     * @AdminSecurity("is_granted('create', request.get('_legacy_controller'))", message="Access denied.")
     * @Template("@Modules/ps_linklist/views/templates/admin/link_block/form.html.twig")
     *
     * @param Request $request
     *
     * @return array
     *
     * @throws \Exception
     */
    public function createAction(Request $request)
    {
        $this->get('prestashop.module.link_block.form_provider')->setIdLinkBlock(null);
        $form = $this->get('prestashop.module.link_block.form_handler')->getForm();

        return [
            'linkBlockForm' => $form->createView(),
            'enableSidebar' => true,
            'layoutHeaderToolbarBtn' => $this->getToolbarButtons(),
            'help_link' => $this->generateSidebarLink($request->attributes->get('_legacy_controller')),
        ];
    }

    /**
     * @AdminSecurity("is_granted('update', request.get('_legacy_controller'))", message="Access denied.")
     * @Template("@Modules/ps_linklist/views/templates/admin/link_block/form.html.twig")
     *
     * @param Request $request
     * @param int $linkBlockId
     *
     * @return array
     *
     * @throws \Exception
     */
    public function editAction(Request $request, $linkBlockId)
    {
        $this->get('prestashop.module.link_block.form_provider')->setIdLinkBlock($linkBlockId);
        $form = $this->get('prestashop.module.link_block.form_handler')->getForm();

        return [
            'linkBlockForm' => $form->createView(),
            'enableSidebar' => true,
            'layoutHeaderToolbarBtn' => $this->getToolbarButtons(),
            'help_link' => $this->generateSidebarLink($request->attributes->get('_legacy_controller')),
        ];
    }

    /**
     * @AdminSecurity("is_granted('update', request.get('_legacy_controller'))", message="Access denied.")
     * @Template("@Modules/ps_linklist/views/templates/admin/link_block/form.html.twig")
     *
     * @param Request $request
     *
     * @return RedirectResponse|array
     *
     * @throws \Exception
     */
    public function createProcessAction(Request $request)
    {
        return $this->processForm($request, 'Successful creation.');
    }

    /**
     * @AdminSecurity("is_granted('update', request.get('_legacy_controller'))", message="Access denied.")
     * @Template("@Modules/ps_linklist/views/templates/admin/link_block/form.html.twig")
     *
     * @param Request $request
     * @param int $linkBlockId
     *
     * @return RedirectResponse|array
     *
     * @throws \Exception
     */
    public function editProcessAction(Request $request, $linkBlockId)
    {
        return $this->processForm($request, 'Successful update.', $linkBlockId);
    }

    /**
     * @param int $linkBlockId
     *
     * @return RedirectResponse
     */
    public function deleteAction($linkBlockId)
    {
        $repository = $this->get('prestashop.module.link_block.repository');
        $errors = [];
        try {
            $repository->delete($linkBlockId);
        } catch (PrestaShopDatabaseException $e) {
            $errors[] = [
                'key' => 'Could not delete #%i',
                'domain' => 'Admin.Catalog.Notification',
                'parameters' => [$linkBlockId],
            ];
        }

        if (0 === count($errors)) {
            $this->clearModuleCache();
            $this->addFlash('success', $this->trans('Successful deletion.', 'Admin.Notifications.Success'));
        } else {
            $this->flashErrors($errors);
        }

        return $this->redirectToRoute('admin_link_block_list');
    }

    /**
     * @param Request $request
     *
     * @throws \Exception
     *
     * @return RedirectResponse
     */
    public function updatePositionsAction(Request $request)
    {
        $positionsData = [
            'positions' => $request->request->get('positions', null),
            'parentId' => $request->request->get('parentId', null),
        ];

        /** @var PositionDefinition $positionDefinition */
        $positionDefinition = $this->get('prestashop.module.link_block.grid.position_definition');
        /** @var PositionDataHandler $positionDataHandler */
        $positionDataHandler = $this->get('prestashop.core.grid.position.update_handler.position_data_handler');
        try {
            /** @var PositionUpdate $positionUpdate */
            $positionUpdate = $positionDataHandler->handleData($positionsData, $positionDefinition);
        } catch (PositionDataException $e) {
            $errors = [$e->toArray()];
            $this->flashErrors($errors);

            return $this->redirectToRoute('admin_link_block_list');
        }

        /** @var GridPositionUpdaterInterface $updater */
        $updater = $this->get('prestashop.core.grid.position.doctrine_grid_position_updater');
        try {
            $updater->update($positionUpdate);
        } catch (PositionUpdateException $e) {
            $errors = [$e->toArray()];
            $this->flashErrors($errors);
        }
        $this->clearModuleCache();
        $this->addFlash('success', $this->trans('Successful update.', 'Admin.Notifications.Success'));

        return $this->redirectToRoute('admin_link_block_list');
    }

    /**
     * @param Request $request
     * @param string $successMessage
     * @param int|null $linkBlockId
     *
     * @return array|RedirectResponse
     *
     * @throws \Exception
     */
    private function processForm(Request $request, $successMessage, $linkBlockId = null)
    {
        /** @var LinkBlockFormDataProvider $formProvider */
        $formProvider = $this->get('prestashop.module.link_block.form_provider');
        $formProvider->setIdLinkBlock($linkBlockId);

        /** @var FormHandlerInterface $formHandler */
        $formHandler = $this->get('prestashop.module.link_block.form_handler');
        $form = $formHandler->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $saveErrors = $formHandler->save($data);

            if (0 === count($saveErrors)) {
                $this->addFlash('success', $this->trans($successMessage, 'Admin.Notifications.Success'));

                return $this->redirectToRoute('admin_link_block_edit', ['linkBlockId' => $formProvider->getIdLinkBlock()]);
            }

            $this->flashErrors($saveErrors);
        }

        return [
            'linkBlockForm' => $form->createView(),
            'enableSidebar' => true,
            'layoutHeaderToolbarBtn' => $this->getToolbarButtons(),
            'help_link' => $this->generateSidebarLink($request->attributes->get('_legacy_controller')),
        ];
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    private function buildFiltersParamsByRequest(Request $request)
    {
        $filtersParams = array_merge(LinkBlockFilters::getDefaults(), $request->query->all());
        $filtersParams['filters']['id_lang'] = $this->getContext()->language->id;

        return $filtersParams;
    }

    /**
     * Gets the header toolbar buttons.
     *
     * @return array
     */
    private function getToolbarButtons()
    {
        $toolbarButtons = [];
        $toolbarButtons['add'] = [
            'href' => $this->generateUrl('admin_link_block_create'),
            'desc' => $this->trans('New block', 'Modules.Linklist.Admin'),
            'icon' => 'add_circle_outline',
        ];

        return $toolbarButtons;
    }

    /**
     * Clear module cache.
     */
    private function clearModuleCache()
    {
        $this->get('prestashop.module.link_block.cache')->clearModuleCache();
    }
}
