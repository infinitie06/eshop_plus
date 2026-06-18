<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Product;
use App\Models\Product_attributes;
use App\Models\Product_variants;
use App\Models\City;
use App\Models\Seller;
use App\Models\User;
use App\Models\Zipcode;
use App\Models\Zone;
use App\Services\MediaService;
use Throwable;

class DataSyncController extends Controller
{
    /* --------------------------------------------------------------------- *
     *  1. Migrate live products (store_id = 3)                              *
     * --------------------------------------------------------------------- */
    public function migrateLiveProducts(): JsonResponse
    {
        $liveApiUrl = 'https://plus.eshopweb.store/api/get_stores';
        $authToken  = '2930|BsUO5XqRnG9CaEaRk6X0gGS0NBQFxwfKPe3Kyb1Faddf1a92';
        $storeId    = 3;
        $limit      = 100;

        $response = Http::withHeaders(['Authorization' => "Bearer $authToken"])
            ->get($liveApiUrl, ['store_id' => $storeId, 'limit' => $limit]);

        if (!$response->ok()) {
            return response()->json([
                'error'   => true,
                'message' => 'Failed to fetch products from live server',
                'details' => $response->body(),
            ]);
        }

        $products = $response->json('data') ?? [];

        if (empty($products)) {
            return response()->json([
                'error'   => true,
                'message' => 'No products found on live server',
            ]);
        }

        $inserted = $skipped = $failed = 0;
        $logs     = [];

        foreach ($products as $p) {
            DB::beginTransaction();
            try {
                $slug = $p['slug'] ?? Str::slug($p['name'] ?? Str::random(8));

                if (Product::where('slug', $slug)->exists()) {
                    $skipped++;
                    $logs[] = "Skipped duplicate: {$slug}";
                    DB::rollBack();
                    continue;
                }

                // ---------- Image download ----------
                $downloadedImagePath = null;
                if (!empty($p['image'])) {
                    $downloadedImagePath = $this->downloadImage(
                        $p['image'],
                        'products',
                        $slug,
                        $logs
                    );
                }

                $nameJson      = json_encode(['en' => $p['name'] ?? 'Unnamed Product']);
                $shortDescJson = json_encode(['en' => $p['short_description'] ?? '']);

                $product = Product::create([
                    'name'                    => $nameJson,
                    'short_description'       => $shortDescJson,
                    'slug'                    => $slug,
                    'category_id'             => $p['category_id'] ?? null,
                    'type'                    => $p['type'] ?? 'simple_product',
                    'seller_id'               => $p['seller_id'] ?? 1,
                    'deliverable_type'        => $p['deliverable_type'] ?? 0,
                    'status'                  => $p['status'] ?? 1,
                    'store_id'                => $storeId,
                    'image'                   => $downloadedImagePath,
                    'is_returnable'           => 1,
                    'is_cancelable'           => 1,
                    'cod_allowed'             => 1,
                    'is_prices_inclusive_tax' => 1,
                ]);

                Product_attributes::create([
                    'product_id'         => $product->id,
                    'attribute_value_ids' => '',
                ]);

                Product_variants::create([
                    'product_id'   => $product->id,
                    'price'        => $p['price'] ?? 0,
                    'special_price' => $p['special_price'] ?? 0,
                    'stock'        => $p['stock'] ?? 10,
                    'weight'       => 0,
                    'height'       => 0,
                    'breadth'      => 0,
                    'length'       => 0,
                ]);

                DB::commit();
                $inserted++;
                $logs[] = "Inserted product: {$slug}";
            } catch (Throwable $e) {
                DB::rollBack();
                $failed++;
                $logs[] = "Error inserting " . ($p['name'] ?? 'unknown') . ": " . $e->getMessage();

                Log::error('Migration error', ['slug' => $p['slug'] ?? null, 'error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'error'    => false,
            'message'  => 'Migration completed',
            'inserted' => $inserted,
            'skipped'  => $skipped,
            'failed'   => $failed,
            'log'      => $logs,
        ]);
    }

    /* --------------------------------------------------------------------- *
     *  2. Sync stores (image names only)                                    *
     * --------------------------------------------------------------------- */
    public function syncStores(): string
    {
        $url = 'https://eshop-pro.eshopweb.store/api/get_stores';

        $response = Http::withHeaders([
            'language-id'  => '28',
            'Authorization' => 'Bearer 184|SKAKQSqIHa9Z91rtPBIJ7OfAn1PUDTZmaUFl89q917c067e9',
        ])->get($url);

        if (!$response->ok()) {
            return 'Failed to fetch stores from API.';
        }

        $data = $response->json('data');
        if (empty($data)) {
            return 'No store data found.';
        }

        // Extract filename from URL
        $getFileName = fn($path) => $path ? '/' . basename(parse_url($path, PHP_URL_PATH)) : null;

        foreach ($data as $store) {

            // -----------------------------------------------
            // 1. SAFE ID HANDLING - ZERO DATA LOSS
            // -----------------------------------------------
            $idFromApi = $store['id'] ?? null;

            if (is_numeric($idFromApi) && intval($idFromApi) > 0) {
                $id = intval($idFromApi);
            } else {
                // Auto-generate next safe ID
                $id = DB::table('stores')->max('id') + 1;
            }

            // If ID conflicts, regenerate
            if (DB::table('stores')->where('id', $id)->exists()) {
                $id = DB::table('stores')->max('id') + 1;
            }

            // -----------------------------------------------
            // 2. SAFE NAME HANDLING
            // -----------------------------------------------
            $name = trim($store['name'] ?? '');

            if ($name === '') {
                $name = "Store-" . $id; // Fallback
            }

            // -----------------------------------------------
            // 3. UNIQUE SLUG HANDLING
            // -----------------------------------------------
            $slug = Str::slug($name);

            if (DB::table('stores')->where('slug', $slug)->exists()) {
                $slug = $slug . '-' . uniqid();
            }

            // -----------------------------------------------
            // 4. INSERT STORE SAFELY
            // -----------------------------------------------
            DB::table('stores')->insert([
                'id'                                           => $id,
                'name'                                         => json_encode(['en' => $name]),
                'slug'                                         => $slug,

                'description'                                  => json_encode(['en' => $store['description'] ?? '']),
                'image'                                        => $getFileName($store['image'] ?? null),
                'banner_image'                                 => $getFileName($store['banner_image'] ?? null),
                'banner_image_for_most_selling_product'        => $getFileName($store['banner_image_for_most_selling_product'] ?? null),
                'stack_image'                                  => $getFileName($store['stack_image'] ?? null),
                'login_image'                                  => $getFileName($store['login_image'] ?? null),

                'half_store_logo'                              => null,
                'disk'                                         => 'public',

                'is_single_seller_order_system'                => $store['is_single_seller_order_system'] ?? 0,
                'is_default_store'                             => $store['is_default_store'] ?? 0,
                'note_for_necessary_documents'                 => $store['note_for_necessary_documents'] ?? null,
                'primary_color'                                => $store['primary_color'] ?? null,
                'secondary_color'                              => $store['secondary_color'] ?? null,
                'store_settings'                               => json_encode($store['store_settings'] ?? []),
                'hover_color'                                  => $store['hover_color'] ?? null,
                'active_color'                                 => $store['active_color'] ?? null,
                'background_color'                             => $store['background_color'] ?? '#ffffff',

                'status'                                       => $store['status'] ?? 1,
                'rating'                                       => 0,
                'no_of_ratings'                                => 0,
                'delivery_charge_type'                         => $store['delivery_charge_type'] ?? 'zipcode_wise_delivery_charge',
                'delivery_charge_amount'                       => $store['delivery_charge_amount'] ?? 0,
                'minimum_free_delivery_amount'                 => $store['minimum_free_delivery_amount'] ?? 0,
                'product_deliverability_type'                  => $store['product_deliverability_type'] ?? 'zipcode_wise_deliverability',

                'created_at'                                   => now(),
                'updated_at'                                   => now(),
            ]);
        }

        return 'Stores synced successfully without data loss.';
    }


    /* --------------------------------------------------------------------- *
     *  3. Sync all categories (with images)                                 *
     * --------------------------------------------------------------------- */
    public function syncAllCategoriesWithImages(): JsonResponse
    {
        $languageId = 28;
        $messages   = [];

        // Fetch all local stores
        $stores = DB::table('stores')->select('id', 'name')->get();

        if ($stores->isEmpty()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No stores found.',
                'details' => [],
            ]);
        }

        // Reusable image downloader
        $downloadImage = function ($url) use (&$messages) {
            if (!$url) {
                return null;
            }
            try {
                $parsed      = parse_url($url);
                parse_str($parsed['query'] ?? '', $queryParams);
                $imageUrl    = $queryParams['url'] ?? $url;
                $decodedUrl  = urldecode($imageUrl);
                $filename    = basename($decodedUrl);
                $imageData   = Http::timeout(15)->get($decodedUrl)->body();
                $path        = 'media/' . $filename;
                Storage::disk('public')->put($path, $imageData);
                return '/' . $path;
            } catch (\Exception $e) {
                return null;
            }
        };

        // Insert category
        $insertCategory = null;
        $insertCategory = function ($category, $storeId, $parentId = 0) use (&$insertCategory, $downloadImage, &$messages) {

            $slug = $category['slug'] ?? Str::slug($category['name']);
            $name = $category['name'];

            // Check duplication by slug or name for this store
            $exists = DB::table('categories')
                ->where('store_id', $storeId)
                ->where(function ($q) use ($slug, $name) {
                    $q->where('slug', $slug)->orWhere('name', json_encode(['en' => $name]));
                })
                ->exists();

            if ($exists) {
                return;
            }

            try {
                DB::table('categories')->insert([
                    'store_id'           => $storeId,
                    'name'               => json_encode(['en' => $name]),
                    'slug'               => $slug,
                    'parent_id'          => $parentId,
                    'image'              => $downloadImage($category['image'] ?? null),
                    'banner'             => $downloadImage($category['banner'] ?? null),
                    'style'              => $category['style'] ?? '',
                    'row_order'          => $category['row_order'] ?? 0,
                    'status'             => $category['status'] ?? 1,
                    'affiliate_commission' => $category['affiliate_commission'] ?? 0,
                    'is_in_affiliate'    => $category['is_in_affiliate'] ?? 0,
                    'clicks'             => $category['clicks'] ?? 0,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);
            } catch (\Exception $e) {
                // optional logging
            }

            // recursive children
            if (!empty($category['children'])) {
                foreach ($category['children'] as $child) {
                    $insertCategory($child, $storeId, $parentId);
                }
            }
        };

        // -------------------------------------------
        // MAIN LOOP: REMOTE STORE IDs 1 → 50
        // -------------------------------------------
        for ($remoteId = 1; $remoteId <= 50; $remoteId++) {

            $url = "https://plus.eshopweb.store/api/get_categories?store_id={$remoteId}";

            try {
                $response   = Http::withHeaders(['language-id' => $languageId])->get($url);

                $categories = $response->json('data') ?? [];
                if (empty($categories)) {
                    continue;
                }

                // Assign fetched categories to all local stores
                foreach ($stores as $store) {
                    foreach ($categories as $category) {
                        $insertCategory($category, $store->id, 0);
                    }
                }
            } catch (\Exception $e) {
                // Continue loop even if one fails
                continue;
            }
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Category sync completed using slug/name matching.',
            'details' => $messages,
        ]);
    }


