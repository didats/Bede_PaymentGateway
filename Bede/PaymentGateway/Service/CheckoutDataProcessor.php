<?php

namespace Bede\PaymentGateway\Service;

use Bede\PaymentGateway\Model\Payment\Bede;
use Bede\PaymentGateway\Model\Payment\BedeBuyer;
use Bede\PaymentGateway\Model\LogFactory;
use Bede\PaymentGateway\Helper\Data;
use Magento\Framework\UrlInterface;
use Bede\PaymentGateway\Model\Payment\PaymentRepository;

class CheckoutDataProcessor
{
    protected $bede;
    protected $buyer;
    protected $logFactory;
    protected $helper;
    protected $paymentURL = "";
    protected $urlBuilder;
    protected $paymentRepository;

    public function __construct(
        Bede $bede,
        BedeBuyer $buyer,
        PaymentRepository $paymentRepository,
        Data $helper,
        UrlInterface $urlBuilder
    ) {
        $this->bede = $bede;
        $this->buyer = $buyer;
        $this->helper = $helper;
        $this->urlBuilder = $urlBuilder;
        $this->paymentRepository = $paymentRepository;
    }

    public function process($payment, $selectedSubmethod)
    {
        $quote = $payment->getQuote();

        if (!$quote) {
            return;
        }

        $existingTrackId = $payment->getAdditionalInformation('bede_merchant_track_id');
        if ($existingTrackId) {
            // Already processed, return existing data
            error_log("CheckoutDataProcessor: Already processed for trackID: " . $existingTrackId);
            return;
        }

        $existingPayment = $this->paymentRepository->getPaymentByCartId($quote->getId());
        if ($existingPayment && !empty($existingPayment['merchant_track_id'])) {
            error_log("CheckoutDataProcessor: Payment already exists in DB for cart: " . $quote->getId());
            $payment->setAdditionalInformation('bede_merchant_track_id', $existingPayment['merchant_track_id']);
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

        $payment->setTrasactionId($this->buyer->trackID);

        // Call API, log, etc.
        $response = $this->bede->requestLink($this->buyer, $selectedSubmethod);
        $responsejson = json_decode($response, true);

        $this->paymentRepository->addLog($this->bede->logData);

        // add payment data
        $this->paymentRepository->addPaymentData([
            'cart_id' => $quote->getId(),
            'amount' => $grandTotal,
            'merchant_track_id' => $this->bede->merchantTrackID,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'order_status' => 'pending',
            'order_state' => 'pending'
        ]);

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
