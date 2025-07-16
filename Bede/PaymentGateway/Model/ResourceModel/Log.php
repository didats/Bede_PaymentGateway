<?php

namespace Bede\PaymentGateway\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Log extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('bede_payment_logs', 'id'); // table name, primary key
    }
}
