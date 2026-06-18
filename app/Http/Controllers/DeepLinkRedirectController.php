<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SettingService;

class DeepLinkRedirectController extends Controller
{
    /**
     * Handle redirection for product, seller, blog, etc.
     */
    public function handle(Request $request, $type, $slug)
    {
        $settings = json_decode(app(SettingService::class)->getSettings('system_settings', true), true);
        
        $scheme = str_replace('://', '', $settings['deep_link_scheme'] ?? 'eshop');
        $host = $settings['deep_link_host'] ?? 'eshop-pro.eshopweb.store';
        $appName = $settings['app_name'] ?? 'eShop';
        
        $playStore = $settings['play_store_link_for_customer_app'] ?? 'https://play.google.com/store/apps/details?id=com.eshop';
        $appStore = $settings['app_store_link_for_customer_app'] ?? 'https://apps.apple.com/app/eshop';
        
        $androidPackage = $settings['android_package_name'] ?? 'com.eshop';
        if ($androidPackage == 'com.eshop' && preg_match('/id=([^&]+)/', $playStore, $matches)) {
            $androidPackage = $matches[1];
        }
        
        // Normalize type (app expects singular)
        $appType = str_replace(['products', 'sellers', 'blogs'], ['product', 'seller', 'blog'], $type);
        
        $query = $request->getQueryString();
        $deepLink = "{$scheme}://{$host}/{$appType}/{$slug}" . ($query ? "?{$query}" : "");
        
        return view('redirect-to-app', [
            'deepLink' => $deepLink,
            'playStore' => $playStore,
            'appStore' => $appStore,
            'scheme' => $scheme,
            'host' => $host,
            'androidPackage' => $androidPackage,
            'appName' => $appName,
            'type' => $appType,
            'slug' => $slug,
            'queryString' => $query ? "?{$query}" : ""
        ]);
    }
}
