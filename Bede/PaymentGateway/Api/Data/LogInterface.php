<?php

namespace Bede\PaymentGateway\Api\Data;

interface LogInterface
{
    const ID = 'id';
    const TYPE = 'type';
    const ENDPOINT = 'endpoint';
    const METHOD = 'method';
    const STATUS = 'status';
    const ORDER_ID = 'order_id';
    const TRANSACTION_ID = 'transaction_id';
    const EXECUTED_AT = 'executed_at';
    const CURL_COMMAND = 'curl_command';
    const CREATED_AT = 'created_at';

    /**
     * @return int
     */
    public function getId();

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id);

    /**
     * @return string
     */
    public function getType();

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type);

    /**
     * @return string
     */
    public function getEndpoint();

    /**
     * @param string $endpoint
     * @return $this
     */
    public function setEndpoint($endpoint);

    /**
     * @return string
     */
    public function getMethod();

    /**
     * @param string $method
     * @return $this
     */
    public function setMethod($method);

    /**
     * @return int
     */
    public function getStatus();

    /**
     * @param int $status
     * @return $this
     */
    public function setStatus($status);

    /**
     * @return int
     */
    public function getOrderId();

    /**
     * @param int $orderId
     * @return $this
     */
    public function setOrderId($orderId);

    /**
     * @return int
     */
    public function getTransactionId();

    /**
     * @param int $transactionId
     * @return $this
     */
    public function setTransactionId($transactionId);

    /**
     * @return \DateTimeInterface
     */
    public function getExecutedAt();

    /**
     * @param \DateTimeInterface $executedAt
     * @return $this
     */
    public function setExecutedAt($executedAt);

    /**
     * @return string
     */
    public function getCurlCommand();

    /**
     * @param string $curlCommand
     * @return $this
     */
    public function setCurlCommand($curlCommand);

    /**
     * @return \DateTimeInterface
     */
    public function getCreatedAt();

    /**
     * @param \DateTimeInterface $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt);
}