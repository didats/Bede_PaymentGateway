<?php

namespace Bede\PaymentGateway\Model;

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

        if ($quote) {
            // Total amount
            $grandTotal = $quote->getGrandTotal();

            // Buyer data
            $customerEmail = $quote->getCustomerEmail();
            $billingAddress = $quote->getBillingAddress();
            $firstName = $billingAddress->getFirstname();
            $lastName = $billingAddress->getLastname();
            // ...other fields as needed
        }

        return $this;
    }
}