    /* --------------------------------------------------------------------- *
     *  4. Sync cities                                                       *
     * --------------------------------------------------------------------- */
    public function syncCity(): JsonResponse
    {
        $apiUrl     = 'https://plus.eshopweb.store/api/get_cities';
        $languageId = 28;

        $response = Http::withHeaders(['language-id' => $languageId])->get($apiUrl);

        if (!$response->ok()) {
            return response()->json([
                'error'   => true,
                'message' => 'Failed to fetch cities from live server',
                'details' => $response->body(),
            ]);
        }

        $cities = $response->json('data') ?? [];

        if (empty($cities)) {
            return response()->json([
                'error'   => true,
                'message' => 'No cities found on live server',
            ]);
        }

        $inserted = $skipped = 0;
        $logs     = [];

        foreach ($cities as $c) {
            try {
                $cityName = $c['name'] ?? 'Unnamed City';

                if (City::where('name', $cityName)->exists()) {
                    $skipped++;
                    $logs[] = "Skipped duplicate city: {$cityName}";
                    continue;
                }

                City::create([
                    'id'                              => $c['id'],
                    'name'                            => json_encode(['en' => $cityName]),
                    'minimum_free_delivery_order_amount' => $c['minimum_free_delivery_order_amount'] ?? 0,
                    'delivery_charges'                => $c['delivery_charges'] ?? 0,
                ]);

                $inserted++;
                $logs[] = "Inserted city: {$cityName}";
            } catch (Throwable $e) {
                $logs[] = "Error inserting city " . ($c['name'] ?? 'unknown') . ": " . $e->getMessage();
            }
        }

        return response()->json([
            'error'    => false,
            'message'  => 'City migration completed',
            'inserted' => $inserted,
            'skipped'  => $skipped,
            'log'      => $logs,
        ]);
    }

