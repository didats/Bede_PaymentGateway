<?php

namespace Bede\PaymentGateway\Model\Payment;

class Bede
{
    private $moduleVersion = "1.0.0";
    private $moduleName = "Magento";

    public $baseURL = "https://demo.bookeey.com";
    public $merchantID = "mer2500011";
    public $secretKey = "7483493";
    public $successURL;
    public $failureURL;
    public $subMerchantID;

    public $requestLogger;
    public $responseLogger;
    public $requestData;

    public $logger;
    public $rawHashing;

    public function __construct() {}

    private function generateNumber(): string
    {
        $code = substr((string)hrtime(true), -8);
        return $code;
    }

    private function curlCommand($url, $jsonData): string
    {
        return <<<CURL
curl --location '$url' \
--header 'Content-Type: application/json' \
--header 'Accept: application/json' \
--data '$jsonData'
CURL;
    }

    private function exec($path, array $postdata, bool $isPost = true): string
    {
        $url = $this->baseURL . $path;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: text/json']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        if ($isPost) {
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postdata));
        } else {
            curl_setopt($curl, CURLOPT_URL, $url);
        }

        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($curl, CURLOPT_TIMEOUT, 400);

        $originalResponse = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $responseLog = "Request:\n";
        $responseLog .= $path . "\n";
        $responseLog .= json_encode($postdata) . "\n\n";
        $responseLog .= "Response:\n";
        $responseLog .= $originalResponse;

        $logger = $url . "\n";
        $logger .= json_encode($postdata) . "\n";
        $logger .= $originalResponse;
        $this->logger = $logger;

        $this->requestLogger = [
            'type' => 'request',
            'endpoint' => $path,
            'method' => ($isPost) ? 'POST' : 'GET',
            'status' => 200,
            'order_id' => "-",
            'transaction_id' => "-",
            'executed_at' => date('Y-m-d H:i:s'),
            'curl_command' => $this->curlCommand($url, json_encode($postdata)),
        ];

        $this->responseLogger = [
            'type' => 'response',
            'endpoint' => $path,
            'method' => ($isPost) ? 'POST' : 'GET',
            'status' => $statusCode,
            'order_id' => "-",
            'transaction_id' => "-",
            'executed_at' => date('Y-m-d H:i:s'),
            'curl_command' => $originalResponse,
        ];


        return $originalResponse;
    }

    private function hashing($trackID, $amount, $generateNumber): string
    {
        $input = sprintf(
            '%s|%s|%s|%s|%s|%s|%s|%s',
            (string)$this->merchantID,
            (string)$trackID,
            (string)$this->successURL,
            (string)$this->failureURL,
            (string)$amount,
            'GEN', // crossCat
            (string)$this->secretKey,
            (string)$generateNumber
        );

        $this->rawHashing = $input;

        return hash("sha512", $input);
    }

    private function rawHashing($trackID, $amount, $generateNumber): string
    {
        $input = sprintf(
            '%s|%s|%s|%s|%s|%s|%s|%s',
            (string)$this->merchantID,
            (string)$trackID,
            (string)$this->successURL,
            (string)$this->failureURL,
            $amount,
            'GEN', // crossCat
            (string)$this->secretKey,
            (string)$generateNumber
        );
        return $input;
    }

    private function IPAddress()
    {
        $ip_keys = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );

        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }

        // Fallback to REMOTE_ADDR or default
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
    }

    private function osInfo()
    {
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown';
        $os_array = array(
            '/windows nt 10/i'      => 'Windows 10',
            '/windows nt 6.3/i'     => 'Windows 8.1',
            '/windows nt 6.2/i'     => 'Windows 8',
            '/windows nt 6.1/i'     => 'Windows 7',
            '/windows nt 6.0/i'     => 'Windows Vista',
            '/windows nt 5.2/i'     => 'Windows Server 2003/XP x64',
            '/windows nt 5.1/i'     => 'Windows XP',
            '/windows xp/i'         => 'Windows XP',
            '/windows nt 5.0/i'     => 'Windows 2000',
            '/windows me/i'         => 'Windows ME',
            '/win98/i'              => 'Windows 98',
            '/win95/i'              => 'Windows 95',
            '/win16/i'              => 'Windows 3.11',
            '/macintosh|mac os x/i' => 'Mac OS X',
            '/mac_powerpc/i'        => 'Mac OS 9',
            '/linux/i'              => 'Linux',
            '/ubuntu/i'             => 'Ubuntu',
            '/iphone/i'             => 'iPhone',
            '/ipod/i'               => 'iPod',
            '/ipad/i'               => 'iPad',
            '/android/i'            => 'Android',
            '/blackberry/i'         => 'BlackBerry',
            '/webos/i'              => 'Mobile'
        );

        $browser_array = array(
            '/msie/i'       => 'Internet Explorer',
            '/firefox/i'    => 'Firefox',
            '/safari/i'     => 'Safari',
            '/chrome/i'     => 'Chrome',
            '/edge/i'       => 'Edge',
            '/opera/i'      => 'Opera',
            '/netscape/i'   => 'Netscape',
            '/maxthon/i'    => 'Maxthon',
            '/konqueror/i'  => 'Konqueror',
            '/mobile/i'     => 'Handheld Browser'
        );

        $os = 'Unknown OS';
        foreach ($os_array as $regex => $value) {
            if (preg_match($regex, $user_agent)) {
                $os = $value;
                break;
            }
        }

        $browser = 'Unknown Browser';
        foreach ($browser_array as $regex => $value) {
            if (preg_match($regex, $user_agent)) {
                $browser = $value;
                break;
            }
        }

        return $os . ' - ' . $browser;
    }

    public function paymentMethods()
    {
        $path = "/pgapi/api/payment/paymethods";
        $response = $this->exec($path, [
            'MerchantId' => $this->merchantID
        ]);

        $json = json_decode($response, true);
        $mapped = array_map(function ($item) {
            return [
                'code' => $item['PM_CD'],
                'title' => $item['PM_Name'],
                'logo' => "https://magento2.awanesia.my.id/" . strtolower($item['PM_CD']) . ".png"
            ];
        }, $json['PayOptions']);

        return $mapped;
    }

    public function paymentStatus($transactionID)
    {
        $path = "/pgapi/api/payment/paymentstatus";
        $hashInput = $this->merchantID . '|' . $this->secretKey;
        $hashMac = hash('sha512', $hashInput);

        $response = $this->exec($path, [
            'Mid' => $this->merchantID,
            'MerchantTxnRefNo' => [
                $transactionID
            ],
            'HashMac' => $hashMac
        ]);

        return $response;
    }

    public function requestLink(BedeBuyer $buyer, $paymentMethod = "knet")
    {
        $path = "/pgapi/api/payment/requestLink";
        $generateNumber = $this->generateNumber();

        $hashMac = $this->hashing($buyer->trackID, $buyer->amount(), $generateNumber);
        $rawHash = $this->rawHashing($buyer->trackID, $buyer->amount(), $generateNumber);

        $postData = [
            'DBRqst' => 'PY_ECom',
            'Do_Appinfo' => array(
                'AppTyp' => $this->moduleName,
                'AppVer' => $this->moduleVersion,
                'ApiVer' => '0.61',
                'IPAddrs' => $this->IPAddress(),
                'Country' => 'Kuwait',
                'APPID' => 'Magento_',
                'MdlID' => 'Bede_Payment_Extension',
                'DevcType' => 'SYSTEM',
                'OS' => $this->osInfo()
            ),
            'Do_MerchDtl' => array(
                'MerchUID' => $this->merchantID,
                'BKY_PRDENUM' => 'Ecom',
                'FURL' => $this->failureURL,
                'SURL' => $this->successURL
            ),
            'Do_MoreDtl' => array(
                'Cust_Data1' => $buyer->customerData1,
                'Cust_Data2' => $buyer->customerData2,
                'Cust_Data3' => $buyer->customerData3,
            ),
            'Do_PyrDtl' => array(
                'Pyr_MPhone' => $buyer->phoneNumber,
                'ISDNCD' => $buyer->countryCode,
                'Pyr_Name' => $buyer->name
            ),
            'Do_TxnDtl' => array(
                array(
                    'SubMerchUID' => $this->subMerchantID ?? "",
                    'Txn_AMT' => (float)$buyer->amount()
                )
            ),
            'Do_TxnHdr' => array(
                'Merch_Txn_UID' => $buyer->trackID,
                'PayFor' => 'ECom',
                'PayMethod' => $paymentMethod,
                'Txn_HDR' => $generateNumber,
                'hashMac' => $hashMac,
                'rawHash' => $rawHash,
                'BKY_Txn_UID' => ''
            )
        ];

        $this->requestData = $postData;

        $response = $this->exec($path, $postData);
        return $response;
    }
}
