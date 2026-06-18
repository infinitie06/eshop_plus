<?php

namespace App\Http\Controllers\Seller;

use App\Models\Brand;
use App\Models\Language;
use App\Models\Product;
use App\Models\Seller;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Services\TranslationService;
use App\Traits\HandlesValidation;
use App\Services\StoreService;
use App\Services\MediaService;
use Illuminate\Support\Facades\Auth;

class BrandController extends Controller
{
    use HandlesValidation;
    public function index()
    {
        $languages = Language::all();
        return view('seller.pages.forms.brands', compact('languages'));
    }


    public function store(Request $request)
    {
        $storeId = app(StoreService::class)->getStoreId();

$user_id = Auth::user()->id;

        $seller_id = Seller::where('user_id', $user_id)->value('id');
        $rules = [
            'brand_name' => 'required|string',
            'translated_brand_name' => 'sometimes|array',
            'translated_brand_name.*' => 'nullable|string',
            'image' => 'required',
        ];

        if ($response = $this->HandlesValidation($request, $rules)) {
            return $response;
        }
        $brandData = $request->all();
        $existingBrand = Brand::where('store_id', app(StoreService::class)->getStoreId())
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.en')) = ?", $brandData['brand_name'])
            ->first();

        if ($existingBrand) {
            return response()->json([
                'error' => true,
                'message' => 'Brand name already exists.',
                'language_message_key' => 'brand_name_exists',
            ], 422);
        }

        $translations = [
            'en' => $brandData['brand_name']
        ];

        // Merge other translations if available
        if (!empty($brandData['translated_brand_name'])) {
            $translations = array_merge($translations, $brandData['translated_brand_name']);
        }


        $brandData['name'] = json_encode($translations, JSON_UNESCAPED_UNICODE);


        unset($brandData['brand_name'], $brandData['translated_brand_name']);

        // Add additional fields
        $brandData['slug'] = generateSlug($translations['en'], 'brands');
        $brandData['status'] = 2;
        $brandData['store_id'] = $storeId;
        $brandData['seller_id'] = $seller_id;

        unset($brandData['_method']);
        unset($brandData['_token']);

        $brand = new Brand();
        $brand->fill($brandData);
        $brand->save();

        // Return response
        if ($request->ajax()) {
            return response()->json(['message' => labels('admin_labels.brand_created_successfully', 'Brand created successfully, Wait for approval of admin')]);
        }

        return redirect()->back()->with('success', labels('admin_labels.brand_created_successfully', 'Brand created successfully'));
    }
    public function list(Request $request)
    {
        $storeId = app(StoreService::class)->getStoreId();
        $search = trim($request->get('search'));
        $sort = $request->get('sort', 'id');
        $order = $request->get('order', 'DESC');
        $offset = $request->get('offset', 0);
        $limit = $request->get('limit', 10);
 $user_id = Auth::user()->id;

        $seller_id = Seller::where('user_id', $user_id)->value('id');
        $brandData = Brand::where('store_id', $storeId)
            ->where('status', 2)
             ->where('seller_id', $seller_id);  // ONLY REQUESTED BRANDS

        if ($search) {
            $brandData = $brandData->where('name', 'like', "%$search%");
        }

        $total = $brandData->count();

        $brands = $brandData
            ->orderBy($sort, $order)
            ->offset($offset)
            ->limit($limit)
            ->get();

        $rows = $brands->map(function ($b) {
            $languageCode = app(TranslationService::class)->getLanguageCode();
            $image = route('admin.dynamic_image', [
                'url' => app(MediaService::class)->getMediaImageUrl($b->image),
                'width' => 60,
                'quality' => 90
            ]);

            return [
                'id' => $b->id,
                'name' => app(TranslationService::class)
                    ->getDynamicTranslation(Brand::class, 'name', $b->id, $languageCode),
                'image' => '<img src="' . $image . '" class="rounded" />',
                'status' => '<span class="badge bg-warning">Requested</span>',
            ];
        });

        return response()->json([
            "rows" => $rows,
            "total" => $total
        ]);
    }
}
