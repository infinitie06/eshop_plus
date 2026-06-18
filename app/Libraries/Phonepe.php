<?php

namespace App\Libraries;

use App\Services\SettingService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class Phonepe
{
    protected $client_id;
    protected $client_secret;
    protected $merchant_id;
    protected $url;
    protected $environment;

    public function __construct()
    {
        $raw = app(\App\Services\SettingService::class)->getSettings('payment_method', true);
        $settings = is_string($raw) ? json_decode($raw, true) : $raw;
        $this->client_id = $settings['phonepe_client_id'] ?? env('PHONEPE_CLIENT_ID', '');
        $this->client_secret = $settings['phonepe_client_secret'] ?? env('PHONEPE_CLIENT_SECRET', '');
        $this->merchant_id = $settings['phonepe_merchant_id'] ?? env('PHONEPE_MERCHANT_ID', '');
        //dd($this->client_id, $this->client_secret, $this->merchant_id);
        $this->environment  = $settings['phonepe_payment_mode'] ?? 'SANDBOX';
        $mode = strtolower($settings['phonepe_mode'] ?? 'sandbox');
        $this->url = $mode === 'production'
            ? 'https://api.phonepe.com/apis/pg'
            : 'https://api-preprod.phonepe.com/apis/pg-sandbox';
    }


    public function get_access_token()
    {
        $settings = app(SettingService::class)->getSettings('payment_method', true);
        if (!empty($settings['phonepe_payment_mode']) && $settings['phonepe_payment_mode'] === "PRODUCTION") {
            $token_url = "https://api.phonepe.com/apis/pg/v1/oauth/token";
        } else {
            $token_url = "https://api-preprod.phonepe.com/apis/pg-sandbox/v1/oauth/token";
        }
        $payload = [
            'client_id'      => $this->client_id,
            'client_secret'  => $this->client_secret,
            'client_version' => 1,
            'grant_type'     => 'client_credentials',
        ];
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $token_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => http_build_query($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        ]);
        $response = curl_exec($curl);
        if (curl_errno($curl)) {
            throw new \Exception('Curl error: ' . curl_error($curl));
        }
        curl_close($curl);
        $result = json_decode($response, true);
        if (!empty($result['access_token'])) {
            return $result['access_token'];
        }
        throw new \Exception('Token not received. Response: ' . $response);
    }

    public function pay_v2(array $data, string $type = 'web')
    {
        $accessToken = $this->get_access_token();
        if (!$accessToken) {
            return ['error' => true, 'message' => 'Access token not available'];
        }

        $endpoint = $type === 'app'
            ? '/checkout/v2/sdk/order'
            : '/checkout/v2/pay';

        $url = $this->url . $endpoint;

        $metaData = [
            'udf1' => $data['merchantTransactionId'] ?? 'TXN_' . time(),
            'udf2' => $data['merchantUserId'] ?? 'USER_' . rand(1000, 9999),
            'udf3' => $data['amount'] ?? 0,
        ];

        $payload = [
            "merchantOrderId" => $data['merchantTransactionId'] ?? 'TXN_' . time(),
            "amount" => intval($data['amount']),
            "metaInfo" => $metaData,
            "paymentFlow" => [
                "type" => "PG_CHECKOUT",
                "message" => "Payment message used for collect requests",
                "merchantUrls" => [
                    "redirectUrl" => ($data['redirectUrl'] ?? '') . "?TransactionID=" . ($data['merchantTransactionId'] ?? 'TXN_' . time()),
                ]
            ]
        ];

        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'O-Bearer ' . $accessToken,
        ];

        try {
            $response = Http::withHeaders($headers)->post($url, $payload);
            $result = $response->json();

            if ($response->failed()) {
                Log::error('PhonePe: Payment creation failed', ['response' => $result]);
                return ['error' => true, 'response' => $result];
            }

            if (isset($data['redirectUrl'])) {
                return [
                    'orderId' => $result['orderId'] ?? '',
                    'redirectUrl' => $result['redirectUrl'] ?? '',
                    'merchantOrderId' => $data['merchantTransactionId'] ?? '',
                ];
            }

            return $result;
        } catch (\Throwable $e) {
            Log::error('PhonePe: pay_v2 exception', ['error' => $e->getMessage()]);
            return ['error' => true, 'message' => $e->getMessage()];
        }
    }

    public function check_status_v2(string $id)
    {
        $accessToken = $this->get_access_token();
        if (!$accessToken) {
            return ['error' => true, 'message' => 'Access token not available'];
        }

        $endpoint = "/checkout/v2/order/{$id}/status";
        $url = $this->url . $endpoint;

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'O-Bearer ' . $accessToken,
            ])->get($url);

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('PhonePe: check_status_v2 exception', ['error' => $e->getMessage()]);
            return ['error' => true, 'message' => $e->getMessage()];
        }
    }

    public function phonepe_checksum_v2(array $data)
    {
        $token = $this->get_access_token();

        $order = $this->pay_v2($data, 'app');

        $payload = [
            "state" => "PENDING",
            "merchantOrderId" => $order['orderId'] ?? '',
            "amount" => $data['amount'] ?? 0,
            "merchantId" => $this->client_id,
            "expireAT" => 1200,
            "token" => $order['token'] ?? '',
            "paymentMode" => ["type" => "PAY_PAGE"],
        ];

        return [
            "environment" => $this->environment,
            "merchantOrderId" => $order['orderId'] ?? '',
            "flowId" => 'TX' . time(),
            "enableLogging" => true,
            "request" => $payload,
            "token" => $token,
        ];
    }
}
