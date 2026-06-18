<?php

namespace App\Http\Controllers\Admin;

use App\Models\Attribute_values;
use App\Models\ComboProduct;
use App\Models\ComboProductAttribute;
use App\Models\ComboProductAttributeValue;
use App\Models\Language;
use App\Models\PickupLocation;
use App\Models\Product;
use App\Models\Product_variants;
use App\Models\ComboProductCustomFieldValue;
use App\Models\StorageType;
use App\Models\Seller;
use App\Models\Store;
use App\Models\Tax;
use App\Models\User;
use App\Models\UserFcm;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Services\TranslationService;
use App\Models\CustomField;
use Illuminate\Support\Facades\Validator;
use App\Services\FirebaseNotificationService;
use App\Services\ProductService;
use App\Services\ComboProductService;
use App\Services\StoreService;
use App\Services\MediaService;
use App\Services\SettingService;
use App\Traits\HandlesValidation;

class ComboProductController extends Controller
{
    use HandlesValidation;
    public function index()
    {
        $store_id = app(StoreService::class)->getStoreId();
        $attributes = ComboProductAttribute::where('store_id', $store_id)->with('attribute_values')->get();
        $languages = Language::all();
        $product_deliverability_type = fetchDetails(Store::class, ['id' => $store_id], 'product_deliverability_type');
        $product_deliverability_type = !$product_deliverability_type->isEmpty() ? $product_deliverability_type[0]->product_deliverability_type : '';

        $store = Store::find($store_id);

        $sellers = $store->sellers()->where('seller_data.status', 1)->get();
        $customFields = CustomField::where('store_id', $store_id)
            ->where('active', 1)
            ->get();
        return view('admin.pages.forms.combo_products', compact('attributes', 'sellers', 'product_deliverability_type', 'languages', 'customFields'));
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $store_id = app(StoreService::class)->getStoreId();

        // --- Unified Validation Rules Setup ---
        $rules = [
            // Core Rules
            'title' => 'required|string',
            'short_description' => 'required|string',
            'image' => 'required',
            'seller_id' => 'required|integer',
            'product_type_in_combo' => 'required|in:physical_product,digital_product',

            // Price Rules (Required and Special Price must be less than Regular Price)
            'simple_price' => 'required|numeric|min:0',
            'simple_special_price' => 'nullable|numeric|min:0|lt:simple_price',

            // Other Data Rules
            'description' => 'nullable|string',
            'weight' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'breadth' => 'nullable|numeric|min:0',
            'tags' => 'nullable', // Handled as JSON later
            'quantity_step_size' => 'nullable|integer|min:1',
            'minimum_order_quantity' => 'nullable|integer|min:1',
            'total_allowed_quantity' => 'nullable|integer|min:1',
            'minimum_free_delivery_order_qty' => 'nullable|numeric|min:0',
            'delivery_charges' => 'nullable|numeric|min:0',

            // Stock Rules (if simple_stock_management_status is 'on')
            'product_sku' => 'nullable|unique:combo_products,sku', // Apply unique rule here
        ];

        // Conditional Rules based on Request Input

        // Stock Management Rules
        if ($request->input('simple_stock_management_status') === 'on') {
            $rules['product_sku'] = 'required|string|unique:combo_products,sku';
            $rules['product_total_stock'] = 'required|numeric|min:0';
        }

        // Product Type Rules
        $product_type_in_combo = $request->input('product_type_in_combo');
        if ($product_type_in_combo === 'physical_product') {
            $rules['physical_product_variant_id'] = 'required|array|min:1';
            $rules['physical_product_variant_id.*'] = 'required|integer|exists:product_variants,id';
        } elseif ($product_type_in_combo === 'digital_product') {
            $rules['digital_product_id'] = 'required|array|min:1';
            $rules['digital_product_id.*'] = 'required|integer|exists:products,id';
        }

        // Custom Fields Validation
        $customFields = CustomField::where('store_id', $store_id)->where('active', 1)->get();

        foreach ($customFields as $field) {
            if ($field->required) {
                $fieldKey = "custom_fields.{$field->id}.0.value";
                $customFieldRules = [];

                switch ($field->type) {
                    case 'number':
                        $customFieldRules = ['required', 'numeric', "min:{$field->min}", "max:{$field->max}"];
                        break;
                    case 'file':
                        $customFieldRules = ['required', 'file'];
                        break;
                    case 'color':
                        $customFieldRules = ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'];
                        break;
                    case 'date':
                        $customFieldRules = ['required', 'date'];
                        break;
                    case 'checkbox':
                        $customFieldRules = ['required', 'array', 'min:1'];
                        break;
                    default:
                        $customFieldRules = ['required', 'string'];
                        break;
                }
                $rules[$fieldKey] = implode('|', $customFieldRules);
            }
        }

        // --- Run Validation (Assuming $this->HandlesValidation exists) ---
        if ($response = $this->HandlesValidation($request, $rules)) {
            return $response;
        }

        // --- Product Data Processing ---

        $product_ids = '';
        $product_variant_ids = '';
        $selected_products = 0;
        $category_ids = '';

        // Determine selected products and get associated categories
        if ($product_type_in_combo === 'digital_product' && isset($request->digital_product_id) && !empty($request->digital_product_id)) {
            $product_ids = implode(',', (array) $request->digital_product_id);
            $selected_products = count($request->digital_product_id);

            $category_ids = Product::whereIn('id', $request->digital_product_id)
                ->pluck('category_id')
                ->unique()
                ->implode(',');
        } elseif ($product_type_in_combo === 'physical_product' && isset($request->physical_product_variant_id) && !empty($request->physical_product_variant_id)) {
            $product_variant_ids = implode(',', (array) $request->physical_product_variant_id);
            $selected_products = count($request->physical_product_variant_id);

            $product_ids_array = Product_variants::whereIn('id', $request->physical_product_variant_id)
                ->pluck('product_id')
                ->unique()
                ->toArray();

            $product_ids = implode(',', $product_ids_array);

            $category_ids = Product::whereIn('id', $product_ids_array)
                ->pluck('category_id')
                ->unique()
                ->implode(',');
        }

        $zones = implode(',', (array) $request->deliverable_zones);
        $attribute = implode(',', (array) $request->attribute_id);
        $attribute_value_ids = implode(',', (array) $request->attribute_value_ids);

        // Tags handling
        $tag_data = isset($request->tags) ? json_decode($request->tags, true) : [];
        $tag_values = array_column($tag_data, 'value');
        $tags = implode(',', $tag_values);

        // Translations merge
        $translations = ['en' => $request->title];
        if (!empty($request['translated_product_title']) && is_array($request['translated_product_title'])) {
            $translations = array_merge($translations, $request['translated_product_title']);
        }
        $translation_descriptions = ['en' => $request->short_description];
        if (!empty($request['translated_product_short_description']) && is_array($request['translated_product_short_description'])) {
            $translation_descriptions = array_merge($translation_descriptions, $request['translated_product_short_description']);
        }

        $product_data = [
            'title' => json_encode($translations, JSON_UNESCAPED_UNICODE),
            'short_description' => json_encode($translation_descriptions, JSON_UNESCAPED_UNICODE),
            'slug' => generateSlug($request->input('title'), 'combo_products', 'slug'),
            'seller_id' => $request->seller_id ?? '',
            'image' => $request->image ?? '',
            'description' => $request->description ?? '',
            'deliverable_type' => $request->deliverable_type ?? '',
            'deliverable_zones' => ($request->deliverable_type == '1' || $request->deliverable_type == '0') ? '' : $zones,
            'pickup_location' => $request->pickup_location ?? '',
            'tax' => (isset($request->pro_input_tax) && !empty($request->pro_input_tax)) ? implode(',', (array) $request->pro_input_tax) : '',
            'weight' => $request->weight ?? 0,
            'height' => $request->height ?? 0,
            'length' => $request->length ?? 0,
            'breadth' => $request->breadth ?? 0,
            'tags' => $tags,
            'selected_products' => $selected_products,
            'price' => $request->simple_price ?? null,
            'special_price' => $request->simple_special_price ?? null,
            'product_type' => $product_type_in_combo,
            'quantity_step_size' => $request->quantity_step_size ?? null,
            'minimum_order_quantity' => $request->minimum_order_quantity ?? null,
            'total_allowed_quantity' => $request->total_allowed_quantity ?? null,
            'product_ids' => $product_ids,
            'category_ids' => $category_ids,
            'product_variant_ids' => $product_variant_ids,
            'status' => 1,
            'store_id' => $store_id,
            'attribute' => $attribute,
            'attribute_value_ids' => $attribute_value_ids,
            'minimum_free_delivery_order_qty' => $request->minimum_free_delivery_order_qty ?? 0,
            'delivery_charges' => $request->delivery_charges ?? 0,
            'other_images' => isset($request->other_images) ? json_encode($request->other_images, JSON_UNESCAPED_UNICODE) : '[]',
        ];

        // Download/Digital Product Fields
        $download_allowed = isset($request->download_allowed) && in_array($request->download_allowed, ["on", '1']);
        $download_link = ($request->download_link_type == 'add_link') ? $request->download_link : $request->pro_input_zip;
        $download_type = $request->download_link_type ?? '';

        $product_data['download_allowed'] = $download_allowed ? '1' : '0';
        $product_data['download_type'] = $download_allowed ? $download_type : '';
        $product_data['download_link'] = $download_allowed ? $download_link : '';

        // Stock
        if ($request->input('simple_stock_management_status') === 'on') {
            $product_data['sku'] = $request->product_sku ?? null;
            $product_data['stock'] = $request->product_total_stock ?? null;
            $product_data['availability'] = $request->simple_product_stock_status;
        }

        // Boolean/Toggle fields
        $is_physical = $product_type_in_combo !== 'digital_product';

        $product_data['cod_allowed'] = ($is_physical && isset($request->cod_allowed) && in_array($request->cod_allowed, ["on", '1'])) ? '1' : '0';
        $product_data['is_prices_inclusive_tax'] = (isset($request->is_prices_inclusive_tax) && in_array($request->is_prices_inclusive_tax, ["on", '1'])) ? '1' : '0';
        $product_data['is_returnable'] = ($is_physical && isset($request->is_returnable) && in_array($request->is_returnable, ["on", '1'])) ? '1' : '0';

        $is_cancelable = ($is_physical && isset($request->is_cancelable) && in_array($request->is_cancelable, ["on", '1']));
        $product_data['is_cancelable'] = $is_cancelable ? '1' : '0';
        $product_data['cancelable_till'] = $is_cancelable ? $request->cancelable_till : '';

        $product_data['is_attachment_required'] = (isset($request->is_attachment_required) && in_array($request->is_attachment_required, ["on", '1'])) ? '1' : '0';
        $product_data['is_in_affiliate'] = (isset($request->is_in_affiliate) && in_array($request->is_in_affiliate, ["on", '1'])) ? '1' : '0';

        $has_similar = ($is_physical && isset($request->has_similar_product) && in_array($request->has_similar_product, ["on", '1']));
        $product_data['has_similar_product'] = $has_similar ? '1' : '0';
        $product_data['similar_product_ids'] = $has_similar ? implode(',', (array) $request->similar_product_id) : '';

        // --- Create Product ---
        $product = ComboProduct::create($product_data);

        // --- Custom Fields Save Logic ---
        if ($product && $request->has('custom_fields')) {
            foreach ($request->custom_fields as $fieldId => $fieldArray) {
                // Your custom field structure seems to iterate over a single item array [0]
                foreach ($fieldArray as $index => $field) {
                    if (!isset($field['value'])) {
                        continue;
                    }

                    $value = $field['value'];

                    // Check for file upload at the expected index (0)
                    if ($request->hasFile("custom_fields.$fieldId.$index.value")) {
                        $file = $request->file("custom_fields.$fieldId.$index.value");

                        $media_storage_settings = fetchDetails(StorageType::class, ['is_default' => 1], '*');
                        $mediaStorageType = !$media_storage_settings->isEmpty() ? $media_storage_settings[0]->id : 1;
                        $disk = !$media_storage_settings->isEmpty() ? $media_storage_settings[0]->name : 'public';
                        $media = StorageType::find($mediaStorageType);

                        $storedMedia = $media->addMedia($file)
                            ->sanitizingFileName(function ($fileName) {
                                $sanitizedFileName = strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));
                                $uniqueId = time() . '_' . mt_rand(1000, 9999);
                                $extension = pathinfo($sanitizedFileName, PATHINFO_EXTENSION);
                                $baseName = pathinfo($sanitizedFileName, PATHINFO_FILENAME);
                                return "{$baseName}-{$uniqueId}.{$extension}";
                            })
                            ->toMediaCollection('custom_field_files', $disk);

                        $value = $storedMedia->file_name;
                    }

                    // Save custom field value
                    ComboProductCustomFieldValue::create(
                        [
                            'product_id' => $product->id,
                            'custom_field_id' => $fieldId,
                            'value' => is_array($value) ? json_encode($value) : $value,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }
        }


        // --- Final Response ---
        if ($product) {
            return response()->json([
                'error' => false,
                'message' => labels('admin_labels.product_added_successfully', 'Product added successfully.'),
                'location' => route('admin.combo_products.manage_product')
            ]);
        }

        // Fallback error response
        return response()->json([
            'error' => true,
            'message' => labels('admin_labels.product_add_failed', 'Failed to add product.'),
        ], 500);
    }

    public function update(Request $request, $data)
    {
        // --- Initial Setup and Retrieval ---
        $product_details = fetchDetails(ComboProduct::class, ['id' => $data], '*');

        if ($product_details->isEmpty()) {
            return response()->json(['error' => true, 'message' => labels('admin_labels.product_not_found', 'Combo Product not found!')], 404);
        }

        $product_details = $product_details[0];
        $store_id = app(StoreService::class)->getStoreId();
        $current_product_type = $request->input('product_type_in_combo') ?? $product_details->product_type;

        // --- Unified Validation Rules Setup ---
        $rules = [
            // Core Rules
            'title' => 'required|string',
            'short_description' => 'required|string',
            'image' => 'required',
            'seller_id' => 'required|integer',
            'product_type_in_combo' => 'nullable|in:physical_product,digital_product',

            // Price Rules
            'simple_price' => 'required|numeric|min:0',
            'simple_special_price' => 'nullable|numeric|min:0|lt:simple_price',

            // Stock Rules
            // Ensure SKU uniqueness, excluding the current product ID for updates
            'product_sku' => 'nullable|string|unique:combo_products,sku,' . $data,

            // Other Data Rules
            'description' => 'nullable|string',
            'weight' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'breadth' => 'nullable|numeric|min:0',
            'quantity_step_size' => 'nullable|integer|min:1',
            'minimum_order_quantity' => 'nullable|integer|min:1',
            'total_allowed_quantity' => 'nullable|integer|min:1',
            'minimum_free_delivery_order_qty' => 'nullable|numeric|min:0',
            'delivery_charges' => 'nullable|numeric|min:0',
        ];

        // Conditional Rules based on Request Input

        // Stock Management Rules
        if ($request->input('simple_stock_management_status') === 'on' || $request->input('simple_stock_management_status') === '1') {
            $rules['product_sku'] = 'required|string|unique:combo_products,sku,' . $data;
            $rules['product_total_stock'] = 'required|numeric|min:0';
        }

        // Product Type Rules
        if ($current_product_type === 'physical_product') {
            $rules['physical_product_variant_id'] = 'required|array|min:1';
            $rules['physical_product_variant_id.*'] = 'required|integer|exists:product_variants,id';
        } elseif ($current_product_type === 'digital_product') {
            $rules['digital_product_id'] = 'required|array|min:1';
            $rules['digital_product_id.*'] = 'required|integer|exists:products,id';
        }

        // Custom Fields Validation Logic
        $customFields = CustomField::where('store_id', $store_id)->where('active', 1)->get();

        $fieldValues = ComboProductCustomFieldValue::whereIn('custom_field_id', $customFields->pluck('id'))
            ->where('product_id', $data)
            ->get()
            ->keyBy('custom_field_id');

        foreach ($customFields as $field) {
            if ($field->required) {
                $fieldKey = "custom_fields.{$field->id}.0.value";
                $existingValue = $fieldValues->get($field->id)->value ?? null;
                $customFieldRules = [];

                switch ($field->type) {
                    case 'number':
                        $customFieldRules = ['required', 'numeric', "min:{$field->min}", "max:{$field->max}"];
                        break;
                    case 'file':
                        // If no existing value AND no new file input, make it required.
                        if (!$existingValue) {
                            $customFieldRules[] = 'required';
                        }
                        $customFieldRules[] = 'file';
                        break;
                    case 'color':
                        $customFieldRules = ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'];
                        break;
                    case 'date':
                        $customFieldRules = ['required', 'date'];
                        break;
                    case 'checkbox':
                        $customFieldRules = ['required', 'array', 'min:1'];
                        break;
                    default:
                        $customFieldRules = ['required', 'string'];
                        break;
                }
                $rules[$fieldKey] = implode('|', $customFieldRules);
            }
        }

        // --- Run Validation (Assuming $this->HandlesValidation exists) ---
        if ($response = $this->HandlesValidation($request, $rules)) {
            return $response;
        }

        // --- Product Data Processing ---

        $zones = implode(',', (array) $request->deliverable_zones);
        $attribute = implode(',', (array) $request->attribute_id);
        $attribute_value_ids = implode(',', (array) $request->attribute_value_ids);

        // Tags handling
        $tag_data = isset($request->tags) ? json_decode($request->tags, true) : [];
        $tag_values = array_column($tag_data, 'value');
        $tags = implode(',', $tag_values);

        $new_name = $request->title;
        $current_name = json_decode($product_details->title, true)['en'] ?? $product_details->title;
        $current_slug = $product_details->slug;

        // Translations merge
        $translations = json_decode($product_details->title, true) ?? [];
        $translation_descriptions = json_decode($product_details->short_description, true) ?? [];

        $translations['en'] = $request->title;
        $translation_descriptions['en'] = $request->short_description;

        if (!empty($request->translated_product_title) && is_array($request->translated_product_title)) {
            $translations = array_merge($translations, $request->translated_product_title);
        }
        if (!empty($request->translated_product_short_description) && is_array($request->translated_product_short_description)) {
            $translation_descriptions = array_merge($translation_descriptions, $request->translated_product_short_description);
        }

        $product_ids = '';
        $product_variant_ids = '';
        $selected_products = 0;
        $category_ids = '';

        // Determine selected products and get associated categories
        if ($current_product_type === 'digital_product' && isset($request->digital_product_id) && !empty($request->digital_product_id)) {
            $product_ids = implode(',', (array) $request->digital_product_id);
            $selected_products = count($request->digital_product_id);

            $category_ids = Product::whereIn('id', $request->digital_product_id)
                ->pluck('category_id')
                ->unique()
                ->implode(',');
        } elseif ($current_product_type === 'physical_product' && isset($request->physical_product_variant_id) && !empty($request->physical_product_variant_id)) {
            $product_variant_ids = implode(',', (array) $request->physical_product_variant_id);
            $selected_products = count($request->physical_product_variant_id);

            $product_ids_array = Product_variants::whereIn('id', $request->physical_product_variant_id)
                ->pluck('product_id')
                ->unique()
                ->toArray();

            $product_ids = implode(',', $product_ids_array);

            $category_ids = Product::whereIn('id', $product_ids_array)
                ->pluck('category_id')
                ->unique()
                ->implode(',');
        }

        $update_data = [
            'title' => json_encode($translations, JSON_UNESCAPED_UNICODE),
            'short_description' => json_encode($translation_descriptions, JSON_UNESCAPED_UNICODE),
            'slug' => generateSlug($new_name, 'combo_products', 'slug', $current_slug, $current_name),
            'seller_id' => $request->seller_id ?? '',
            'image' => $request->image ?? '',
            'description' => $request->description ?? '',
            'deliverable_type' => $request->deliverable_type ?? '',
            'deliverable_zones' => ($request->deliverable_type == '1' || $request->deliverable_type == '0') ? '' : $zones,
            'pickup_location' => $request->pickup_location ?? '',
            'tax' => (isset($request->pro_input_tax) && !empty($request->pro_input_tax)) ? implode(',', (array) $request->pro_input_tax) : '',
            'weight' => $request->weight ?? 0,
            'height' => $request->height ?? 0,
            'length' => $request->length ?? 0,
            'breadth' => $request->breadth ?? 0,
            'tags' => $tags,
            'selected_products' => $selected_products,
            'price' => $request->simple_price ?? null,
            'special_price' => $request->simple_special_price ?? null,
            'product_type' => $current_product_type,
            'quantity_step_size' => $request->quantity_step_size ?? null,
            'minimum_order_quantity' => $request->minimum_order_quantity ?? null,
            'total_allowed_quantity' => $request->total_allowed_quantity ?? null,
            'minimum_free_delivery_order_qty' => $request->minimum_free_delivery_order_qty ?? 0,
            'delivery_charges' => $request->delivery_charges ?? 0,
            'product_ids' => $product_ids,
            'category_ids' => $category_ids,
            'product_variant_ids' => $product_variant_ids,
            'status' => $request->status ?? 1,
            'store_id' => $store_id,
            'attribute' => $attribute,
            'attribute_value_ids' => $attribute_value_ids,
            'other_images' => isset($request->other_images) ? json_encode($request->other_images, JSON_UNESCAPED_UNICODE) : '[]',
        ];

        // Stock Management Fields
        $update_data['sku'] = null;
        $update_data['stock'] = null;
        $update_data['availability'] = null;

        if ($request->input('simple_stock_management_status') === 'on' || $request->input('simple_stock_management_status') === '1') {
            $update_data['sku'] = $request->product_sku ?? null;
            $update_data['stock'] = $request->product_total_stock ?? null;
            $update_data['availability'] = $request->simple_product_stock_status;
        }

        // Boolean/Toggle fields
        $is_physical = $current_product_type !== 'digital_product';

        $update_data['cod_allowed'] = ($is_physical && isset($request->cod_allowed) && in_array($request->cod_allowed, ["on", '1'])) ? '1' : '0';
        $update_data['is_prices_inclusive_tax'] = (isset($request->is_prices_inclusive_tax) && in_array($request->is_prices_inclusive_tax, ["on", '1'])) ? '1' : '0';
        $update_data['is_returnable'] = ($is_physical && isset($request->is_returnable) && in_array($request->is_returnable, ["on", '1'])) ? '1' : '0';

        $is_cancelable = ($is_physical && isset($request->is_cancelable) && in_array($request->is_cancelable, ["on", '1']));
        $update_data['is_cancelable'] = $is_cancelable ? '1' : '0';
        $update_data['cancelable_till'] = $is_cancelable ? $request->cancelable_till : '';

        $update_data['is_attachment_required'] = (isset($request->is_attachment_required) && in_array($request->is_attachment_required, ["on", '1'])) ? '1' : '0';
        $update_data['is_in_affiliate'] = (isset($request->is_in_affiliate) && in_array($request->is_in_affiliate, ["on", '1'])) ? '1' : '0';

        $has_similar = ($is_physical && isset($request->has_similar_product) && in_array($request->has_similar_product, ["on", '1']));
        $update_data['has_similar_product'] = $has_similar ? '1' : '0';
        $update_data['similar_product_ids'] = $has_similar ? implode(',', (array) $request->similar_product_id) : '';

        // --- Product Update ---
        $product = ComboProduct::where('id', $data)->update($update_data);

        // --- Custom Fields Update Logic ---
        if ($request->has('custom_fields')) {
            $submittedFieldIds = array_keys($request->custom_fields);

            // Step 1: Get existing field IDs for this product
            $existingFieldIds = ComboProductCustomFieldValue::where('product_id', $data)->pluck('custom_field_id')->toArray();

            // Step 2 & 3: Delete removed field values
            $fieldsToDelete = array_diff($existingFieldIds, $submittedFieldIds);
            if (!empty($fieldsToDelete)) {
                ComboProductCustomFieldValue::where('product_id', $data)->whereIn('custom_field_id', $fieldsToDelete)->delete();
            }

            // Step 4: Save new or update existing field values
            foreach ($request->custom_fields as $fieldId => $fieldArray) {
                foreach ($fieldArray as $index => $field) {
                    if (!isset($field['value'])) {
                        continue;
                    }

                    $value = $field['value'];

                    // Check for file upload
                    if ($request->hasFile("custom_fields.$fieldId.$index.value")) {
                        $file = $request->file("custom_fields.$fieldId.$index.value");

                        $media_storage_settings = fetchDetails(StorageType::class, ['is_default' => 1], '*');
                        $mediaStorageType = !$media_storage_settings->isEmpty() ? $media_storage_settings[0]->id : 1;
                        $disk = !$media_storage_settings->isEmpty() ? $media_storage_settings[0]->name : 'public';
                        $media = StorageType::find($mediaStorageType);

                        $storedMedia = $media->addMedia($file)
                            ->sanitizingFileName(function ($fileName) {
                                $sanitizedFileName = strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));
                                $uniqueId = time() . '_' . mt_rand(1000, 9999);
                                $extension = pathinfo($sanitizedFileName, PATHINFO_EXTENSION);
                                $baseName = pathinfo($sanitizedFileName, PATHINFO_FILENAME);
                                return "{$baseName}-{$uniqueId}.{$extension}";
                            })
                            ->toMediaCollection('custom_field_files', $disk);

                        $value = $storedMedia->file_name;
                    } elseif (isset($field['old_value']) && !$request->hasFile("custom_fields.$fieldId.$index.value")) {
                        // Retain old value if no new file is uploaded
                        $value = $field['old_value'];
                    }

                    // Save or update field value
                    ComboProductCustomFieldValue::updateOrInsert(
                        [
                            'product_id' => $data,
                            'custom_field_id' => $fieldId,
                        ],
                        [
                            'value' => is_array($value) ? json_encode($value) : $value,
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                }
            }
        }


        // --- Final Response ---
        if ($product) {
            return response()->json([
                'error' => false,
                'message' => labels('admin_labels.product_updated_successfully', 'Product updated successfully.'),
                'location' => route('admin.combo_products.manage_product')
            ]);
        }

        // Fallback error response
        return response()->json([
            'error' => true,
            'message' => labels('admin_labels.product_update_failed', 'Failed to update product.'),
        ], 500);
    }


    public function manageProduct()
    {
        return view('admin.pages.tables.manage_combo_products');
    }

    public function list()
    {
        $store_id = app(StoreService::class)->getStoreId();
        $search = trim(request('search'));
        $sort = (request('sort')) ? request('sort') : "id";
        $order = (request('order')) ? request('order') : "DESC";
        $limit = request("limit");
        $offset = $search || (request('pagination_offset')) ? (request('pagination_offset')) : 0;
        $settings = app(SettingService::class)->getSettings('system_settings', true);
        $settings = json_decode($settings, true);
        $low_stock_limit = isset($settings['low_stock_limit']) ? $settings['low_stock_limit'] : 5;

        $query = ComboProduct::query();
        $query->select('combo_products.*', 'seller_store.store_name', 'combo_products.id as pid', 'combo_products.title', 'combo_products.product_type', 'combo_products.image', 'combo_products.status', 'combo_products.price', 'combo_products.special_price', 'combo_products.stock')
            ->join('seller_store', 'seller_store.seller_id', '=', 'combo_products.seller_id',)
            ->where('combo_products.store_id', $store_id);

        $language_code = app(TranslationService::class)->getLanguageCode();
        if (request()->filled('search')) {
            $search = trim(request('search'));
            $query->where(function ($q) use ($search, $language_code) {
                $q->where('combo_products.id', (string) $search)
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(combo_products.title, '$.\"$language_code\"')) LIKE ?", ["%$search%"])
                    ->orWhere('combo_products.description', 'LIKE', "%$search%")
                    ->orWhere('combo_products.short_description', 'LIKE', "%$search%");
            });
        }

        if (request()->has('flag') && request('flag') === 'low') {
            $query->where(function ($q) use ($low_stock_limit) {
                $q->whereNotNull('combo_products.stock_type')
                    ->where('combo_products.stock', '<=', $low_stock_limit)
                    ->where('combo_products.availability', '=', 1)
                    ->orWhere('combo_products.stock', '<=', $low_stock_limit)
                    ->where('combo_products.availability', '=', 1);
            });
        }

        if (request()->filled('seller_id')) {
            $query->where('combo_products.seller_id', request('seller_id'));
        }

        if (request()->filled('status')) {
            $query->where('combo_products.status', request('status'));
        }

        if (request()->has('flag') && request('flag') === 'sold') {
            $query->where(function ($q) {
                $q->whereNotNull('combo_products.stock_type')
                    ->where('combo_products.stock', '=', 0)
                    ->where('combo_products.availability', '=', 0)
                    ->orWhere('combo_products.stock', '=', 0)
                    ->where('combo_products.availability', '=', 0);
            });
        }

        $total = $query->distinct('combo_products.id')->count('pid');

        $combo_products = $query->groupBy('pid')
            ->orderBy($sort, $order)
            ->skip($offset)
            ->take($limit)
            ->get();

        $combo_products = $combo_products->map(function ($p) use ($language_code) {

            $store_id = app(StoreService::class)->getStoreId();
            $edit_url = route('admin.combo_products.edit', $p->pid);
            $delete_url = route('admin.combo_products.destroy', $p->pid);


            $show_url = route('admin.combo_products.show', $p->id);

            $action = '<div class="dropdown bootstrap-table-dropdown">
                    <a href="#" class="text-dark" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="bx bx-dots-horizontal-rounded"></i>
                        </a>
                        <div class="dropdown-menu table_dropdown combo_product_action_dropdown" aria-labelledby="dropdownMenuButton">
                            <a class="dropdown-item dropdown_menu_items" href="' . $edit_url . '"><i class="bx bx-pencil mx-2"></i> ' . labels('admin_labels.edit', 'Edit') . '</a>
                            <a class="dropdown-item delete-data dropdown_menu_items" data-url="' . $delete_url . '"><i class="bx bx-trash mx-2"></i> ' . labels('admin_labels.delete', 'Delete') . '</a>
                            <a class="dropdown-item dropdown_menu_items" href="' . $show_url . '"><i class="bx bxs-show mx-2"></i>View</a>
                        </div>
                    </div>';

            $product_ids = explode(',', $p->product_ids);

            $product_names = array_map(function ($product_id) use ($language_code) {
                return app(TranslationService::class)->getDynamicTranslation(Product::class, 'name', $product_id, $language_code);
            }, $product_ids);
            $image = route('admin.dynamic_image', [
                'url' => app(MediaService::class)->getMediaImageUrl($p->image),
                'width' => 60,
                'quality' => 90
            ]);
            return [
                'id' => $p->id,
                'title' => app(TranslationService::class)->getDynamicTranslation(ComboProduct::class, 'title', $p->id, $language_code) . '<br><small>' . ucwords(str_replace('_', ' ', $p->product_type)) . '</small><br><small> By </small><b>' . ($p->store_name) ?? '' . '</b>',
                'products' => $product_names,
                'image' => '<div class="d-flex align-items-center"><a href="' . app(MediaService::class)->getMediaImageUrl($p->image) . '" data-lightbox="image-' . $p->id . '"><img src=' . $image . ' class="rounded mx-2"></a></div>',
                'status' => '<select class="form-select status_dropdown change_toggle_status ' .
                    ($p->status == 1 ? 'active_status' : ($p->status == 2 ? 'not_approved_status bg-gray-500' : 'inactive_status')) .
                    '" data-id="' . $p->id . '" data-url="/admin/combo_products/update_status/' . $p->id . '" aria-label="">
                <option value="1" ' . ($p->status == 1 ? 'selected' : '') . '>' . labels('admin_labels.active', 'Active') . '</option>
                <option value="0" ' . ($p->status == 0 ? 'selected' : '') . '>' . labels('admin_labels.deactive', 'Deactive') . '</option>' .
                    ($p->status == 2 ? '<option value="2" ' . ($p->status == 2 ? 'selected' : '') . '>Not Approved</option>' : '') .
                    '</select>',
                'operate' => $action,

            ];
        });

        return response()->json([
            "rows" => $combo_products,
            "total" => $total,
        ]);
    }

