<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute_values;
use App\Models\Category;
use App\Models\ComboProduct;
use App\Models\Product_variants;
use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\HandlesValidation;
use App\Services\ProductService;
use App\Services\ComboProductService;
use App\Services\StoreService;
use App\Services\MediaService;

class ManageStockController extends Controller
{
    use HandlesValidation;
    public function index()
    {
        $fetched_data = [];
        $fetched = [];
        $attribute = [];
        if (request()->has('edit_id')) {
            $store_id = app(StoreService::class)->getStoreId();
            $editId = request('edit_id');

            $stock = Product_variants::select('stock', 'product_id', 'attribute_value_ids')
                ->where('id', $editId)
                ->first();

            if ($stock) {
                $attributeValue = Attribute_values::select('value')
                    ->where('id', $stock->attribute_value_ids)
                    ->first();

                $productId = $stock->product_id;

                $fetched_data = app(ProductService::class)->fetchProduct("", "", $productId, "", "", "", "", "", "", "", "", '', $store_id);
                $fetched = $stock->stock;
                $attribute = isset($attributeValue->value) ? $attributeValue->value : '';
            }
        }

        $categories = Category::all();

        $sellers = User::select('users.username as seller_name', 'users.id as seller_id', 'seller_store.category_ids', 'seller_data.id as seller_data_id')
            ->join('seller_data', 'seller_data.user_id', '=', 'users.id')
            ->join('seller_store', 'seller_data.user_id', '=', 'users.id')
            ->where('users.role_id', 4)
            ->get();


        if (request()->ajax()) {
            return response()->json(['fetched_data' => $fetched_data, 'fetched' => $fetched, 'attribute' => $attribute]);
        }
        return view('admin.pages.tables.manage_stock', compact('fetched_data', 'fetched', 'attribute', 'categories', 'sellers'));
    }
    public function manage_combo_stock()
    {

        $categories = Category::all();

        $sellers = User::select('users.username as seller_name', 'users.id as seller_id', 'seller_store.category_ids', 'seller_data.id as seller_data_id')
            ->join('seller_data', 'seller_data.user_id', '=', 'users.id')
            ->join('seller_store', 'seller_data.user_id', '=', 'users.id')
            ->where('users.role_id', 4)
            ->get();


        return view('admin.pages.tables.manage_combo_stock', compact('categories', 'sellers'));
    }
    public function list()
    {
        $store_id = app(StoreService::class)->getStoreId();

        $filters['show_only_stock_product'] = true;
        $filters['search'] = request()->query('search');
        if (is_numeric($filters['search'])) {
            $filters['product_variant_ids'] = [(int)$filters['search']];
        }

        $sort = (request('sort')) ? request('sort') : "id";
        $order = (request('order')) ? request('order') : "DESC";
        $offset = $filters['search'] || (request('pagination_offset')) ? (request('pagination_offset')) : 0;

        $limit = (request('limit')) ? request('limit') : "25";
        $category_id = request()->query('category_id');
        $seller_id = request()->query('seller_id');


        $products = app(ProductService::class)->fetchProduct("", $filters, "", $category_id, $limit, $offset, $sort, $order, "", "", $seller_id, '', $store_id);
        //dd($products);
        $total = $products['total'];
        $bulkData = $rows = [];

        $bulkData['total'] = $total;

        foreach ($products['product'] as $product) {

            $category_id = $product['category_id'];
            $category_name = fetchDetails(Category::class, ['id' => $category_id], ['name', 'id']);

            $variants = app(ProductService::class)->getVariantsValuesByPid($product['id']);


            // Handle the case when the stock type is 2 (multiple variants)
            if ($product['stock_type'] == 2) {
                foreach ($variants as $variant) {
                    $tempRow = createRow($product, $variant, $category_name);
                    $rows[] = $tempRow;
                }
            } else {

                // Handle the case when the stock type is 0 or 1
                $variant = reset($variants); // Assuming there is at least one variant

                $tempRow = createRow($product, $variant, $category_name);
                $rows[] = $tempRow;
            }
        }

        $pagedRows = array_slice($rows, $offset, $limit);
        $bulkData['rows'] = $pagedRows;
        $bulkData['total'] = count($rows);

        return response()->json($bulkData);
    }
    public function combo_stock_list()
    {
        $store_id = app(StoreService::class)->getStoreId();
        $filters['show_only_stock_product'] = true;
        $filters['search'] = request()->query('search');
        $search = $filters['search'];

        $sort = request('sort', 'id');
        $order = request('order', 'ASC');
        $offset = request('pagination_offset', 0);
        $limit = request('limit', 10);
        $seller_id = request('seller_id');

        // If numeric search, fetch directly by product ID
        if (is_numeric($search)) {
            $query = \DB::table('combo_products as p')
                ->where('p.id', (int)$search)
                ->when($store_id, fn($q) => $q->where('p.store_id', $store_id))
                ->select('p.*')
                ->orderBy($sort, $order)
                ->limit($limit)
                ->offset($offset)
                ->get();

            $total = $query->count();
            $combo_products = $query;
        } else {
            // Default fetchComboProduct logic
            $products = app(ComboProductService::class)->fetchComboProduct(
                "",
                $filters,
                "",
                $limit,
                $offset,
                $sort,
                $order,
                "",
                "",
                $seller_id,
                $store_id
            );
            $total = $products['total'] ?? 0;
            $combo_products = $products['combo_product'] ?? [];
        }

        $bulkData = ['total' => $total, 'rows' => []];

        foreach ($combo_products as $product) {
            // Decode multilingual title
            $decodedTitle = json_decode($product->title, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($decodedTitle['en'])) {
                $title = $decodedTitle['en'];
            } else {
                $title = $product->title;
            }

            // Prepare product image
            $imagePath = $product->image ?? '';
            $product_image = route('admin.dynamic_image', [
                'url' => $imagePath,
                'width' => 60,
                'quality' => 90
            ]);

            $stock_status = $product->availability == 1
                ? '<label class="badge bg-success">In Stock</label>'
                : '<label class="badge bg-danger">Out of Stock</label>';

            $action = '
        <div class="d-flex align-items-center">
            <a href="#" class="btn edit-combo-stock single_action_button"
               title="Edit" data-id="' . $product->id . '"
               data-bs-toggle="modal" data-bs-target="#edit_modal">
                <i class="bx bx-pencil mx-2"></i>
            </a>
        </div>';

            $tempRow = [
                'id' => $product->id,
                'price' => $product->special_price . ' <strike>' . $product->price . '</strike>',
                'stock_count' => $product->stock,
                'stock_status' => $stock_status,
                'name' => '
            <div class="d-flex align-items-center">
                <a href="' . app(MediaService::class)->getMediaImageUrl($product_image) . '" data-lightbox="image-' . $product->id . '">
                    <img src=' . $product_image . ' class="rounded mx-2">
                </a>
                <div class="ms-2"><p class="m-0">' . e($title) . '</p></div>
            </div>',
                'operate' => $action
            ];

            $bulkData['rows'][] = $tempRow;
        }

        return response()->json($bulkData);
    }

