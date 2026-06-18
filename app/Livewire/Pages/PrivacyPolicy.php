<?php

namespace App\Livewire\Pages;

use Livewire\Component;
use App\Services\SettingService;
class PrivacyPolicy extends Component
{
    public function render()
    {
        $privacy_policy = json_decode(app(SettingService::class)->getSettings('privacy_policy',true), true);
        $data = $privacy_policy['privacy_policy'];

        return view('livewire.'.config('constants.theme').'.pages.privacy-policy',[
            'privacy_policy' => $data
        ])->title("Privacy Policy |");
    }
}
