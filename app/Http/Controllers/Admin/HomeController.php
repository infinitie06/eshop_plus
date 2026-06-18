<?php

namespace App\Http\Controllers\Admin;

use App\Models\ComboProduct;
use App\Models\Currency;
use App\Models\OrderItems;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Store;
use App\Models\User;
use App\Services\MediaService;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Services\StoreService;
use App\Services\OrderService;
use App\Models\Language;

class HomeController extends Controller
{

    public function index()
    {
        $id = 0;

        $store_id = app(StoreService::class)->getStoreId();

        $currency = fetchDetails(Currency::class, ['is_default' => 1], 'symbol')[0]->symbol ?? "";

        // total statictis


        $order_counter = app(OrderService::class)->countNewOrders();
        $product_counter = Product::where('store_id', $store_id)->count();
        $combo_product_counter = ComboProduct::where('store_id', $store_id)->count();


        $total_products = $product_counter + $combo_product_counter;
        // dd($total_products);
        $total_store = Store::count();
        $total_seller = Seller::whereHas('stores', function ($query) use ($store_id) {
            $query->where('store_id', $store_id);
        })->count();



        $delivery_boy_counter = countDeliveryBoys();
        $notconverted = AdmintotalEarnings();

        $defaultCurrency = Currency::where('is_default', 1)->first();
        $defaultRate = $defaultCurrency->exchange_rate;
        $fromRate = Currency::where('code', 'USD')->value('exchange_rate');
        $toRate   = Currency::where('code', $defaultCurrency->code)->value('exchange_rate');

        // chatify
        $total_earnings = ($fromRate && $fromRate > 0) ? ($notconverted / $fromRate) * $toRate : $notconverted;

        $role_id = Auth::user() ? Auth::user()->role_id : "";

        $store_details = fetchDetails(Store::class, ['id' => $store_id], ['primary_color', 'secondary_color', 'hover_color', 'active_color']);
        $primary_colour = (isset($store_details[0]->primary_color) && !empty($store_details[0]->primary_color)) ? $store_details[0]->primary_color : '#B52046';
        $messengerColor = $primary_colour;
        $dark_mode = Auth::user() && Auth::user()->dark_mode < 1 ? 'light' : 'dark';

        // user counter

        $user_counter = countNewUsers();


        //-------------------------------- get admin overview statistics ------------------------------------

        $sales = [];

        // monthly earnings

        $allMonths = [
            'Jan' => 0,
            'Feb' => 0,
            'Mar' => 0,
            'Apr' => 0,
            'May' => 0,
            'Jun' => 0,
            'Jul' => 0,
            'Aug' => 0,
            'Sep' => 0,
            'Oct' => 0,
            'Nov' => 0,
            'Dec' => 0
        ];

        // Fetch data for each type

        $monthRes = $this->getMonthlyData('sub_total', $store_id);
        $monthCommissionRes = $this->getMonthlyData('admin_commission_amount', $store_id);
        $monthSalesRes = $this->getMonthlyData('quantity', $store_id);

        // Merge the database results with the allMonths array, replacing existing values
        $monthWiseRevenueDetail = array_merge($allMonths, array_combine(array_column($monthRes, 'month_name'), array_map('intval', array_column($monthRes, 'total'))));
        $monthCommissionDetail = array_merge($allMonths, array_combine(array_column($monthCommissionRes, 'month_name'), array_map('intval', array_column($monthCommissionRes, 'total'))));
        $monthSalesDetail = array_merge($allMonths, array_combine(array_column($monthSalesRes, 'month_name'), array_map('intval', array_column($monthSalesRes, 'total'))));

        // Create the result array
        $monthWiseSales['total_revenue'] = array_values($monthWiseRevenueDetail);
        $monthWiseSales['total_commission'] = array_values($monthCommissionDetail);
        $monthWiseSales['total_sales'] = array_values($monthSalesDetail);
        $monthWiseSales['month_name'] = array_keys($monthWiseRevenueDetail);

        $sales[0] = $monthWiseSales;
        $now = now();

        // weekly earnings

        $startDate = Carbon::now()->startOfWeek(); // Start of the current week (Sunday)
        $endDate = Carbon::now()->endOfWeek(); // End of the current week (Saturday)

        $weekWiseSales = [
            'total_revenue' => [],
            'total_commission' => [],
            'total_sales' => [],
            'day' => []
        ];
        $currentDate = Carbon::now();
        // Loop to retrieve data for each day of the week
        for ($i = 0; $i < 7; $i++) {
            // Get the day name for the current iteration
            $dayName = $currentDate->copy()->startOfWeek()->addDays($i)->format('D, d M');

            // Get sales data for the current day
            $dayRes = $this->getWeeklySalesData('order_items', 'created_at', 'sub_total', 'admin_commission_amount', 'quantity', $store_id);

            // If data exists for the current day
            if (isset($dayRes['total_revenue'][$i])) {
                $weekWiseSales['total_revenue'][] = intval($dayRes['total_revenue'][$i]);
                $weekWiseSales['total_commission'][] = intval($dayRes['total_commission'][$i]);
                $weekWiseSales['total_sales'][] = intval($dayRes['total_sales'][$i]);
            } else {
                // If no data exists for the current day, set totals to 0
                $weekWiseSales['total_revenue'][] = 0;
                $weekWiseSales['total_commission'][] = 0;
                $weekWiseSales['total_sales'][] = 0;
            }

            // Add the day name to the week-wise sales array
            $weekWiseSales['day'][] = $dayName;
        }


        $sales[1] = $weekWiseSales;
        // daily earnings

        $endDate = Carbon::now();
        $startDate = $endDate->copy()->subDays(29);

        // Create an array with all dates of the month
        $allDatesOfMonth = [];
        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            $allDatesOfMonth[] = [
                'date' => $currentDate->format('j'),
                'month' => $currentDate->format('M'),
                'year' => $currentDate->format('Y')
            ];
            $currentDate->addDay();
        }

