<?php

namespace Bede\PaymentGateway\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Quote\Model\QuoteFactory;
use Magento\Framework\Controller\Result\JsonFactory;

class GetPayUrl extends Action
{
    protected $quoteFactory;
    protected $resultJsonFactory;
    protected $checkoutDataProcessor;

    public function __construct(
        Context $context,
        QuoteFactory $quoteFactory,
        JsonFactory $resultJsonFactory,
        \Bede\PaymentGateway\Service\CheckoutDataProcessor $checkoutDataProcessor
    ) {
        parent::__construct($context);
        $this->quoteFactory = $quoteFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->checkoutDataProcessor = $checkoutDataProcessor;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $cartId = $this->getRequest()->getParam('cartId');
        $selectedSubmethod = $this->getRequest()->getParam('selected_submethod');

        $quote = $this->quoteFactory->create()->load($cartId, 'entity_id');
        if (!$quote->getId()) {
            return $result->setData(['error' => 'Quote not found.']);
        }

        $this->checkoutDataProcessor->process($quote->getPayment(), $selectedSubmethod);
        $payUrl = $this->checkoutDataProcessor->getPayUrl($quote, $selectedSubmethod);
        if ($payUrl) {
            return $result->setData(['pay_url' => $payUrl]);
        } else {
            return $result->setData(['error' => 'Could not generate payment URL.']);
        }
    }
}
