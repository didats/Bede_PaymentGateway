define([
    'jquery',
    'Magento_Ui/js/modal/alert',
    'Magento_Ui/js/modal/confirm',
    'mage/translate'
], function ($, alert, confirmation, $t) {
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
            
            // Show loading
            $('#loading-indicator').show();
            $('#search-results').hide();
            
            $.ajax({
                url: config.searchUrl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    $('#loading-indicator').hide();
                    
                    if (response.success) {
                        displayResults(response.payments, response.count);
                    } else {
                        alert({
                            title: $t('Error'),
                            content: response.message || $t('An error occurred while searching.')
                        });
                    }
                },
                error: function() {
                    $('#loading-indicator').hide();
                    alert({
                        title: $t('Error'),
                        content: $t('An error occurred while searching.')
                    });
                }
            });
        }

        function displayResults(payments, count) {
            var tbody = $('#results-body');
            tbody.empty();

            // Update results count
            $('#results-count').text('(' + count + ' ' + $t('payment(s) found') + ')');

            if (payments.length === 0) {
                tbody.append('<tr><td colspan="9" style="text-align: center; padding: 20px; color: #666;">' + 
                    '<em>' + $t('No payments found matching your search criteria.') + '</em></td></tr>');
            } else {
                payments.forEach(function(payment) {
                    var actions = '';
                    
                    if (payment.can_refund) {
                        actions += '<button type="button" class="action-default primary refund-btn" data-payment-id="' + 
                            payment.id + '" data-amount="' + payment.amount + '" style="margin-right: 5px;">' + 
                            $t('Refund') + '</button>';
                    }
                    
                    if (payment.order_id) { // sales/order/view/order_id/39/
                        var baseUrl = window.location.origin + window.location.pathname;
                        baseUrl = baseUrl.replace("/bedepg/refund/index", "/sales/order/view/order_id/" + payment.order_id);
                        actions += '<a href="' + baseUrl + '" target="_blank" class="action-default">' + $t('View Order') + '</a>';
                        actions += "&nbsp;|&nbsp;";
                         actions += '<a href="#" class="action-default request-refund-btn" ' +
                            'data-payment-id="' + payment.id + '" ' +
                            'data-bookeey-track-id="' + (payment.bookeey_track_id || '') + '" ' +
                            'data-merchant-track-id="' + payment.merchant_track_id + '" ' +
                            'data-amount="' + payment.amount + '" ' +
                            'style="background: #B22222; color: #FFF; margin-left: 5px;">' + 
                            $t('Request Refund') + '</a>';
                    }

                    var statusClass = '';
                    if (payment.payment_status === 'completed') {
                        statusClass = 'grid-severity-notice';
                    } else if (payment.payment_status === 'failed') {
                        statusClass = 'grid-severity-critical';
                    } else if (payment.payment_status === 'pending') {
                        statusClass = 'grid-severity-minor';
                    }

                    var refundInfo = '';
                    if (payment.refund_status) {
                        refundInfo = '<br><small style="color: #666;">Refund: ' + payment.refund_status;
                        if (payment.refund_amount) {
                            refundInfo += ' (' + payment.refund_amount + ')';
                        }
                        refundInfo += '</small>';
                    }

                    var row = '<tr>' +
                        '<td>' + payment.id + '</td>' +
                        '<td><strong>' + (payment.order_id || 'N/A') + '</strong></td>' +
                        '<td><code style="font-size: 11px;">' + payment.merchant_track_id + '</code></td>' +
                        '<td><code style="font-size: 11px;">' + (payment.transaction_id || 'N/A') + '</code></td>' +
                        '<td><strong style="color: #007cba;">KWD ' + payment.amount + '</strong></td>' +
                        '<td><span class="' + statusClass + '">' + payment.order_status + '</span>' + refundInfo + '</td>' +
                        '<td>' + payment.payment_method + '</td>' +
                        '<td>' + new Date(payment.created_at).toLocaleDateString() + '</td>' +
                        '<td>' + actions + '</td>' +
                        '</tr>';
                    tbody.append(row);
                });
            }

            $('#search-results').show();
        }

        // Handle refund clicks
        $(document).on('click', '.refund-btn', function() {
            var paymentId = $(this).data('payment-id');
            var amount = $(this).data('amount');
            
            confirmation({
                title: $t('Confirm Refund'),
                content: $t('Are you sure you want to refund payment ID %1 with amount $%2?')
                    .replace('%1', paymentId).replace('%2', amount),
                actions: {
                    confirm: function() {
                        processRefund(paymentId);
                    }
                }
            });
        });

        $(document).on('click', '.request-refund-btn', function(e) {
            e.preventDefault();
            
            var paymentId = $(this).data('payment-id');
            var bookeyTrackId = $(this).data('bookeey-track-id');
            var merchantTrackId = $(this).data('merchant-track-id');
            var amount = $(this).data('amount');
            
            if (!bookeyTrackId) {
                alert({
                    title: $t('Error'),
                    content: $t('Bookeey Track ID is required for refund request.')
                });
                return;
            }
            
            confirmation({
                title: $t('Request Refund'),
                content: $t('Are you sure you want to request a refund for:<br><br>') +
                        $t('Payment ID: %1<br>').replace('%1', paymentId) +
                        $t('Merchant Track ID: %1<br>').replace('%1', merchantTrackId) +
                        $t('Amount: KWD %1<br><br>').replace('%1', amount) +
                        $t('This will send a refund request to the payment gateway.'),
                actions: {
                    confirm: function() {
                        requestRefund(paymentId, bookeyTrackId, merchantTrackId, amount);
                    }
                }
            });
        });

        function processRefund(paymentId) {
            $.ajax({
                url: config.refundUrl,
                type: 'POST',
                data: { payment_id: paymentId },
                beforeSend: function() {
                    $('button[data-payment-id="' + paymentId + '"]').prop('disabled', true).text($t('Processing...'));
                },
                success: function(response) {
                    if (response.success) {
                        alert({
                            title: $t('Success'),
                            content: $t('Refund processed successfully.'),
                            actions: {
                                always: function() {
                                    // Refresh search results
                                    performSearch();
                                }
                            }
                        });
                    } else {
                        alert({
                            title: $t('Error'),
                            content: response.message || $t('Failed to process refund.')
                        });
                        $('button[data-payment-id="' + paymentId + '"]').prop('disabled', false).text($t('Refund'));
                    }
                },
                error: function() {
                    alert({
                        title: $t('Error'),
                        content: $t('An error occurred while processing refund.')
                    });
                    $('button[data-payment-id="' + paymentId + '"]').prop('disabled', false).text($t('Refund'));
                }
            });
        }

        function requestRefund(paymentId, bookeyTrackId, merchantTrackId, amount) {
            console.log("Request: " + config.requestRefundUrl);
            // document.location.href = config.requestRefundUrl + '?payment_id=' + paymentId + 
            //     '&bookeey_track_id=' + bookeyTrackId + 
            //     '&merchant_track_id=' + merchantTrackId + 
            //     '&amount=' + amount;
            $('a[data-payment-id="' + paymentId + '"].request-refund-btn')
                        .css('opacity', '0.5')
                        .text($t('Requesting...'));
            $.ajax({
                url: config.requestRefundUrl,
                type: 'GET',
                data: {
                    payment_id: paymentId,
                    bookeey_track_id: bookeyTrackId,
                    merchant_track_id: merchantTrackId,
                    amount: amount
                },
                // beforeSend: function() {
                    // $('a[data-payment-id="' + paymentId + '"].request-refund-btn')
                    //     .css('opacity', '0.5')
                    //     .text($t('Requesting...'));
                // },
                success: function(response) {
                    console.log("Response:", response); 
                    if (response.success) {
                        alert({
                            title: $t('Success'),
                            content: $t('Refund request sent successfully.'),
                            actions: {
                                always: function() {
                                    // Refresh search results
                                    performSearch();
                                }
                            }
                        });
                    } else {
                        alert({
                            title: $t('Error'),
                            content: response.message || $t('Failed to send refund request.')
                        });
                        $('a[data-payment-id="' + paymentId + '"].request-refund-btn')
                            .css('opacity', '1')
                            .text($t('Request Refund'));
                    }
                },
                error: function(xhr, status, error) {
                    console.log("AJAX Error:", xhr.responseText);
                    alert({
                        title: $t('Error'),
                        content: $t('An error occurred while sending refund request.')
                    });
                    $('a[data-payment-id="' + paymentId + '"].request-refund-btn')
                        .css('opacity', '1')
                        .text($t('Request Refund'));
                }
            });
        }
    };
});