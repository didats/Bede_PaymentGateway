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
        $fp = fopen(__DIR__."/log.txt", "a");
        fwrite($fp, print_r([
            'method_code' => $this->_code,
            'parent_result' => parent::isAvailable($quote),
            'quote_id' => $quote ? $quote->getId() : null,
            'store_id' => $quote ? $quote->getStoreId() : null
        ], true));
        fclose($fp);
        return true;
    }

    public function assignData(\Magento\Framework\DataObject $data) {
        parent::assignData($data);
        
        $additionalData = $data->getData('additional_data');
        if (isset($additionalData['selected_submethod'])) {
            $this->getInfoInstance()->setAdditionalInformation(
                'selected_submethod',
                $additionalData['selected_submethod']
            );
        }
        
        return $this;
    }
}