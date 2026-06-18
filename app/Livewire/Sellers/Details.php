<?php

namespace App\Livewire\Sellers;

use App\Models\SellerStore;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use App\Services\ProductService;

class Details extends Component
{
    public function render(Request $request)
    {
        $seller_slug = $request->segment(2);
        $user_id = Auth::user()->id ?? null;
        $store_id = session('store_id');
        $seller = fetchDetails(SellerStore::class, ['slug' => $seller_slug]);
        if ($seller->isEmpty()) {
            $seller = fetchDetails(SellerStore::class, ['seller_id' => $seller_slug]);
        }
        if ($seller->isEmpty()) {
            abort(404);
        }
        $offset = request()->query('offset', 0);
        $limit = request()->query('limit', 20);
        $seller_details = fetchUsers($seller[0]->user_id);
        $products = app(ProductService::class)->fetchProduct($user_id, null, null, null, $limit,$offset, null, null, null, null, $seller[0]->seller_id, null, $store_id);
        $total_products = $products['total'] ?? count($products ?? []);
        $products = collect($products['product'] ?? []);
        $page = request()->get('page', 1);
        if (isset($page)) {
            $perPage = 12;
            $paginator = new LengthAwarePaginator(
                $products->forPage((int)$page, (int)$perPage),
                $total_products,
                (int)$perPage,
                (int)$page,
                ['path' => url()->current()]
            );
        }
        $product_list['product'] = $paginator->items();
        $product_list['links'] = $paginator->links();
        $bread_crumb['page_main_bread_crumb'] = '<a href="' . customUrl('sellers') . '">' . labels('front_messages.sellers', 'Sellers') . '</a>';

        $right_breadcrumb = [];
        // dd($seller_details);
        // $breadcrumb = $seller[0]->store_name;
        $breadcrumb = $seller_details->username;
        array_push($right_breadcrumb, $breadcrumb);

        if (count($right_breadcrumb) >= 1) {
            $bread_crumb['right_breadcrumb'] = $right_breadcrumb;
        }

        return view('livewire.' . config('constants.theme') . '.sellers.details', [
            'seller' => $seller,
            'seller_details' => $seller_details,
            'bread_crumb' => $bread_crumb,
            'products' => $product_list,
        ])->title($seller[0]->store_name . ' - Seller | ');
    }
}
