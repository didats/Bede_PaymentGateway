<?php

namespace Bede\PaymentGateway\Model\ResourceModel\Log;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Bede\PaymentGateway\Model\Log;
use Bede\PaymentGateway\Model\ResourceModel\Log as ResourceModelLog;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            Log::class,
            ResourceModelLog::class
        );
    }
}
