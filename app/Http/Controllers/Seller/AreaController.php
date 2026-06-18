<?php

namespace App\Http\Controllers\Seller;

use App\Models\City;
use App\Models\Language;
use App\Models\Seller;
use App\Models\SellerStore;
use App\Models\Zone;
use App\Models\Zipcode;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\TranslationService;
use App\Services\StoreService;

class AreaController extends Controller
{

    // zipcode

    public function zipcodes()
    {
        $languages = Language::all();
        return view('seller.pages.tables.zipcodes', ['languages' => $languages]);
    }


    public function zipcode_list(Request $request, $language_code = '')
    {
        $search = trim($request->input('search'));
        $sort = $request->input('sort', 'id');
        $order = $request->input('order', 'DESC');
        $offset = $request->input('pagination_offset', 0);
        $limit = $request->input('limit', 10);
        $language_code = $language_code ?: app(TranslationService::class)->getLanguageCode();

        $query = Zipcode::with('city');

        // Apply case-insensitive search
        if (!empty($search)) {
            $lowerSearch = strtolower($search);
            $query->where(function ($q) use ($lowerSearch) {
                $q->whereRaw('LOWER(zipcodes.id) LIKE ?', ["%{$lowerSearch}%"])
                    ->orWhereRaw('LOWER(zipcodes.zipcode) LIKE ?', ["%{$lowerSearch}%"])
                    ->orWhereRaw('LOWER(zipcodes.minimum_free_delivery_order_amount) LIKE ?', ["%{$lowerSearch}%"])
                    ->orWhereRaw('LOWER(zipcodes.delivery_charges) LIKE ?', ["%{$lowerSearch}%"])
                    ->orWhereHas('city', function ($q2) use ($lowerSearch) {
                        $q2->whereRaw('LOWER(cities.name) LIKE ?', ["%{$lowerSearch}%"]);
                    });
            });
        }

        $total = $query->count();

        $rows = $query->orderBy($sort, $order)
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(function ($zipcode) use ($language_code) {
                return [
                    'id' => $zipcode->id,
                    'zipcode' => $zipcode->zipcode,
                    'city_name' => $zipcode->city
                        ? app(TranslationService::class)->getDynamicTranslation(City::class, 'name', $zipcode->city->id, $language_code)
                        : '',
                    'city_id' => $zipcode->city->id ?? '',
                    'minimum_free_delivery_order_amount' => $zipcode->minimum_free_delivery_order_amount ?? 0,
                    'delivery_charges' => $zipcode->delivery_charges ?? 0,
                ];
            });

        return response()->json([
            'rows' => $rows,
            'total' => $total,
        ]);
    }



    // city

    public function city()
    {
        $languages = Language::all();
        return view('seller.pages.tables.city', ['languages' => $languages]);
    }




    public function city_list(Request $request, $language_code = '')
    {
        $search = trim($request->input('search'));
        $sort = $request->input('sort', 'id');
        $order = $request->input('order', 'DESC');
        $offset = $request->input('pagination_offset', 0);
        $limit = $request->input('limit', 10);
        $language_code = $language_code ?: app(TranslationService::class)->getLanguageCode();

        $city_data = City::query();

        // Case-insensitive search
        if (!empty($search)) {
            $lowerSearch = strtolower($search);
            $city_data->whereRaw('LOWER(cities.name) LIKE ?', ["%{$lowerSearch}%"]);
        }

        $total = $city_data->count();

        $cities = $city_data->orderBy($sort, $order)
            ->offset($offset)
            ->limit($limit)
            ->get();

        $data = $cities->map(function ($c) use ($language_code) {
            $translatedName = app(TranslationService::class)
                ->getDynamicTranslation(City::class, 'name', $c->id, $language_code);

            return [
                'id' => $c->id ?? '',
                'name' => $translatedName ?? '',
                'text' => $translatedName ?? '',
                'minimum_free_delivery_order_amount' => $c->minimum_free_delivery_order_amount ?? '',
                'delivery_charges' => $c->delivery_charges ?? '',
            ];
        });

        return response()->json([
            'rows' => $data,
            'total' => $total,
        ]);
    }



    public function get_cities(Request $request)
    {
        $search = trim($request->search) ?? "";
        $cities = City::whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.en'))) LIKE ?", ['%' . strtolower($search) . '%'])->get();
        $data = array();
        foreach ($cities as $city) {
            $data[] = array("id" => $city->id, "text" => $city->name);
        }
        return response()->json($data);
    }

