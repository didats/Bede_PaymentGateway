<?php

namespace Bede\PaymentGateway\Controller\Adminhtml\Refund;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class Request extends Action
{
    protected $jsonFactory;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        try {
            $paymentId = $this->getRequest()->getParam('payment_id');
            $bookeyTrackId = $this->getRequest()->getParam('bookeey_track_id');
            $merchantTrackId = $this->getRequest()->getParam('merchant_track_id');
            $amount = $this->getRequest()->getParam('amount');
            $checkStatus = $this->getRequest()->getParam('check_status', false);
            $cancel = $this->getRequest()->getParam('cancel', false);

            if (!$paymentId || !$bookeyTrackId || !$merchantTrackId) {
                return $result->setData([
                    'success' => false,
                    'message' => __('Missing required parameters for refund request.')
                ]);
            }

            // Get dependencies via ObjectManager
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $resourceConnection = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
            $bede = $objectManager->get(\Bede\PaymentGateway\Model\Payment\Bede::class);
            $helper = $objectManager->get(\Bede\PaymentGateway\Helper\Data::class);
            $logger = $objectManager->get(\Psr\Log\LoggerInterface::class);

            // Get payment data from database
            $paymentData = $this->getPaymentData($paymentId, $resourceConnection);
            if (!$paymentData) {
                return $result->setData([
                    'success' => false,
                    'message' => __('Payment record not found.')
                ]);
            }

            // Check if refund is already requested or completed
            if (in_array($paymentData['refund_status'], ['requested', 'processing', 'completed'])) {
                return $result->setData([
                    'success' => false,
                    'message' => __('Refund has already been requested or processed for this payment.')
                ]);
            }

            // Configure Bede client
            $bede->merchantID = $helper->getMerchantId();
            $bede->secretKey = $helper->getSecretKey();
            $bede->baseURL = $helper->getBaseUrl();

            // Make refund request
            if ($cancel) {
                $response = $bede->cancelRefund($bookeyTrackId, $merchantTrackId);
            } else if ($checkStatus) {
                $response = $bede->statusRefund($bookeyTrackId, $merchantTrackId);
            } else {
                $response = $bede->requestRefund($bookeyTrackId, $merchantTrackId, $amount);
            }

            // Log the request
            $connection = $resourceConnection->getConnection();
            $tableName = $resourceConnection->getTableName('bede_payment_logs');
            $connection->insert($tableName, $bede->logData);

            $jsonResponse = json_decode($response, true);

            if ($jsonResponse && isset($jsonResponse['data']) && $jsonResponse['isSuccess'] === true) {
                // Update payment record
                $status = "Requested";
                if (isset($jsonResponse['data']['ReqSts'][0]['ReqSts'])) {
                    $status = $jsonResponse['data']['ReqSts'][0]['ReqSts'];
                }
                $this->updatePaymentRefundStatus($paymentId, $status, $resourceConnection, $amount, $response, $bede->logData);

                return $result->setData([
                    'success' => true,
                    'message' => $jsonResponse['message'] ?? __('Refund request submitted successfully.')
                ]);
            } else {
                $errorMessage = $jsonResponse['ErrMsg'] ?? 'Unknown error occurred';
                $this->updatePaymentRefundStatus($paymentId, 'Failed', $resourceConnection, $amount, $response, $bede->logData);

                return $result->setData([
                    'success' => false,
                    'message' => __('Refund request failed: %1', $errorMessage)
                ]);
            }
        } catch (\Exception $e) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $logger = $objectManager->get(\Psr\Log\LoggerInterface::class);
            $logger->error('Request refund error: ' . $e->getMessage());
            return $result->setData([
                'success' => false,
                'message' => __('An error occurred while processing the refund request: %1', $e->getMessage())
            ]);
        }
    }

    private function getPaymentData($paymentId, $resourceConnection)
    {
        $connection = $resourceConnection->getConnection();
        $tableName = $resourceConnection->getTableName('bede_payments');

        $select = $connection->select()
            ->from($tableName)
            ->where('id = ?', $paymentId);

        return $connection->fetchRow($select);
    }

    private function updatePaymentRefundStatus($paymentId, $status, $resourceConnection, $amount = null, $response = null, $request = null)
    {
        $connection = $resourceConnection->getConnection();
        $tableName = $resourceConnection->getTableName('bede_payments');

        $updateData = [
            'refund_status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($amount) {
            $updateData['refund_amount'] = $amount;
        }

        if ($response) {
            $updateData['refund_response'] = $response;
        }

        if ($request) {
            $updateData['refund_request'] = json_encode($request);
        }

        $connection->update(
            $tableName,
            $updateData,
            ['id = ?' => $paymentId]
        );
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Bede_PaymentGateway::refund');
    }
}