    public function destroy($id)
    {
        $product = ComboProduct::find($id);
        if ($product) {
            $product->delete();
            return response()->json([
                'error' => false,
                'message' => labels('admin_labels.product_deleted_successfully', 'Product deleted successfully!')
            ]);
        } else {
            return response()->json(['error' => labels('admin_labels.data_not_found', 'Data Not Found')]);
        }
    }

    public function update_status($id, Request $request)
    {
        $nextStatus = (int) ($request->status ?? 0);
        $combo_product = ComboProduct::findOrFail($id);
        $currentStatus = (int) $combo_product->status;

        $seller_id = Seller::where('id', $combo_product->seller_id)->value('user_id');
        $user = User::where('id', $seller_id)->select('fcm_id', 'username')->first();

        $results = UserFcm::with('user:id,id,is_notification_on')
            ->where('user_id', $seller_id)
            ->whereHas('user', function ($q) {
                $q->where('is_notification_on', 1);
            })
            ->get()
            ->map(function ($fcm) {
                return [
                    'fcm_id' => $fcm->fcm_id,
                    'is_notification_on' => $fcm->user?->is_notification_on,
                ];
            });

        // ---------- STATUS RULES ----------

        // 1. Rejected cannot change again
        if ($currentStatus === 3) {
            return response()->json([
                'status_error' => 'This product is rejected and cannot be changed.'
            ]);
        }

        // 2. From Active(1) or Deactive(0) → cannot move to Rejected(3)
        if (in_array($currentStatus, [0, 1]) && $nextStatus === 3) {
            return response()->json([
                'status_error' => 'Active or deactive products cannot be rejected.'
            ]);
        }

        // 3. Validate allowed statuses
        if (!in_array($nextStatus, [0, 1, 2, 3])) {
            return response()->json([
                'status_error' => 'Invalid status.'
            ]);
        }

        // 4. FK restriction only if moving to inactive or rejected
        if (in_array($nextStatus, [0, 3])) {
            $tables = [Product::class, SellerStore::class];
            $columns = ['combo_id', 'combo_ids']; // adjust if needed for combo relations

            if (isForeignKeyInUse($tables, $columns, $id)) {
                return response()->json([
                    'status_error' => labels(
                        'admin_labels.cannot_deactivate_category_associated_with_products_seller',
                        'You cannot change the status because it is associated with products or sellers.'
                    )
                ]);
            }
        }

        // ---------- APPLY STATUS CHANGE ----------
        if ($currentStatus !== $nextStatus) {
            $combo_product->status = $nextStatus;
            $combo_product->save();

            // ---------- SEND NOTIFICATION -----------
            $fcm_ids = [];
            foreach ($results as $result) {
                $fcm_ids[] = $result['fcm_id'];
            }

            $chunks = array_chunk($fcm_ids, 1000);

            $title = "Product status changed";
            $message = "Hello dear " . $user->username . ", your product status has been changed";

            $fcmMsg = [
                'title' => $title,
                'body' => $message,
                'type' => "combo_product",
                'product_id' => $combo_product->id,
                'store_id' => $combo_product->store_id,
                'status' => $nextStatus
            ];

            app(FirebaseNotificationService::class)->sendNotification(
                '',
                $chunks,
                $fcmMsg
            );
        }

        return response()->json([
            'success' => labels('admin_labels.status_updated_successfully', 'Status updated successfully.')
        ]);
    }


