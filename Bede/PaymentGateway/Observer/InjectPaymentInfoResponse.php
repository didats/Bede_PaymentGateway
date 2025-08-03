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

    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        // For layout_render_before event, get the layout and request
        $layout = $observer->getEvent()->getLayout();

        // If layout is not in the event, try to get it from observer data
        if (!$layout) {
            $layout = $observer->getEvent()->getData('layout');
        }

        // If still no layout, try getting from ObjectManager
        if (!$layout) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $layout = $objectManager->get(\Magento\Framework\View\LayoutInterface::class);
        }

        // Get request from ObjectManager since it might not be in the event
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $request = $objectManager->get(\Magento\Framework\App\Request\Http::class);

        if (!$request) {
            return;
        }

        $successUrl = $this->helper->getSuccessUrl();
        $failureUrl = $this->helper->getFailureUrl();

        // Extract the path from the URLs
        $successPath = rtrim(parse_url($successUrl, PHP_URL_PATH), '/');
        $failurePath = rtrim(parse_url($failureUrl, PHP_URL_PATH), '/');
        $currentPath = rtrim(parse_url($request->getRequestUri(), PHP_URL_PATH), '/');

        $isSuccessOrFailurePage = ($currentPath === $successPath || $currentPath === $failurePath);

        if ($isSuccessOrFailurePage) {
            $merchantTxnId = $request->getParam('merchant_transaction_id');

            // Fetch payment data from database
            $paymentData = null;
            if ($merchantTxnId) {
                try {
                    $paymentData = $this->paymentRepository->getPaymentByMerchantTrackId($merchantTxnId);
                } catch (\Exception $e) {
                    // Log error if needed
                }
            }

            // Generate the payment info HTML
            $paymentHtml = $this->generatePaymentInfoHtml($request, $paymentData, ($currentPath === $successPath));

            if ($layout) {
                // Try to add a block to the layout
                try {
                    // Create a text block with our payment HTML
                    $block = $layout->createBlock(\Magento\Framework\View\Element\Text::class, 'bede_payment_info');
                    $block->setText($paymentHtml);

                    // Try to add it to the content area or main content
                    if ($layout->getBlock('content')) {
                        $layout->getBlock('content')->insert($block, '', false, 'bede_payment_info');
                    } elseif ($layout->getBlock('main')) {
                        $layout->getBlock('main')->insert($block, '', false, 'bede_payment_info');
                    } elseif ($layout->getBlock('page.wrapper')) {
                        $layout->getBlock('page.wrapper')->insert($block, '', false, 'bede_payment_info');
                    } else {
                        // Try to inject using JavaScript after the CMS content
                        echo '<script>document.addEventListener("DOMContentLoaded", function() { 
                            var targets = [
                                "#maincontent .column.main",
                                ".cms-page-view .column.main", 
                                ".column.main",
                                ".cms-page-view .page-main",
                                ".page-main .cms-content",
                                ".page-main",
                                ".main-content",
                                ".content-wrapper",
                                ".page-wrapper .main"
                            ];
                            
                            for (var i = 0; i < targets.length; i++) {
                                var content = document.querySelector(targets[i]);
                                if (content) {
                                    content.insertAdjacentHTML("beforeend", ' . json_encode($paymentHtml) . ');
                                    break;
                                }
                            }
                        });</script>';
                    }
                } catch (\Exception $e) {
                    // Fallback to direct output
                    echo $paymentHtml;
                }
            } else {
                // Direct output as fallback
                echo $paymentHtml;
            }
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

        $html = '<div class="bede-payment-info" style="margin-top: 30px; padding: 20px; border: 1px solid #ddd; border-radius: 5px; background-color: #f9f9f9;">';

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
