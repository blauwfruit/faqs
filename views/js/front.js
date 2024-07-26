/**
 * 2007-2024 PrestaShop
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
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2024 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */

$(document).ready(function() {
    $('.faq-question').click(function() {
        var answer = $(this).next('.faq-answer');
        $('.faq-answer').not(answer).hide(); // Hide all other answers
        answer.toggle();

        $('.faq-question').removeClass('faq-open').addClass('faq-closed');
        if (answer.css('display') !== 'none') {
            $(this).addClass('faq-open').removeClass('faq-closed');
        }
    });

    $('.thumb-up, .thumb-down').click(function() {
        var parentElement = $(this).parent();
        var id_faq_question = $(this).data('id-faq-question');
        var isUseful = $(this).hasClass('thumb-up') ? 1 : 0;
        $.ajax({
            url: feedback_callback_url,
            type: 'POST',
            data: {
                id_faq_question: id_faq_question,
                is_useful: isUseful
            },
            success: function(response) {
                var result = JSON.parse(response);
                if (result.success) {
                    parentElement.text(result.message).addClass('text-success');
                } else {
                    parentElement.text(result.message).addClass('text-danger');
                }
            },
            error: function() {
                parentElement.text('Failed to submit feedback').addClass('text-danger');
            }
        });
    });
});