        $dayRes = $this->getDailySalesData('order_items', 'created_at', 'sub_total', 'admin_commission_amount', 'quantity', $store_id, 29);

        // Create an associative array with date as key for easier merging
        $dayData = [];
        foreach ($dayRes as $day) {
            $dayData[$day->date] = [
                'total_revenue' => intval($day->total_revenue),
                'total_commission' => intval($day->total_commission),
                'total_sales' => intval($day->total_sales)
            ];
        }

        // Merge fetched data with all dates of the month, filling missing dates with zeros
        $dayWiseSales = [];
        foreach ($allDatesOfMonth as $dateInfo) {
            $date = $dateInfo['date'];
            if (isset($dayData[$date])) {
                $dayWiseSales['total_revenue'][] = $dayData[$date]['total_revenue'];
                $dayWiseSales['total_commission'][] = $dayData[$date]['total_commission'];
                $dayWiseSales['total_sales'][] = $dayData[$date]['total_sales'];
            } else {
                $dayWiseSales['total_revenue'][] = 0;
                $dayWiseSales['total_commission'][] = 0;
                $dayWiseSales['total_sales'][] = 0;
            }
            $dayWiseSales['day'][] = $date . '-' . $dateInfo['month'] . '-' . $dateInfo['year'];
        }

        $sales[2] = $dayWiseSales;

        $store = Store::find($store_id);
        $top_sellers = [];
        if ($store) {
            $top_sellers = $store->sellers()
                ->with(['order_items' => function ($q) {
                    $q->select('seller_id', 'sub_total', 'seller_commission_amount', 'active_status');
                }, 'user'])
                ->get()
                ->map(function ($seller) {
                    $deliveredItems = $seller->order_items->where('active_status', 'delivered');
                    // dd($seller->order_items);
                    return [
                        'seller_id' => $seller->id,
                        'store_name' => $seller->pivot->store_name,
                        'logo' => $seller->pivot->logo,
                        'seller_name' => optional($seller->user)->username,
                        'total_sales' => intval($deliveredItems->sum('sub_total')),
                        'total_commission' => intval($seller->order_items->sum('seller_commission_amount')),
                    ];
                })
                ->sortByDesc('total_sales')
                ->take(6)
                ->values();
        }
        $setupSteps = [
            'add_store' => 'Add Store',
            'add_category' => 'Add Category',
            'add_city' => 'Add City',
            'add_zipcode' => 'Add Zipcode',
            'add_delivery_zone' => 'Add Delivery Zone',
            'add_seller' => 'Add Seller',
            'add_delivery_boy' => 'Add Delivery Boy',
            'add_product' => 'Add Product',
        ];

