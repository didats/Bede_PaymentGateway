define([
    'jquery',
    'Magento_Ui/js/modal/alert',
    'mage/translate'
], function ($, alert, $t) {
    'use strict';

    return function (config) {
        $('#search-button').on('click', function() {
            performSearch();
        });

        $('#clear-button').on('click', function() {
            $('#refund-search-form')[0].reset();
            $('#search-results').hide();
        });

        function performSearch() {
            var formData = $('#refund-search-form').serialize();
            
            $.ajax({
                url: config.searchUrl,
                type: 'POST',
                data: formData,
                showLoader: true,
                success: function(response) {
                    if (response.success) {
                        displayResults(response.orders);
                    } else {
                        alert({
                            title: $t('Error'),
                            content: response.message || $t('An error occurred while searching.')
                        });
                    }
                },
                error: function() {
                    alert({
                        title: $t('Error'),
                        content: $t('An error occurred while searching.')
                    });
                }
            });
        }

        function displayResults(orders) {
            var tbody = $('#results-body');
            tbody.empty();

            if (orders.length === 0) {
                tbody.append('<tr><td colspan="7" style="text-align: center;">' + $t('No orders found.') + '</td></tr>');
            } else {
                orders.forEach(function(order) {
                    var actions = '';
                    if (order.can_refund) {
                        actions += '<a href="javascript:void(0)" class="action-default refund-link" data-order-id="' + order.entity_id + '">' + $t('Refund') + '</a>';
                    }
                    actions += ' <a href="/admin/sales/order/view/order_id/' + order.entity_id + '" target="_blank">' + $t('View') + '</a>';

                    var row = '<tr>' +
                        '<td>' + order.increment_id + '</td>' +
                        '<td>' + order.customer_name + '</td>' +
                        '<td>' + order.status + '</td>' +
                        '<td>' + order.grand_total + ' ' + order.currency_code + '</td>' +
                        '<td>' + order.created_at + '</td>' +
                        '<td>' + (order.transaction_id || '-') + '</td>' +
                        '<td>' + actions + '</td>' +
                        '</tr>';
                    tbody.append(row);
                });
            }

            $('#search-results').show();
        }

        // Handle refund clicks
        $(document).on('click', '.refund-link', function() {
            var orderId = $(this).data('order-id');
            window.open('/admin/sales/order_creditmemo/new/order_id/' + orderId, '_blank');
        });
    };
});