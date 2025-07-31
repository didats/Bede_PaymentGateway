<?php

namespace Bede\PaymentGateway\Model\Payment;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class PaymentRepository
{
    private $resourceConnection;
    private $logger;

    public function __construct(
        ResourceConnection $resourceConnection,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    /**
     * Add payment data
     */
    public function addPaymentData(array $data): bool
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $tableName = $this->resourceConnection->getTableName('bede_payments');

            // Add timestamps if not provided
            if (!isset($data['created_at'])) {
                $data['created_at'] = date('Y-m-d H:i:s');
            }
            if (!isset($data['updated_at'])) {
                $data['updated_at'] = date('Y-m-d H:i:s');
            }

            return $connection->insert($tableName, $data) > 0;
        } catch (\Exception $e) {
            $this->logger->error('Failed to add payment data: ' . $e->getMessage());
            throw new LocalizedException(__('Unable to save payment data.'));
        }
    }

    /**
     * Add log entry
     */
    public function addLog(array $data): bool
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $tableName = $this->resourceConnection->getTableName('bede_payment_logs');

            // Add timestamp if not provided
            if (!isset($data['created_at'])) {
                $data['created_at'] = date('Y-m-d H:i:s');
            }

            return $connection->insert($tableName, $data) > 0;
        } catch (\Exception $e) {
            $this->logger->error('Failed to add log: ' . $e->getMessage());
            throw new LocalizedException(__('Unable to save log entry.'));
        }
    }

    public function getPaymentByMerchantTrackId(string $merchantTrackId): ?array
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $tableName = $this->resourceConnection->getTableName('bede_payments');

            $select = $connection->select()
                ->from($tableName)
                ->where('merchant_track_id = ?', $merchantTrackId);

            $result = $connection->fetchRow($select);
            return $result ?: null;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get payment by merchant track ID: ' . $e->getMessage());
            return null;
        }
    }

    public function getPaymentByCartId(int $cartId): ?array
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $tableName = $this->resourceConnection->getTableName('bede_payments');

            $select = $connection->select()
                ->from($tableName)
                ->where('cart_id = ?', $cartId)
                ->order('created_at DESC')
                ->limit(1);

            $result = $connection->fetchRow($select);
            return $result ?: null;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get payment by cart ID: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update payment data
     */
    public function updatePaymentData(string $merchantTrackID, array $data): bool
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $tableName = $this->resourceConnection->getTableName('bede_payments');

            $data['updated_at'] = date('Y-m-d H:i:s');

            return $connection->update($tableName, $data, ['merchant_track_id = ?' => $merchantTrackID]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to update payment data: ' . $e->getMessage());
            throw new LocalizedException(__('Unable to update payment data.'));
        }
    }

    /**
     * Search payments for refund page
     */
    public function searchPayments(array $filters): array
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $tableName = $this->resourceConnection->getTableName('bede_payments');

            $select = $connection->select()->from($tableName);

            // Apply filters
            if (!empty($filters['order_id'])) {
                $select->where('order_id LIKE ?', '%' . $filters['order_id'] . '%');
            }

            if (!empty($filters['transaction_id'])) {
                $select->where('transaction_id LIKE ?', '%' . $filters['transaction_id'] . '%');
            }

            if (!empty($filters['payment_status'])) {
                $select->where('payment_status = ?', $filters['payment_status']);
            }

            if (!empty($filters['date_from'])) {
                $select->where('created_at >= ?', $filters['date_from'] . ' 00:00:00');
            }

            if (!empty($filters['date_to'])) {
                $select->where('created_at <= ?', $filters['date_to'] . ' 23:59:59');
            }

            $select->order('created_at DESC')->limit(50);

            return $connection->fetchAll($select);
        } catch (\Exception $e) {
            $this->logger->error('Failed to search payments: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get payment by transaction ID
     */
    public function getPaymentByTransactionId(string $transactionId): ?array
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $tableName = $this->resourceConnection->getTableName('bede_payments');

            $select = $connection->select()
                ->from($tableName)
                ->where('transaction_id = ?', $transactionId);

            $result = $connection->fetchRow($select);
            return $result ?: null;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get payment: ' . $e->getMessage());
            return null;
        }
    }
}
