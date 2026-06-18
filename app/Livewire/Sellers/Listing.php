<?php

namespace App\Livewire\Sellers;

use App\Models\SellerStore;
use App\Models\Store;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;



class Listing extends Component
{
    public function render(Store $store)
{
    $store_id = session('store_id');

    $seller_listing = fetchDetails(
        SellerStore::class,
        ['store_id' => $store_id, 'status' => 1]
    );

    $sellers = [
        'listing' => [],
        'links' => ''
    ];

    if (count($seller_listing) >= 1) {
        $products = collect($seller_listing);
        $page = request()->get('page', 1);
        $perPage = 12;

        $paginator = new LengthAwarePaginator(
            $products->forPage((int) $page, (int) $perPage),
            $products->count(),
            (int) $perPage,
            (int) $page,
            ['path' => url()->current()]
        );

        $sellers['listing'] = $paginator->items();
        $sellers['links']   = $paginator->links();
    }

    $bread_crumb['page_main_bread_crumb'] =
        '<a href="' . customUrl('sellers') . '">' .
        labels("front_messages.sellers", "Sellers") .
        '</a>';

    return view('livewire.' . config('constants.theme') . '.sellers.listing', [
        'Sellers' => $sellers,
        'bread_crumb' => $bread_crumb
    ])->title('Sellers | ');
}

}
