<?php

namespace Bede\PaymentGateway\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Controller\Result\JsonFactory;

class Result extends Action
{
    protected $checkoutSession;
    protected $resultJsonFactory;

    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $order = $this->checkoutSession->getLastRealOrder();
        if (!$order) {
            return $result->setData(['error' => 'Order not found in session.']);
        }

        $payment = $order->getPayment();
        if (!$payment) {
            return $result->setData(['error' => 'Payment information not found. - ' . json_encode($order)]);
        }
        $payUrl = $payment->getAdditionalInformation('bede_pay_url');
        $error = $payment->getAdditionalInformation('bede_pay_error');

        if ($payUrl) {
            return $result->setData(['pay_url' => $payUrl]);
        } elseif ($error) {
            return $result->setData(['error' => $error]);
        } else {
            return $result->setData(['error' => 'Payment gateway did not return a valid URL.']);
        }
    }
}
