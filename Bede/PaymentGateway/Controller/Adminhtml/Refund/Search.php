<?php

namespace Bede\PaymentGateway\Controller\Adminhtml\Refund;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\ResourceConnection;

class Search extends Action
{
    protected $jsonFactory;
    protected $resourceConnection;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        ResourceConnection $resourceConnection
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->resourceConnection = $resourceConnection;
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $result = $this->jsonFactory->create();

        try {
            $payments = $this->searchPayments($params);
            return $result->setData([
                'success' => true,
                'payments' => $payments,
                'count' => count($payments)
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    private function searchPayments($params)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('bede_payments');

        $select = $connection->select()
            ->from(['bp' => $tableName]);

        // Apply filters - Only search within bede_payments table
        if (!empty($params['merchant_track_id'])) {
            $select->where('bp.merchant_track_id LIKE ?', '%' . $params['merchant_track_id'] . '%');
        }

        if (!empty($params['transaction_id'])) {
            $select->where('bp.transaction_id LIKE ?', '%' . $params['transaction_id'] . '%');
        }

        if (!empty($params['order_status'])) {
            $select->where('bp.order_status = ?', $params['order_status']);
        }

        if (!empty($params['date_from'])) {
            $select->where('bp.created_at >= ?', $params['date_from'] . ' 00:00:00');
        }

        if (!empty($params['date_to'])) {
            $select->where('bp.created_at <= ?', $params['date_to'] . ' 23:59:59');
        }

        // Order by created date descending
        $select->order('bp.created_at DESC')
            ->limit(100); // Limit results

        $results = $connection->fetchAll($select);

        $payments = [];
        foreach ($results as $row) {
            $canRefund = $this->canRefundPayment($row);

            $payments[] = [
                'id' => $row['id'],
                'cart_id' => $row['cart_id'] ?: 'N/A',
                'order_id' => $row['order_id'] ?: 'N/A',
                'merchant_track_id' => $row['merchant_track_id'],
                'transaction_id' => $row['transaction_id'] ?: 'N/A',
                'amount' => number_format($row['amount'], 2),
                'payment_status' => $row['payment_status'],
                'order_status' => $row['order_status'],
                'final_status' => $row['final_status'] ?: 'N/A',
                'payment_method' => $row['payment_method'] ?: 'N/A',
                'payment_id' => $row['payment_id'] ?: 'N/A',
                'bank_ref_number' => $row['bank_ref_number'] ?: 'N/A',
                'bookeey_track_id' => $row['bookeey_track_id'] ?: 'N/A',
                'error_code' => $row['error_code'] ?: 'N/A',
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
                'can_refund' => $canRefund,
                'refund_status' => $row['refund_status'] ?? null,
                'refund_request' => $row['refund_request'] ?? "",
                'refund_amount' => $row['refund_amount'] ? number_format($row['refund_amount'], 2) : null
            ];
        }

        return $payments;
    }

    private function canRefundPayment($paymentData)
    {
        // Check if payment is completed and not already fully refunded
        if ($paymentData['payment_status'] !== 'completed') {
            return false;
        }

        // Check if already refunded
        if (isset($paymentData['refund_status']) && $paymentData['refund_status'] === 'completed') {
            return false;
        }

        // Additional checks can be added here
        return true;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Bede_PaymentGateway::refund');
    }
}