    public function edit($data)
    {
        $store_id = app(StoreService::class)->getStoreId();
        $languages = Language::all();
        $language_code = app(TranslationService::class)->getLanguageCode();
        $data = ComboProduct::where('store_id', $store_id)
            ->find($data);

        if ($data === null || empty($data)) {
            return view('admin.pages.views.no_data_found');
        } else {
            $attributes = ComboProductAttribute::with('attribute_values')->where('store_id', $store_id)->get();

            $store = Store::find($store_id);
            $sellers = $store->sellers()->where('seller_data.status', 1)->where('seller_store.store_id', $store_id)->get();

            $seller_id = fetchDetails(ComboProduct::class, ['id' => $data->id], 'seller_id')[0]->seller_id;

            $productCustomFieldValues = ComboProductCustomFieldValue::where('product_id', $data->id)->get()->groupBy('custom_field_id');
            $customFields = CustomField::where('store_id', $store_id)
                ->where('active', 1)
                ->get();
            // Extract product IDs and variant IDs
            $product_ids = explode(',', $data->product_ids);
            $variant_ids = explode(',', $data->product_variant_ids);

            // Fetch products
            $products = Product::whereIn('id', $product_ids)
                ->where('store_id', $store_id)
                ->get();
            $results = [];

            // Iterate through each product
            foreach ($products as $product) {
                // dd($product);
                // If the product is a variable product
                if ($product->type == 'variable_product') {
                    // Fetch all variants for this product
                    $variants = Product_variants::where('product_id', $product->id)
                        ->whereIn('id', $variant_ids)
                        ->get();

                    foreach ($variants as $variant) {
                        // Get attribute names for the variant
                        $attribute_value_ids = explode(',', $variant->attribute_value_ids);
                        $attribute_names = Attribute_values::whereIn('id', $attribute_value_ids)
                            ->pluck('value')
                            ->toArray();

                        $variant_name = implode(', ', $attribute_names);

                        // Add the variant details to the results
                        $results[] = [
                            'id' => $variant->id,
                            'product_id' => $product->id,
                            'name' => $product->name,
                            'variant_name' => $variant_name,
                        ];
                    }
                } else {
                    // For simple products, fetch the single variant
                    $variant = Product_variants::where('product_id', $product->id)
                        ->first();

                    if ($variant) {
                        // Add the simple product details to the results
                        $results[] = [
                            'id' => $variant->id,
                            'product_id' => $product->id,
                            'name' => $product->name,
                            'variant_name' => '', // No variant name for simple products
                        ];
                    }
                }
            }

            // Convert results to array
            $product_details = $results;

            // dd($product_details);
            $combo_product_details = ComboProduct::whereIn('id', explode(',', $data->similar_product_ids))->where('store_id', $store_id)
                ->get()
                ->toArray();

            $product_deliverability_type = fetchDetails(Store::class, ['id' => $store_id], 'product_deliverability_type');
            $product_deliverability_type = !$product_deliverability_type->isEmpty() ? $product_deliverability_type[0]->product_deliverability_type : '';

            $shipping_data = fetchDetails(PickupLocation::class, ['status' => 1, 'seller_id' => $seller_id], ['id', 'pickup_location']);

            return view('admin.pages.forms.update_combo_product', compact('data', 'attributes', 'sellers', 'shipping_data', 'product_details', 'combo_product_details', 'product_deliverability_type', 'languages', 'language_code', 'customFields', 'productCustomFieldValues'));
        }
    }

