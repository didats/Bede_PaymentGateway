<?php

namespace Bede\PaymentGateway\Model\Payment;

use Bede\PaymentGateway\Helper\Data;
use Bede\PaymentGateway\Model\Payment\Bede;
use Bede\PaymentGateway\Model\LogFactory;

class MethodList
{
    protected $bede;
    protected $helper;
    protected $logFactory;

    public function __construct(Data $helper, LogFactory $logFactory)
    {
        $this->helper = $helper;
        $this->logFactory = $logFactory;

        $this->bede = new Bede();
        $this->bede->merchantID = $helper->getMerchantId();
        $this->bede->secretKey = $helper->getSecretKey();
        $this->bede->successURL = $helper->getSuccessUrl();
        $this->bede->failureURL = $helper->getFailureUrl();
        $this->bede->subMerchantID = $helper->getSubmerchantUid();

        if ((string)$this->bede->merchantID == "") {
            $this->bede->merchantID = "Mer2000012";
        }
        if ((string)$this->bede->merchantID == "") {
            $this->bede->merchantID = "1234567";
        }
    }

    public function getAvailableMethods()
    {
        // Call your API to get available methods
        $response = $this->bede->paymentMethods();

        $requestLog = $this->logFactory->create();
        $requestLog->setData($this->bede->requestLogger);
        $requestLog->save();

        $responseLog = $this->logFactory->create();
        $responseLog->setData($this->bede->responseLogger);
        $responseLog->save();

        return $response;
    }
}
