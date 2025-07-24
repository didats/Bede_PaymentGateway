define([
    'jquery',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/action/select-payment-method',
    'Magento_Checkout/js/model/url-builder',
    'Magento_Checkout/js/checkout-data',
    'mage/url',
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/full-screen-loader',
    'ko'
], function (
    $,
    Component,
    selectPaymentMethodAction,
    urlBuilder,
    checkoutData,
    url,
    quote,
    customer,
    fullScreenLoader,
    ko
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Bede_PaymentGateway/payment/bede_template',
            code: 'bede_payment',
            selectedMethod: null,
            availableMethods: [],
            payUrl: null
        },

        initialize: function () {
            this._super();
            this.selectedMethod = ko.observable(null);
            this.availableMethods = ko.observableArray([]);
            this.payUrl = null;
            this.loadPaymentMethods();
            return this;
        },

        getCode: function () {
            return this.code;
        },

        isActive: function () {
            return true;
        },

        loadPaymentMethods: function () {
            var self = this;
            var serviceUrl = url.build('bede_paymentgateway/payment/methods');
            $.ajax({
                url: serviceUrl,
                type: 'GET',
                dataType: 'json',
                data: {
                    cartId: quote.quoteId
                }
            }).done(function (response) {
                if (response.success) {
                    self.availableMethods(response.methods);
                    if (response.methods.length > 0) {
                        self.selectedMethod(response.methods[0].value);
                    }
                }
            });

            return true;
        },

        getData: function () {
            return {
                'method': this.item.method,
                'additional_data': {
                    'selected_submethod': this.selectedMethod()
                }
            };
        },

        /**
         * Override the default placeOrder method to handle payment processing
         * before the order is placed
         */
        placeOrder: function (data, event) {
            var self = this;

            if (event) {
                event.preventDefault();
            }

            if (this.validate() && this.isPlaceOrderActionAllowed() === true) {
                this.isPlaceOrderActionAllowed(false);
                fullScreenLoader.startLoader();
                
                // Call an endpoint to get the PayUrl or error
                var serviceUrl = url.build('bede_paymentgateway/payment/getpayurl');
                var cartId = quote.getQuoteId ? quote.getQuoteId() : quote.quoteId;
                
                var component = this;

                // Process the payment first
                $.ajax({
                    url: serviceUrl,
                    type: 'GET',
                    dataType: 'json',
                    data: {
                        cartId: cartId,
                        selected_submethod: this.selectedMethod()
                    },
                    success: function (response) {
                        console.log('Payment result response:', response);
                        fullScreenLoader.stopLoader();
                        
                        if (response.pay_url) {
                            // If we have a payment URL, redirect to it
                            component.payUrl = response.pay_url;
                            console.log('Set payUrl:', component.payUrl);
                            window.location.href = response.pay_url;
                            Component.prototype.placeOrder.apply(self, [data, event]);
                        } else if (response.error) {
                            // Show error message and stay on checkout page
                            alert(response.error);
                            self.isPlaceOrderActionAllowed(true);
                        } else {
                            // Fallback error
                            alert('Payment gateway did not return a valid URL.');
                            self.isPlaceOrderActionAllowed(true);
                        }
                    },
                    error: function (xhr, status, error) {
                        fullScreenLoader.stopLoader();
                        console.log('AJAX error:', status, error, xhr.responseText);
                        alert('Could not connect to payment gateway.');
                        self.isPlaceOrderActionAllowed(true);
                    }
                });
                
                return false;
            }
            
            return false;
        },
        
        /**
         * Keep the afterPlaceOrder method for compatibility
         */
        afterPlaceOrder: function () {
            console.log('afterPlaceOrder called', this.payUrl);
            if (this.payUrl) {
                window.location.href = this.payUrl;
            }
        }
    });
});