<?php

namespace Bede\PaymentGateway\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Bede\PaymentGateway\Helper\Data;
use Bede\PaymentGateway\Model\Payment\PaymentRepository;
use Psr\Log\LoggerInterface;

class InjectPaymentInfoResponse implements ObserverInterface
{

    protected $helper;
    protected $paymentRepository;
    protected $logger;

    public function __construct(
        Data $helper,
        PaymentRepository $paymentRepository,
        LoggerInterface $logger
    ) {
        $this->helper = $helper;
        $this->paymentRepository = $paymentRepository;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        /** @var \Magento\Framework\App\Response\Http $response */
        $response = $observer->getEvent()->getResponse();

        /** @var \Magento\Framework\App\Request\Http $request */
        $request = $observer->getEvent()->getRequest();

        $successUrl = $this->helper->getSuccessUrl();
        $failureUrl = $this->helper->getFailureUrl();

        // Extract the path from the URLs
        $successPath = parse_url($successUrl, PHP_URL_PATH);
        $failurePath = parse_url($failureUrl, PHP_URL_PATH);
        $currentPath = parse_url($request->getRequestUri(), PHP_URL_PATH);

        if ($currentPath === $successPath || $currentPath === $failurePath) {
            $merchantTxnId = $request->getParam('merchant_transaction_id');

            // Fetch payment data from database
            $paymentData = null;
            if ($merchantTxnId) {
                $paymentData = $this->paymentRepository->getPaymentByMerchantTrackId($merchantTxnId);
            }

            // Generate the payment info HTML
            $paymentHtml = $this->generatePaymentInfoHtml($request, $paymentData, ($currentPath === $successPath));

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

    private function generatePaymentInfoHtml($request, ?array $paymentData, bool $isSuccess)
    {
        // Get data from request parameters (fallback)
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
        $paymentStatus = $request->getParam('payment_status');

        if ($paymentData) {
            $orderId = $paymentData['order_id'] ?: $orderId;
            $transactionId = $paymentData['transaction_id'] ?: $transactionId;
            $merchantTxnId = $paymentData['merchant_track_id'] ?: $merchantTxnId;
            $paymentType = $paymentData['payment_method'] ?: $paymentType;
            $paymentId = $paymentData['payment_id'] ?: $paymentId;
            $bankReference = $paymentData['bank_ref_number'] ?: $bankReference;
            $amount = $paymentData['amount'] ? number_format($paymentData['amount'], 2) : $amount;
            $errorCode = $paymentData['error_code'] ?: $errorCode;

            // Additional data from database
            $cartId = $paymentData['cart_id'];
            $paymentStatus = $paymentData['payment_status'];
            $bookeeyTrackId = $paymentData['bookeey_track_id'];
            $createdAt = $paymentData['created_at'];
            $updatedAt = $paymentData['updated_at'];
        }

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
                'Payment Status' => $paymentStatus,
                'Amount' => $amount
            ];
        } else {
            $html .= '<h3>Payment Failed</h3>';

            $fields = [
                'Order ID' => $orderId,
                'Transaction ID' => $transactionId,
                'Merchant Transaction ID' => $merchantTxnId,
                'Payment Status' => $paymentStatus,
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
