<?php

namespace Bede\PaymentGateway\Model;

use Bede\PaymentGateway\Api\LogRepositoryInterface;
use Bede\PaymentGateway\Api\Data\LogInterface;
use Bede\PaymentGateway\Model\ResourceModel\Log as LogResource;

class LogRepository implements LogRepositoryInterface
{
    protected $logResource;

    public function __construct(
        LogResource $logResource
    ) {
        $this->logResource = $logResource;
    }

    public function save(LogInterface $log)
    {
        $this->logResource->save($log);
        return $log;
    }
}
