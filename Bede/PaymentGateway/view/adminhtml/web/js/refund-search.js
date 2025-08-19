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

                        if(!payment.refund_request) {
                            actions += "&nbsp;|&nbsp;";
                            actions += '<a href="#" class="action-default request-refund-btn" ' +
                                'data-payment-id="' + payment.id + '" ' +
                                'data-bookeey-track-id="' + (payment.bookeey_track_id || '') + '" ' +
                                'data-merchant-track-id="' + payment.merchant_track_id + '" ' +
                                'data-amount="' + payment.amount + '" ' +
                                'style="background: #B22222; color: #FFF; margin-left: 5px;">' + 
                                $t('Request Refund') + '</a>';
                        }

                        actions += "&nbsp;|&nbsp;";
                        actions += '<a href="#" class="action-default refund-status-btn" ' +
                                'data-payment-id="' + payment.id + '" ' +
                                'data-bookeey-track-id="' + (payment.bookeey_track_id || '') + '" ' +
                                'data-merchant-track-id="' + payment.merchant_track_id + '" ' +
                                'style="background: #535353; color: #FFF; margin-left: 5px;">' + 
                                $t('Check Status') + '</a>';
                        
                        if(payment.refund_status.toLowerCase() == "pending") {
                            actions += "&nbsp;|&nbsp;";
                            actions += '<a href="#" class="action-default refund-cancel-btn" ' +
                                'data-payment-id="' + payment.id + '" ' +
                                'data-bookeey-track-id="' + (payment.bookeey_track_id || '') + '" ' +
                                'data-merchant-track-id="' + payment.merchant_track_id + '" ' +
                                'style="background: #990000; color: #FFF; margin-left: 5px;">' +
                                $t('Cancel Refund') + '</a>';
                        }
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
                        '<td>' + payment.payment_status + '</td>' +
                        '<td>' + payment.order_status + '</td>' +
                        '<td>' + payment.refund_status + '</td>' +
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

        $(document).on('click', '.refund-status-btn', function(e) {
            e.preventDefault();
            
            var paymentId = $(this).data('payment-id');
            var bookeyTrackId = $(this).data('bookeey-track-id');
            var merchantTrackId = $(this).data('merchant-track-id');
            
            $('a[data-payment-id="' + paymentId + '"].refund-status-btn')
                        .css('opacity', '0.5')
                        .text($t('Checking...'));
            $.ajax({
                url: config.requestRefundUrl,
                type: 'GET',
                data: {
                    payment_id: paymentId,
                    bookeey_track_id: bookeyTrackId,
                    merchant_track_id: merchantTrackId,
                    check_status: true
                },
                success: function(response) {
                    console.log("Response:", response); 
                    if (response.success) {
                        alert({
                            title: $t('Success'),
                            content: $t('Status updated successfully.'),
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
                            content: response.message || $t('Failed to check refund status.')
                        });
                        $('a[data-payment-id="' + paymentId + '"].refund-status-btn')
                            .css('opacity', '1')
                            .text($t('Check Status'));
                    }
                },
                error: function(xhr, status, error) {
                    console.log("AJAX Error:", xhr.responseText);
                    alert({
                        title: $t('Error'),
                        content: $t('An error occurred while checking refund status.')
                    });
                    $('a[data-payment-id="' + paymentId + '"].refund-status-btn')
                        .css('opacity', '1')
                        .text($t('Check Status'));
                }
            });
        });

        $(document).on('click', '.refund-cancel-btn', function(e) {
            e.preventDefault();
            
            var paymentId = $(this).data('payment-id');
            var bookeyTrackId = $(this).data('bookeey-track-id');
            var merchantTrackId = $(this).data('merchant-track-id');

            $('a[data-payment-id="' + paymentId + '"].refund-cancel-btn')
                .css('opacity', '0.5')
                .text($t('Canceling...'));
            $.ajax({
                url: config.requestRefundUrl,
                type: 'GET',
                data: {
                    payment_id: paymentId,
                    bookeey_track_id: bookeyTrackId,
                    merchant_track_id: merchantTrackId,
                    cancel: true
                },
                success: function(response) {
                    console.log("Response:", response);
                    if (response.success) {
                        alert({
                            title: $t('Success'),
                            content: $t('Status updated successfully.'),
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
                            content: response.message || $t('Failed to cancel.')
                        });
                        $('a[data-payment-id="' + paymentId + '"].refund-cancel-btn')
                            .css('opacity', '1')
                            .text($t('Cancel Refund'));
                    }
                },
                error: function(xhr, status, error) {
                    console.log("AJAX Error:", xhr.responseText);
                    alert({
                        title: $t('Error'),
                        content: $t('An error occurred while canceling your refund.')
                    });
                    $('a[data-payment-id="' + paymentId + '"].refund-cancel-btn')
                        .css('opacity', '1')
                        .text($t('Cancel Refund'));
                }
            });
        });

        $(document).on('click', '.request-refund-btn', function(e) {
            e.preventDefault();
            
            var paymentId = $(this).data('payment-id');
            var bookeyTrackId = $(this).data('bookeey-track-id');
            var merchantTrackId = $(this).data('merchant-track-id');
            var maxAmount = parseFloat($(this).data('amount'));
            
            if (!bookeyTrackId) {
                alert({
                    title: $t('Error'),
                    content: $t('Bookeey Track ID is required for refund request.')
                });
                return;
            }
            
            // Create custom modal content with editable amount
            var modalContent = $t('Enter refund details for:<br><br>') +
                        $t('Payment ID: %1<br>').replace('%1', paymentId) +
                        $t('Merchant Track ID: %1<br>').replace('%1', merchantTrackId) +
                        $t('Maximum Amount: KWD %1<br><br>').replace('%1', maxAmount) +
                        '<div style="margin: 15px 0;">' +
                        '<label for="refund-amount" style="display: block; margin-bottom: 5px; font-weight: bold;">' + $t('Refund Amount (KWD):') + '</label>' +
                        '<input type="number" id="refund-amount" min="1" max="' + maxAmount + '" step="0.001" value="' + maxAmount + '" ' +
                        'style="width: 100%; padding: 5px; border: 1px solid #ccc; border-radius: 3px;" />' +
                        '<small style="color: #666; display: block; margin-top: 5px;">' + 
                        $t('Amount must be between 1.000 and %1 KWD').replace('%1', maxAmount) + '</small>' +
                        '</div>';
            
            confirmation({
                title: $t('Request Refund'),
                content: modalContent,
                actions: {
                    confirm: function() {
                        var refundAmount = parseFloat($('#refund-amount').val());
                        
                        // Validate amount
                        if (isNaN(refundAmount) || refundAmount < 1) {
                            alert({
                                title: $t('Invalid Amount'),
                                content: $t('Refund amount must be at least 1.000 KWD.')
                            });
                            return false;
                        }
                        
                        if (refundAmount > maxAmount) {
                            alert({
                                title: $t('Invalid Amount'),
                                content: $t('Refund amount cannot exceed the original payment amount of %1 KWD.').replace('%1', maxAmount)
                            });
                            return false;
                        }
                        
                        requestRefund(paymentId, bookeyTrackId, merchantTrackId, refundAmount);
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