<?php

namespace App\Livewire\Pages;

use Livewire\Component;
use App\Services\SettingService;
class AboutUs extends Component
{
    public function render()
    {
        $about_us = app(SettingService::class)->getSettings('about_us',true);
        $about_us = json_decode($about_us);
        $about_us = $about_us->about_us;

        $settings = app(SettingService::class)->getSettings('web_settings',true);
        $settings = json_decode($settings);
        return view('livewire.'.config('constants.theme').'.pages.about-us',[
            "about_us" => $about_us,
            "settings" => $settings,
        ])->title("About Us |");
    }
}
