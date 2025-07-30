<?php

namespace Bede\PaymentGateway\Block\Adminhtml\Refund;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;

class Index extends Template
{
    public function __construct(Context $context, array $data = [])
    {
        parent::__construct($context, $data);
    }

    public function getSearchUrl()
    {
        return $this->getUrl('bedepg/refund/search');
    }

    public function getRefundUrl()
    {
        return $this->getUrl('bedepg/refund/process');
    }

    public function getOrderStatusOptions()
    {
        return [
            '' => __('-- Select Status --'),
            'processing' => __('Processing'),
            'complete' => __('Complete'),
            'closed' => __('Closed'),
            'canceled' => __('Canceled')
        ];
    }
}
