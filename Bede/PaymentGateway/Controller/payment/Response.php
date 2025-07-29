<?php

namespace Bede\PaymentGateway\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Bede\PaymentGateway\Helper\Data;
use Bede\PaymentGateway\Model\Payment\Bede;

class Response extends Action
{
    protected $helper;
    protected $bede;

    public function __construct(
        Context $context,
        Data $helper,
        Bede $bede
    ) {
        parent::__construct($context);
        $this->helper = $helper;
        $this->bede = $bede;
    }

    public function execute()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        // Get parameters from the request
        $merchantTxnId = $this->getRequest()->getParam('merchantTxnId');
        $errorMessage = $this->getRequest()->getParam('errorMessage');
        $errorCode    = $this->getRequest()->getParam('errorCode');
        $finalStatus  = $this->getRequest()->getParam('finalstatus');
        $transactionID  = $this->getRequest()->getParam('txnId');
        $rawPostData = file_get_contents('php://input');

        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        $tableName = $resource->getTableName('bede_payment_logs');

        // callback
        $callbackData = [
            'type' => 'callback',
            'endpoint' => "",
            'method' => 'GET',
            'status' => 200,
            'order_id' => "-",
            'transaction_id' => $transactionID,
            'executed_at' => date('Y-m-d H:i:s'),
            'curl_command' => "GET:\n" . json_encode($_GET) . "\n\n" . $rawPostData,
            'cart_id' => "",
            'transaction_ref' => $merchantTxnId ?? ""
        ];

        $successURL = $this->helper->getSuccessUrl();
        $failureURL = $this->helper->getFailureUrl();

        $this->bede->merchantID = $this->helper->getMerchantId();
        $this->bede->secretKey = $this->helper->getSecretKey();

        $isPaid = false;
        $order = null;

        if ($merchantTxnId && $transactionID) {

            $callbackData['curl_command'] = $callbackData['curl_command'];

            $isPaid = false;
            if ($errorCode == 0) {
                $isPaid = true;
            }

            $select = $connection->select()
                ->from($tableName)
                ->where('transaction_ref = ?', $merchantTxnId)
                ->order('id DESC')
                ->limit(1);

            $logEntry = $connection->fetchRow($select);

            if ($logEntry && !empty($logEntry['cart_id'])) {
                $cartId = $logEntry['cart_id'];
                $callbackData['cart_id'] = $cartId;

                $order = $objectManager->create(\Magento\Sales\Model\Order::class)
                    ->getCollection()
                    ->addFieldToFilter('quote_id', $cartId)
                    ->getLastItem();

                if ($order->getId()) {
                    $callbackData['order_id'] = $order->getId();
                } else {
                    $isPaid = false;
                }
            }

            // check the payment status
            $response = $this->bede->paymentStatus($merchantTxnId);
            $responseData = [
                'type' => 'payment',
                'endpoint' => "/pgapi/api/payment/paymentstatus",
                'method' => 'POST',
                'status' => 200,
                'order_id' => "-",
                'transaction_id' => $transactionID,
                'executed_at' => date('Y-m-d H:i:s'),
                'curl_command' => $response,
                'cart_id' => $cartId,
                'transaction_ref' => $merchantTxnId ?? ""
            ];
            $connection->insert($tableName, $responseData);

            $callbackData['curl_command'] .= "\n\n" . $response;

            $jsonResponse = json_decode($response, true);
            $paymentType = "";
            $paymentID = "";
            $processDate = "";
            $bankReference = "";
            if (isset($jsonResponse['PaymentStatus'])) {
                $dataResponse = $jsonResponse['PaymentStatus'][0];
                $isPaid = false;
                if ($dataResponse['ErrorCode'] == 0) {
                    $isPaid = true;
                    $paymentType = $dataResponse['PaymentType'];
                    $paymentID = $dataResponse['PaymentId'];
                    $processDate = $dataResponse['ProcessDate'];
                    $bankReference = $dataResponse['BankRefNo'];
                }
            }
        }

        $connection->insert($tableName, $callbackData);

        if ($isPaid && $order && $order->getId()) {
            $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING)
                ->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
            // Optionally add a comment
            $order->addStatusHistoryComment('Payment successful via gateway callback.');

            $payment = $order->getPayment();
            $payment->setTransactionId($transactionID);
            $payment->setIsTransactionClosed(true);
            $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE, null, false);
            $payment->setAdditionalInformation('gateway_transaction_id', $transactionID);
            $payment->setAdditionalInformation('merchant_transaction_id', $merchantTxnId);
            $payment->setAdditionalInformation('payment_gateway', 'Bede Payment Gateway: ' . $paymentType);
            $payment->setAdditionalInformation('payment_id', $paymentID);
            $payment->setAdditionalInformation('bank_reference_number', $bankReference);

            $order->save();

            $successUrlWithParams = $successURL . (strpos($successURL, '?') !== false ? '&' : '?') . http_build_query([
                'status' => 'success',
                'order_id' => $order->getIncrementId(),
                'transaction_id' => $transactionID,
                'merchant_transaction_id' => $merchantTxnId,
                'payment_type' => $paymentType,
                'payment_id' => $paymentID,
                'bank_reference' => $bankReference,
                'amount' => $order->getOrderCurrencyCode() . " " . $order->getGrandTotal(),
            ]);

            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)
                ->setUrl($successUrlWithParams);
        } else {
            $order->setState(\Magento\Sales\Model\Order::STATE_CANCELED)
                ->setStatus(\Magento\Sales\Model\Order::STATE_CANCELED);
            $order->addStatusHistoryComment('Payment failed: ' . $errorMessage);

            $payment = $order->getPayment();
            $payment->setTransactionId($transactionID ?? $merchantTxnId);
            $payment->setIsTransactionClosed(true);
            $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_VOID, null, false);
            $payment->setAdditionalInformation('error_message', $errorMessage);
            $payment->setAdditionalInformation('error_code', $errorCode);
            $payment->setAdditionalInformation('payment_gateway', 'Bede Payment Gateway: ' . $paymentType);

            $order->save();

            $failureUrlWithParams = $failureURL . (strpos($failureURL, '?') !== false ? '&' : '?') . http_build_query([
                'status' => 'failure',
                'order_id' => $order ? $order->getIncrementId() : '',
                'error_message' => $errorMessage,
                'error_code' => $errorCode,
                'transaction_id' => $transactionID ?? "",
                'merchant_transaction_id' => $merchantTxnId,
            ]);

            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)
                ->setUrl($failureUrlWithParams);
        }
    }

    protected function saveLogData(array $data)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        $tableName = $resource->getTableName('bede_payment_logs');

        $connection->insert($tableName, $data);
    }
}
