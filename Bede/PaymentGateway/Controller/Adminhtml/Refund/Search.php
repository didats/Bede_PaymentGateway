<?php

namespace Bede\PaymentGateway\Controller\Adminhtml\Refund;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

class Search extends Action
{
    protected $jsonFactory;
    protected $orderCollectionFactory;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        CollectionFactory $orderCollectionFactory
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $result = $this->jsonFactory->create();

        try {
            $orders = $this->searchOrders($params);
            return $result->setData([
                'success' => true,
                'orders' => $orders
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    private function searchOrders($params)
    {
        $collection = $this->orderCollectionFactory->create();

        // Join with payment table to get transaction info
        $collection->getSelect()->joinLeft(
            ['payment' => $collection->getTable('sales_order_payment')],
            'main_table.entity_id = payment.parent_id',
            ['last_trans_id']
        );

        // Apply filters
        if (!empty($params['order_id'])) {
            $collection->addFieldToFilter('increment_id', ['like' => '%' . $params['order_id'] . '%']);
        }

        if (!empty($params['transaction_id'])) {
            $collection->addFieldToFilter('payment.last_trans_id', ['like' => '%' . $params['transaction_id'] . '%']);
        }

        if (!empty($params['order_status'])) {
            $collection->addFieldToFilter('status', $params['order_status']);
        }

        if (!empty($params['date_from'])) {
            $collection->addFieldToFilter('created_at', ['gteq' => $params['date_from'] . ' 00:00:00']);
        }

        if (!empty($params['date_to'])) {
            $collection->addFieldToFilter('created_at', ['lteq' => $params['date_to'] . ' 23:59:59']);
        }

        // Only get orders with Bede payment method
        $collection->getSelect()->joinLeft(
            ['bede_logs' => $collection->getTable('bede_payment_logs')],
            'main_table.quote_id = bede_logs.cart_id',
            ['bede_transaction_ref' => 'bede_logs.transaction_ref']
        );
        $collection->addFieldToFilter('bede_logs.transaction_ref', ['notnull' => true]);

        $collection->setPageSize(50);

        $orders = [];
        foreach ($collection as $order) {
            $orders[] = [
                'entity_id' => $order->getId(),
                'increment_id' => $order->getIncrementId(),
                'status' => $order->getStatus(),
                'grand_total' => $order->getGrandTotal(),
                'currency_code' => $order->getOrderCurrencyCode(),
                'created_at' => $order->getCreatedAt(),
                'customer_name' => $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname(),
                'transaction_id' => $order->getLastTransId(),
                'bede_transaction_ref' => $order->getBedeTransactionRef(),
                'can_refund' => $order->canCreditmemo()
            ];
        }

        return $orders;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Bede_PaymentGateway::refund');
    }
}