    public function get_zipcodes(Request $request)
    {
        $search = trim($request->search) ?? "";
        $zipcodes = Zipcode::where('zipcode', 'like', '%' . $search . '%')->get();

        $data = array();
        foreach ($zipcodes as $zipcode) {
            $data[] = array("id" => $zipcode->id, "text" => $zipcode->zipcode);
        }
        return response()->json($data);
    }

    public function getCities(Request $request)
    {
        $search = trim($request->search) ?? "";
        $cities = City::whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.en'))) LIKE ?", ['%' . strtolower($search) . '%'])->get();
        $language_code = app(TranslationService::class)->getLanguageCode();
        $data = array();
        foreach ($cities as $city) {
            $data[] = array("id" => $city->id, "text" => app(TranslationService::class)->getDynamicTranslation(City::class, 'name', $city->id, $language_code));
        }
        return response()->json($data);
    }

    public function zone_data(Request $request)
    {
        $store_id = app(StoreService::class)->getStoreId();
        $user_id = Auth::user()->id;
        $seller_id = Seller::where('user_id', $user_id)->value('id');
        $seller_zones = fetchDetails(SellerStore::class, ['seller_id' => $seller_id, 'store_id' => $store_id], ['deliverable_type', 'deliverable_zones']);
        $seller_zones = !$seller_zones->isEmpty() ? $seller_zones[0] : [];
        $search = trim($request->input('search'));

        $limit = (int) $request->input('limit', 50);

        $query = Zone::where('status', 1)
            ->where(function ($query) use ($search) {
                $query->where('id', 'like', '%' . $search . '%')
                    ->orWhere('name', 'like', '%' . $search . '%');
            });

        if ($seller_zones->deliverable_type == '2' || $seller_zones->deliverable_type == '3') {
            $zone_ids = explode(',', $seller_zones->deliverable_zones);
            $query->whereIn('id', $zone_ids);
        }
        $zones = $query->limit($limit)->get(['id', 'name', 'serviceable_city_ids', 'serviceable_zipcode_ids']);
        $total = $query->count();

        $cities = [];
        $zipcodes = [];
        $language_code = app(TranslationService::class)->getLanguageCode();
        foreach ($zones as $zone) {
            $city_ids = explode(',', $zone->serviceable_city_ids);
            $zipcode_ids = explode(',', $zone->serviceable_zipcode_ids);

            $cities = array_unique(array_merge($cities, $city_ids));
            $zipcodes = array_unique(array_merge($zipcodes, $zipcode_ids));
        }

        $city_names = City::whereIn('id', $cities)->pluck('name', 'id')->toArray();

        $zipcode_names = Zipcode::whereIn('id', $zipcodes)->pluck('zipcode', 'id')->toArray();

        $response = [
            'total' => $total,
            'results' => $zones->map(function ($zone) use ($city_names, $zipcode_names, $language_code) {
                $city_ids = explode(',', $zone->serviceable_city_ids);
                $zipcode_ids = explode(',', $zone->serviceable_zipcode_ids);

                return [
                    'id' => $zone->id,
                    'text' => app(TranslationService::class)->getDynamicTranslation(Zone::class, 'name', $zone->id, $language_code),
                    'serviceable_cities' => implode(', ', array_map(function ($city_id) use ($language_code) {
                        return app(TranslationService::class)->getDynamicTranslation(Zone::class, 'name', $city_id, $language_code);
                    }, $city_ids)),
                    'serviceable_zipcodes' => implode(', ', array_map(function ($zipcode_id) use ($zipcode_names) {
                        return $zipcode_names[$zipcode_id] ?? null;
                    }, $zipcode_ids)),
                ];
            }),
        ];

        return response()->json($response);
    }

    public function zones()
    {
        return view('seller.pages.tables.zones');
    }

