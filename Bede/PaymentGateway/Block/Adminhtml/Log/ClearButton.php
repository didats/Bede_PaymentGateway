<?php

namespace Bede\PaymentGateway\Block\Adminhtml\Log;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class ClearButton implements ButtonProviderInterface
{
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    public function __construct(
        \Magento\Framework\UrlInterface $urlBuilder
    ) {
        $this->urlBuilder = $urlBuilder;
    }

    public function getButtonData()
    {
        return [
            'label'      => __('Clear Log'),
            'class'      => 'primary',
            'on_click'   => 'deleteConfirm(\'' .
                __('Are you sure you want to clear all logs?') .
                '\', \'' .
                $this->urlBuilder->getUrl('*/*/clear') . '\')',
            'sort_order' => 20,
        ];
    }
}
