define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'bede_payment',
                component: 'Bede_PaymentGateway/js/view/payment/method-renderer/bede_payment'
            }
        );
        return Component.extend({});
    }
);