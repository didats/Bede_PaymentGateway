<?php

namespace Bede\PaymentGateway\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class OrderStatusChange implements ObserverInterface
{
    protected $resourceConnection;
    protected $logger;

    public function __construct(
        ResourceConnection $resourceConnection,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        try {
            $order = $observer->getEvent()->getOrder();

            if (!$order || !$order->getId()) {
                return;
            }

            // Only update if the order actually changed status/state
            if (!$order->hasDataChanges()) {
                return;
            }

            $orderId = $order->getId();
            $newStatus = $order->getStatus();
            $newState = $order->getState();

            // Check if status or state actually changed
            $originalData = $order->getOrigData();
            $statusChanged = !$originalData ||
                ($originalData['status'] ?? '') !== $newStatus ||
                ($originalData['state'] ?? '') !== $newState;

            if ($statusChanged) {
                // Update bede_payments table
                $this->updateBedePaymentStatus($orderId, $newStatus, $newState);
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to update Bede payment status: ' . $e->getMessage());
        }
    }

    private function updateBedePaymentStatus($orderId, $status, $state)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('bede_payments');

        // Check if there are any bede_payments records for this order
        $select = $connection->select()
            ->from($tableName, ['COUNT(*)'])
            ->where('order_id = ?', $orderId);

        $count = $connection->fetchOne($select);

        if ($count > 0) {
            // Update all payments for this order
            $affectedRows = $connection->update(
                $tableName,
                [
                    'order_status' => $status,
                    'order_state' => $state,
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                ['order_id = ?' => $orderId]
            );

            $this->logger->info("Updated {$affectedRows} Bede payment record(s) for Order ID: {$orderId} to Status: {$status}, State: {$state}");
        }
    }
}
