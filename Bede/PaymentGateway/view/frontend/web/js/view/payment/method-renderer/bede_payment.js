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
            payUrl: null,
            redirectAfterPlaceOrder: false,
            payUrlRequested: false
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
        // placeOrder: function (data, event) {
        //     var self = this;

        //     console.log('Running placeOrder');

        //     if (event) {
        //         event.preventDefault();
        //     }

        //     if (this.validate() && this.isPlaceOrderActionAllowed() === true) {
        //         console.log('Running placeOrder 2');
        //         this.isPlaceOrderActionAllowed(false);
        //         fullScreenLoader.startLoader();
                
        //         // Call an endpoint to get the PayUrl or error
                // var serviceUrl = url.build('bede_paymentgateway/payment/getpayurl');
                // var cartId = quote.getQuoteId ? quote.getQuoteId() : quote.quoteId;

        //         // Process the payment first
        //         if (!this.payUrlRequested) {
        //             console.log('Running placeOrder 4');
        //             $.ajax({
        //                 url: serviceUrl,
        //                 type: 'GET',
        //                 dataType: 'json',
        //                 data: {
        //                     cartId: cartId,
        //                     selected_submethod: this.selectedMethod()
        //                 },
        //                 success: function (response) {
        //                     console.log('Payment result response:', response);
        //                     fullScreenLoader.stopLoader();
                            
        //                     if (response.pay_url) {
        //                         //  
        //                         // If we have a payment URL, redirect to it
        //                         self.payUrl = response.pay_url;
        //                         console.log('Set payUrl:', self.payUrl);
        //                         self.payUrlRequested = true;
        //                         //window.location.href = response.pay_url;
        //                         self.continuePlaceOrder(data, event);
        //                         // return self._super(data, event);
        //                     } else if (response.error) {
        //                         // Show error message and stay on checkout page
        //                         alert(response.error);
        //                         self.isPlaceOrderActionAllowed(true);
        //                     } else {
        //                         // Fallback error
        //                         alert('Payment gateway did not return a valid URL.');
        //                         self.isPlaceOrderActionAllowed(true);
        //                     }
        //                 },
        //                 error: function (xhr, status, error) {
        //                     fullScreenLoader.stopLoader();
        //                     console.log('AJAX error:', status, error, xhr.responseText);
        //                     alert('Could not connect to payment gateway.');
        //                     self.isPlaceOrderActionAllowed(true);
        //                 }
        //             });
        //         } else {
        //             console.log('Running placeOrder 5');
        //             self.payUrlRequested = false;
        //             return self.continuePlaceOrder(data, event);
        //         }
        //     } else {
        //         console.log('Running placeOrder 3');
        //         return self.continuePlaceOrder(data, event);
        //     }
        // },

        // continuePlaceOrder: function(data, event) {
        //     console.log('continuePlaceOrder called', this.payUrl);
        //     return this._super(data, event);
        // },
        
        // /**
        //  * Keep the afterPlaceOrder method for compatibility
        //  */
        // afterPlaceOrder: function () {
        //     console.log('afterPlaceOrder called', this.payUrl);
        //     if (this.payUrl) {
        //         window.location.href = this.payUrl;
        //     }
        // }

        placeOrder: function (data, event) {
            var item = this;

            if(item.payUrlRequested) {
                return item._super(data, event);
            } else {
                if (item.validate() && item.isPlaceOrderActionAllowed() === true) {
                    fullScreenLoader.startLoader();
                    var serviceUrl = url.build('bede_paymentgateway/payment/getpayurl');
                    var cartId = quote.getQuoteId ? quote.getQuoteId() : quote.quoteId;

                    $.ajax({
                        url: serviceUrl,
                        type: 'GET',
                        dataType: 'json',
                        data: {
                            cartId: cartId,
                            selected_submethod: this.selectedMethod()
                        },
                        success: function (response) {
                            fullScreenLoader.stopLoader();
                            if (response.pay_url) {
                                item.payUrl = response.pay_url;
                                item.payUrlRequested = true;
                                item.placeOrder(data, event); // Call again, now will go to parent
                            } else if (response.error) {
                                alert(response.error);
                                item.isPlaceOrderActionAllowed(true); // Re-enable on error
                            } else {
                                alert('Payment gateway did not return a valid URL.');
                                item.isPlaceOrderActionAllowed(true); // Re-enable on error
                            }
                        },
                        error: function () {
                            fullScreenLoader.stopLoader();
                            alert('Could not connect to payment gateway.');
                            item.isPlaceOrderActionAllowed(true); // Re-enable on error
                        }
                    });
                }
            }
        },
        afterPlaceOrder: function () {
            console.log('afterPlaceOrder called', this.payUrl);
            if (this.payUrl) {
                window.location.href = this.payUrl;
            }
        }
    });
});