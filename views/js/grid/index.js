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

import Grid from '../../../../../admin-dev/themes/new-theme/js/components/grid/grid';
import LinkRowActionExtension from '../../../../../admin-dev/themes/new-theme/js/components/grid/extension/link-row-action-extension';
import SubmitRowActionExtension from '../../../../../admin-dev/themes/new-theme/js/components/grid/extension/action/row/submit-row-action-extension';
import SortingExtension from "../../../../../admin-dev/themes/new-theme/js/components/grid/extension/sorting-extension";
import PositionExtension from "../../../../../admin-dev/themes/new-theme/js/components/grid/extension/position-extension";

const $ = window.$;

$(() => {
  let gridDivs = document.querySelectorAll('.js-grid');
  gridDivs.forEach((gridDiv) => {
      const linkBlockGrid = new Grid(gridDiv.dataset.gridId);

      linkBlockGrid.addExtension(new SortingExtension());
      linkBlockGrid.addExtension(new LinkRowActionExtension());
      linkBlockGrid.addExtension(new SubmitRowActionExtension());
      linkBlockGrid.addExtension(new PositionExtension());
  });
});
