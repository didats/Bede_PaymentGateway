<?php

namespace Bede\PaymentGateway\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class InjectPaymentInfoResponse implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        /** @var \Magento\Framework\App\Response\Http $response */
        $response = $observer->getEvent()->getResponse();

        /** @var \Magento\Framework\App\Request\Http $request */
        $request = $observer->getEvent()->getRequest();

        // Get your success/failure URLs from config
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $helper = $objectManager->get(\Bede\PaymentGateway\Helper\Data::class);

        $successUrl = $helper->getSuccessUrl();
        $failureUrl = $helper->getFailureUrl();

        // Extract the path from the URLs
        $successPath = parse_url($successUrl, PHP_URL_PATH);
        $failurePath = parse_url($failureUrl, PHP_URL_PATH);
        $currentPath = parse_url($request->getRequestUri(), PHP_URL_PATH);


        if ($currentPath === $successPath || $currentPath === $failurePath) {
            // Generate the payment info HTML
            $paymentHtml = $this->generatePaymentInfoHtml($request, ($currentPath === $successPath));

            // Get the current response body
            $body = $response->getBody();

            // Inject the payment info after the opening <body> tag or at the beginning of content
            if (strpos($body, '<div class="page-wrapper">') !== false) {
                $body = str_replace(
                    '<div class="page-wrapper">',
                    '<div class="page-wrapper">' . $paymentHtml,
                    $body
                );
            } elseif (strpos($body, '<main ') !== false) {
                $body = preg_replace(
                    '/(<main [^>]*>)/',
                    '$1' . $paymentHtml,
                    $body
                );
            } else {
                // Fallback: inject after <body> tag
                $body = str_replace('<body', $paymentHtml . '<body', $body);
            }

            $response->setBody($body);
        }
    }

    private function generatePaymentInfoHtml($request, bool $isSuccess)
    {
        $status = $request->getParam('status');
        $orderId = $request->getParam('order_id');
        $transactionId = $request->getParam('transaction_id');
        $merchantTxnId = $request->getParam('merchant_transaction_id');
        $paymentType = $request->getParam('payment_type');
        $paymentId = $request->getParam('payment_id');
        $bankReference = $request->getParam('bank_reference');
        $amount = $request->getParam('amount');
        $errorMessage = $request->getParam('error_message');
        $errorCode = $request->getParam('error_code');

        $html = '<div class="bede-payment-info" style="margin: 20px 0; padding: 20px; border: 2px solid #ddd; border-radius: 8px; background-color: #f9f9f9; font-family: Arial, sans-serif;">';

        if ($isSuccess) {
            $html .= '<div style="color: #4CAF50; font-weight: bold; font-size: 20px; margin-bottom: 15px; text-align: center;">✅ Payment Successful!</div>';

            $fields = [
                'Order ID' => $orderId,
                'Transaction ID' => $transactionId,
                'Merchant Transaction ID' => $merchantTxnId,
                'Payment Method' => $paymentType,
                'Payment ID' => $paymentId,
                'Bank Reference' => $bankReference,
                'Amount' => $amount
            ];
        } else {
            $html .= '<div style="color: #f44336; font-weight: bold; font-size: 20px; margin-bottom: 15px; text-align: center;">❌ Payment Failed</div>';

            $fields = [
                'Order ID' => $orderId,
                'Transaction ID' => $transactionId,
                'Merchant Transaction ID' => $merchantTxnId,
                'Error Message' => $errorMessage,
                'Error Code' => $errorCode
            ];
        }

        $html .= '<table style="width: 100%; border-collapse: collapse; margin-top: 10px;">';

        foreach ($fields as $label => $value) {
            if (!empty($value)) {
                $html .= '<tr style="border-bottom: 1px solid #ddd;">';
                $html .= '<td style="padding: 10px; background-color: #f5f5f5; font-weight: bold; width: 40%; vertical-align: top;">' . htmlspecialchars($label) . ':</td>';
                $html .= '<td style="padding: 10px; background-color: #fff; word-break: break-word;">' . htmlspecialchars($value) . '</td>';
                $html .= '</tr>';
            }
        }

        $html .= '</table>';

        if ($isSuccess) {
            $html .= '<div style="margin-top: 15px; padding: 10px; background-color: #e8f5e8; border-left: 4px solid #4CAF50; color: #2e7d32;"><strong>Thank you for your payment!</strong> Your transaction has been processed successfully.</div>';
        } else {
            $html .= '<div style="margin-top: 15px; padding: 10px; background-color: #ffebee; border-left: 4px solid #f44336; color: #c62828;"><strong>Payment could not be processed.</strong> Please try again or contact support if the issue persists.</div>';
        }

        $html .= '</div>';

        return $html;
    }
}