        $completedSteps = [
            'add_store' => \App\Models\Store::count() > 0,
            'add_category' => \App\Models\Category::count() > 0,
            'add_city' => \App\Models\City::count() > 0,
            'add_zipcode' => \App\Models\Zipcode::count() > 0,
            'add_delivery_zone' => \App\Models\Zone::count() > 0,
            'add_seller' => \App\Models\Seller::count() > 0,
            'add_delivery_boy' => \App\Models\User::where('role_id', 3)->count() > 0,
            'add_product' => \App\Models\Product::count() > 0,
        ];
        $totalSteps = count($setupSteps);
        $completedCount = count(array_filter($completedSteps));
        $progressPercentage = round(($completedCount / $totalSteps) * 100);

        $allStepsCompleted = $completedCount === $totalSteps;


        if ($store) {
            $store_settings = $store->store_settings ?? [];
            $setup_done_shown = $store_settings['setup_done_shown'] ?? false;

            // If all steps are done and not yet marked as shown in DB
            if ($allStepsCompleted && !$setup_done_shown) {
                session()->flash('show_done_message', true);

                $store_settings['setup_done_shown'] = true;
                $store->store_settings = $store_settings;
                $store->save();

                // Update session store_details to reflect the change if it's cached there
                session()->forget("store_details");
            }
        }

        // Get missing labels summary
        $missing_labels_summary = $this->getMissingLabelsSummary();

