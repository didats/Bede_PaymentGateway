<?php

namespace Bede\PaymentGateway\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Bede\PaymentGateway\Model\Payment\MethodList;

class Methods extends Action
{
    protected $resultJsonFactory;
    protected $methodList;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        MethodList $methodList
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->methodList = $methodList;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        try {
            $methods = $this->methodList->getAvailableMethods();
            return $result->setData([
                'success' => true,
                'methods' => $methods
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
