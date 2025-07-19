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
    protected $checkoutDataProcessor;

    // public function __construct(
    //     Data $helper,
    //     Bede $bede,
    //     LogFactory $logFactory,
    //     BedeBuyer $buyer,
    // \Magento\Framework\Model\Context $context,
    // \Magento\Framework\Registry $registry,
    // \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
    // \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
    // \Magento\Payment\Helper\Data $paymentData,
    // \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
    // \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
    // \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
    // \Magento\Payment\Model\Method\Logger $logger = null,
    // array $data = []
    // ) {

    // parent::__construct(
    //     $context,
    //     $registry,
    //     $extensionFactory,
    //     $customAttributeFactory,
    //     $paymentData,
    //     $scopeConfig,
    //     $resource,
    //     $resourceCollection,
    //     $logger,
    //     $data
    // );

    //     $this->logFactory = $logFactory;
    //     $this->bede = $bede;
    //     $this->buyer = $buyer;

    //     $this->bede->merchantID = $helper->getMerchantId();
    //     $this->bede->secretKey = $helper->getSecretKey();
    //     $this->bede->successURL = $helper->getSuccessUrl();
    //     $this->bede->failureURL = $helper->getFailureUrl();
    //     $this->bede->subMerchantID = $helper->getSubmerchantUid();
    // }

    public function __construct(
        \Bede\PaymentGateway\Service\CheckoutDataProcessor $checkoutDataProcessor,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        $this->checkoutDataProcessor = $checkoutDataProcessor;
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

        $payment = $this->getInfoInstance();
        $selectedSubmethod = $payment->getAdditionalInformation('selected_submethod');

        $this->checkoutDataProcessor->process($payment, $selectedSubmethod);

        return $this;
    }
}
