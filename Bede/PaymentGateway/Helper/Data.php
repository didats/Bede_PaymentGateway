<?php

/**
 * Bede Payment Helper Data
 */

namespace Bede\PaymentGateway\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const XML_PATH_ENVIRONMENT = 'bede_payment_connection/general/environment';
    const XML_PATH_LIVE_BASE_URL = 'bede_payment_connection/live_settings/base_url';
    const XML_PATH_LIVE_MERCHANT_ID = 'bede_payment_connection/live_settings/merchant_id';
    const XML_PATH_LIVE_SECRET_KEY = 'bede_payment_connection/live_settings/secret_key';
    const XML_PATH_LIVE_SUCCESS_URL = 'bede_payment_connection/live_settings/success_url';
    const XML_PATH_LIVE_FAILURE_URL = 'bede_payment_connection/live_settings/failure_url';
    const XML_PATH_LIVE_SUBMERCHANT_UID = 'bede_payment_connection/live_settings/submerchant_uid';

    const XML_PATH_TEST_BASE_URL = 'bede_payment_connection/test_settings/base_url';
    const XML_PATH_TEST_MERCHANT_ID = 'bede_payment_connection/test_settings/merchant_id';
    const XML_PATH_TEST_SECRET_KEY = 'bede_payment_connection/test_settings/secret_key';
    const XML_PATH_TEST_SUCCESS_URL = 'bede_payment_connection/test_settings/success_url';
    const XML_PATH_TEST_FAILURE_URL = 'bede_payment_connection/test_settings/failure_url';
    const XML_PATH_TEST_SUBMERCHANT_UID = 'bede_payment_connection/test_settings/submerchant_uid';

    const XML_PATH_ENABLE_LOGGING = 'bede_payment_logs/settings/enable_logging';
    const XML_PATH_LOG_LEVEL = 'bede_payment_logs/settings/log_level';

    const XML_PATH_ENABLE_REFUND = 'bede_payment_refund/settings/enable_refund';
    const XML_PATH_PARTIAL_REFUND = 'bede_payment_refund/settings/partial_refund';
    const XML_PATH_REFUND_TIMEOUT = 'bede_payment_refund/settings/refund_timeout';

    /**
     * Get environment (test/live)
     *
     * @param null $storeId
     * @return string
     */
    public function getEnvironment($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_ENVIRONMENT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get base URL based on environment
     *
     * @param null $storeId
     * @return string
     */
    public function getBaseUrl($storeId = null)
    {
        $environment = $this->getEnvironment($storeId);
        $path = ($environment === 'live') ? self::XML_PATH_LIVE_BASE_URL : self::XML_PATH_TEST_BASE_URL;

        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get merchant ID based on environment
     *
     * @param null $storeId
     * @return string
     */
    public function getMerchantId($storeId = null)
    {
        $environment = $this->getEnvironment($storeId);
        $path = ($environment === 'live') ? self::XML_PATH_LIVE_MERCHANT_ID : self::XML_PATH_TEST_MERCHANT_ID;

        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get secret key based on environment
     *
     * @param null $storeId
     * @return string
     */
    public function getSecretKey($storeId = null)
    {
        $environment = $this->getEnvironment($storeId);
        $path = ($environment === 'live') ? self::XML_PATH_LIVE_SECRET_KEY : self::XML_PATH_TEST_SECRET_KEY;

        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get success URL based on environment
     *
     * @param null $storeId
     * @return string
     */
    public function getSuccessUrl($storeId = null)
    {
        $environment = $this->getEnvironment($storeId);
        $path = ($environment === 'live') ? self::XML_PATH_LIVE_SUCCESS_URL : self::XML_PATH_TEST_SUCCESS_URL;

        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get failure URL based on environment
     *
     * @param null $storeId
     * @return string
     */
    public function getFailureUrl($storeId = null)
    {
        $environment = $this->getEnvironment($storeId);
        $path = ($environment === 'live') ? self::XML_PATH_LIVE_FAILURE_URL : self::XML_PATH_TEST_FAILURE_URL;

        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get submerchant UID based on environment
     *
     * @param null $storeId
     * @return string
     */
    public function getSubmerchantUid($storeId = null)
    {
        $environment = $this->getEnvironment($storeId);
        $path = ($environment === 'live') ? self::XML_PATH_LIVE_SUBMERCHANT_UID : self::XML_PATH_TEST_SUBMERCHANT_UID;

        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