    /* --------------------------------------------------------------------- *
     *  5. Sync zipcodes                                                     *
     * --------------------------------------------------------------------- */
    public function syncZipcodes(): JsonResponse
    {
        $apiUrl = 'https://eshop-pro.eshopweb.store/api/get_zipcodes';

        $response = Http::get($apiUrl);

        if (!$response->ok()) {
            return response()->json([
                'error'   => true,
                'message' => 'Failed to fetch zipcodes from live server',
                'details' => $response->body(),
            ]);
        }

        $zipcodes = $response->json('data') ?? [];

        if (empty($zipcodes)) {
            return response()->json([
                'error'   => true,
                'message' => 'No zipcodes found on live server',
            ]);
        }

        $inserted = $skipped = 0;
        $logs     = [];

        foreach ($zipcodes as $z) {
            try {
                $zip     = $z['zipcode'] ?? null;
                $cityId  = $z['city_id'] ?? null;

                if (!$zip || !$cityId) {
                    $logs[] = "Skipped incomplete zipcode entry: " . json_encode($z);
                    continue;
                }

                if (Zipcode::where('zipcode', $zip)->where('city_id', $cityId)->exists()) {
                    $skipped++;
                    $logs[] = "Skipped duplicate zipcode: {$zip} for city_id: {$cityId}";
                    continue;
                }

                Zipcode::create([
                    'id'                              => $z['id'],
                    'zipcode'                         => $zip,
                    'city_id'                         => $cityId,
                    'minimum_free_delivery_order_amount' => $z['minimum_free_delivery_order_amount'] ?? 0,
                    'delivery_charges'                => $z['delivery_charges'] ?? 0,
                ]);

                $inserted++;
                $logs[] = "Inserted zipcode: {$zip} for city_id: {$cityId}";
            } catch (Throwable $e) {
                $logs[] = "Error inserting zipcode " . ($z['zipcode'] ?? 'unknown') . ": " . $e->getMessage();
            }
        }

        return response()->json([
            'error'    => false,
            'message'  => 'Zipcode migration completed',
            'inserted' => $inserted,
            'skipped'  => $skipped,
            'log'      => $logs,
        ]);
    }
    public function syncUsers(): JsonResponse
    {
        $apiUrl = 'https://plus.eshopweb.store/users';
        $mediaService = app(MediaService::class);

        $response = Http::get($apiUrl);

        if (!$response->ok()) {
            return response()->json([
                'error'   => true,
                'message' => 'Failed to fetch users from live server',
                'details' => $response->body(),
            ]);
        }

        $users = $response->json('data') ?? [];

        if (empty($users)) {
            return response()->json([
                'error'   => true,
                'message' => 'No users found on live server',
            ]);
        }

        $inserted = 0;
        $updated  = 0;
        $skipped  = 0;
        $logs     = [];

        foreach ($users as $u) {
            try {
                if (!isset($u['id'])) {
                    $logs[] = "Skipped user without ID: " . json_encode($u);
                    $skipped++;
                    continue;
                }

                $userId = $u['id'];
                $existing = User::find($userId);

                // ------------------------
                // Download images locally
                // ------------------------
                // $imagePath  = $this->downloadImage($u['image'] ?? null,  'users/images',  $u['username'] ?? $userId, $logs);
                // $avatarPath = $this->downloadImage($u['avatar'] ?? null, 'users/avatars', $u['username'] ?? $userId, $logs);

                // Build user data
                $payload = [
                    'role_id'            => $u['role_id'] ?? null,
                    'username'           => $u['username'] ?? null,
                    'email'              => $u['email'] ?? null,
                    'mobile'             => $u['mobile'] ?? null,
                    'password'           => $u['password'] ?? null,
                    'address'            => $u['address'] ?? null,
                    'bonus_type'         => $u['bonus_type'] ?? null,
                    'bonus'              => $u['bonus'] ?? null,
                    'cash_received'      => $u['cash_received'] ?? 0,
                    'country_code'       => $u['country_code'] ?? null,
                    'city'               => $u['city'] ?? null,
                    'area'               => $u['area'] ?? null,
                    'street'             => $u['street'] ?? null,
                    'pincode'            => $u['pincode'] ?? null,
                    'type'               => $u['type'] ?? null,
                    'status'             => $u['status'] ?? 0,
                    'is_notification_on' => $u['is_notification_on'] ?? 1,
                    'is_affiliate_user'  => $u['is_affiliate_user'] ?? 0,
                    'active_status'      => $u['active_status'] ?? 0,
                    'dark_mode'          => $u['dark_mode'] ?? 0,
                    'messenger_color'    => $u['messenger_color'] ?? '#FF9800',

                ];

                if ($existing) {
                    $existing->update($payload);
                    $updated++;
                    $logs[] = "Updated user: {$userId}";
                } else {
                    $payload['id'] = $userId;
                    User::create($payload);
                    $inserted++;
                    $logs[] = "Inserted user: {$userId}";
                }
            } catch (Throwable $e) {
                $logs[] = "Error syncing user " . ($u['id'] ?? 'unknown') . ": " . $e->getMessage();
                $skipped++;
            }
        }

        return response()->json([
            'error'    => false,
            'message'  => 'User sync completed',
            'inserted' => $inserted,
            'updated'  => $updated,
            'skipped'  => $skipped,
            'log'      => $logs,
        ]);
    }




