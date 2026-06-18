<?php

namespace App\Livewire\Compare;

use Illuminate\Http\Request;
use Livewire\Component;
use App\Services\TranslationService;
use App\Services\ProductService;
use App\Services\CurrencyService;
use App\Services\MediaService;
use App\Services\ComboProductService;
use App\Models\Category;
use App\Models\Brand;

class View extends Component
{
    public function render()
    {
        $bread_crumb = [
            'page_main_bread_crumb' => labels('front_messages.compare', 'Compare'),
        ];

        return view('livewire.' . config('constants.theme') . '.compare.view', [
            'bread_crumb' => $bread_crumb
        ])->title("Compare Items |");
    }

    public function add_to_compare(Request $request)
    {
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
                        $products[$key]->image = app(MediaService::class)->dynamic_image($product->image ?? '', 150);
                        $products[$key]->category_name = app(TranslationService::class)
                            ->getDynamicTranslation(Category::class, 'name', $product->category_id ?? null, $language_code);
                        $products[$key]->brand_name = app(TranslationService::class)
                            ->getDynamicTranslation(Brand::class, 'name', $product->brand ?? null, $language_code) ?? '';

                        if (!empty($product->min_max_price)) {
                            $products[$key]->min_max_price['max_price'] =
                                app(CurrencyService::class)->currentCurrencyPrice($product->min_max_price['max_price']);
                            $products[$key]->min_max_price['special_min_price'] =
                                app(CurrencyService::class)->currentCurrencyPrice($product->min_max_price['special_min_price'], true);
                        }
                    }
                }
            }

            $combo_products = [];
            if (count($combo_product_id) >= 1) {
                $comboResult = app(ComboProductService::class)->fetchComboProduct(id: $combo_product_id, store_id: $store_id);

                if (!empty($comboResult['combo_product'])) {
                    $combo_products = $comboResult['combo_product'];

                    foreach ($combo_products as $key => $combo_product) {
                        $combo_products[$key]->image = app(MediaService::class)->dynamic_image($combo_product->image ?? '', 150);
                        $combo_products[$key]->price = app(CurrencyService::class)->currentCurrencyPrice($combo_product->price ?? 0);
                        $combo_products[$key]->special_price = app(CurrencyService::class)->currentCurrencyPrice($combo_product->special_price ?? 0);
                    }
                }
            }

            return response()->json([
                'error' => false,
                'message' => 'Compare Product Added Successfully',
                'data' => [
                    'regular_product' => $products,
                    'combo_products' => $combo_products,
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
