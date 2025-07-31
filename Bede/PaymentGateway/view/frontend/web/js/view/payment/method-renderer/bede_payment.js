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

        // selectPaymentMethod: function () {
        //     var result = this._super();
            
        //     if (!this.availableMethods().length) {
        //         this.loadPaymentMethods();
        //     }
            
        //     return result;
        // },

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

        // onSubMethodChange: function(data, event) {
        //     // This is called when user changes sub-payment method
        //     var selectedValue = event.target.value;
        //     this.selectedMethod(selectedValue);
            
        //     // Don't call any API here - just update the selection
        //     console.log('Sub-method selected:', selectedValue);
            
        //     return true;
        // },

        getData: function () {
            return {
                'method': this.item.method,
                'additional_data': {
                    'selected_submethod': this.selectedMethod()
                }
            };
        },

        // validate: function() {
        //     var result = this._super();
            
        //     if (!this.selectedMethod()) {
        //         alert('Please select a payment method.');
        //         return false;
        //     }
            
        //     return result;
        // },

        placeOrder: function (data, event) {
            var item = this;

            if (self.isPlaceOrderActionAllowed() === false) {
                return false;
            }

            self.isPlaceOrderActionAllowed(false);

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