<?php

namespace Bede\PaymentGateway\Block\Adminhtml\Refund;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\UrlInterface;

class Index extends Template
{
    protected $urlBuilder;

    public function __construct(Context $context, array $data = [])
    {
        parent::__construct($context, $data);
        $this->urlBuilder = $context->getUrlBuilder();
    }

    public function getSearchUrl()
    {
        return $this->getUrl('bedepg/refund/search');
    }

    public function getRefundUrl()
    {
        return $this->getUrl('bedepg/refund/process');
    }

    public function getRequestRefundUrl()
    {
        return $this->getUrl('bedepg/refund/request');
    }

    public function getOrderStatusOptions()
    {
        return [
            '' => __('All Statuses'),
            'pending' => __('Pending'),
            'pending_payment' => __('Pending Payment'),
            'payment_review' => __('Payment Review'),
            'fraud' => __('Suspected Fraud'),
            'processing' => __('Processing'),
            'complete' => __('Complete'),
            'closed' => __('Closed'),
            'canceled' => __('Canceled'),
            'holded' => __('On Hold'),
            'refunded' => __('Refunded'),
            'partially_refunded' => __('Partially Refunded')
        ];
    }
}
