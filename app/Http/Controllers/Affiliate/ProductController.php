<?php

namespace App\Http\Controllers\Affiliate;

use Illuminate\Routing\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ComboProduct;
use App\Models\Store;
use Illuminate\Http\Request;
use App\Models\AffiliateTracking;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Services\StoreService;
use App\Services\TranslationService;
use App\Services\MediaService;
class ProductController extends Controller
{
    public function generateToken(Request $request)
    {
        // dd($request);
        $productId = $request->product_id;
        $productName = $request->product_name;
        $productSlug = $request->product_slug;
        $categoryId = $request->category_id;
        $affiliateUuid = $request->affiliate_uuid;
        $productType = $request->product_type;

        $user = auth()->user();

        if (!$user || !$user->affiliateUser || $user->affiliateUser->uuid !== $affiliateUuid) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $affiliateId = $user->id;

        // ✅ Get affiliate commission based on category
        $category = Category::find($categoryId);
        if (!$category) {
            return response()->json(['error' => 'Invalid category'], 404);
        }
        $categoryCommission = $category->affiliate_commission ?? 0;

        // ✅ Get product or combo product
        if ($productType == 'combo_products') {
            $product = ComboProduct::find($productId);
            $baseUrl = 'combo-products';
        } else {
            $product = Product::find($productId);
            $baseUrl = 'products';
        }

        if (!$product) {
            return response()->json(['error' => 'Invalid product'], 404);
        }

        // ✅ Get store slug
        $store = Store::find($product->store_id);
        if (!$store) {
            return response()->json(['error' => 'Invalid store'], 404);
        }

        $storeSlug = $store->slug;

        // ✅ Create or fetch token
        $plainToken = $productSlug . '-' . $productId . '-' . $categoryId . '-' . $affiliateUuid . '-' . now()->timestamp;

        $existing = AffiliateTracking::where('product_id', $productId)
            ->where('affiliate_id', $affiliateId)
            ->where('category_id', $categoryId)
            ->first();

        if ($existing) {
            $token = $existing->original_token;
            $hashedToken = $existing->token;
        } else {
            $token = $plainToken;
            $hashedToken = Hash::make($plainToken);

            $track = new AffiliateTracking();
            $track->product_id = $productId;
            $track->affiliate_id = $affiliateId;
            $track->token = $hashedToken;
            $track->original_token = $plainToken;
            $track->category_id = $categoryId;
            $track->category_commission = $categoryCommission;
            $track->affiliate_uuid = $affiliateUuid;
            $track->product_type = $productType;
            $track->save();
        }

        // ✅ Construct full link (based on product type)
        $fullLink = url("/{$baseUrl}/{$productSlug}/?store={$storeSlug}&ref={$hashedToken}");

        return response()->json([
            'token' => $token,
            'url' => $fullLink
        ]);
    }


    public function PromotedProducts(Request $request)
    {
         $categories = Category::select('id', 'name')->get();

        return view('affiliate.pages.tables.promoted_products', compact('categories'));
    }
    public function PromotedProductlist(Request $request)
    {
        $storeId = app(StoreService::class)->getStoreId();
        $search = trim($request->input('search'));
        $sort = $request->input('sort', 'id');
        $order = $request->input('order', 'DESC');
        $offset = $search || $request->input('pagination_offset') ? $request->input('pagination_offset') : 0;
        $limit = $request->input('limit', 10);
        $languageCode = app(TranslationService::class)->getLanguageCode();

        $affiliateId = auth()->user()->id;

        // Step 1: Get raw tracking entries
        $trackings = AffiliateTracking::where('affiliate_id', $affiliateId)
            ->whereNotNull('product_id')
            ->whereNotNull('token')
            ->get();
        // dd($trackings);

        // Step 2: Gather product/category IDs
        $productIds = $trackings->pluck('product_id')->unique();
        $categoryIds = $trackings->pluck('category_id')->unique();

        // Step 3: Bulk load product and category names/images
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');
        $comboProducts = ComboProduct::whereIn('id', $productIds)->get()->keyBy('id');
        $categories = Category::whereIn('id', $categoryIds)->get()->keyBy('id');

        // Step 4: Transform data for search/sort
        $data = $trackings->map(function ($item) use ($products, $comboProducts, $categories, $languageCode) {
            $productName = '';
            $imagePath = null;

            if ($item->product_type === 'combo_products') {
                $combo = $comboProducts[$item->product_id] ?? null;
                $productName = $combo
                    ? app(TranslationService::class)->getDynamicTranslation(ComboProduct::class, 'title', $combo->id, $languageCode)
                    : 'Unknown Combo';
                $imagePath = $combo?->image;
            } else {
                $product = $products[$item->product_id] ?? null;
                $productName = $product
                    ? app(TranslationService::class)->getDynamicTranslation(Product::class, 'name', $product->id, $languageCode)
                    : 'Unknown Product';
                $imagePath = $product?->image;
            }

            $category = $categories[$item->category_id] ?? null;
            $categoryName = $category
                ? app(TranslationService::class)->getDynamicTranslation(Category::class, 'name', $category->id, $languageCode)
                : 'Unknown Category';

            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $productName ?? '',
                'category_name' => $categoryName ?? '',
                'category_commission' => $item->category_commission,
                'token' => $item->token,
                'usage_count' => $item->usage_count,
                'commission_earned' => $item->commission_earned,
                'total_order_value' => $item->total_order_value,
                'image' => $imagePath,
                'created_at' => $item->created_at,
            ];
        });

        // Step 5: Search filter
        if (!empty($search)) {
            $data = $data->filter(function ($item) use ($search) {
                return str_contains(strtolower($item['product_name']), strtolower($search))
                    || str_contains(strtolower($item['category_name']), strtolower($search))
                    || str_contains(strtolower($item['token']), strtolower($search));
            });
        }

        // Step 6: Sort
        $data = $data->sortBy($sort, SORT_REGULAR, strtolower($order) === 'desc');

        $total = $data->count();
        $data = $data->slice($offset, $limit)->values();

        // Step 7: Add action buttons and image
        $data = $data->map(function ($item) {
            $imageTag = '';
            if ($item['image']) {
                $imgUrl = app(MediaService::class)->getMediaImageUrl($item['image']);
                $imgThumb = route('admin.dynamic_image', ['url' => $imgUrl, 'width' => 60, 'quality' => 90]);
                $imageTag = '<div><a href="' . $imgUrl . '" data-lightbox="image-' . $item['id'] . '"><img src="' . $imgThumb . '" alt="Product" class="rounded"/></a></div>';
            }

            return array_merge($item, [
                'image' => $imageTag,
            ]);
        });

        return response()->json([
            'rows' => $data,
            'total' => $total,
        ]);
    }
}
