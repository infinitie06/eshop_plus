<?php

namespace App\Livewire\Orders;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Services\OrderService;

class Listing extends Component
{
    public $orderStatus = "";
    public $currentPage = 1;

   public function render()
{
    $store_id = session('store_id');
    $user = Auth::user();

    if (!$user) {
        abort(404);
    }

    $perPage = 8; // or 8, whatever you are using
    $user_orders = $this->getOrders($user->id, $store_id, $perPage);

    return view('livewire.' . config('constants.theme') . '.orders.listing', [
        'user_info' => $user,
        'user_orders' => $user_orders,
        'currentPage' => $this->currentPage,
        'perPage' => $perPage,
    ])->title("Orders |");
}


    public function getOrders($userId, $store_id, $perPage)
{
    $orders = [];

    if (empty($this->orderStatus)) {
        $orders = app(OrderService::class)->fetchOrders(
            user_id: $userId,
            sort: "o.id",
            order: 'DESC',
            store_id: $store_id
        );
    } else {
        $order_status = explode(',', $this->orderStatus);
        $orders = app(OrderService::class)->fetchOrders(
            user_id: $userId,
            sort: "o.id",
            order: 'DESC',
            status: $order_status,
            store_id: $store_id
        );
    }

    $ordersCollection = collect($orders['order_data']);
    $page = $this->currentPage;

    $paginator = new LengthAwarePaginator(
        $ordersCollection->forPage($page, $perPage),
        $orders['total'],
        $perPage,
        $page,
        ['path' => url()->current()]
    );

    return [
        'order_data' => $paginator->items(),
        'total' => $orders['total'],
        'links' => '', // We're not using this anymore
    ];
}


    public function filterOrders($status)
    {
        $this->orderStatus = $status;
      $this->currentPage =1;
    }

    public function goToPage($page)
    {
        $this->currentPage = $page;
    }
}
