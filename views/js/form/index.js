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

import TranslatableInput from '../../../../../admin-dev/themes/new-theme/js/components/translatable-input';

const $ = window.$;

$(() => {
  new TranslatableInput({localeInputSelector: '.js-locale-input'});

  const addCustomButton = $('.add-collection-btn');
  addCustomButton.on('click', appendPrototype);

  const collectionId = addCustomButton.data().collectionId;
  const collection = document.getElementById(collectionId);
  const collectionPrototype = collection.dataset.prototype;

  if (collection.children.length) {
    $('.custom_collection .col-sm-12').each((index, customBlock) => {
      appendDeleteButton($(customBlock));
    });
  } else {
    appendPrototype();
  }

  function appendPrototype(event) {
    if (event) {
      event.preventDefault();
    }

    const newChild = collectionPrototype.replace(/__name__/g, (collection.children.length + 1));
    const $newChild = $(newChild);
    $('#'+collectionId).append($newChild);
    appendDeleteButton($newChild);
  }

  function appendDeleteButton(customBlock) {
    const collection = customBlock.closest('.custom_collection');
    const $button = $('<button class="remove_custom_url btn btn-primary mt-1">'+collection.data('deleteButtonLabel')+'</button>');
    $button.on('click', (event) => {
      event.preventDefault();
      const $button = $(event.target);
      const $row = $button.closest('.row');
      $row.remove();

      return false;
    });
    customBlock.find('.locale-input-group').first().closest('.col-sm-12').append($button);
  }
});
