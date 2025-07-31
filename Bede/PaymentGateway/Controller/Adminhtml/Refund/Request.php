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

            if (!$paymentId || !$bookeyTrackId || !$merchantTrackId || !$amount) {
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
            $response = $bede->requestRefund($bookeyTrackId, $merchantTrackId, $amount);

            // Log the request
            $this->logRefundRequest($paymentId, $response, $merchantTrackId, $resourceConnection, $bede);

            $jsonResponse = json_decode($response, true);

            if ($jsonResponse && isset($jsonResponse['StatusCD']) && $jsonResponse['StatusCD'] === 0) {
                // Update payment record
                $this->updatePaymentRefundStatus($paymentId, 'requested', $resourceConnection, $amount, $response);

                return $result->setData([
                    'success' => true,
                    'message' => __('Refund request submitted successfully. Reference: %1', $jsonResponse['RefNo'] ?? 'N/A')
                ]);
            } else {
                $errorMessage = $jsonResponse['ErrMsg'] ?? 'Unknown error occurred';
                $this->updatePaymentRefundStatus($paymentId, 'failed', $resourceConnection, null, $response);

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

    private function updatePaymentRefundStatus($paymentId, $status, $resourceConnection, $amount = null, $response = null)
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

        $connection->update(
            $tableName,
            $updateData,
            ['id = ?' => $paymentId]
        );
    }

    private function logRefundRequest($paymentId, $response, $merchantTrackId, $resourceConnection, $bede)
    {
        $connection = $resourceConnection->getConnection();
        $tableName = $resourceConnection->getTableName('bede_payment_logs');

        $logData = [
            'type' => 'refund-request',
            'endpoint' => '/bkycoreapi/v1/Accounts/request-refund',
            'method' => 'POST',
            'status' => 200,
            'request_data' => json_encode($bede->requestData ?? []),
            'response_data' => $response,
            'merchant_track_id' => $merchantTrackId,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $connection->insert($tableName, $logData);
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Bede_PaymentGateway::refund');
    }
}
