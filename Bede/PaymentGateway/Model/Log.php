<?php

namespace Bede\PaymentGateway\Model;

use Magento\Framework\Model\AbstractModel;

class Log extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\Bede\PaymentGateway\Model\ResourceModel\Log::class);
    }
}