    public function fetchAttributesById(request $request)
    {
        $id = $request->edit_id;


        $res['attr_values'] = app(ComboProductService::class)->getComboAttributeValuesByPid($id);


        $response['result'] = $res;
        return $response;
    }

    public function getProductdetails(Request $request)
    {
        $store_id = app(StoreService::class)->getStoreId();
        $search = trim($request->input('search'));
        $limit = (int) $request->input('limit', 2);
        $page = (int) $request->input('page', 1);
        $offset = ($page - 1) * $limit;
        $is_update = (int) $request->input('is_update', 0);
        $product_id = (int) $request->input('product_id', 0);

        // Create the query for fetching products
        $productsQuery = ComboProduct::where('title', 'like', '%' . $search . '%')
            ->where('store_id', $store_id)
            ->where('status', 1);

        // If 'is_update' is 1, exclude the product with the given 'product_id'
        if ($is_update === 1 && $product_id > 0) {
            $productsQuery->where('id', '!=', $product_id);
        }

        // Fetch paginated product results
        $products = $productsQuery->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get(['id', 'title']);
        $language_code = app(TranslationService::class)->getLanguageCode();
        // Count total products matching the query
        $totalCount = $productsQuery->count();

        // Prepare response data
        $response = [
            'total' => $totalCount,
            'results' => $products->map(function ($product) use ($language_code) {
                return [
                    'id' => $product->id,
                    'text' => app(TranslationService::class)->getDynamicTranslation(ComboProduct::class, 'title', $product->id, $language_code),
                ];
            }),
            'pagination' => [
                'more' => ($page * $limit) < $totalCount,
            ],
        ];

        return response()->json($response);
    }


