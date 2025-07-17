<?php

namespace Bede\PaymentGateway\Model;

use Bede\PaymentGateway\Model\Payment\Bede;
use Bede\PaymentGateway\Model\Payment\BedeBuyer;
use Bede\PaymentGateway\Model\LogFactory;
use Bede\PaymentGateway\Helper\Data;

/**
 * Pay In Store payment method model
 */
class PaymentMethod extends \Magento\Payment\Model\Method\AbstractMethod
{
    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'bede_payment';

    protected $_isOffline = false;
    protected $_canAuthorize = true;
    protected $_canCapture = true;

    public function __construct(
        Data $helper,
        Bede $bede,
        LogFactory $logFactory,
        BedeBuyer $buyer,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        \Magento\Payment\Model\Method\Logger $logger = null,
        array $data = []
    ) {

        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $resource,
            $resourceCollection,
            $logger,
            $data
        );

        $this->logFactory = $logFactory;
        $this->bede = $bede;
        $this->buyer = $buyer;

        $this->bede->merchantID = $helper->getMerchantId();
        $this->bede->secretKey = $helper->getSecretKey();
        $this->bede->successURL = $helper->getSuccessUrl();
        $this->bede->failureURL = $helper->getFailureUrl();
        $this->bede->subMerchantID = $helper->getSubmerchantUid();
    }

    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        return true;
    }

    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);

        $additionalData = $data->getData('additional_data');
        if (isset($additionalData['selected_submethod'])) {
            $this->getInfoInstance()->setAdditionalInformation(
                'selected_submethod',
                $additionalData['selected_submethod']
            );
        }

        // Get quote from payment info instance
        $payment = $this->getInfoInstance();
        $quote = $payment->getQuote();

        $methodCode = $payment->getMethod();
        $selectedSubmethod = $payment->getAdditionalInformation('selected_submethod');

        if ($quote) {
            // Total amount
            $grandTotal = $quote->getGrandTotal();

            // Buyer data
            $customerEmail = $quote->getCustomerEmail();
            $billingAddress = $quote->getBillingAddress();
            $firstName = $billingAddress->getFirstname();
            $lastName = $billingAddress->getLastname();
            $countryCode = $billingAddress->getCountryId();


            $this->buyer->amount = $grandTotal;
            $this->buyer->email = $customerEmail;
            $this->buyer->name = $firstName . " " . $lastName;
            $this->buyer->countryCode = $this->buyer->countryDialCode($countryCode);
            $this->buyer->customerData1 = $customerEmail;
            $this->buyer->customerData2 = $billingAddress;
            $this->buyer->orderID = $quote->getId();
            $response = $this->bede->requestLink($this->buyer, $selectedSubmethod);

            $requestLog = $this->logFactory->create();
            $requestLog->setData($this->bede->requestLogger);
            $requestLog->save();

            $responseLog = $this->logFactory->create();
            $responseLog->setData($this->bede->responseLogger);
            $responseLog->save();
        }

        return $this;
    }
}
