<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace PrestaShop\Module\LinkList\Controller\Admin\Improve\Design;

use PrestaShop\Module\LinkList\Cache\LegacyLinkBlockCache;
use PrestaShop\Module\LinkList\Core\Grid\LinkBlockGridFactory;
use PrestaShop\Module\LinkList\Core\Search\Filters\LinkBlockFilters;
use PrestaShop\Module\LinkList\Form\LinkBlockFormDataProvider;
use PrestaShop\Module\LinkList\Repository\LinkBlockRepository;
use PrestaShop\PrestaShop\Core\Context\ShopContext;
use PrestaShop\PrestaShop\Core\Exception\DatabaseException;
use PrestaShop\PrestaShop\Core\Form\FormHandlerInterface;
use PrestaShopBundle\Controller\Admin\PrestaShopAdminController;
use PrestaShopBundle\Security\Attribute\AdminSecurity;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LinkBlockController extends PrestaShopAdminController
{
    #[AdminSecurity("is_granted('read', request.get('_legacy_controller'))", redirectRoute: 'admin_homepage')]
    public function listAction(Request $request, LinkBlockRepository $repository, LinkBlockGridFactory $linkBlockGridFactory): Response
    {
        // Get hook list, then loop through hooks setting it in the filter
        $hooks = $repository->getHooksWithLinks();
        $filtersParams = $this->buildFiltersParamsByRequest($request);
        $grids = $linkBlockGridFactory->getGrids($hooks, $filtersParams);

        $presentedGrids = [];
        foreach ($grids as $grid) {
            $presentedGrids[] = $this->presentGrid($grid);
        }

        $presentedGrids = array_filter(
            $presentedGrids,
            function ($grid) {
                return $grid['data']['records_total'] > 0;
            }
        );

        return $this->render('@Modules/ps_linklist/views/templates/admin/link_block/list.html.twig', [
            'grids' => $presentedGrids,
            'enableSidebar' => true,
            'layoutHeaderToolbarBtn' => $this->getToolbarButtons(),
            'help_link' => $this->generateSidebarLink($request->attributes->get('_legacy_controller')),
        ]);
    }

    #[AdminSecurity("is_granted('create', request.get('_legacy_controller'))", redirectRoute: 'admin_homepage')]
    public function createAction(
        Request $request,
        LinkBlockFormDataProvider $linkBlockFormDataProvider,
        #[Autowire(service: 'prestashop.module.link_block.form_handler')]
        FormHandlerInterface $formHandler,
    ): Response {
        $linkBlockFormDataProvider->setIdLinkBlock(null);
        $form = $formHandler->getForm();

        return $this->render('@Modules/ps_linklist/views/templates/admin/link_block/form.html.twig', [
            'linkBlockForm' => $form->createView(),
            'enableSidebar' => true,
            'layoutHeaderToolbarBtn' => $this->getToolbarButtons(),
            'help_link' => $this->generateSidebarLink($request->attributes->get('_legacy_controller')),
        ]);
    }

    #[AdminSecurity("is_granted('update', request.get('_legacy_controller'))", redirectRoute: 'admin_homepage')]
    public function editAction(
        Request $request,
        int $linkBlockId,
        LinkBlockFormDataProvider $linkBlockFormDataProvider,
        #[Autowire(service: 'prestashop.module.link_block.form_handler')]
        FormHandlerInterface $formHandler,
    ): Response {
        $linkBlockFormDataProvider->setIdLinkBlock($linkBlockId);
        $form = $formHandler->getForm();

        return $this->render('@Modules/ps_linklist/views/templates/admin/link_block/form.html.twig', [
            'linkBlockForm' => $form->createView(),
            'enableSidebar' => true,
            'layoutHeaderToolbarBtn' => $this->getToolbarButtons(),
            'help_link' => $this->generateSidebarLink($request->attributes->get('_legacy_controller')),
        ]);
    }

    #[AdminSecurity("is_granted('create', request.get('_legacy_controller'))", redirectRoute: 'admin_homepage')]
    public function createProcessAction(
        Request $request,
        LinkBlockFormDataProvider $formProvider,
        #[Autowire(service: 'prestashop.module.link_block.form_handler')]
        FormHandlerInterface $formHandler,
    ): RedirectResponse|Response {
        return $this->processForm($request, 'Successful creation.', null, $formProvider, $formHandler);
    }

    #[AdminSecurity("is_granted('update', request.get('_legacy_controller'))", redirectRoute: 'admin_homepage')]
    public function editProcessAction(
        Request $request,
        int $linkBlockId,
        LinkBlockFormDataProvider $formProvider,
        #[Autowire(service: 'prestashop.module.link_block.form_handler')]
        FormHandlerInterface $formHandler,
    ): RedirectResponse|Response {
        return $this->processForm($request, 'Successful update.', $linkBlockId, $formProvider, $formHandler);
    }

    #[AdminSecurity("is_granted('delete', request.get('_legacy_controller'))", redirectRoute: 'admin_homepage')]
    public function deleteAction(
        int $linkBlockId,
        LegacyLinkBlockCache $linkBlockCache,
        LinkBlockRepository $linkBlockRepository,
    ): RedirectResponse {
        $errors = [];
        try {
            $linkBlockRepository->delete($linkBlockId);
        } catch (DatabaseException) {
            $errors[] = [
                'key' => 'Could not delete #%i',
                'domain' => 'Admin.Catalog.Notification',
                'parameters' => [$linkBlockId],
            ];
        }

        if (0 === count($errors)) {
            $linkBlockCache->clearModuleCache();
            $this->addFlash('success', $this->trans('Successful deletion.', [], 'Admin.Notifications.Success'));
        } else {
            $this->addFlashErrors($errors);
        }

        return $this->redirectToRoute('admin_link_block_list');
    }

    #[AdminSecurity("is_granted('update', request.get('_legacy_controller'))", redirectRoute: 'admin_homepage')]
    public function updatePositionsAction(
        Request $request,
        int $hookId,
        LegacyLinkBlockCache $linkBlockCache,
        LinkBlockRepository $linkBlockRepository,
        ShopContext $shopContext,
    ): RedirectResponse {
        $positionsData = [
            'positions' => $request->request->all()['positions'],
            'parentId' => $hookId,
        ];

        try {
            $linkBlockRepository->updatePositions($shopContext->getId(), $positionsData);
            $linkBlockCache->clearModuleCache();
            $this->addFlash('success', $this->trans('Successful update.', [], 'Admin.Notifications.Success'));
        } catch (DatabaseException $e) {
            $errors = [$e->getMessage()];
            $this->addFlashErrors($errors);
        }

        return $this->redirectToRoute('admin_link_block_list');
    }

    private function processForm(
        Request $request,
        string $successMessage,
        ?int $linkBlockId,
        LinkBlockFormDataProvider $formProvider,
        #[Autowire(service: 'prestashop.module.link_block.form_handler')]
        FormHandlerInterface $formHandler,
    ): RedirectResponse|Response {
        $formProvider->setIdLinkBlock($linkBlockId);
        $form = $formHandler->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $saveErrors = $formHandler->save($form->getData());
                if (0 === count($saveErrors)) {
                    $this->addFlash('success', $this->trans($successMessage, [], 'Admin.Notifications.Success'));

                    return $this->redirectToRoute('admin_link_block_list');
                }
                $this->addFlashErrors($saveErrors);
            }
            $formErrors = [];
            foreach ($form->getErrors(true) as $error) {
                $formErrors[] = $error->getMessage();
            }
            $this->addFlashErrors($formErrors);
        }

        return $this->render('@Modules/ps_linklist/views/templates/admin/link_block/form.html.twig', [
            'linkBlockForm' => $form->createView(),
            'enableSidebar' => true,
            'layoutHeaderToolbarBtn' => $this->getToolbarButtons(),
            'help_link' => $this->generateSidebarLink($request->attributes->get('_legacy_controller')),
        ]);
    }

    protected function buildFiltersParamsByRequest(Request $request): array
    {
        $filtersParams = array_merge(LinkBlockFilters::getDefaults(), $request->query->all());

        return $filtersParams;
    }

    /**
     * Gets the header toolbar buttons.
     */
    private function getToolbarButtons(): array
    {
        return [
            'add' => [
                'href' => $this->generateUrl('admin_link_block_create'),
                'desc' => $this->trans('New block', [], 'Modules.Linklist.Admin'),
                'icon' => 'add_circle_outline',
            ],
        ];
    }
}
