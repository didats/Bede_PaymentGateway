<?php

namespace Bede\PaymentGateway\Service;

use Bede\PaymentGateway\Model\Payment\Bede;
use Bede\PaymentGateway\Model\Payment\BedeBuyer;
use Bede\PaymentGateway\Model\LogFactory;
use Bede\PaymentGateway\Helper\Data;

class CheckoutDataProcessor
{
    protected $bede;
    protected $buyer;
    protected $logFactory;
    protected $helper;
    protected $paymentURL = "";

    public function __construct(
        Bede $bede,
        BedeBuyer $buyer,
        LogFactory $logFactory,
        Data $helper
    ) {
        $this->bede = $bede;
        $this->buyer = $buyer;
        $this->logFactory = $logFactory;
        $this->helper = $helper;
    }

    public function process($payment, $selectedSubmethod)
    {
        $quote = $payment->getQuote();
        if (!$quote) {
            return;
        }

        $grandTotal = $quote->getGrandTotal();
        $customerEmail = $quote->getCustomerEmail();
        $billingAddress = $quote->getBillingAddress();
        $firstName = $billingAddress->getFirstname();
        $lastName = $billingAddress->getLastname();
        $countryCode = $billingAddress->getCountryId();

        $addressDataArray = $billingAddress->getData();

        $this->bede->merchantID = $this->helper->getMerchantId();
        $this->bede->secretKey = $this->helper->getSecretKey();
        $this->bede->successURL = $this->helper->getSuccessUrl();
        $this->bede->failureURL = $this->helper->getFailureUrl();
        $this->bede->subMerchantID = $this->helper->getSubmerchantUid();

        $this->buyer->amount = $grandTotal;
        $this->buyer->email = $customerEmail;
        $this->buyer->phoneNumber = $billingAddress->getTelephone();
        $this->buyer->name = $firstName . " " . $lastName;
        $this->buyer->countryCode = $this->buyer->countryDialCode($countryCode);
        $this->buyer->orderID = $quote->getId();

        // Call API, log, etc.
        $response = $this->bede->requestLink($this->buyer, $selectedSubmethod);
        $responsejson = json_decode($response, true);

        $requestLog = $this->logFactory->create();
        $requestLog->setData($this->bede->requestLogger);
        $requestLog->save();

        $responseLog = $this->logFactory->create();
        $responseLog->setData($this->bede->responseLogger);
        $responseLog->save();

        if (isset($responsejson['PayUrl'])) {
            $this->paymentURL = $responsejson['PayUrl'];
            $payment->setAdditionalInformation('bede_pay_url', $responsejson['PayUrl']);
        } else {
            $payment->setAdditionalInformation('bede_pay_error', 'Payment gateway did not return a valid URL.');
        }
    }

    public function getPayUrl(): string
    {
        return $this->paymentURL;
    }
}
