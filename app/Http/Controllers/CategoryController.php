<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\TranslationService;
use App\Services\MediaService;
class CategoryController extends Controller
{

    public function getCategories($id = null, $limit = '', $offset = '', $sort = 'row_order', $order = 'ASC', $has_child_or_item = 'true', $slug = '', $ignore_status = '', $seller_id = '', $store_id = '')
    {
        $query = Category::with(['children' => function ($query) use ($has_child_or_item) {
            if ($has_child_or_item == 'false') {
                $query->withCount('products')
                    ->withCount('children')
                    ->havingRaw('(products_count > 0 OR children_count > 0)');
            } else {
                $query->with('children');
            }
        }]);

        if ($ignore_status == 1) {
            $query->where(function ($q) use ($id) {
                $q->whereNull('parent_id')
                    ->orWhere('parent_id', 0)
                    ->orWhere('id', $id);
            });
        } else {
            $query->where(function ($q) use ($id) {
                $q->where('status', 1)
                    ->whereNull('parent_id')
                    ->orWhere('status', 1)
                    ->where('parent_id', 0)
                    ->orWhere('id', $id)
                    ->where('status', 1);
            });
        }

        if (!empty($slug)) {
            $query->where('slug', $slug);
        }

        if (!empty($store_id)) {
            $query->where('store_id', $store_id);
        }

        if (!empty($limit) || !empty($offset)) {
            $query->offset($offset)->limit($limit);
        }

        $query->orderBy($sort, $order);

        $categories = $query->get();
        $language_code = app(TranslationService::class)->getLanguageCode();

        // Batch product counts in one GROUP BY query instead of one COUNT per category.
        $categoryIds = $categories->pluck('id')->all();
        $productCounts = !empty($categoryIds)
            ? Product::whereIn('category_id', $categoryIds)
                ->where('status', 1)
                ->selectRaw('category_id, COUNT(*) as cnt')
                ->groupBy('category_id')
                ->pluck('cnt', 'category_id')
            : collect();

        foreach ($categories as $category) {
            $category->product_count = (int) ($productCounts[$category->id] ?? 0);
            // Decode translation directly from the already-loaded `name` JSON column
            // instead of running another Model::find via TranslationService.
            $translations = json_decode($category->name, true);
            $category->name = is_array($translations)
                ? ($translations[$language_code] ?? ($translations['en'] ?? $category->name))
                : $category->name;
        }

        $countRes = Category::where(function ($q) use ($id, $ignore_status) {
            if ($ignore_status == 1) {
                $q->whereNull('parent_id')
                    ->orWhere('parent_id', 0)
                    ->orWhere('id', $id);
            } else {
                $q->where('status', 1)
                    ->whereNull('parent_id')
                    ->orWhere('status', 1)
                    ->where('parent_id', 0)
                    ->orWhere('id', $id)
                    ->where('status', 1);
            }
        })->count();

        $categories = $this->formatCategories($categories);

        if (!empty($categories)) {
            $categories[0]['total'] = $countRes;
        }

        return response()->json(compact('categories', 'countRes'));
    }


    private function formatCategories($categories, $level = 0)
    {
        $formattedCategories = [];
        $language_code = app(TranslationService::class)->getLanguageCode();
        foreach ($categories as $category) {
            $category['text'] = e($category['name']);
            $category['name'] = app(TranslationService::class)->getDynamicTranslation(Category::class, 'name', $category->id, $language_code);
            $category['state'] = ['opened' => true];
            $category['icon'] = "jstree-folder";
            $category['level'] = $level;
            $category['image'] = app(MediaService::class)->getMediaImageUrl($category['image']);
            $category['banner'] = app(MediaService::class)->getMediaImageUrl($category['banner']);

            if (!empty($category['children'])) {
                $category['children'] = $this->formatCategories($category['children'], $level + 1);
            }

            $formattedCategories[] = $category;
        }

        return $formattedCategories;
    }
}
