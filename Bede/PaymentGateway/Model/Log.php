<?php

namespace Bede\PaymentGateway\Model;

use Magento\Framework\Model\AbstractModel;
use Bede\PaymentGateway\Model\ResourceModel\Log;

class Log extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(Log::class);
    }
}
