<?php

namespace App\Livewire\Footer;
use App\Services\SettingService;
use Livewire\Component;

class Footer extends Component
{

    public function render()
    {
        $settings = app(SettingService::class)->getSettings('web_settings',true);
        $settings = json_decode($settings);
        return view('components.footer.footer',[
            'settings'=>$settings,
        ]);
    }
}
