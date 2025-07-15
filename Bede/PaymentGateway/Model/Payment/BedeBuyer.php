<?php

namespace Bede\PaymentGateway\Model\Payment;

class BedeBuyer
{
    public $name = "";
    public $amount = 1;
    public $phoneNumber = "";
    public $countryCode = "965";
    public $orderID = 1;
    public $customerData1 = "";
    public $customerData2 = "";
    public $customerData3 = "";
    public $trackID = "";

    public function __construct()
    {
        $this->trackID = $this->generateTrackID();
    }

    private function generateTrackID(): string
    {
        $code = date("YmdHis") . substr((string)hrtime(true), -8);
        return $code;
    }
}
