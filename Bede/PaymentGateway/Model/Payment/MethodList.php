<?php
namespace Bede\PaymentGateway\Model\Payment;

use Bede\PaymentGateway\Model\Payment\Bede;

class MethodList
{
    protected $bede;
    
    public function __construct(Bede $apiclient) {
        $this->bede = $apiclient;
    }
    
    public function getAvailableMethods()
    {
        // Call your API to get available methods
        return $this->bede->paymentMethods();
    }
}