    public function get_zones(Request $request)
    {
        $search = trim($request->input('term', $request->input('q', '')));
        $limit = (int) $request->input('limit', 50);

        $query = Zone::where('status', 1);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            });
        }

        $zones = $query->limit($limit)->get(['id', 'name', 'serviceable_city_ids', 'serviceable_zipcode_ids']);
        $total = $query->count();

        $cities = [];
        $zipcodes = [];
        $language_code = app(TranslationService::class)->getLanguageCode();

        foreach ($zones as $zone) {
            $city_ids = array_filter(explode(',', $zone->serviceable_city_ids));
            $zipcode_ids = array_filter(explode(',', $zone->serviceable_zipcode_ids));

            $cities = array_unique(array_merge($cities, $city_ids));
            $zipcodes = array_unique(array_merge($zipcodes, $zipcode_ids));
        }

        $city_names = City::whereIn('id', $cities)->pluck('name', 'id')->toArray();
        $zipcode_names = Zipcode::whereIn('id', $zipcodes)->pluck('zipcode', 'id')->toArray();

        $response = [
            'total' => $total,
            'results' => $zones->map(function ($zone) use ($city_names, $zipcode_names, $language_code) {
                $city_ids = array_filter(explode(',', $zone->serviceable_city_ids));
                $zipcode_ids = array_filter(explode(',', $zone->serviceable_zipcode_ids));

                return [
                    'id' => $zone->id,
                    'text' => app(TranslationService::class)->getDynamicTranslation(Zone::class, 'name', $zone->id, $language_code),
                    'serviceable_cities' => implode(', ', array_map(function ($city_id) use ($city_names) {
                        return $city_names[$city_id] ?? null;
                    }, $city_ids)),
                    'serviceable_zipcodes' => array_values(array_filter(array_map(function ($zipcode_id) use ($zipcode_names) {
                        return isset($zipcode_names[$zipcode_id])
                            ? ['id' => $zipcode_id, 'zipcode' => $zipcode_names[$zipcode_id]]
                            : null;
                    }, $zipcode_ids))),
                ];
            }),
        ];

        return response()->json($response);
    }



   public function zone_list(Request $request)
{
    $search = trim($request->input('search'));
    $limit = (int) $request->input('limit', 10);
    $offset = (int) $request->input('pagination_offset', 0);
    $sort = $request->input('sort', 'id');
    $order = $request->input('order', 'DESC');
    $user_id = Auth::id();

    $seller_id = Seller::where('user_id', $user_id)->value('id');

    $seller = SellerStore::where('seller_id', $seller_id)
        ->select('deliverable_type', 'deliverable_zones')
        ->first();

    $query = Zone::where('status', 1);

    // Properly group and make search case-insensitive
    if (!empty($search)) {
        $lowerSearch = strtolower($search);
        $query->where(function ($q) use ($lowerSearch) {
            $q->whereRaw('LOWER(zones.id) LIKE ?', ["%{$lowerSearch}%"])
              ->orWhereRaw('LOWER(zones.name) LIKE ?', ["%{$lowerSearch}%"]);
        });
    }

    // Restrict to seller deliverable zones if applicable
    if ($seller && $seller->deliverable_type == 2) {
        $deliverable_zone_ids = array_filter(explode(',', $seller->deliverable_zones));
        if (!empty($deliverable_zone_ids)) {
            $query->whereIn('id', $deliverable_zone_ids);
        }
    }

    $total = $query->count();

    $language_code = app(TranslationService::class)->getLanguageCode();

    $zones = $query->orderBy($sort, $order)
        ->offset($offset)
        ->limit($limit)
        ->get(['id', 'name', 'serviceable_city_ids', 'serviceable_zipcode_ids']);

    // Extract unique city and zipcode IDs
    $city_ids = [];
    $zipcode_ids = [];

    foreach ($zones as $zone) {
        $city_ids = array_merge($city_ids, explode(',', $zone->serviceable_city_ids));
        $zipcode_ids = array_merge($zipcode_ids, explode(',', $zone->serviceable_zipcode_ids));
    }

    $city_ids = array_unique(array_filter($city_ids));
    $zipcode_ids = array_unique(array_filter($zipcode_ids));

    $city_names = City::whereIn('id', $city_ids)->pluck('name', 'id')->toArray();
    $zipcode_names = Zipcode::whereIn('id', $zipcode_ids)->pluck('zipcode', 'id')->toArray();

    $data = $zones->map(function ($zone) use ($city_names, $zipcode_names, $language_code) {
        $translatedName = app(TranslationService::class)->getDynamicTranslation(Zone::class, 'name', $zone->id, $language_code);

        // Use City::class for serviceable cities translation
        $serviceableCities = collect(explode(',', $zone->serviceable_city_ids))
            ->map(fn($id) => app(TranslationService::class)->getDynamicTranslation(City::class, 'name', $id, $language_code))
            ->filter()
            ->implode(', ');

        $serviceableZipcodes = collect(explode(',', $zone->serviceable_zipcode_ids))
            ->map(fn($id) => $zipcode_names[$id] ?? '')
            ->filter()
            ->implode(', ');

        return [
            'id' => $zone->id,
            'name' => $translatedName,
            'serviceable_cities' => $serviceableCities,
            'serviceable_zipcodes' => $serviceableZipcodes,
        ];
    });

    return response()->json([
        'total' => $total,
        'rows' => $data,
    ]);
}
}
