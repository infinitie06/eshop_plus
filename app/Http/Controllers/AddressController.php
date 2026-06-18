<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Zipcode;
use Illuminate\Routing\Controller;

class AddressController extends Controller
{
    public function getAddress($user_id, $id = null, $fetch_latest = false, $is_default = false)
    {
        $query = Address::query();

        if (!is_null($user_id)) {
            $query->where('user_id', $user_id);
        }

        if (!is_null($id)) {
            $query->where('id', $id);
        }

        if ($is_default) {
            $query->where('is_default', true);
        }

        $query->orderByDesc('id');

        if ($fetch_latest) {
            $query->limit(1);
        }

        $addresses = $query->get();
        // dd($addresses);
        $addresses = $addresses->map(
            function ($address) {
                $zipcode = $address->pincode ?? null;
                $zipcode_id = fetchDetails(Zipcode::class, ['zipcode' => $zipcode], 'id');
                $zipcode_id = !$zipcode_id->isEmpty() ? $zipcode_id[0]->id : '';
                $zipcode_id = $zipcode_id ?? '';

                $minimumFreeDeliveryOrderAmount = fetchDetails(Zipcode::class, ['id' => $zipcode_id], ['minimum_free_delivery_order_amount', 'delivery_charges']);

                $address->minimum_free_delivery_order_amount = optional($minimumFreeDeliveryOrderAmount)->minimum_free_delivery_order_amount ?? 0;
                $address->delivery_charges = optional($minimumFreeDeliveryOrderAmount)->delivery_charges ?? 0;
                $address->area_id = $address->area_id ?? "";
                $address->city_id = $address->city_id ?? "";
                $address->latitude = $address->latitude ?? "";
                $address->longitude = $address->longitude ?? "";
                $address->landmark = $address->landmark ?? "";
                return $address;
            }
        );
        return $addresses;
    }
}