    public function syncZones(): string
    {
        $url = "https://eshop-pro.eshopweb.store/api/get_zones";

        $response = Http::withHeaders([
            'language-id'  => '28',
            'Authorization' => 'Bearer YOUR_TOKEN',
        ])->get($url);

        if (!$response->ok()) {
            return "Failed to fetch zones from API.";
        }

        $zones = $response->json('data');

        if (empty($zones)) {
            return "No zones found.";
        }

        foreach ($zones as $zone) {

            $zoneId   = $zone['zone_id'];
            $zoneName = $zone['zone_name'];

            // JSON name (required by your table)
            $nameJson = json_encode([
                'en' => $zoneName,
            ]);

            // Collect city IDs → comma-separated
            $cityIds = collect($zone['cities'] ?? [])
                ->pluck('city_id')
                ->unique()
                ->implode(',');

            // Collect zipcode IDs → comma-separated
            $zipcodeIds = collect($zone['zipcodes'] ?? [])
                ->pluck('zipcode_id')
                ->unique()
                ->implode(',');

            Zone::updateOrCreate(
                ['id' => $zoneId],
                [
                    'name'                    => $nameJson,
                    'serviceable_city_ids'    => $cityIds,        // stores "33,44,66"
                    'serviceable_zipcode_ids' => $zipcodeIds,     // stores "10,11,15"
                    'status'                  => 1,
                ]
            );
        }

        return "Zones synced successfully.";
    }