        // dd($top_sellers);
        return view('admin.pages.forms.home', compact(
            'order_counter',
            'id',
            'store_id',
            'user_counter',
            'delivery_boy_counter',
            'currency',
            'top_sellers',
            'total_products',
            'total_store',
            'total_seller',
            'total_earnings',
            'role_id',
            'store_details',
            'primary_colour',
            'messengerColor',
            'dark_mode',
            'sales',
            'totalSteps',
            'completedSteps',
            'progressPercentage',
            'setupSteps',
            'missing_labels_summary'
        ));
    }

    private function getMonthlyData($type, $store_id)
    {
        return OrderItems::selectRaw("SUM($type) as total, DATE_FORMAT(created_at, '%b') AS month_name")
            ->where('store_id', $store_id)
            ->groupByRaw('YEAR(CURDATE()), MONTH(created_at)')
            ->orderByRaw('YEAR(CURDATE()), MONTH(created_at)')
            ->get()
            ->toArray();
    }


    private function getWeeklySalesData($tableName, $dateColumn, $revenueColumn, $commissionColumn, $salesColumn, $store_id)
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        // Initialize the week-wise sales data structure
        $weekWiseSales = [
            'total_revenue' => array_fill(0, 7, 0), // 0 for each day of the week
            'total_commission' => array_fill(0, 7, 0),
            'total_sales' => array_fill(0, 7, 0),
            'week' => [],
        ];

        // Fetch sales data from the database

        $res = OrderItems::selectRaw("
            DATE_FORMAT(created_at, '%d-%b') as date,
            SUM(sub_total) as total_revenue,
            SUM(admin_commission_amount) as total_commission,
            SUM(quantity) as total_sales
        ")
            ->where('store_id', $store_id)
            ->whereBetween(DB::raw('DATE(created_at)'), [
                $startOfWeek->format('Y-m-d'),
                $endOfWeek->format('Y-m-d')
            ])
            ->groupByRaw('DATE(created_at)')
            ->orderByRaw('DATE(created_at)')
            ->get();

        // dd($res);
        // Populate the week-wise sales data
        foreach ($res as $dayData) {
            // Get the day index based on the difference from the start of the week
            $dayIndex = Carbon::createFromFormat('d-M', $dayData->date)->diffInDays($startOfWeek);

            // Set the corresponding revenue, commission, and sales for that day
            $weekWiseSales['total_revenue'][$dayIndex] = intval($dayData->total_revenue);
            $weekWiseSales['total_commission'][$dayIndex] = intval($dayData->total_commission);
            $weekWiseSales['total_sales'][$dayIndex] = intval($dayData->total_sales);
            $weekWiseSales['week'][$dayIndex] = $dayData->date;
        }

        // Fill in the week with day names for all 7 days
        foreach (range(0, 6) as $i) {
            if (!isset($weekWiseSales['week'][$i])) {
                $weekWiseSales['week'][$i] = $startOfWeek->copy()->addDays($i)->format('d-M');
            }
        }

        return $weekWiseSales;
    }

    private function getDailySalesData($tableName, $dateColumn, $revenueColumn, $commissionColumn, $salesColumn, $store_id, $daysBack)
    {
        $endDate = Carbon::now();
        $startDate = $endDate->copy()->subDays($daysBack);

        $res = OrderItems::selectRaw("
        DAY(created_at) as date,
        SUM(sub_total) as total_revenue,
        SUM(admin_commission_amount) as total_commission,
        SUM(quantity) as total_sales
    ")
            ->where('store_id', $store_id)
            ->where('created_at', '>=', $startDate)
            ->groupByRaw("DAY(created_at)")
            ->get();

        return $res;
    }

    private function getMissingLabelsSummary()
    {
        $languages = Language::all();
        $file_types = ['app', 'panel', 'web', 'seller', 'delivery'];
        $summary = [];

        foreach ($languages as $language) {
            $language_code = $language->code;
            $language_summary = [
                'language_id' => $language->id,
                'language_name' => $language->language,
                'language_code' => $language_code,
                'files' => []
            ];

            foreach ($file_types as $file_type) {
                $filename = match($file_type) {
                    'app' => 'app_labels.json',
                    'panel', 'admin' => 'panel_labels.json',
                    'web' => 'web_labels.json',
                    'seller' => 'seller_labels.json',
                    'delivery' => 'delivery_labels.json',
                    default => 'panel_labels.json'
                };

                $current_file = base_path("/resources/lang/{$language_code}/{$filename}");
                $reference_file = match($file_type) {
                    'app' => base_path("/resources/lang/en/app_labels.json"),
                    'panel', 'admin' => base_path("/resources/lang/en/panel_labels.json"),
                    'web' => base_path("/resources/lang/en/web_labels.json"),
                    'seller' => base_path("/resources/lang/en/seller_labels.json"),
                    'delivery' => base_path("/resources/lang/en/delivery_labels.json"),
                    default => base_path("/resources/lang/en/panel_labels.json")
                };

                $current_data = [];
                $reference_data = [];
                $missing_labels = [];
                $updated_at = null;

                // Load current file
                if (file_exists($current_file)) {
                    $currentContent = file_get_contents($current_file);
                    $current_data = json_decode($currentContent, true);

                    if (isset($current_data['_metadata'])) {
                        $updated_at = $current_data['_metadata']['updated_at'] ?? null;
                        unset($current_data['_metadata']);
                    }
                    if (isset($current_data['_missing_labels'])) {
                        unset($current_data['_missing_labels']);
                    }
                }

                // Load reference file
                if (file_exists($reference_file)) {
                    $referenceContent = file_get_contents($reference_file);
                    $reference_data = json_decode($referenceContent, true);

                    if (isset($reference_data['_metadata'])) {
                        unset($reference_data['_metadata']);
                    }
                    if (isset($reference_data['_missing_labels'])) {
                        unset($reference_data['_missing_labels']);
                    }
                }

                // Compare
                if (!empty($reference_data) && !empty($current_data)) {
                    $missing_labels = array_diff_key($reference_data, $current_data);
                } elseif (!empty($reference_data) && empty($current_data)) {
                    $missing_labels = $reference_data;
                }

                $language_summary['files'][] = [
                    'file_type' => $file_type,
                    'exists' => file_exists($current_file),
                    'missing_labels_count' => count($missing_labels),
                    'missing_labels' => array_values(array_keys($missing_labels)),
                    'total_labels' => count($current_data),
                    'reference_labels' => count($reference_data),
                    'updated_at' => $updated_at,
                ];
            }

            // Only add to summary if there are missing labels
            $total_missing = array_sum(array_column($language_summary['files'], 'missing_labels_count'));
            if ($total_missing > 0) {
                $language_summary['total_missing_labels'] = $total_missing;
                $summary[] = $language_summary;
            }
        }

        return $summary;
    }

}
