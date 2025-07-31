<?php

namespace Bede\PaymentGateway\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Bede\PaymentGateway\Helper\Data;
use Bede\PaymentGateway\Model\Payment\Bede;
use Bede\PaymentGateway\Model\Payment\PaymentRepository;

class Response extends Action
{
    protected $helper;
    protected $bede;
    protected $paymentRepository;

    public function __construct(
        Context $context,
        Data $helper,
        Bede $bede,
        PaymentRepository $paymentRepository
    ) {
        parent::__construct($context);
        $this->helper = $helper;
        $this->bede = $bede;
        $this->paymentRepository = $paymentRepository;
    }

    public function execute()
    {
        $cartID = 0;
        $amount = 0;
        $orderID = "";
        $merchantTxnId = "";
        $transactionID = "";
        $paymentID = "";
        $paymentStatus = "";
        $bookeyTransactionID = "";
        $bankReference = "";
        $paymentMethod = "";
        $errorCode = 0;
        $finalStatus = "";
        $orderStatus = "pending";
        $orderState = "pending";

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
        $tableName = $resource->getTableName('bede_payments');

        $successURL = $this->helper->getSuccessUrl();
        $failureURL = $this->helper->getFailureUrl();

        $this->bede->merchantID = $this->helper->getMerchantId();
        $this->bede->secretKey = $this->helper->getSecretKey();

        $isPaid = false;
        $order = null;

        if ($merchantTxnId && $transactionID) {
            $isPaid = false;
            if ($errorCode == 0) {
                $isPaid = true;
            }

            $select = $connection->select()
                ->from($tableName)
                ->where('merchant_track_id = ?', $merchantTxnId)
                ->order('id DESC')
                ->limit(1);

            $logEntry = $connection->fetchRow($select);

            if ($logEntry && !empty($logEntry['cart_id'])) {
                $cartID = $logEntry['cart_id'];

                $order = $objectManager->create(\Magento\Sales\Model\Order::class)
                    ->getCollection()
                    ->addFieldToFilter('quote_id', $cartID)
                    ->getLastItem();

                if ($order->getId()) {
                    $amount = $order->getGrandTotal();
                    $orderID = $order->getId();
                    $orderStatus = $order->getStatusLabel(); // Get the current order status
                    $orderState = $order->getState(); // Get the current order state
                } else {
                    $isPaid = false;
                }
            }

            // check the payment status
            $response = $this->bede->paymentStatus($merchantTxnId);
            $this->paymentRepository->addLog($this->bede->logData);

            $jsonResponse = json_decode($response, true);

            if (isset($jsonResponse['PaymentStatus'])) {
                $dataResponse = $jsonResponse['PaymentStatus'][0];
                $isPaid = false;
                $paymentStatus = $dataResponse['finalStatus'] ?? '';
                $bookeyTransactionID = $dataResponse['BookeeyTrackId'] ?? '';
                $merchantTxnId = $dataResponse['MerchantTxnRefNo'] ?? '';
                $errorCode = $dataResponse['ErrorCode'] ?? 0;


                if ($dataResponse['ErrorCode'] == 0) {
                    $isPaid = true;
                    $paymentType = $dataResponse['PaymentType'];
                    $paymentID = $dataResponse['PaymentId'];
                    $processDate = $dataResponse['ProcessDate'];
                    $bankReference = $dataResponse['BankRefNo'];
                }
            }
        }



        $this->addPaymentData(
            $cartID,
            $amount,
            $orderID,
            $merchantTxnId,
            $bookeyTransactionID,
            $paymentID,
            $paymentStatus,
            $bookeyTransactionID,
            $bankReference,
            $paymentType,
            $errorCode,
            $finalStatus,
            $orderStatus,
            $orderState
        );

        if ($isPaid && $order && $order->getId()) {

            $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING)
                ->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
            // Optionally add a comment
            $order->addStatusHistoryComment('Payment successful via gateway callback.');

            $payment = $order->getPayment();

            $payment->setTransactionId($transactionID);
            $payment->setLastTransId($transactionID);

            $payment->setIsTransactionClosed(true);
            $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE, null, false);
            $payment->setAdditionalInformation('gateway_transaction_id', $transactionID);
            $payment->setAdditionalInformation('merchant_transaction_id', $merchantTxnId);
            $payment->setAdditionalInformation('payment_gateway', 'Bede Payment Gateway: ' . $paymentType);
            $payment->setAdditionalInformation('payment_id', $paymentID);
            $payment->setAdditionalInformation('bank_reference_number', $bankReference);

            $order->save();

            $successUrlWithParams = $successURL . (strpos($successURL, '?') !== false ? '&' : '?') . http_build_query([
                // 'status' => 'success',
                // 'order_id' => $order->getIncrementId(),
                // 'transaction_id' => $transactionID,
                'merchant_transaction_id' => $merchantTxnId,
                // 'payment_type' => $paymentType,
                // 'payment_id' => $paymentID,
                // 'bank_reference' => $bankReference,
                // 'amount' => $order->getOrderCurrencyCode() . " " . $order->getGrandTotal(),
            ]);

            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)
                ->setUrl($successUrlWithParams);
        } else {
            $order->setState(\Magento\Sales\Model\Order::STATE_CANCELED)
                ->setStatus(\Magento\Sales\Model\Order::STATE_CANCELED);
            $order->addStatusHistoryComment('Payment failed: ' . $errorMessage);

            $payment = $order->getPayment();
            $payment->setTransactionId($merchantTxnId);
            $payment->setIsTransactionClosed(true);
            $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_VOID, null, false);
            $payment->setAdditionalInformation('error_message', $errorMessage);
            $payment->setAdditionalInformation('error_code', $errorCode);
            $payment->setAdditionalInformation('payment_gateway', 'Bede Payment Gateway: ' . $paymentType);

            $order->save();

            $failureUrlWithParams = $failureURL . (strpos($failureURL, '?') !== false ? '&' : '?') . http_build_query([
                // 'status' => 'failure',
                // 'order_id' => $order ? $order->getIncrementId() : '',
                // 'error_message' => $errorMessage,
                // 'error_code' => $errorCode,
                // 'transaction_id' => $transactionID ?? "",
                'merchant_transaction_id' => $merchantTxnId,
            ]);

            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)
                ->setUrl($failureUrlWithParams);
        }
    }

    protected function addPaymentData(
        $cartID,
        $amount,
        $orderID,
        $merchantTxnId,
        $transactionID,
        $paymentID,
        $paymentStatus,
        $bookeeyTrackID,
        $bankReference,
        $paymentMethod,
        $errorCode,
        $finalStatus,
        $orderStatus,
        $orderState
    ) {
        $arr = [
            'cart_id' => $cartID,
            'amount' => $amount,
            'order_id' => $orderID,
            'merchant_track_id' => $merchantTxnId,
            'transaction_id' => $transactionID,
            'payment_id' => $paymentID,
            'payment_status' => $paymentStatus,
            'bookeey_track_id' => $bookeeyTrackID,
            'bank_ref_number' => $bankReference,
            'payment_method' => $paymentMethod,
            'error_code' => $errorCode,
            'final_status' => $finalStatus,
            'order_status' => $orderStatus,
            'order_state' => $orderState,
            'refund_status' => null,
            'refund_amount' => null,
            'refund_request' => null,
            'refund_response' => null,
        ];

        $this->paymentRepository->updatePaymentData($merchantTxnId, $arr);
    }
}
