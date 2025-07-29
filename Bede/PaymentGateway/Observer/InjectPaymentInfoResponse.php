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

        $html = '<div class="bede-payment-info">';

        if ($isSuccess) {
            $html .= '<h3>Payment Successful!</h3>';

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
            $html .= '<h3>Payment Failed</h3>';

            $fields = [
                'Order ID' => $orderId,
                'Transaction ID' => $transactionId,
                'Merchant Transaction ID' => $merchantTxnId,
                'Error Message' => $errorMessage,
                'Error Code' => $errorCode
            ];
        }

        $html .= '<table class="table">';

        foreach ($fields as $label => $value) {
            if (!empty($value)) {
                $html .= '<tr>';
                $html .= '<td class="label" width="30%">' . htmlspecialchars($label) . ':</td>';
                $html .= '<td>' . htmlspecialchars($value) . '</td>';
                $html .= '</tr>';
            }
        }

        $html .= '</table>';

        if ($isSuccess) {
            $html .= '<h4>Thank you for your payment!</h4> <p>Your transaction has been processed successfully.</p>';
        } else {
            $html .= '<h4>Payment could not be processed.</h4> <p>Please try again or contact support if the issue persists.</p>';
        }

        $html .= '</div>';

        return $html;
    }
}
