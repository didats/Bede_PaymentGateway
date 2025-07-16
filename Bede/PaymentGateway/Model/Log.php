<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bede\PaymentGateway\Model;

use Magento\Framework\Model\AbstractModel;
use Bede\PaymentGateway\Api\Data\LogInterface;

class Log extends AbstractModel implements LogInterface
{

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(\Bede\PaymentGateway\Model\ResourceModel\Log::class);
    }

    public function getId()
    {
        return $this->getData(self::ID);
    }

    public function setId($id)
    {
        $this->setData(self::ID, $id);
        return $this;
    }

    public function getType()
    {
        return $this->getData(self::TYPE);
    }

    public function setType($type)
    {
        $this->setData(self::TYPE, $type);
        return $this;
    }

    public function getEndpoint()
    {
        return $this->getData(self::ENDPOINT);
    }

    public function setEndpoint($endpoint)
    {
        $this->setData(self::ENDPOINT, $endpoint);
        return $this;
    }

    public function getMethod()
    {
        return $this->getData(self::METHOD);
    }

    public function setMethod($method)
    {
        $this->setData(self::METHOD, $method);
        return $this;
    }

    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    public function setStatus($status)
    {
        $this->setData(self::STATUS, $status);
        return $this;
    }

    public function getOrderId()
    {
        return $this->getData(self::ORDER_ID);
    }

    public function setOrderId($orderId)
    {
        $this->setData(self::ORDER_ID, $orderId);
        return $this;
    }

    public function getTransactionId()
    {
        return $this->getData(self::TRANSACTION_ID);
    }

    public function setTransactionId($transactionId)
    {
        $this->setData(self::TRANSACTION_ID, $transactionId);
        return $this;
    }

    public function getExecutedAt()
    {
        return $this->getData(self::EXECUTED_AT);
    }

    public function setExecutedAt($executedAt)
    {
        $this->setData(self::EXECUTED_AT, $executedAt);
        return $this;
    }

    public function getCurlCommand()
    {
        return $this->getData(self::CURL_COMMAND);
    }

    public function setCurlCommand($curlCommand)
    {
        $this->setData(self::CURL_COMMAND, $curlCommand);
        return $this;
    }

    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    public function setCreatedAt($createdAt)
    {
        $this->setData(self::CREATED_AT, $createdAt);
        return $this;
    }
}