    public function show($id)
    {
        $store_id = app(StoreService::class)->getStoreId();


        $data = ComboProduct::where('store_id', $store_id)
            ->find($id);
        // dd($data);
        if ($data === null || empty($data)) {
            return view('admin.pages.views.no_data_found');
        } else {
            $store = Store::find($store_id);
            $taxes = Tax::where('status', 1)->get();
            $language_code = app(TranslationService::class)->getLanguageCode();
            $seller_id = fetchDetails(ComboProduct::class, ['id' => $data->id], 'seller_id')[0]->seller_id;

            $product_details = Product::whereIn('id', explode(',', $data->product_ids))->where('store_id', $store_id)
                ->get()
                ->toArray();
            $combo_product_details = ComboProduct::whereIn('id', explode(',', $data->similar_product_ids))->where('store_id', $store_id)
                ->get()
                ->toArray();

            $shipping_data = fetchDetails(PickupLocation::class, ['status' => 1, 'seller_id' => $seller_id], ['id', 'pickup_location']);


            $product_faqs = app(ProductService::class)->getProductFaqs('', $data->id);

            $ProductRatingController = new ComboProductRatingController;

            $rating = $ProductRatingController->fetch_rating(($id) ? $id : '', '', 10, 0, 'id', 'desc');
            // dd($data);
            $attributeValueIds = explode(',', $data->attribute_value_ids);

            $attributes = ComboProductAttributeValue::with('attribute')
                ->whereIn('id', $attributeValueIds)
                ->get();

            $product_faqs = app(ComboProductService::class)->getComboProductFaqs('', $data->id);


            return view('admin.pages.views.combo_product', compact('data', 'attributes', 'taxes', 'shipping_data', 'product_faqs', 'product_details', 'combo_product_details', 'rating', 'language_code'));
        }
    }

