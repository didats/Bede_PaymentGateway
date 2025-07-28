<?php

namespace Bede\PaymentGateway\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Bede\PaymentGateway\Helper\Data;
use Bede\PaymentGateway\Model\Payment\Bede;

class Test extends Action
{
    protected $helper;
    protected $bede;

    public function __construct(
        Context $context,
        Data $helper,
        Bede $bede
    ) {
        parent::__construct($context);
        $this->helper = $helper;
        $this->bede = $bede;
    }

    public function execute()
    {
        $transaction = "2025072803222787856708";
        $status = $this->bede->paymentStatus($transaction);
        print_r($status);
        exit;
    }
}
