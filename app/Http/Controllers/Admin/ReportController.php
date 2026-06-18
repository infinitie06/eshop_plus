<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Services\TranslationService;
use App\Services\StoreService;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        return view('admin.pages.tables.sales_reports');
    }

    public function list(Request $request)
    {
        $store_id = app(StoreService::class)->getStoreId();
        $search = trim($request->input('search'));
        $offset = $search || $request->has('pagination_offset') ? $request->input('pagination_offset', 0) : 0;
        $limit = $request->input('limit', 10);
        $sort = $request->input('sort', 'id');
        $order = $request->input('order', 'ASC');
        $allowedStatuses = [
            'delivered',
            'return_request_pending',
            'return_request_decline'
        ];

        // Fetch orders with relationships - only include delivered and return-related items
        $ordersQuery = Order::with([
            'items' => function ($q) use ($allowedStatuses) {
                $q->whereIn(
                    DB::raw("
                        JSON_UNQUOTE(
                            JSON_EXTRACT(
                                status,
                        CONCAT('$[', JSON_LENGTH(status) - 1, '][0]')
                    )
                )
            "),
            $allowedStatuses
        );
    },
    'items.productVariant.product',
    'store'
])
->where('store_id', $store_id)
->where('is_pos_order', 0)
->whereHas('items', function ($q) use ($allowedStatuses) {
    $q->whereIn(
        DB::raw("
            JSON_UNQUOTE(
                JSON_EXTRACT(
                    status,
                    CONCAT('$[', JSON_LENGTH(status) - 1, '][0]')
                )
            )
        "),
        $allowedStatuses
    );
});


        // Optional search on order ID or product name
        if (!empty($search)) {
            $ordersQuery->where(function ($query) use ($search) {
                $query->where('id', 'like', '%' . $search . '%')
                    ->orWhereHas('items', function ($itemQuery) use ($search) {
                        $itemQuery->where('product_name', 'like', '%' . $search . '%');
                    });
            });
        }


        // Clone to get total before pagination
        $total = (clone $ordersQuery)->count();

        // Apply pagination and sorting
        $orders = $ordersQuery
            ->orderBy($sort, $order)
            ->skip($offset)
            ->take($limit)
            ->get();

        $language_code = app(TranslationService::class)->getLanguageCode();
        $rows = [];

        foreach ($orders as $order) {
            $adminCommission = 0;
            $sellerCommission = 0;
            $productCost = 0;
            $productName = '';

            foreach ($order->items as $item) {
                $adminCommission += $item->admin_commission_amount;
                $sellerCommission += $item->seller_commission_amount;
                $productCost += $item->price * $item->quantity;

                // Get product name via variant -> product relationship
                if ($item->productVariant && $item->productVariant->product) {
                    $productName .= $productName == "" ? "" : ", ";
                    $productName .= app(TranslationService::class)->getDynamicTranslation(Product::class, 'name', $item->productVariant->product->id, $language_code);
                }
            }

            $totalCommissions = $adminCommission + $sellerCommission;
            $netRevenue = $order->total - $order->promo_discount + $order->delivery_charge;
            $totalCosts = $productCost + $totalCommissions;
            $profit = max($netRevenue - $totalCosts, 0);
            $loss = max(abs(min($netRevenue - $totalCosts, 0)), 0);

            $rows[] = [
                'id' => $order->id,
                'product_name' => $productName,
                'total' => (float) $order->total,
                'promo_discount' => (float) $order->promo_discount,
                'delivery_charge' => (float) $order->delivery_charge,
                'admin_commission' => (float) $adminCommission,
                'seller_commission' => (float) $sellerCommission,
                'net_revenue' => (float) $netRevenue,
                'total_commissions' => (float) $totalCommissions,
                'profit' => (float) $profit,
                'loss' => (float) $loss,
                'order_items' => $order->items,
            ];
        }

        return response()->json([
            'total' => $total,
            'rows' => $rows,
        ]);
    }
}
