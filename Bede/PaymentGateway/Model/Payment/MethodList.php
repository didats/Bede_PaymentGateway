<?php

namespace Bede\PaymentGateway\Model\Payment;

use Bede\PaymentGateway\Helper\Data;
use Bede\PaymentGateway\Model\Payment\Bede;

class MethodList
{
    protected $bede;

    public function __construct()
    {
        $helper = new Data();

        $this->bede = new Bede();
        $this->bede->merchantID = $helper->getMerchantId();
        $this->bede->secretKey = $helper->getSecretKey();
        $this->bede->successURL = $helper->getSuccessUrl();
        $this->bede->failureURL = $helper->getFailureUrl();
        $this->bede->subMerchantID = $helper->getSubmerchantUid();
    }

    public function getAvailableMethods()
    {
        // Call your API to get available methods
        return $this->bede->paymentMethods();
    }
}