    public function bulk_upload()
    {
        return view('admin.pages.forms.combo_product_bulk_upload');
    }

    public function process_bulk_upload(Request $request)
    {

        if (!$request->hasFile('upload_file')) {
            return response()->json(['error' => 'true', 'message' => labels('admin_labels.please_choose_file', 'Please Choose File')]);
        }
        $allowed_mime_types = [
            'text/x-comma-separated-values',
            'text/comma-separated-values',
            'application/x-csv',
            'text/x-csv',
            'text/csv',
            'application/csv',
        ];

        $uploaded_file = $request->file('upload_file');
        $uploaded_mime_type = $uploaded_file->getClientMimeType();

        if (!in_array($uploaded_mime_type, $allowed_mime_types)) {
            return response()->json(['error' => 'true', 'message' => labels('admin_labels.invalid_file_format', 'Invalid File Format')]);
        }

        $csv = $_FILES['upload_file']['tmp_name'];
        $temp = 0;
        $temp1 = 0;
        $handle = fopen($csv, "r");
        $allowed_status = array("received", "processed", "shipped");
        $video_types = array("youtube", "vimeo");
        $type = $request->type;

        if ($type == 'upload') {
            while (($row = fgetcsv($handle, 10000, ",")) != FALSE) //get row values
            {


                if ($temp != 0) {
                    if (empty($row[0])) {
                        return response()->json([
                            'error' => 'true',
                            'message' => labels('admin_labels.store_id_empty_at_row', 'Store id is empty at row')
                                . $row[0]
                        ]);
                    }
                    if (empty($row[1])) {
                        return response()->json([
                            'error' => 'true',
                            'message' => labels('admin_labels.seller_id_empty_at_row', 'Seller id is empty at row')
                                . $row[1]
                        ]);
                    }
                    if (empty($row[2])) {
                        return response()->json([
                            'error' => 'true',
                            'message' => labels('admin_labels.title_empty_at_row', 'Title is empty at row')
                                . $row[2]
                        ]);
                    }
                    if ($row[3] != 'physical_product' && $row[3] != 'digital_product') {
                        return response()->json([
                            'error' => 'true',
                            'message' => labels('admin_labels.product_type_invalid_at_row', 'Product type is invalid at row')
                                . $temp
                        ]);
                    }

                    if (empty($row[4])) {
                        return response()->json([
                            'error' => 'true',
                            'message' => labels('admin_labels.short_description_empty_at_row', 'Short Description is empty at row')
                                . $temp
                        ]);
                    }
                    if (empty($row[5])) {
                        return response()->json(['error' => 'true', 'message' => labels('admin_labels.image_is_empty_at_row', 'Image is empty at row') . $temp]);
                    }
                    if (empty($row[8])) {
                        return response()->json([
                            'error' => 'true',
                            'message' => labels('admin_labels.product_ids_empty_at_row', 'Product Ids are empty at row')
                                . $temp
                        ]);
                    }
                    if (empty($row[9])) {
                        return response()->json([
                            'error' => 'true',
                            'message' => labels('admin_labels.selected_products_empty_at_row', 'Selected Products is empty at row')
                                . $temp
                        ]);
                    }

                    if ($row[10] == '1' || $row[11] == '') {
                        if (empty($row[11])) {
                            return response()->json([
                                'error' => 'true',
                                'message' => labels('admin_labels.similar_product_ids_empty_at_row', 'Similar Product Ids are empty at row')
                                    . $temp
                            ]);
                        }
                    }

                    if (empty($row[12])) {
                        return response()->json([
                            'error' => 'true',
                            'message' => labels('admin_labels.price_empty_at_row', 'Price is empty at row')
                                . $temp
                        ]);
                    }
                    if (empty($row[13])) {
                        return response()->json([
                            'error' => 'true',
                            'message' => labels('admin_labels.special_price_empty_at_row', 'Special Price is empty at row')
                                . $temp
                        ]);
                    }

                    if (
                        $row[14] == '2' || $row[14] == '3'
                    ) {
                        if (empty($row[15])) {
                            return response()->json([
                                'error' => 'true',
                                'message' => labels('admin_labels.deliverable_zipcodes_empty_at_row', 'Deliverable Zipcodes is empty at row')
                                    . $temp
                            ]);
                        }
                    }

                    if ($row[14] != 0 && $row[14] != 1 && $row[14] != 2 && $row[14] != 3 && $row[14] == "") {
                        return response()->json([
                            'error' => 'true',
                            'message' => labels('admin_labels.not_valid_value_for_deliverable_type_at_row', 'Not valid value for deliverable type at row')
                                . $temp
                        ]);
                    }

                    if (!empty($row[30]) && $row[30] != 1) {
                        return response()->json([
                            'error' => 'true',
                            'message' => labels('admin_labels.cod_allowed_invalid_at_row', 'COD allowed is invalid at row')
                                . $temp
                        ]);
                    }

                    if (!empty($row[31]) && $row[31] != 1) {
                        return response()->json([
                            'error' => 'true',
                            'message' => labels('admin_labels.prices_inclusive_tax_invalid_at_row', 'Is prices inclusive tax is invalid at row')
                                . $temp
                        ]);
                    }

                    if (!empty($row[32]) && $row[32] != 1) {
                        return response()->json([
                            'error' => 'true',
                            'message' => labels('admin_labels.returnable_invalid_at_row', 'Is Returnable is invalid at row')
                                . $temp
                        ]);
                    }

                    if (!empty($row[33]) && $row[33] != 1) {
                        return response()->json([
                            'error' => 'true',
                            'message' => labels('admin_labels.cancelable_invalid_at_row', 'Is Cancelable is invalid at row')
                                . $temp
                        ]);
                    }

                    if (!empty($row[33]) && $row[33] == 1 && (empty($row[34]) || !in_array($row[34], $allowed_status))) {
                        return response()->json([
                            'error' => 'true',
                            'message' => labels('admin_labels.cancelable_till_invalid_at_row', 'Cancelable till is invalid at row')
                                . $temp
                        ]);
                    }

                    if (empty($row[33]) && !(empty($row[34]))) {
                        return response()->json([
                            'error' => 'true',
                            'message' => labels('admin_labels.cancelable_till_invalid_at_row', 'Cancelable till is invalid at row')
                                . $temp
                        ]);
                    }

                    $seller_id = $row[1];
                }
                $temp++;
            }

            fclose($handle);
            $handle = fopen($csv, "r");
            while (($row = fgetcsv($handle, 10000, ",")) != FALSE) //get row vales
            {


                if ($temp1 != 0) {
                    $data = [
                        'store_id' => !empty($row[0]) ? $row[0] : "",
                        'seller_id' => !empty($row[1]) ? $row[1] : "",
                        'title' => !empty($row[2]) ? $row[2] : "",
                        'slug' => !empty($row[2]) ? generateSlug($row[2], 'combo_products') : "",
                        'product_type' => !empty($row[3]) ? $row[3] : "",
                        'short_description' => !empty($row[4]) ? $row[4] : "",
                        'image' => !empty($row[5]) ? $row[5] : "",
                        'description' => !empty($row[6]) ? $row[6] : "",
                        'tags' => !empty($row[7]) ? $row[7] : "",
                        'product_ids' => !empty($row[8]) ? $row[8] : "",
                        'selected_products' => !empty($row[9]) ? $row[9] : "",
                        'has_similar_product' => !empty($row[10]) ? $row[10] : "",
                        'similar_product_ids' => !empty($row[11]) ? $row[11] : "",
                        'price' => !empty($row[12]) ? $row[12] : "",
                        'special_price' => !empty($row[13]) ? $row[13] : "",
                        'deliverable_type' => !empty($row[14]) ? $row[14] : "",
                        'deliverable_zones' => !empty($row[15]) ? $row[15] : "",
                        'pickup_location' => $row[16],
                        'tax' => $row[17],
                        'weight' => $row[18],
                        'height' => $row[19],
                        'length' => $row[20],
                        'breadth' => $row[21],
                        'quantity_step_size' => !empty($row[22]) ? $row[22] : "",
                        'minimum_order_quantity' => !empty($row[23]) ? $row[23] : "",
                        'total_allowed_quantity' => !empty($row[24]) ? $row[24] : "",
                        'attribute' => !empty($row[25]) ? $row[25] : "",
                        'attribute_value_ids' => !empty($row[26]) ? $row[26] : "",
                        'sku' => !empty($row[27]) ? $row[27] : "",
                        'stock' => !empty($row[28]) ? $row[28] : "",
                        'availability' => !empty($row[29]) ? $row[29] : "",
                        'cod_allowed' => !empty($row[30]) ? $row[30] : "",
                        'is_prices_inclusive_tax' => !empty($row[31]) ? $row[31] : "",
                        'is_returnable' => !empty($row[32]) ? $row[32] : "",
                        'is_cancelable' => !empty($row[33]) ? $row[33] : "",
                        'cancelable_till' => !empty($row[34]) ? $row[34] : "",
                        'minimum_free_delivery_order_qty' => !empty($row[35]) ? $row[35] : "",
                        'delivery_charges' => !empty($row[36]) ? $row[36] : "",
                        'other_images' => isset($row[37]) && !empty($row[37]) ? json_encode(explode(',', $row[37]), 1) : '[]',
                    ];

                    $product = ComboProduct::create($data);
                }
                $temp1++;
            }
            fclose($handle);
            return response()->json([
                'error' => 'false',
                'message' => labels('admin_labels.products_uploaded_successfully', 'Products uploaded successfully!')
            ]);
        } else { // bulk_update
            while (($row = fgetcsv($handle, 10000, ",")) != FALSE) //get row vales
            {

                if ($temp != 0) {
                    if (empty($row[0])) {
                        return response()->json([
                            'error' => 'true',
                            'message' => labels('admin_labels.product_id_empty_at_row', 'Product id is empty at row')
                                . $temp
                        ]);
                    }
                    if (empty($row[1])) {
                        return response()->json([
                            'error' => 'true',
                            'message' => labels('admin_labels.seller_id_empty_at_row', 'Seller id is empty at row')
                                . $row[0]
                        ]);
                    }
                    if (empty($row[2])) {
                        return response()->json([
                            'error' => 'true',
                            'message' => labels('admin_labels.title_empty_at_row', 'Title is empty at row')
                                . $row[0]
                        ]);
                    }

                    if (empty($row[3])) {
                        return response()->json([
                            'error' => 'true',
                            'message' => labels('admin_labels.short_description_empty_at_row', 'Short Description is empty at row')
                                . $temp
                        ]);
                    }
                    if (empty($row[4])) {
                        return response()->json(['error' => 'true', 'message' => labels('admin_labels.image_is_empty_at_row', 'Image is empty at row') . $temp]);
                    }
                    if (empty($row[7])) {
                        return response()->json([
                            'error' => 'true',
                            'message' => labels('admin_labels.product_ids_empty_at_row', 'Product Ids are empty at row')
                                . $temp
                        ]);
                    }
                    if (empty($row[8])) {
                        return response()->json([
                            'error' => 'true',
                            'message' => labels('admin_labels.selected_products_empty_at_row', 'Selected Products is empty at row')
                                . $temp
                        ]);
                    }

                    if ($row[9] == '1' || $row[10] == '') {
                        if (empty($row[10])) {
                            return response()->json([
                                'error' => 'true',
                                'message' => labels('admin_labels.similar_product_ids_empty_at_row', 'Similar Product Ids are empty at row')
                                    . $temp
                            ]);
                        }
                    }

                    if (empty($row[11])) {
                        return response()->json([
                            'error' => 'true',
                            'message' => labels('admin_labels.price_empty_at_row', 'Price is empty at row')
                                . $temp
                        ]);
                    }
                    if (empty($row[12])) {
                        return response()->json([
                            'error' => 'true',
                            'message' => labels('admin_labels.special_price_empty_at_row', 'Special Price is empty at row')
                                . $temp
                        ]);
                    }

                    if (
                        $row[13] == '2' || $row[13] == '3'
                    ) {
                        if (empty($row[14])) {
                            return response()->json([
                                'error' => 'true',
                                'message' => labels('admin_labels.deliverable_zipcodes_empty_at_row', 'Deliverable Zipcodes is empty at row')
                                    . $temp
                            ]);
                        }
                    }

                    if ($row[13] != 0 && $row[13] != 1 && $row[13] != 2 && $row[13] != 3 && $row[13] == "") {
                        return response()->json([
                            'error' => 'true',
                            'message' => labels('admin_labels.not_valid_value_for_deliverable_type_at_row', 'Not valid value for deliverable_type at row')
                                . $temp
                        ]);
                    }

                    if (!empty($row[29]) && $row[29] != 1) {
                        return response()->json([
                            'error' => 'true',
                            'message' => labels('admin_labels.cod_allowed_invalid_at_row', 'COD allowed is invalid at row')
                                . $temp
                        ]);
                    }

                    if (!empty($row[30]) && $row[30] != 1) {
                        return response()->json([
                            'error' => 'true',
                            'message' => labels('admin_labels.prices_inclusive_tax_invalid_at_row', 'Is prices inclusive tax is invalid at row')
                                . $temp
                        ]);
                    }

                    if (!empty($row[31]) && $row[31] != 1) {
                        return response()->json([
                            'error' => 'true',
                            'message' => labels('admin_labels.returnable_invalid_at_row', 'Is Returnable is invalid at row')
                                . $temp
                        ]);
                    }

                    if (!empty($row[32]) && $row[32] != 1) {
                        return response()->json([
                            'error' => 'true',
                            'message' => labels('admin_labels.cancelable_invalid_at_row', 'Is Cancelable is invalid at row')
                                . $temp
                        ]);
                    }

                    if (!empty($row[32]) && $row[32] == 1 && (empty($row[33]) || !in_array($row[33], $allowed_status))) {
                        return response()->json([
                            'error' => 'true',
                            'message' => labels('admin_labels.cancelable_till_invalid_at_row', 'Cancelable till is invalid at row')
                                . $temp
                        ]);
                    }

                    if (empty($row[32]) && !(empty($row[33]))) {
                        return response()->json([
                            'error' => 'true',
                            'message' => labels('admin_labels.cancelable_till_invalid_at_row', 'Cancelable till is invalid at row')
                                . $temp
                        ]);
                    }
                }
                $temp++;
            }

            fclose($handle);
            $handle = fopen($csv, "r");
            while (($row = fgetcsv($handle, 10000, ",")) != FALSE) //get row values
            {

                if ($temp1 != 0) {
                    $product_id = $row[0];
                    $product = fetchDetails(
                        ComboProduct::class,
                        ['id' => $product_id],
                        '*'
                    );
                    if (isset($product[0]) && !empty($product[0])) {
                        $fields = [
                            'seller_id',
                            'title',
                            'short_description',
                            'image',
                            'description',
                            'tags',
                            'product_ids',
                            'selected_products',
                            'has_similar_product',
                            'similar_product_ids',
                            'price',
                            'special_price',
                            'deliverable_type',
                            'deliverable_zones',
                            'pickup_location',
                            'tax',
                            'weight',
                            'height',
                            'length',
                            'breadth',
                            'quantity_step_size',
                            'minimum_order_quantity',
                            'total_allowed_quantity',
                            'attribute',
                            'attribute_value_ids',
                            'sku',
                            'stock',
                            'availability',
                            'cod_allowed',
                            'is_prices_inclusive_tax',
                            'is_returnable',
                            'is_cancelable',
                            'cancelable_till',
                            'minimum_free_delivery_order_qty',
                            'delivery_charges',
                            'other_images'
                        ];

                        foreach ($fields as $index => $field) {
                            if (!empty($row[$index + 1])) {
                                $data[$field] = $row[$index + 1];
                            } else {
                                $data[$field] = $product[0]->{$field};
                            }
                        }

                        if (isset($row[36]) && $row[36] != '') {
                            $other_images = explode(',', $row[36]);
                            $data['other_images'] = json_encode($other_images, 1);
                        }

                        ComboProduct::where('id', $product_id)->update($data);
                    }
                }

                $temp1++;
            }
            fclose($handle);
            return response()->json([
                'error' => 'false',
                'message' => labels('admin_labels.products_updated_successfully', 'Products updated successfully!')
            ]);
        }
    }
    public function delete_selected_data(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:combo_products,id'
        ]);

        foreach ($request->ids as $id) {
            $combo_products = ComboProduct::find($id);

            if ($combo_products) {
                ComboProduct::where('id', $id)->delete();
            }
        }

        return response()->json([
            'error' => false,
            'message' => labels('admin_labels.products_deleted_successfully', 'Selected products deleted successfully!'),
        ]);
    }
}
