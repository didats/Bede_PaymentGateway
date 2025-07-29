<?php

namespace Bede\PaymentGateway\Block\Payment;

use Magento\Framework\View\Element\Template;

class Info extends Template
{
    public function getPaymentInfo()
    {
        $request = $this->getRequest();

        // Only show if we have payment parameters
        if (!$request->getParam('order_id') && !$request->getParam('transaction_id')) {
            return null;
        }

        return [
            'order_id' => $request->getParam('order_id'),
            'transaction_id' => $request->getParam('transaction_id'),
            'payment_type' => $request->getParam('payment_type'),
            'payment_id' => $request->getParam('payment_id'),
            'bank_reference' => $request->getParam('bank_reference'),
            'amount' => $request->getParam('amount'),
            'currency' => $request->getParam('currency'),
            'error_message' => $request->getParam('error_message'),
            'error_code' => $request->getParam('error_code'),
            'status' => $request->getParam('status')
        ];
    }

    public function isSuccessPage()
    {
        return $this->getRequest()->getParam('status') === 'success';
    }

    public function isFailurePage()
    {
        return $this->getRequest()->getParam('status') === 'failure';
    }
}
