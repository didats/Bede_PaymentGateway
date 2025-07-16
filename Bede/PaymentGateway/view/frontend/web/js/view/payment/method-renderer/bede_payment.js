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
            availableMethods: []
        },

        initialize: function () {
            this._super();
            this.selectedMethod = ko.observable(null);
            this.availableMethods = ko.observableArray([]);
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
        }
    });
});