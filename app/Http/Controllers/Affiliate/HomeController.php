<?php

namespace App\Http\Controllers\Affiliate;
use App\Models\ComboProduct;
use Illuminate\Http\Request;
use App\Models\OrderItems;
use App\Models\PaymentRequest;
use Illuminate\Routing\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Currency;
use App\Services\TranslationService;
use App\Services\SettingService;
use Illuminate\Support\Facades\Auth;
use App\Models\AffiliateTracking;
use App\Services\MediaService;

class HomeController extends Controller
{
    public function index()
    {
        $userId = Auth::id() ?? 0;
        $categories = Category::where('status', 1)->where('is_in_affiliate', 1)->get();
        $languageCode = app(TranslationService::class)->getLanguageCode();
        $currencyDetails = fetchDetails(Currency::class, ['is_default' => 1], 'symbol');
        $currency = !$currencyDetails->isEmpty() ? $currencyDetails[0]->symbol : '';
        $earningData = $this->getCommissionSummary($userId);
        $topProducts = $this->topSellingProducts($userId);
        return view('affiliate.pages.forms.home', compact('categories', 'languageCode', 'earningData', 'currency', 'topProducts'));
    }


    public function categoryEarnings(Request $request)
    {
        $languageCode = app(TranslationService::class)->getLanguageCode();
        $currencyDetails = fetchDetails(Currency::class, ['is_default' => 1], 'symbol');
        $currency = !$currencyDetails->isEmpty() ? $currencyDetails[0]->symbol : '';

        $mode = $request->get('mode', 'top');
        $earningsQuery = AffiliateTracking::selectRaw('category_id, SUM(commission_earned) as total')
            ->groupBy('category_id')
            ->orderByDesc('total');

        if ($mode == 'top') {
            $earningsQuery->limit(5);
        }

        $earnings = $earningsQuery->pluck('total', 'category_id');
        // dd($earningsQuery->toSql(), $earningsQuery->getBindings());
        $categories = Category::where('is_in_affiliate', true)
            ->whereIn('id', $earnings->keys())
            ->get();

        $data = [
            'labels' => [],
            'values' => [],
            'currency' => $currency,
        ];

        foreach ($categories as $category) {
            $translatedName = app(TranslationService::class)
                ->getDynamicTranslation(Category::class, 'name', $category->id, $languageCode);

            $data['labels'][] = $translatedName;
            $data['values'][] = round($earnings[$category->id], 2);
        }
        // dd($data);
        return response()->json($data);
    }


    public function monthlyEarnings()
    {
        $affiliateId = auth()->id(); // Get logged-in affiliate
        $currency = fetchDetails(Currency::class, ['is_default' => 1], 'symbol')->first()?->symbol ?? '₹';

        // Get commission earned, grouped by month
        $earnings = OrderItems::selectRaw('MONTH(created_at) as month, SUM(affiliate_commission_amount) as total')
            ->where('affiliate_id', $affiliateId)
            ->where('is_affiliate_commission_settled', 1)
            ->where('active_status', 'delivered')
            ->groupByRaw('MONTH(created_at)')
            ->orderBy('month')
            ->pluck('total', 'month');

        $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $labels = [];
        $values = [];

        for ($i = 1; $i <= 12; $i++) {
            $labels[] = $monthNames[$i - 1];
            $values[] = round($earnings[$i] ?? 0, 2); // 0 if no data for that month
        }

        return response()->json([
            'labels' => $labels,
            'values' => $values,
            'currency' => $currency
        ]);
    }


    public function showByCategory($id)
    {
        // Get category
        $category = Category::findOrFail($id);
        $languageCode = app(TranslationService::class)->getLanguageCode();

        // Get individual affiliate products in this category
        $products = Product::where('category_id', $id)
            ->where('is_in_affiliate', 1)
            ->with('firstVariant')
            ->get()
            ->each(function ($product) {
                $product->source_type = 'product';
            });

        // Get affiliate combo products that contain this category ID in their comma-separated category_ids
        $comboProducts = ComboProduct::where('is_in_affiliate', 1)
            ->whereRaw("FIND_IN_SET(?, category_ids)", [$id])
            ->get()
            ->each(function ($combo) {
                $combo->source_type = 'combo_product';
            });

        // Merge both collections
        $products = $products->merge($comboProducts);

        $currency = fetchDetails(Currency::class, ['is_default' => 1], 'symbol')[0]->symbol ?? "";

        return view('affiliate.pages.views.products', compact('products', 'category', 'languageCode', 'currency'));
    }

    public function getCategories()
    {
        // Get category
        $categories = Category::where('status', 1)->where('is_in_affiliate', 1)->get();
        $languageCode = app(TranslationService::class)->getLanguageCode();
        return view('affiliate.pages.views.categories', compact('categories', 'languageCode'));
    }

    public function getPolicies()
    {
        // Get policies
        $privacyPolicy = app(SettingService::class)->getSettings('affiliate_privacy_policy', true);
        $privacyPolicy = json_decode($privacyPolicy, true);

        $termsAndConditions = app(SettingService::class)->getSettings('affiliate_terms_and_conditions', true);
        $termsAndConditions = json_decode($termsAndConditions, true);

        return view('affiliate.pages.views.policies', compact('privacyPolicy', 'termsAndConditions'));
    }

