<?php

namespace App\Http\Controllers;
use App\Services\SettingService;
class SettingController extends Controller
{
    public function getFirebaseCredentials()
    {
        $firebase_settings = app(SettingService::class)->getSettings('firebase_settings');
        $firebase_settings = json_decode($firebase_settings, true);
        return $firebase_settings;
    }
}
