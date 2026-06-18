<?php

namespace App\Services;

use App\Models\Currency;
use App\Services\SettingService;

class CurrencyService
{
    public function getDefaultCurrency()
    {

        static $currency = null;

        if ($currency != null) {
            return $currency;
        }

        // Fetch default currency using Eloquent
        $currency = Currency::where('is_default', 1)->first();



        return $currency;
    }
    public function getAllCurrency()
    {


        static $currencies = null;

        if ($currencies != null) {
            return $currencies;
        }


        $currencies = Currency::all()->toArray();



        return $currencies;
    }
    public function getCurrencyCodeSettings($code, $fetchWithSymbol = false)
    {
        static $cache = [];

        $key = ($fetchWithSymbol ? 'symbol:' : 'code:') . $code;

        if (isset($cache[$key])) {
            return $cache[$key];
        }

        $query = Currency::select('*');

        if ($fetchWithSymbol) {
            $query->where('symbol', $code);
        } else {
            $query->where('code', $code);
        }

        $currency = $query->get()->toArray();

        $cache[$key] = $currency;

        return $currency;
    }
    public function currentCurrencyPrice($price, $with_symbol = false)
    {
        // The active currency (symbol + exchange rate) is stable for the duration
        // of a single request, so resolve it once and memoize. Output is also
        // memoized per (price, with_symbol) pair to avoid repeated number_format
        // / arithmetic when the same price is rendered many times on a page.
        static $resolved = null;
        static $resultCache = [];

        $key = $price . '|' . ($with_symbol ? 1 : 0);
        if (array_key_exists($key, $resultCache)) {
            return $resultCache[$key];
        }

        if ($resolved === null) {
            $shipping_settings = app(SettingService::class)->getSettings('shipping_method', true);
            $shipping_settings = json_decode($shipping_settings, true);

            if (isset($shipping_settings['shiprocket_shipping_method']) && $shipping_settings['shiprocket_shipping_method'] == 1) {
                $inr_details = $this->getCurrencyCodeSettings('INR');
                $resolved = [
                    'symbol' => $inr_details[0]['symbol'] ?? '₹',
                    'rate'   => 1.0,
                ];
            } else {
                $system_settings = app(SettingService::class)->getSettings('system_settings', true);
                $system_settings = json_decode($system_settings, true);
                $currency_code = session('currency') ?? $system_settings['currency_setting']['code'];
                $currency_details = $this->getCurrencyCodeSettings($currency_code);
                $resolved = [
                    'symbol' => $currency_details[0]['symbol'] ?? $system_settings['currency_setting']['symbol'],
                    'rate'   => (float) $currency_details[0]['exchange_rate'],
                ];
            }
        }

        $amount = (float) $price * $resolved['rate'];
        $result = $with_symbol ? $resolved['symbol'] . number_format($amount, 2) : $amount;
        $resultCache[$key] = $result;
        return $result;
    }
    public function getPriceCurrency($price)
    {
        // Check if Shiprocket shipping is enabled
        $shipping_settings = app(SettingService::class)->getSettings('shipping_method', true);
        $shipping_settings = json_decode($shipping_settings, true);
        
        // If Shiprocket is enabled, return only INR currency data without conversion
        if (isset($shipping_settings['shiprocket_shipping_method']) && $shipping_settings['shiprocket_shipping_method'] == 1) {
            // Get INR currency details
            $inr_details = $this->getCurrencyCodeSettings('INR');
            
            if (!empty($inr_details)) {
                return [
                    'INR' => [
                        'currency_code' => 'INR',
                        'symbol' => $inr_details[0]['symbol'] ?? '₹',
                        'exchange_rate' => '1.00',
                        'amount' => formatePriceDecimal((float) $price)
                    ]
                ];
            }
        }

        $currencies = app(CurrencyService::class)->getAllCurrency();
        // dd($currencies);
        $rows = [];

        foreach ($currencies as $currency) {
            // Make sure $currency is an object
            // if (!is_object($currency)) {
            //     continue; // skip if it's not an object
            // }

            // Calculate the amount in target currency
            $exchangeRate = (float) $currency['exchange_rate'];
            $amount = (float) $price * $exchangeRate;

            // Format and build the result row
            $rows[$currency['code']] = [
                'currency_code' => $currency['code'],
                'symbol' => $currency['symbol'],
                'exchange_rate' => number_format($exchangeRate, 2),
                'amount' => formatePriceDecimal($amount)
            ];
        }

        return $rows;
    }

    public function formateCurrency($price, $currency = '', $before = true)
    {
        $baseCurrency = app(CurrencyService::class)->getDefaultCurrency()->symbol;

        $currency_symbol = isset($currency) && !empty($currency) ? $currency : $baseCurrency;
        if ($before == true) {
            return $currency_symbol . $price;
        } else {
            return $price . $currency_symbol;
        }
    }
}
