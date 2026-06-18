<?php

use App\Services\SettingService;
function parseSmsString($string, $data = [])
{

    foreach ($data as $key => $val) {

        if ($val != null) {
            $string = str_replace("{" . $key . "}", $val, $string);
        } else {
            $string = str_replace("{" . $key . "}", "NULL", $string);
        }
    }
    return $string;
}

/**
 *
 ** This function sends verifies the modules and sends sms for email from config saved in database.
 *@param array $emails = [
 *    "customer" => [],
 *    "admin" => [],
 *    "seller" => [],
 *    "delivery_boy" => []
 *]
 * @param array $phone = [
 *    "customer" => [],
 *    "admin" => [],
 *    "seller" => [],
 *    "delivery_boy" => []
 *]
 * @param string $event
 * This the the event like place_order, update_order_status, etc...
 * @return array [
 *   "error" => bool,
 *   "message" => string,
 *   "data" => mixed
 *]
 */

function send_sms($phone, $msg, $country_code = "+20")
{
    $settings = app(SettingService::class)->getSettings('sms_gateway_settings', true);
    $settings = json_decode($settings, true);

    if (empty($settings) || empty($settings['base_url'])) {
        return [
            "error"     => true,
            "message"   => "SMS gateway is not configured. Please set it up in Admin > Settings > SMS Gateway.",
            "http_code" => 0,
        ];
    }

    $only_mobile_number = ltrim((string) $phone, '+');
    $cc = ltrim((string) $country_code, '+');
    if ($cc !== '' && strpos($only_mobile_number, $cc) === 0) {
        $only_mobile_number = substr($only_mobile_number, strlen($cc));
    }
    $only_mobile_number = ltrim($only_mobile_number, '0');
    $mobile_with_cc = $cc . $only_mobile_number;

    $placeholders = [
        'only_mobile_number'              => $only_mobile_number,
        'mobile_number_with_country_code' => $mobile_with_cc,
        'country_code'                    => $cc,
        'message'                         => $msg,
    ];

    $url    = parseSmsString($settings['base_url'], $placeholders);
    $method = strtoupper($settings['sms_gateway_method'] ?? 'POST');

    $headers = [];
    if (!empty($settings['header_key']) && is_array($settings['header_key'])) {
        foreach ($settings['header_key'] as $i => $hk) {
            if ($hk === '' || $hk === null) {
                continue;
            }
            $hv = $settings['header_value'][$i] ?? '';
            $headers[] = $hk . ': ' . parseSmsString($hv, $placeholders);
        }
    }

    if (!empty($settings['params_key']) && is_array($settings['params_key'])) {
        $query = [];
        foreach ($settings['params_key'] as $i => $pk) {
            if ($pk === '' || $pk === null) {
                continue;
            }
            $pv = $settings['params_value'][$i] ?? '';
            $query[$pk] = parseSmsString($pv, $placeholders);
        }
        if (!empty($query)) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($query);
        }
    }

    $body           = null;
    $is_json_body   = false;
    $text_body_raw  = trim((string) ($settings['text_format_data'] ?? ''));

    if ($text_body_raw !== '') {
        $body         = parseSmsString($text_body_raw, $placeholders);
        $is_json_body = true;
    } elseif (!empty($settings['body_key']) && is_array($settings['body_key'])) {
        $form = [];
        foreach ($settings['body_key'] as $i => $bk) {
            if ($bk === '' || $bk === null) {
                continue;
            }
            $bv = $settings['body_value'][$i] ?? '';
            $form[$bk] = parseSmsString($bv, $placeholders);
        }
        $body = $form;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $is_json_body ? $body : http_build_query($body));
        }
    } else {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    }

    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    $response   = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    \Log::info("SMS Gateway Response", [
        "url"        => $url,
        "method"     => $method,
        "http_code"  => $http_code,
        "response"   => $response,
        "curl_error" => $curl_error,
    ]);

    if ($curl_error) {
        return [
            "error"     => true,
            "message"   => $curl_error,
            "http_code" => $http_code,
        ];
    }

    $json = json_decode($response, true);

    if (is_array($json) && isset($json['status']) && strtolower((string) $json['status']) === 'error') {
        return [
            "error"     => true,
            "message"   => $json['message'] ?? "Unknown SMS error",
            "http_code" => $http_code,
            "response"  => $json,
        ];
    }

    $success = ($http_code >= 200 && $http_code < 300);

    return [
        "error"     => !$success,
        "message"   => is_array($json) && isset($json['message'])
            ? $json['message']
            : ($success ? "SMS sent successfully" : "SMS request failed"),
        "http_code" => $http_code,
        "response"  => $json ?? $response,
    ];
}



function curl_sms($url, $method = 'GET', $data = [], $headers = [])
{

    $ch = curl_init();
    $curl_options = array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_HEADER => 0,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/x-www-form-urlencoded',
        )
    );

    if (count($headers) != 0) {

        $curl_options[CURLOPT_HTTPHEADER] = $headers;
    }

    if (strtolower($method) == 'post') {
        $curl_options[CURLOPT_POST] = 1;
        $curl_options[CURLOPT_POSTFIELDS] = http_build_query($data);
    } else {
        $curl_options[CURLOPT_CUSTOMREQUEST] = 'GET';
    }
    curl_setopt_array($ch, $curl_options);

    $result = array(
        'body' => json_decode(curl_exec($ch), true),
        'http_code' => curl_getinfo($ch, CURLINFO_HTTP_CODE),
    );

    return $result;
}
