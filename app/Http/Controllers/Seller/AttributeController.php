<?php

namespace App\Http\Controllers\Seller;

use App\Models\Attribute;
use Illuminate\Http\Request;
use App\Models\Attribute_values;
use Illuminate\Routing\Controller;
use App\Services\StoreService;

class AttributeController extends Controller
{
    public function index()
    {
        $attributes = Attribute::where('status', 1)->get();
        return view('seller.pages.tables.attributes', ['attributes' => $attributes]);
    }

    public function list(Request $request)
    {
        $store_id = $request->store_id ?: app(StoreService::class)->getStoreId();
        $search = trim($request->search ?? '');
        $sort = $request->sort ?? 'id';
        $order = $request->order ?? 'DESC';
        $limit = $request->limit ?? 10;
        $offset = $request->offset ?? 0;

        // Convert comma-separated IDs to arrays
        $attribute_ids = $request->attribute_ids ? explode(',', $request->attribute_ids) : [];
        $attribute_value_ids = $request->attribute_value_ids ? explode(',', $request->attribute_value_ids) : [];

        // Base query
        $attributes = Attribute::where('store_id', $store_id)
            ->when($request->has('status'), fn($q) => $q->where('status', $request->status), fn($q) => $q->where('status', 1))
            ->with('attribute_values')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('id', 'like', "%{$search}%")
                        ->orWhereHas('attribute_values', fn($q2) => $q2->where('value', 'like', "%{$search}%"));
                });
            })
            ->when($attribute_ids, fn($q) => $q->whereIn('id', $attribute_ids))
            ->when($attribute_value_ids, fn($q) => $q->whereHas('attribute_values', fn($q2) => $q2->whereIn('id', $attribute_value_ids)));

        $total = $attributes->count();

        // Apply sorting & pagination
        $attributes = $attributes->orderBy($sort, $order)
            ->skip($offset)
            ->take($limit)
            ->get();

        // Format the data
        $rows = $attributes->map(function ($attribute) {
            return [
                'id' => $attribute->id,
                'name' => $attribute->name,
                'attribute_value_id' => $attribute->attribute_values->pluck('id')->implode(','),
                'value' => $attribute->attribute_values->pluck('value')->implode(','),
                'status_code' => $attribute->status,
                'status' => $attribute->status == 1 ? 'Active' : 'Inactive',
            ];
        });

        return response()->json([
            'rows' => $rows,
            'total' => $total,
        ]);
    }

    public function getAttributes(Request $request)
    {
        $attributes = Attribute::with(['attribute_values' => function ($query) {
            $query->select('id', 'value', 'attribute_id');
        }])
            ->where('status', 1)
            ->where('category_id', $request->category_id)
            ->get(['id', 'name']);
        // dd($attributes);
        $attributes_refind = [];

        foreach ($attributes as $attribute) {
            // dd($attribute->attribute_values);
            $values = [];

            foreach ($attribute->attribute_values as $value) {
                $values[] = [
                    'id' => $value->id,
                    'text' => $value->value,
                    'data_values' => $value->value,
                    'attr_id' => $attribute->id,
                ];
            }

            $attributes_refind[$attribute->name] = $values;
        }

        if (!empty($attributes_refind)) {
            $response['error'] = false;
            $response['data'] = $attributes_refind;
        } else {
            $response['error'] = true;
            $response['data'] = [];
        }

        return response()->json($response);
    }

    public function getAttributeValue(Request $request)
    {
        $store_id = $request->input('store_id', app(StoreService::class)->getStoreId());
        $search = trim($request->input('search'));
        $attribute_id = $request->input('attribute_id');
        $sort = $request->input('sort', 'id');
        $order = $request->input('order', 'DESC');
        $limit = $request->input('limit', 10);
        $offset = $request->input('offset', 0);

        // Start query
        $query = Attribute_values::with('attribute')
            ->where('status', 1)
            ->whereHas('attribute', function ($q) use ($store_id) {
                $q->where('store_id', $store_id)->where('status', 1);
            });

        // Search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('value', 'like', "%{$search}%")
                    ->orWhere('swatche_value', 'like', "%{$search}%");
            });
        }

        // Attribute ID filter
        if ($attribute_id) {
            $query->where('attribute_id', $attribute_id);
        }

        // Get total count for pagination
        $totalCount = $query->count();

        // Fetch data with pagination
        $attributes = $query->orderBy($sort, $order)
            ->skip($offset)
            ->take($limit)
            ->get();

        // Format result
        $data = $attributes->map(function ($attr) {
            return [
                'id' => $attr->id,
                'attribute_id' => $attr->attribute_id,
                'filterable' => $attr->filterable,
                'value' => $attr->value,
                'swatche_type' => $attr->swatche_type,
                'swatche_value' => $attr->swatche_value,
                'status' => $attr->status,
                'attribute_name' => optional($attr->attribute)->name,
            ];
        });

        return response()->json([
            'error' => $data->isEmpty(),
            'message' => $data->isEmpty() ? 'Attributes Not Found' : 'Attributes Retrieved Successfully',
            'total' => $totalCount,
            'data' => $data,
        ]);
    }
}