    public function edit($id)
    {
        // dd($id);
        $store_id = app(StoreService::class)->getStoreId();

        $stock = Product_variants::select('stock', 'product_id', 'attribute_value_ids')
            ->where('id', $id)
            ->first();
        $offset = request()->query('offset', 0);
        $limit = request()->query('limit', 1);
        if ($stock) {
            $attributeValue = Attribute_values::select('value')
                ->where('id', $stock->attribute_value_ids)
                ->first();

            $productId = $stock->product_id;

            $fetched_data = app(ProductService::class)->fetchProduct("", "", $productId, "", $limit, $offset, "", "", "", "", "", '', $store_id);
            // dd($fetched_data['product'][0]['stock']);
            $fetched = $stock->stock;
            $attribute = isset($attributeValue->value) ? $attributeValue->value : '';
        }

        $variant = Product_variants::find($id);

        $attributeValue = isset($attribute) && !empty($attribute) ? $attribute : "";
        $productName = isset($fetched_data['product'][0]['name']) && !empty($fetched_data['product'][0]['name']) ? $fetched_data['product'][0]['name'] : '';

        $stockType = isset($fetched_data['product'][0]['stock_type']) && $fetched_data['product'][0]['stock_type'] != 1 ? $fetched_data['product'][0]['name'] : '';
        $pro_image = isset($fetched_data['product'][0]['image']) && !empty($fetched_data['product'][0]['image']) ? $fetched_data['product'][0]['image'] : '';


        $pname = $attributeValue && $stockType ? $productName . " - " . $attributeValue : $productName;


        $stock = isset($fetched_data['product'][0]['stock']) && $fetched_data['product'][0]['stock'] != '' ? $fetched_data['product'][0]['stock'] : $fetched;

        if (!$variant) {
            return response()->json(['error' => true, 'message' => 'Data not found'], 404);
        }

        $data = [
            'product_name' => $pname,
            'stock' => $stock,
            'variant' => $variant,
            'pro_image' => $pro_image,

        ];

        // dd($data);

        return response()->json($data);
    }


