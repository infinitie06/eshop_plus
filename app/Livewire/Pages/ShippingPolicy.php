<?php

namespace App\Livewire\Pages;
use App\Services\SettingService;
use Livewire\Component;

class ShippingPolicy extends Component
{
    public function render()
    {
        $shipping_policy = json_decode(app(SettingService::class)->getSettings('shipping_policy',true), true);
        $data = $shipping_policy['shipping_policy'];

        return view('livewire.'.config('constants.theme').'.pages.shipping-policy',[
            'shipping_policy' => $data
        ])->title("Shipping Policy |");
    }
}
