/**
 * 2007-2018 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
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
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

import TranslatableInput from '../../../../../admin-dev/themes/new-theme/js/components/translatable-input';

const $ = window.$;

$(() => {
    new TranslatableInput({localeInputSelector: '.js-locale-input'});
    $('.custom_collection .col-sm-12').each((index, customBlock) => {
        appendDeleteButton($(customBlock));
    });

    $('body').on('click', '.add-collection-btn', appendPrototype);

    function appendPrototype(event) {
        event.stopImmediatePropagation();

        const button = event.target;
        const collectionId = button.dataset.collectionId;
        const collection = document.getElementById(collectionId);
        const collectionPrototype = collection.dataset.prototype;
        const newChild = collectionPrototype.replace(/__name__/g, (collection.children.length + 1));
        const $newChild = $(newChild);
        $('#'+collectionId).append($newChild);
        appendDeleteButton($newChild);
    }

    function appendDeleteButton(customBlock) {
        const collection = customBlock.closest('.custom_collection');
        const $button = $('<a class="remove_custom_url btn btn-primary mt-1">'+collection.data('deleteButtonLabel')+'</a>');
        $button.on('click', (event) => {
            const $button = $(event.target);
            const $row = $button.closest('.row');
            $row.remove();
        });
        customBlock.find('.locale-input-group').first().closest('.col-sm-12').append($button);
    }
});