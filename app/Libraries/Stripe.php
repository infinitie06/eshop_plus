<?php

namespace App\Libraries;

use Stripe\Checkout\Session;
use App\Services\SettingService;
use Illuminate\Support\Facades\Log;

const DEFAULT_TOLERANCE = 300;

class Stripe
{
    private $secret_key;
    private $public_key;
    private $currency_code;

    function __construct()
    {
        $payment_method_settings = app(SettingService::class)->getSettings('payment_method', true);
        $payment_method_settings = json_decode($payment_method_settings, true);

        $this->secret_key = $payment_method_settings['stripe_secret_key'] ?? "";
        $this->public_key = $payment_method_settings['stripe_publishable_key'] ?? "";
        $this->currency_code = $payment_method_settings['stripe_currency_code'] ?? "";
    }

    public function createPaymentIntent($data)
    {
        // dd($data);
        \Stripe\Stripe::setApiKey($this->secret_key);
        $email = $data['email'] ?? "test@gmail.com";

        try {
            $allowedCountries = [
                'AE', 'AG', 'AR', 'AT', 'AU', 'BE', 'BG', 'BO', 'BR', 'CA', 'CH', 'CI', 'CL', 'CO', 'CR', 'CY', 'CZ', 'DE', 'DK', 'DO', 'EE', 'ES', 'FI', 'FR', 'GB', 'GI', 'GR', 'GT', 'HK', 'HU', 'ID', 'IE', 'IS', 'IT', 'JP', 'LI', 'LU', 'LV', 'MT', 'MX', 'MY', 'NL', 'NO', 'NZ', 'PE', 'PH', 'PL', 'PT', 'PY', 'RO', 'SE', 'SG', 'SI', 'SK', 'SN', 'TH', 'TT', 'US', 'UY', 'VN', 'ZA',
                'BD', 'IN', 'PK', 'AF', 'AL', 'DZ', 'AS', 'AD', 'AO', 'AI', 'AQ', 'AM', 'AW', 'AX', 'AZ', 'BS', 'BH', 'BJ', 'BM', 'BT', 'BW', 'BV', 'BQ', 'IO', 'BN', 'BF', 'BI', 'CV', 'KH', 'CM', 'KY', 'CF', 'TD', 'CX', 'CC', 'KM', 'CG', 'CD', 'CK', 'CW', 'DJ', 'DM', 'EC', 'EG', 'SV', 'GQ', 'ER', 'ET', 'FK', 'FO', 'FJ', 'GF', 'PF', 'TF', 'GA', 'GM', 'GE', 'GH', 'GL', 'GD', 'GP', 'GU', 'GG', 'GN', 'GW', 'GY', 'HT', 'HM', 'VA', 'HN', 'IM', 'JE', 'JO', 'KZ', 'KE', 'KI', 'KW', 'KG', 'LA', 'LB', 'LS', 'LR', 'LY', 'MO', 'MK', 'MG', 'MW', 'MV', 'ML', 'MH', 'MQ', 'MR', 'MU', 'YT', 'FM', 'MD', 'MC', 'MN', 'ME', 'MS', 'MA', 'MZ', 'MM', 'NA', 'NR', 'NP', 'NC', 'NI', 'NE', 'NG', 'NU', 'NF', 'MP', 'OM', 'PW', 'PS', 'PA', 'PG', 'PN', 'PR', 'QA', 'RE', 'RW', 'BL', 'SH', 'KN', 'LC', 'MF', 'PM', 'VC', 'WS', 'SM', 'ST', 'SA', 'SC', 'SL', 'SX', 'SB', 'SO', 'GS', 'SS', 'LK', 'SR', 'SJ', 'SZ', 'TJ', 'TZ', 'TL', 'TG', 'TK', 'TO', 'TN', 'TR', 'TM', 'TC', 'TV', 'UG', 'UA', 'UZ', 'VU', 'VE', 'VG', 'VI', 'WF', 'EH', 'YE', 'ZM', 'ZW', 'ZZ'
            ];
            
            $productName = json_decode($data['product_name'] ?? '""');
            $productNameEn = !empty($productName?->en) ? $productName->en : ($data['product_name'] ?? 'Refill');

            // ✅ Proper Metadata Flattening
            $metadata = $data['metadata'] ?? $request_data = $data;
            if (isset($data['metadata']) && is_array($data['metadata'])) {
                $nested = $data['metadata'];
                unset($data['metadata']);
                $metadata = array_merge($data, $nested);
            }
            // Ensure metadata is a flat array of strings/numbers
            $metadata = array_map(function($val) {
                return is_array($val) ? json_encode($val) : (string)$val;
            }, (array)$metadata);

            $sessionPayload = [
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => $this->currency_code,
                            'product_data' => [
                                'name' => "Paid for " . $productNameEn,
                            ],
                            'unit_amount' => number_format((float) $data['amount'], 2, ".", "") * 100,
                        ],
                        'quantity' => 1,
                    ]
                ],
                'mode' => 'payment',
                'success_url' => url('payments/stripe-response?session_id={CHECKOUT_SESSION_ID}'),
                'cancel_url' => url('payments?response=order_failed'),
                'customer_email' => $email,
                'payment_intent_data' => [
                    'metadata' => $metadata,
                ],
                'metadata' => $metadata,
            ];

            $response = Session::create($sessionPayload);
        } catch (\Exception $e) {
            dd($e);
            Log::error("Stripe Checkout Error: " . $e->getMessage());
            return false;
        }

        $response['payment_method'] = 'stripe';
        $response['publicKey'] = $this->public_key;
        return $response;
    }

    public function stripe_response($session_id)
    {
        $stripe = new \Stripe\StripeClient($this->secret_key);
        header('Content-Type: application/json');

        try {
            $session = $stripe->checkout->sessions->retrieve($session_id);
            http_response_code(200);
            return json_encode([
                'status' => $session->status, 
                'customer_email' => $session->customer_details->email ?? '', 
                'data' => $session
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            return json_encode(['error' => $e->getMessage()]);
        }
    }

    public function refund_payment($paymentIntentId, $amount)
    {
        \Stripe\Stripe::setApiKey($this->secret_key);

        try {
            $refund = \Stripe\Refund::create([
                'payment_intent' => $paymentIntentId,
                'amount' => intval($amount * 100),
            ]);

            return $refund;
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function curl($url, $method = 'GET', $data = [])
    {
        $ch = curl_init();
        $curl_options = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded',
                'Authorization: Basic ' . base64_encode($this->secret_key . ':')
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
        return $result;
    }

    public function construct_event($request_body, $sigHeader, $secret, $tolerance = DEFAULT_TOLERANCE)
    {
        try {
            $explode_header = explode(",", $sigHeader);
            $parsed_data = [];
            foreach ($explode_header as $item) {
                $kv = explode("=", $item);
                if (count($kv) == 2) {
                    $parsed_data[$kv[0]] = $kv[1];
                }
            }
            
            $timestamp = $parsed_data['t'] ?? null;
            $signs = $parsed_data['v1'] ?? null;

            if (!$timestamp || !$signs) {
                return ['error' => true, 'message' => "Invalid signature header"];
            }

            $signed_payload = "{$timestamp}.{$request_body}";
            $expectedSignature = hash_hmac('sha256', $signed_payload, $secret);

            if (hash_equals($expectedSignature, $signs)) {
                return "Matched";
            }
            
            return ['error' => true, 'message' => "Signatures did not match"];
        } catch (\Exception $e) {
            return ['error' => true, 'message' => $e->getMessage()];
        }
    }
}