    // public function edit($id)
    // {
    //     $store_id = app(StoreService::class)->getStoreId();

    //     $variant = Product_variants::find($id);

    //     if (!$variant) {
    //         return response()->json(['error' => true, 'message' => 'Data not found'], 404);
    //     }

    //     $stock = $variant->stock ?? 0;
    //     $productId = $variant->product_id;

    //     $fetched_data = app(ProductService::class)->fetchProduct("", "", $productId, "", 1, 0, "", "", "", "", "", '', $store_id);

    //     $productName = $fetched_data['product'][0]->name ?? '';
    //     $pro_image = $fetched_data['product'][0]->image ?? '';

    //     // Attribute
    //     $attribute = '';
    //     if (!empty($variant->attribute_value_ids)) {
    //         $attributeValue = Attribute_values::select('value')
    //             ->where('id', $variant->attribute_value_ids)
    //             ->first();

    //         $attribute = $attributeValue->value ?? '';
    //     }

    //     $pname = $attribute ? $productName . " - " . $attribute : $productName;
        
    //     return response()->json([
    //         'product_name' => $pname,
    //         'stock' => $stock,
    //         'variant_id' => $variant->id,
    //         'pro_image' => $pro_image,
    //     ]);
    // }

    public function combo_stock_edit($id)
    {
        $product = ComboProduct::find($id);

        if (!$product) {
            return response()->json(['error' => true, 'message' => labels('admin_labels.data_not_found', 'Data Not Found')], 404);
        }

        return response()->json($product);
    }



    public function update(Request $request, $id)
    {

        $variant = Product_variants::find($id);
        if (!$variant) {
            return response()->json(['error' => true, 'message' => labels('admin_labels.data_not_found', 'Data Not Found')], 404);
        } else {
            $rules = [
                'stock' => 'required',
                'quantity' => 'required',
                'type' => 'required',
            ];

            if ($response = $this->HandlesValidation($request, $rules)) {
                return $response;
            }
            if ($request->type == 'add') {

                app(ProductService::class)->updateStock($id, (int)$request->quantity, 'plus');
                if ($request->ajax()) {
                    return response()->json([
                        'message' => labels('admin_labels.stock_updated_successfully', 'Stock updated successfully')
                    ]);
                }
            } else {
                if ($request->type == 'subtract') {
                    if (
                        $request->quantity > $request->stock
                    ) {
                        return response()->json([
                            'error_message' => labels('admin_labels.subtracted_stock_greater_than_current_stock', 'Subtracted stock cannot be greater than current stock')
                        ]);
                    }
                }
                app(ProductService::class)->updateStock($id, $request->quantity);
                if ($request->ajax()) {
                    return response()->json([
                        'message' => labels('admin_labels.stock_updated_successfully', 'Stock updated successfully')
                    ]);
                }
            }
        }
    }
    public function combo_stock_update(Request $request, $id)
    {

        $product = ComboProduct::find($id);

        if (!$product) {
            return response()->json(['error' => true, 'message' => labels('admin_labels.data_not_found', 'Data Not Found')], 404);
        } else {
            $rules = [
                'stock' => 'required',
                'quantity' => 'required',
                'type' => 'required',
            ];

            if ($response = $this->HandlesValidation($request, $rules)) {
                return $response;
            }
            if ($request->type == 'add') {

                app(ComboProductService::class)->updateComboStock($id, $request->quantity, 'add');
                if ($request->ajax()) {
                    return response()->json([
                        'message' => labels('admin_labels.stock_updated_successfully', 'Stock updated successfully')
                    ]);
                }
            } else {
                if ($request->type == 'subtract') {
                    if (
                        $request->quantity > $request->stock
                    ) {
                        return response()->json(['error_message' => labels('admin_labels.subtracted_stock_greater_than_current_stock', 'Subtracted stock cannot be greater than current stock')]);
                    }
                }
                app(ComboProductService::class)->updateComboStock($id, $request->quantity, 'subtract');
                if ($request->ajax()) {
                    return response()->json([
                        'message' => labels('admin_labels.stock_updated_successfully', 'Stock updated successfully')
                    ]);
                }
            }
        }
    }
}
