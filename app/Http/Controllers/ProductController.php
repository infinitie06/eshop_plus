<?php

namespace App\Http\Controllers;

use App\Libraries\Shiprocket;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Favorite;
use App\Models\PickupLocation;
use App\Models\Product;
use App\Models\Product_variants;
use App\Models\ProductFaq;
use App\Models\Zipcode;
use App\Services\ComboProductService;
use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Traits\HandlesValidation;
use App\Services\ProductService;
use App\Services\DeliveryService;
use App\Services\MediaService;
use App\Services\TranslationService;

class ProductController extends Controller

{
    use HandlesValidation;
    public function AddProductFaqs(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'product_id' => 'required|integer|exists:products,id',
            'question' => 'required',
        ]);

        $faq = new ProductFaq();
        $faq->user_id = $request->user_id;
        $faq->product_id = $request->product_id;
        $faq->question = $request->question;

        $faq->save();

        return response()->json(['message' => 'Product faq add successfully']);
    }

    // public function add_to_favorites(Request $request)
    // {
    //     $request->validate([
    //         'user_id' => 'required|integer|exists:users,id',
    //         'product_id' => 'required|integer|exists:products,id',
    //         'product_type' => 'required',
    //     ]);

    //     $user_id = Auth::user() != '' ? Auth::user()->id : 0;

    //     $product_id = $request->input('product_id');
    //     $product_type = $request->input('product_type');
    //     if (isExist(['user_id' => $user_id, 'product_id' => $product_id, 'product_type' => $product_type], Favorite::class)) {
    //         $response = [
    //             'error' => true,
    //             'message' => 'Already added to favorite !',
    //             'data' => [],
    //         ];
    //         return response()->json($response);
    //     }
    //     $data = [
    //         'user_id' => $user_id,
    //         'product_id' => $product_id,
    //         'product_type' => $product_type,
    //     ];
    //     $fav_res = Favorite::create($data);
    //     $store_id = session('store_id');
    //     $favorite_count = getFavorites(user_id: $user_id, store_id: $store_id);
    //     if ($fav_res) {
    //         $this->emit('wishlistUpdated');

    //         $response = [
    //             'error' => false,
    //             'message' => 'Added to favorite !',
    //             'wishlist_count' => $favorite_count['favorites_count'],
    //             'data' => [],
    //         ];
    //     } else {
    //         $response = [
    //             'error' => true,
    //             'message' => 'Not Added to favorite !',
    //             'data' => [],
    //         ];
    //     }
    //     return response()->json($response);
    // }

    public function add_to_favorites(Request $request)
    {
        $request->validate([
            'product_type' => 'required',
        ]);

        if ($request->product_type === 'combo') {
            $request->validate([
                'product_id' => 'required|integer|exists:combo_products,id',
            ]);
        } else {
            $request->validate([
                'product_id' => 'required|integer|exists:products,id',
            ]);
        }

        // ✅ ADD THIS HERE
        $user_id = Auth::id();

        if (!$user_id) {
            return response()->json([
                'error' => true,
                'message' => 'User not authenticated',
            ]);
        }

        $product_id = $request->input('product_id');
        $product_type = $request->input('product_type');

        if (isExist(['user_id' => $user_id, 'product_id' => $product_id, 'product_type' => $product_type], Favorite::class)) {
            return response()->json([
                'error' => true,
                'message' => 'Already added to favorite !',
                'data' => [],
            ]);
        }

        $data = [
            'user_id' => $user_id,
            'product_id' => $product_id,
            'product_type' => $product_type,
        ];

        $fav_res = Favorite::create($data);
        $store_id = session('store_id');
        $favorite_count = getFavorites(user_id: $user_id, store_id: $store_id);

        if ($fav_res) {
            return response()->json([
                'error' => false,
                'message' => 'Added to favorite !',
                'wishlist_count' => $favorite_count['favorites_count'],
                'data' => [],
            ]);
        }

        return response()->json([
            'error' => true,
            'message' => 'Not Added to favorite !',
            'data' => [],
        ]);
    }

    public function remove_from_favorite(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'productType' => 'required',
        ]);

        if ($request->productType === 'combo') {
            $request->validate([
                'productId' => 'required|integer|exists:combo_products,id',
            ]);
        } else {
            $request->validate([
                'productId' => 'required|integer|exists:products,id',
            ]);
        }
        $product_id = $request->input('productId');
        if ($product_id == '') {
            $response = [
                'error' => true,
                'message' => 'Please pass product id',
                'code' => 102,
            ];
            return response()->json($response);
        } else {
            // uncomment after dynamic login

            if (auth()->check()) {
                $user_id = Auth::user() != '' ? Auth::user()->id : 0;
            } else {
                $response = [
                    'error' => true,
                    'message' => 'Please Login first.',
                    'code' => 102,
                ];
                return response()->json($response);
            }


            $productType = $request->input('productType');
            if (!isExist(['user_id' => $user_id, 'product_id' => $product_id, 'product_type' => $productType], Favorite::class)) {
                $response = [
                    'error' => true,
                    'message' => 'Item not added as favorite !',
                    'data' => [],
                ];
                return response()->json($response);
            }
            $data = [
                'user_id' => $user_id,
                'product_id' => $product_id,
                'product_type' => $productType,
            ];
            deleteDetails($data, Favorite::class);
            $favorite_count = getFavorites($user_id);


            $response = [
                'error' => false,
                'message' => 'Removed from favorite',
                'wishlist_count' => $favorite_count['favorites_count'],
                'data' => [],
            ];
            return response()->json($response);
        }
    }


    public function check_zipcode(request $request)
    {
        $rules = [
            'product_id' => 'required|numeric|exists:products,id',
            'zipcode' => 'required',
        ];
        if ($response = $this->HandlesValidation($request, $rules)) {
            return $response;
        } else {
            $zipcode = request('zipcode');
            $is_pincode = isexist(['zipcode' => $zipcode], Zipcode::class);
            $product_id = request('product_id');
            if ($is_pincode) {
                $zipcode_id = fetchDetails(Zipcode::class, ['zipcode' => $zipcode], 'id');
                $zipcode_id = !$zipcode_id->isEmpty() ? $zipcode_id[0]->id : '';
                $is_available = app(DeliveryService::class)->isProductDelivarable('zipcode', $zipcode_id, $product_id);
                if ($is_available) {
                    session(['valid_zipcode' => $zipcode]);
                    return response()->json([
                        'error' => false,
                        'message' => 'Product is deliverable on "' . $zipcode . '"',
                    ]);
                }
            }

            $product_data = fetchDetails(Product::class, ['id' => $product_id], 'pickup_location');
            $product_variant_data = fetchDetails(Product_variants::class, ['product_id' => $product_id], 'weight');
            $pickup_pincode = fetchDetails(PickupLocation::class, ['id' => $product_data[0]->pickup_location, 'status' => 1], 'pincode');

            if (!empty($zipcode)) {
                $availability_deliverability = [
                    'pickup_postcode' => !$pickup_pincode->isEmpty() ? $pickup_pincode[0]->pincode : "",
                    'delivery_postcode' => $zipcode,
                    'cod' => 0,
                    'weight' => !$product_variant_data->isEmpty() ? $product_variant_data[0]->weight : 0,
                ];
                $shiprocket = new Shiprocket();

                $check_deliverability = $shiprocket->check_serviceability($availability_deliverability);

                if (isset($check_deliverability['status_code']) && $check_deliverability['status_code'] == 422) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Invalid Delivery Pincode "' . $zipcode . '"',
                    ]);
                } else {
                    if (isset($check_deliverability['status']) && $check_deliverability['status'] == 200 && !empty($check_deliverability['data']['available_courier_companies'])) {
                        $estimate_date = $check_deliverability['data']['available_courier_companies'][0]['etd'];
                        session(['valid_zipcode' => $zipcode]);
                        return response()->json([
                            'error' => false,
                            'message' => 'Product is deliverable by ' . $estimate_date . '',
                        ]);
                    } else {
                        return response()->json([
                            'error' => true,
                            'message' => 'Product is not deliverable on "' . $zipcode . '"',
                        ]);
                    }
                }
            } else {
                return response()->json([
                    'error' => true,
                    'message' => 'Cannot deliver to "' . $zipcode . '"',
                ]);
            }
        }
    }
    public function get_compare_data(Request $request)
    {
        $product_ids = $request->compare_data;

        if (empty($product_ids) || !isset($product_ids[0])) {
            return response()->json([
                'error' => true,
                'message' => 'Product IDs are required in compare_data',
                'data' => [],
            ]);
        } else {
            $product_details = [];

            foreach ($product_ids as $product_id) {
                $products = app(ProductService::class)->fetchProduct("", "", $product_id);
                $product_details[] = $products['product'];
            }
            return $product_details;
        }
    }
    public function add_to_compare(Request $request)
    {
            // dd('request->all');
        try {
            $store_id = session('store_id');
            $language_code = app(TranslationService::class)->getLanguageCode();

            // Log or inspect incoming payload
            \Log::info('Compare request:', $request->all());

            // Validate input
            if (!$request->has('product_id') || empty($request->product_id)) {
                return response()->json([
                    'error' => true,
                    'message' => 'No product data provided.',
                    'data' => [],
                ], 400);
            }

            $obj = $request->input('product_id');
            $combo_product_id = [];
            $regular_product_id = [];


            // Handle both array-of-objects and array-of-IDs input formats
            foreach ($obj as $data) {
                if (is_array($data) && isset($data['product_type'])) {
                    if ($data['product_type'] === 'combo') {
                        $combo_product_id[] = $data['product_id'];
                    } else {
                        $regular_product_id[] = $data['product_id'];
                    }
                } else {
                    $regular_product_id[] = $data;
                }
            }

            $products = [];
            if (count($regular_product_id) >= 1) {
                $productResult = app(ProductService::class)->fetchProduct(id: $regular_product_id, store_id: $store_id);

                if (!empty($productResult['product'])) {
                    $products = $productResult['product'];

                    foreach ($products as $key => $product) {
                        $product = (object) $product; // convert to object

                        $product->image = app(MediaService::class)->dynamic_image($product->image ?? '', 150);
                        $product->category_name = app(TranslationService::class)
                            ->getDynamicTranslation(Category::class, 'name', $product->category_id ?? null, $language_code);
                        $product->brand_name = app(TranslationService::class)
                            ->getDynamicTranslation(Brand::class, 'name', $product->brand ?? null, $language_code) ?? '';

                        if (!empty($product->min_max_price)) {
                            $product->min_max_price['max_price'] =
                                app(CurrencyService::class)->currentCurrencyPrice($product->min_max_price['max_price']);
                            $product->min_max_price['special_min_price'] =
                                app(CurrencyService::class)->currentCurrencyPrice($product->min_max_price['special_min_price'], true);
                        }

                        $products[$key] = $product;
                    }
                }
            }

            $combo_products = [];
            if (count($combo_product_id) >= 1) {
                $comboResult = app(ComboProductService::class)->fetchComboProduct(id: $combo_product_id, store_id: $store_id);

                if (!empty($comboResult['combo_product'])) {
                    $combo_products = $comboResult['combo_product'];

                    foreach ($combo_products as $key => $combo_product) {
                        $combo_product = (object) $combo_product;

                        $combo_product->image = app(MediaService::class)->dynamic_image($combo_product->image ?? '', 150);
                        $combo_product->price = app(CurrencyService::class)->currentCurrencyPrice($combo_product->price ?? 0);
                        $combo_product->special_price = app(CurrencyService::class)->currentCurrencyPrice($combo_product->special_price ?? 0);

                        $combo_products[$key] = $combo_product;
                    }
                }
            }

            $valid_compare_items = [];
            foreach ($products as $product) {
                $valid_compare_items[] = [
                    'product_id' => (string) ($product->id ?? ''),
                    'product_type' => 'regular',
                ];
            }

            foreach ($combo_products as $combo_product) {
                $valid_compare_items[] = [
                    'product_id' => (string) ($combo_product->id ?? ''),
                    'product_type' => 'combo',
                ];
            }

            return response()->json([
                'error' => false,
                'message' => 'Compare Product Added Successfully',
                'data' => [
                    'regular_product' => $products,
                    'combo_products' => $combo_products,
                    'valid_compare_items' => $valid_compare_items,
                ],
            ], 200);
        } catch (\Throwable $e) {
            \Log::error('Compare error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'error' => true,
                'message' => 'Server error: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }
}