    public function getCommissionSummary($affiliateId)
    {
        $response = [
            'total_profit' => 0,
            'pending' => 0,
            'confirmed' => 0,
            'requested' => 0,
            'paid' => 0,
        ];

        // Total profit (sum of affiliate_commission_amount)
        $totalProfit = OrderItems::where('affiliate_id', $affiliateId)->where('is_affiliate_commission_settled', 1)->where('active_status', 'delivered')
            ->sum('affiliate_commission_amount');
        $response['total_profit'] = $totalProfit ?? 0;

        // Pending commission (not settled)
        $pending = OrderItems::where('affiliate_id', $affiliateId)
            ->where('is_affiliate_commission_settled', 0)
            ->sum('affiliate_commission_amount');
        $response['pending'] = $pending ?? 0;

        // Confirmed commission (affiliate_wallet_balance)
        $confirmed = OrderItems::where('affiliate_id', $affiliateId)
            ->where('is_affiliate_commission_settled', 0)
            ->where('active_status', 'delivered')
            ->sum('affiliate_commission_amount');
        $response['confirmed'] = $confirmed ?? 0;

        // Requested amount (payment_requests with status=0)
        $requested = PaymentRequest::where('user_id', $affiliateId)
            ->where('payment_type', 'affiliate')
            ->where('status', 0)
            ->sum('amount_requested');
        $response['requested'] = $requested ?? 0;

        // Paid amount (payment_requests with status=1)
        $paid = PaymentRequest::where('user_id', $affiliateId)
            ->where('payment_type', 'affiliate')
            ->where('status', 1)
            ->sum('amount_requested');
        $response['paid'] = $paid ?? 0;

        return $response;
    }

    public function topSellingProducts($affiliateId)
    {

        $languageCode = app(TranslationService::class)->getLanguageCode();

        $topProducts = AffiliateTracking::where('affiliate_id', $affiliateId)
            ->selectRaw('product_id, SUM(usage_count) as total_usage')
            ->groupBy('product_id')
            ->havingRaw('SUM(usage_count) > 0')
            ->orderByDesc('total_usage')
            ->limit(5)
            ->get();

        $data = [];

        foreach ($topProducts as $item) {
            $product = Product::find($item->product_id);
            $category = $product?->category;

            // Handle combo products
            if ($product && $product->type === 'combo') {
                $combo = ComboProduct::find($product->combo_id);

                $productName = $combo
                    ? app(TranslationService::class)->getDynamicTranslation(ComboProduct::class, 'title', $combo->id, $languageCode)
                    : 'Unknown Combo';

                $imagePath = $combo?->image ?? null;
            } else {
                $productName = $product
                    ? app(TranslationService::class)->getDynamicTranslation(Product::class, 'name', $product->id, $languageCode)
                    : 'Unknown Product';

                $imagePath = $product?->image ?? null;
            }

            $categoryName = $category
                ? app(TranslationService::class)->getDynamicTranslation(Category::class, 'name', $category->id, $languageCode)
                : 'Unknown Category';

            $imageUrl = $imagePath
                ? app(MediaService::class)->getMediaImageUrl($imagePath)
                : asset('assets/img/default_full_logo.png');

            $data[] = [
                'product' => $productName,
                'category' => $categoryName,
                'quantity_sold' => $item->total_usage,
                'image' => $imageUrl,
            ];
        }

        if (request()->expectsJson()) {
            return response()->json($data);
        }

        return $data;
    }

    public function getTopCategories(Request $request)
    {
       $affiliateId = auth()->id() ?? null;
        $filter = $request->query('filter', 'monthly');

        // Calculate date range start based on filter
        switch ($filter) {
            case 'weekly':
                $startDate = now()->startOfWeek();
                break;
            case 'monthly':
                $startDate = now()->startOfMonth();
                break;
            case 'yearly':
                $startDate = now()->startOfYear();
                break;
            default:
                $startDate = now()->startOfMonth();
        }

        // Fetch default currency symbol
        $currency = fetchDetails(Currency::class, ['is_default' => 1], 'symbol')[0]->symbol ?? "";

        $topCategories = AffiliateTracking::where('affiliate_id', $affiliateId)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('category_id, SUM(commission_earned) as total_commission')
            ->groupBy('category_id')
            ->havingRaw('SUM(commission_earned) > 0')
            ->orderByDesc('total_commission')
            ->limit(5)
            ->get();

        $languageCode = app(TranslationService::class)->getLanguageCode();

        $data = [];

        foreach ($topCategories as $item) {
            $category = Category::find($item->category_id);

            $categoryName = $category
                ? app(TranslationService::class)->getDynamicTranslation(Category::class, 'name', $category->id, $languageCode)
                : 'Unknown Category';

            $imagePath = $category?->image ?? null;
            $imageUrl = $imagePath ? app(MediaService::class)->getMediaImageUrl($imagePath) : asset('assets/img/default_full_logo.png');

            $data[] = [
                'category' => $categoryName,
                'total_commission' => $currency . ' ' . round($item->total_commission, 2),
                'image' => $imageUrl,
            ];
        }

        return response()->json($data);
    }


}
