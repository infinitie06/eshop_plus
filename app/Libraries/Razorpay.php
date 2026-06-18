<?php

namespace App\Libraries;
use App\Services\SettingService;

class Razorpay
{

    public $key_id = "";
    public $secret_key = "";
    public $secret_hash = "";

    function __construct()
    {
        $payment_method_settings = app(SettingService::class)->getSettings('payment_method', true);
        $payment_method_settings = json_decode($payment_method_settings, true);

        $this->key_id = $payment_method_settings['razorpay_key_id'] ?? "";
        $this->secret_key = $payment_method_settings['razorpay_secret_key'] ?? "";
        $this->secret_hash = $payment_method_settings['refund_webhook_secret_key'] ?? "";
    }

    public function create_order($amount, $receipt = '', $currency = "INR", $notes = [])
    {
        $url = "https://api.razorpay.com/v1/";


        $data = array(
            'amount' => $amount,
            'receipt' => $receipt,
            'currency' => $currency,
            'notes' => $notes
        );
        $url = $url . 'orders';
        $method = 'POST';
        $response = $this->curl($url, $method, $data);
        $res = json_decode($response['body'], true);
        $res['public_key'] = $this->key_id;
        return $res;
    }

    public function curl($url, $method = 'GET', $data = [])
    {
        $key_id = $this->key_id;
        $secret_key = $this->secret_key;

        $ch = curl_init();
        $curl_options = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded',
                'Authorization: Basic ' . base64_encode($key_id . ':' . $secret_key)
            )
        );
        if (strtolower($method) == 'post') {
            $curl_options[CURLOPT_POST] = 1;
            $curl_options[CURLOPT_POSTFIELDS] = http_build_query($data);
        } else {
            $curl_options[CURLOPT_CUSTOMREQUEST] = 'GET';
        }
        curl_setopt_array($ch, $curl_options);
        $result = array(
            'body' => curl_exec($ch),
            'http_code' => curl_getinfo($ch, CURLINFO_HTTP_CODE),
        );

        // Check for cURL errors
        if (curl_error($ch)) {
            $result['error'] = curl_error($ch);
        }

        curl_close($ch);
        return $result;
    }
    public function fetch_payments($id = '')
    {
        $url = "https://api.razorpay.com/v1/";
        $url = $url . 'payments';
        $url .= (!empty(trim($id))) ? '/' . $id : '';
        $method = 'GET';
        $response = $this->curl($url, $method);

        // Check for cURL errors
        if (isset($response['error'])) {
            return [
                'error' => [
                    'code' => 'CURL_ERROR',
                    'description' => $response['error']
                ]
            ];
        }

        // Check for HTTP errors
        if ($response['http_code'] >= 400) {
            $error_data = json_decode($response['body'], true);
            return [
                'error' => [
                    'code' => 'HTTP_' . $response['http_code'],
                    'description' => $error_data['error']['description'] ?? 'API request failed with status ' . $response['http_code']
                ]
            ];
        }

        $res = json_decode($response['body'], true);
        return $res;
    }

    public function capture_payment($amount, $id, $currency = "INR")
    {
        $data = array(
            'amount' => $amount,
            'currency' => $currency,
        );
        $url = "https://api.razorpay.com/v1/";
        $url = $url . 'payments/' . $id . '/capture';
        $method = 'POST';
        $response = $this->curl($url, $method, $data);

        // Check for cURL errors
        if (isset($response['error'])) {
            return [
                'error' => [
                    'code' => 'CURL_ERROR',
                    'description' => $response['error']
                ]
            ];
        }

        // Check for HTTP errors
        if ($response['http_code'] >= 400) {
            $error_data = json_decode($response['body'], true);
            return [
                'error' => [
                    'code' => 'HTTP_' . $response['http_code'],
                    'description' => $error_data['error']['description'] ?? 'API request failed with status ' . $response['http_code']
                ]
            ];
        }

        $res = json_decode($response['body'], true);
        return $res;
    }
}
