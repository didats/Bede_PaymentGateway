<?php

namespace Bede\PaymentGateway\Service;

use Bede\PaymentGateway\Model\Payment\Bede;
use Bede\PaymentGateway\Model\Payment\BedeBuyer;
use Bede\PaymentGateway\Model\LogFactory;
use Bede\PaymentGateway\Helper\Data;
use Bede\PaymentGateway\Api\LogRepositoryInterface;
use Bede\PaymentGateway\Model\LogRepository;
use Magento\Framework\UrlInterface;

class CheckoutDataProcessor
{
    protected $bede;
    protected $buyer;
    protected $logFactory;
    protected $helper;
    protected $paymentURL = "";
    protected $urlBuilder;
    protected $logRepository;

    public function __construct(
        Bede $bede,
        BedeBuyer $buyer,
        LogFactory $logFactory,
        LogRepository $logRepository,
        Data $helper,
        UrlInterface $urlBuilder
    ) {
        $this->bede = $bede;
        $this->buyer = $buyer;
        $this->logFactory = $logFactory;
        $this->helper = $helper;
        $this->urlBuilder = $urlBuilder;
        $this->logRepository = $logRepository;
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

        //TODO: Failed URL and Success URL
        $successURL = $this->urlBuilder->getUrl('bede_paymentgateway/payment/response');
        $failureURL = $this->urlBuilder->getUrl('bede_paymentgateway/payment/response');

        $this->bede->merchantID = $this->helper->getMerchantId();
        $this->bede->secretKey = $this->helper->getSecretKey();
        $this->bede->successURL = $successURL;
        $this->bede->failureURL = $failureURL;
        $this->bede->subMerchantID = $this->helper->getSubmerchantUid();
        $this->bede->cartID = $quote->getId();

        $this->buyer->setAmount($grandTotal);
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
        $this->logRepository->save($requestLog);

        $responseLog = $this->logFactory->create();
        $responseLog->setData($this->bede->responseLogger);
        $this->logRepository->save($responseLog);

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
