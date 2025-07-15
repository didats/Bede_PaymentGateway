<?php
namespace Bede\PaymentGateway\Model\Config\Source;

class Environment implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'live', 'label' => __('Live')],
            ['value' => 'test', 'label' => __('Test')]
        ];
    }
}