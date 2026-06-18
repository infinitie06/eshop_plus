<?php

namespace App\Livewire\Pages;
use App\Services\SettingService;
use Livewire\Component;

class ReturnPolicy extends Component
{
    public function render()
    {
        $return_policy = json_decode(app(SettingService::class)->getSettings('return_policy',true), true);
        $data = $return_policy['return_policy'];

        return view('livewire.'.config('constants.theme').'.pages.return-policy',[
            'return_policy' => $data
        ])->title("Return Policy |");
    }
}
