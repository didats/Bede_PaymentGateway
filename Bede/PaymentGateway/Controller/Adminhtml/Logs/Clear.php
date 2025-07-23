<?php

namespace Bede\PaymentGateway\Controller\Adminhtml\Logs;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception;
use Magento\Framework\Exception\LocalizedException;
use Bede\PaymentGateway\Model\LogFactory;

class Clear extends Action
{

    public function __construct(
        Context $context,
        LogFactory $logFactory
    ) {
        $this->logFactory = $logFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $logsTruncate = $this->logFactory->create();    
        $connection = $logsTruncate->getCollection()->getConnection();
        try {
            $connection->truncateTable($logsTruncate->getCollection()->getMainTable());
            $this->messageManager->addSuccess(__('Success clear all logs'));
        } catch (LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (Exception $e) {
            $this->messageManager->addException($e, __('Something went wrong.'));
        }

        return $resultRedirect->setPath('bedepg/logs/index');
    }
}