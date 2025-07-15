<?php
namespace Bede\PaymentGateway\Plugin;

class DebugPaymentAvailability
{
    public function afterGetAvailableMethods(\Magento\Payment\Api\PaymentMethodListInterface $subject, $result)
    {
        foreach ($result as $method) {
            echo $method->getCode() . " - " . $method->getTitle() . PHP_EOL;
        }
        return $result;
    }
}