    private function downloadAvatar($user, $data)
    {
        if (!isset($data['avatar']) || empty($data['avatar']) || $data['avatar'] === 'NULL') {
            return;
        }

        try {
            $imageUrl = "https://your-external-domain.com/uploads/avatars/" . $data['avatar'];

            $imageResponse = Http::timeout(10)->get($imageUrl);

            if (!$imageResponse->successful()) {
                \Log::warning("Failed to download avatar", [
                    'user_id' => $user->id,
                    'url'      => $imageUrl
                ]);
                return;
            }

            $filename = 'users/' . $user->id . '/' . $data['avatar'];

            Storage::disk('public')->put($filename, $imageResponse->body());

            // Update user record
            $user->update(['avatar' => $filename]);
        } catch (\Exception $e) {
            \Log::error("Avatar download exception: " . $e->getMessage());
        }
    }


    /* --------------------------------------------------------------------- *
     *  Helper: download an image and return the relative path               *
     * --------------------------------------------------------------------- */
    private function downloadImage(string $url, string $folder, string $slug, array &$logs): ?string
    {
        try {
            $imageName = basename(parse_url($url, PHP_URL_PATH));
            $tempDir   = storage_path("app/public/{$folder}");

            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0777, true);
            }

            $imageContent = @file_get_contents($url);
            if ($imageContent === false) {
                $logs[] = "Warning: Image not accessible for {$slug}";
                return null;
            }

            $relativePath = "{$folder}/{$imageName}";
            file_put_contents($tempDir . '/' . $imageName, $imageContent);

            return '/' . $relativePath;
        } catch (Throwable $e) {
            Log::warning("Failed image download for {$slug}: " . $e->getMessage());
            $logs[] = "Warning: Image download failed for {$slug}";
            return null;
        }
    }
}
