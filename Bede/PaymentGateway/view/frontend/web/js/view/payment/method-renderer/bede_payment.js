define([
    'jquery',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/action/select-payment-method',
    'Magento_Checkout/js/model/url-builder',
    'Magento_Checkout/js/checkout-data',
    'mage/url',
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/full-screen-loader'
], function ($, Component, urlBuilder, quote, storage, customer, fullScreenLoader) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Bede_PaymentGateway/payment/bede_template',
            code: 'bede_payment',
            selectedMethod: null,
            availableMethods: []
        },

        initialize: function () {
            this._super();
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
            var serviceUrl = require('mage/url').build('bede_paymentgateway/payment/methods');
            $.ajax({
                url: serviceUrl,
                type: 'GET',
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
        }
    });
});