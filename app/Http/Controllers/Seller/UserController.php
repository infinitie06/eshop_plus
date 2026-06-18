<?php

namespace App\Http\Controllers\Seller;

use App\Models\City;
use App\Models\ComboProduct;
use App\Models\Favorite;
use App\Models\Media;
use App\Models\OrderItems;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerCommission;
use App\Models\SellerStore;
use App\Models\StorageType;
use App\Models\Store;
use App\Models\User;
use App\Models\Zipcode;
use App\Models\Zone;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Services\TranslationService;
use App\Services\StoreService;
use App\Services\MediaService;
class UserController extends Controller
{

    public function edit($id)
    {
        //dd($id);
        $seller_data = User::find($id);
        //dd($seller_data);
        $store_id = app(StoreService::class)->getStoreId();

        $store_data = SellerStore::with(['seller', 'zipcode', 'city'])
            ->where('store_id', $store_id)
            ->where('user_id', $id)
            ->get();

        // Check if seller has any store
        $seller_store_exists = SellerStore::where('user_id', $id)->exists();

        // dd($store_data[0]->zipcode);

        $language_code = app(TranslationService::class)->getLanguageCode();
        $user = User::find($id);
        $zipcodes = Zipcode::orderBy('id', 'desc')->get();

        $cities = City::orderBy('id', 'desc')->get();
        $note_for_necessary_documents = fetchDetails(Store::class, ['id' => $store_id], 'note_for_necessary_documents');
        $note_for_necessary_documents = isset($note_for_necessary_documents) && $note_for_necessary_documents[0]->note_for_necessary_documents != null ? $note_for_necessary_documents[0]->note_for_necessary_documents : "Other Documents";

        // Get all available stores for store creation
        $available_stores = Store::where('status', 1)->get();

        //dd($store_data);
        return view('seller.pages.forms.account', compact('seller_data', 'store_data', 'store_id', 'zipcodes', 'cities', 'note_for_necessary_documents', 'language_code', 'seller_store_exists', 'available_stores'));
    }

    public function update(Request $request, $id, $fromApp = false)
    {

        $seller_data = User::find($id);
        $seller_id = Seller::where('user_id', $id)->value('id');
        $user = User::find($id);

        if (!$seller_data) {
            return response()->json(['error' => true, 'message' => 'Seller not found'], 404);
        } else {
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'email' => 'required',
                'address' => 'required',
                'store_name' => 'required',
                'account_number' => 'required',
                'account_name' => 'required',
                'bank_name' => 'required',
                'bank_code' => 'required',
            ]);
            if (!empty($request->input('old_password')) || !empty($request->input('new_password'))) {
                $validator = Validator::make($request->all(), [
                    'old_password' => 'required',
                    'password' => 'required',
                    'confirm_password' => 'required|same:password',
                ]);
            }

            if (!empty($request->input('old_password'))) {
                if (!Hash::check($request->old_password, $user->password)) {
                    if ($request->ajax()) {
                        return response()->json(['message' => labels('admin_labels.incorrect_old_password', 'The old password is incorrect.')], 422);
                    }
                }
            }
            if ($request->filled('new')) {
                $request['password'] = bcrypt($request->input('password'));
            }
            if ($validator->fails()) {
                $errors = $validator->errors();

                if ($request->ajax()) {
                    return response()->json(['errors' => $errors->all()], 422);
                } else {
                    $response = [
                        'error' => true,
                        'message' => $validator->errors()->first(),
                        'code' => 102,
                    ];
                    return response()->json($response);
                }
            }

            $seller = Seller::find($seller_id);
            $store_id = app(StoreService::class)->getStoreId();
            $disk = $seller->disk; // Example disk (filesystem) from which you want to delete the file


            $media_storage_settings = fetchDetails(StorageType::class, ['is_default' => 1], '*');
            $current_disk = !$media_storage_settings->isEmpty() ? $media_storage_settings[0]->name : 'public';
            $imagePath = '';



            $user_data = [
                'role_id' => 4,
                'active' => 1,

                'address' => $request->address,
                'username' => $request->name,
                'mobile' => $request->mobile ?? $user->mobile,
                'email' => $request->email ?? $user->email,
                'image' => $imagePath,
            ];
            if ($request->filled('password')) {
                $user_data['password'] = $request->input('password');
            }
            $user = User::find($id);
            $user->update($user_data);

            $seller_data = [];
            $seller_store_data = [];


            $seller = Seller::find($seller_id);



            $seller_store_detail = SellerStore::where('seller_id', $seller_id)
                ->where('store_id', $store_id)->get();




            try {
                if ($request->hasFile('other_documents')) {
                    // Retrieve existing files from the database
                    $existing_documents = json_decode($seller_store_detail[0]->other_documents, true) ?? [];

                    $other_documents = $request->file('other_documents');
                    $other_document_file_names = [];

                    foreach ($other_documents as $file) {
                        $uploadedFile = $seller->addMedia($file)
                            ->sanitizingFileName(function ($fileName) {
                                $sanitizedFileName = strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));
                                $uniqueId = time() . '_' . mt_rand(1000, 9999);
                                $extension = pathinfo($sanitizedFileName, PATHINFO_EXTENSION);
                                $baseName = pathinfo($sanitizedFileName, PATHINFO_FILENAME);

                                return "{$baseName}-{$uniqueId}.{$extension}";
                            })
                            ->toMediaCollection('sellers', $current_disk);

                        $other_document_file_names[] = $uploadedFile->file_name;
                        $mediaIds[] = $uploadedFile->id;
                    }

                    // Merge new files with existing ones
                    $all_other_documents = array_merge($existing_documents, $other_document_file_names);
                } else {
                    // If no new files are uploaded, keep old ones
                    $all_other_documents = json_decode($seller_store_detail[0]->other_documents, true) ?? [];
                }
                if ($request->hasFile('address_proof')) {

                    // Specify the path and disk from which you want to delete the file
                    if ($disk == 's3') {
                        $path = $request->edit_address_proof;
                    } else {
                        $path = 'sellers/' . $seller_store_detail[0]->address_proof; // Example path to the file you want to delete
                    }

                    // Call the removeFile method to delete the file
                    app(MediaService::class)->removeMediaFile($path, $disk);

                    $addressProofFile = $request->file('address_proof');

                    $address_proof = $seller->addMedia($addressProofFile)
                        ->sanitizingFileName(function ($fileName) use ($seller) {
                            // Replace special characters and spaces with hyphens
                            $sanitizedFileName = strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));
                            // Generate a unique identifier based on timestamp and random component
                            $uniqueId = time() . '_' . mt_rand(1000, 9999);
                            $extension = pathinfo($sanitizedFileName, PATHINFO_EXTENSION);
                            $baseName = pathinfo($sanitizedFileName, PATHINFO_FILENAME);

                            return "{$baseName}-{$uniqueId}.{$extension}";
                        })
                        ->toMediaCollection('sellers', $current_disk);

                    $mediaIds[] = $address_proof->id;
                }
                if ($request->hasFile('store_logo')) {

                    if ($disk == 's3') {
                        $path = $request->edit_store_logo;
                    } else {
                        $path = 'sellers/' . $seller_store_detail[0]->logo; // Example path to the file you want to delete
                    }

                    // Call the removeFile method to delete the file
                    app(MediaService::class)->removeMediaFile($path, $disk);


                    $storeLogoFile = $request->file('store_logo');

                    $store_logo = $seller->addMedia($storeLogoFile)
                        ->sanitizingFileName(function ($fileName) use ($seller) {
                            // Replace special characters and spaces with hyphens
                            $sanitizedFileName = strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));
                            // Generate a unique identifier based on timestamp and random component
                            $uniqueId = time() . '_' . mt_rand(1000, 9999);
                            $extension = pathinfo($sanitizedFileName, PATHINFO_EXTENSION);
                            $baseName = pathinfo($sanitizedFileName, PATHINFO_FILENAME);

                            return "{$baseName}-{$uniqueId}.{$extension}";
                        })
                        ->toMediaCollection('sellers', $current_disk);

                    $mediaIds[] = $store_logo->id;
                }

                if ($request->hasFile('store_thumbnail')) {

                    if ($disk == 's3') {
                        $path = $request->edit_store_thumbnail;
                    } else {
                        $path = 'sellers/' . $seller_store_detail[0]->store_thumbnail; // Example path to the file you want to delete
                    }

                    // Call the removeFile method to delete the file
                    app(MediaService::class)->removeMediaFile($path, $disk);

                    $storeThumbnailFile = $request->file('store_thumbnail');

                    $store_thumbnail = $seller->addMedia($storeThumbnailFile)
                        ->sanitizingFileName(function ($fileName) use ($seller) {
                            // Replace special characters and spaces with hyphens
                            $sanitizedFileName = strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));
                            // Generate a unique identifier based on timestamp and random component
                            $uniqueId = time() . '_' . mt_rand(1000, 9999);
                            $extension = pathinfo($sanitizedFileName, PATHINFO_EXTENSION);
                            $baseName = pathinfo($sanitizedFileName, PATHINFO_FILENAME);

                            return "{$baseName}-{$uniqueId}.{$extension}";
                        })
                        ->toMediaCollection('sellers', $current_disk);

                    $mediaIds[] = $store_thumbnail->id;
                }


                if ($request->hasFile('authorized_signature')) {


                    if ($disk == 's3') {
                        $path = $request->edit_authorized_signature;
                    } else {
                        $path = 'sellers/' . $seller->authorized_signature; // Example path to the file you want to delete
                    }

                    app(MediaService::class)->removeMediaFile($path, $disk);

                    $authorizedSignatureFile = $request->file('authorized_signature');

                    $authorized_signature = $seller->addMedia($authorizedSignatureFile)
                        ->sanitizingFileName(function ($fileName) use ($seller) {
                            // Replace special characters and spaces with hyphens
                            $sanitizedFileName = strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));
                            // Generate a unique identifier based on timestamp and random component
                            $uniqueId = time() . '_' . mt_rand(1000, 9999);
                            $extension = pathinfo($sanitizedFileName, PATHINFO_EXTENSION);
                            $baseName = pathinfo($sanitizedFileName, PATHINFO_FILENAME);

                            return "{$baseName}-{$uniqueId}.{$extension}";
                        })
                        ->toMediaCollection('sellers', $current_disk);


                    $mediaIds[] = $authorized_signature->id;
                }

                if ($request->hasFile('national_identity_card')) {

                    if ($disk == 's3') {
                        $path = $request->edit_national_identity_card;
                    } else {
                        $path = 'sellers/' . $seller->national_identity_card; // Example path to the file you want to delete
                    }

                    // Call the removeFile method to delete the file
                    app(MediaService::class)->removeMediaFile($path, $disk);

                    $nationalIdentityCardFile = $request->file('national_identity_card');

                    $national_identity_card = $seller->addMedia($nationalIdentityCardFile)
                        ->sanitizingFileName(function ($fileName) use ($seller) {
                            // Replace special characters and spaces with hyphens
                            $sanitizedFileName = strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));
                            // Generate a unique identifier based on timestamp and random component
                            $uniqueId = time() . '_' . mt_rand(1000, 9999);
                            $extension = pathinfo($sanitizedFileName, PATHINFO_EXTENSION);
                            $baseName = pathinfo($sanitizedFileName, PATHINFO_FILENAME);

                            return "{$baseName}-{$uniqueId}.{$extension}";
                        })
                        ->toMediaCollection('sellers', $current_disk);

                    $mediaIds[] = $national_identity_card->id;
                }


                //code for storing s3 object url for media

                if ($current_disk == 's3') {
                    $media_list = $seller->getMedia('sellers');
                    for ($i = 0; $i < count($mediaIds); $i++) {
                        $media_url = $media_list[($media_list->count()) - (count($mediaIds) - $i)]->getUrl();

                        $fileName = implode('/', array_slice(explode('/', $media_url), -1));

                        if (isset($address_proof->file_name) && $fileName == $address_proof->file_name) {
                            $address_proof_url = $media_url;
                        }
                        if (isset($store_logo->file_name) && $fileName == $store_logo->file_name) {
                            $logo_url = $media_url;
                        }
                        if (isset($store_thumbnail->file_name) && $fileName == $store_thumbnail->file_name) {
                            $store_thumbnail_url = $media_url;
                        }
                        if (isset($authorized_signature->file_name) && $fileName == $authorized_signature->file_name) {
                            $authorized_signature_url = $media_url;
                        }
                        if (isset($national_identity_card->file_name) && $fileName == $national_identity_card->file_name) {
                            $national_identity_card_url = $media_url;
                        }

                        Media::destroy($mediaIds[$i]);
                    }
                }
            } catch (Exception $e) {
                return response()->json([
                    'error' => true,
                    'message' => $e->getMessage(),
                ]);
            }

            if (isset($address_proof->file_name)) {
                $seller_store_data['address_proof'] = $current_disk == 's3' ? (isset($address_proof_url) ? $address_proof_url : '') : (isset($address_proof->file_name) ? '/' . $address_proof->file_name : '');
            } else {
                $seller_store_data['address_proof'] = $request->edit_address_proof;
            }

            if (isset($store_logo->file_name)) {
                $seller_store_data['logo'] = $current_disk == 's3' ? (isset($logo_url) ? $logo_url : '') : (isset($store_logo->file_name) ? '/' . $store_logo->file_name : '');
            } else {
                $seller_store_data['logo'] = $request->edit_store_logo;
            }
            $seller_store_data['other_documents'] = json_encode($all_other_documents);
            if (isset($store_thumbnail->file_name)) {
                $seller_store_data['store_thumbnail'] = $current_disk == 's3' ? (isset($store_thumbnail_url) ? $store_thumbnail_url : '') : (isset($store_thumbnail->file_name) ? '/' . $store_thumbnail->file_name : '');
            } else {
                $seller_store_data['store_thumbnail'] = $request->edit_store_thumbnail;
            }

            if (isset($authorized_signature->file_name)) {
                $seller_data['authorized_signature'] = $current_disk == 's3' ? (isset($authorized_signature_url) ? $authorized_signature_url : '') : (isset($authorized_signature->file_name) ? '/' . $authorized_signature->file_name : '');
            } else {
                $seller_data['authorized_signature'] = $request->edit_authorized_signature;
            }

            if (isset($national_identity_card->file_name)) {
                $seller_data['national_identity_card'] = $current_disk == 's3' ? (isset($national_identity_card_url) ? $national_identity_card_url : '') : (isset($national_identity_card->file_name) ? '/' . $national_identity_card->file_name : '');
            } else {
                $seller_data['national_identity_card'] = $request->edit_national_identity_card;
            }

            $seller_data = array_merge($seller_data, [
                'status' => 1,
                'pan_number' => $request->pan_number,
            ]);



            $seller->update($seller_data);

            $updated_seller = Seller::where('user_id', $id)->first();

            $new_name = $request->store_name;
            $current_name = $seller_store_detail[0]->store_name;
            $current_slug = $seller_store_detail[0]->slug;
            if ($fromApp == true) {
                $zones = $request->deliverable_zones;
            } else {
                $zones = implode(',', (array) $request->deliverable_zones);
            }
            $seller_store_data = array_merge($seller_store_data, [
                'store_name' => $request->store_name,
                'store_url' => $request->store_url,
                'store_description' => $request->description,
                'commission' => $request->global_commission ?? 0,
                'account_number' => $request->account_number,
                'account_name' => $request->account_name,
                'bank_name' => $request->bank_name,
                'bank_code' => $request->bank_code,
                'status' => 1,
                'tax_name' => $request->tax_name,
                'tax_number' => $request->tax_number,
                'slug' => generateSlug($new_name, 'seller_store', 'slug', $current_slug, $current_name),
                'store_id' => $store_id,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'city' => $request->city ?? "",
                'zipcode' => $request->zipcode ?? "",
                'deliverable_type' => isset($request->deliverable_type) ? $request->deliverable_type : '',
                'deliverable_zones' => ($request->deliverable_type == '1' || $request->deliverable_type == '0') ? '' : $zones,
            ]);

            $seller_store = SellerStore::where('seller_id', $seller_id)
                ->where('store_id', $store_id)
                ->update($seller_store_data);


            if ($request->ajax()) {
                return response()->json(['message' => labels('admin_labels.profile_details_updated_successfully', 'Profile details updated successfully!')]);
            }
        }
    }
    public function seller_zones_data(Request $request)
    {
        $store_id = app(StoreService::class)->getStoreId();
        $search = trim($request->input('search'));
        $user = Auth::user();
        $seller_id = Seller::where('user_id', $user->id)->value('id');
        $limit = (int) $request->input('limit', 50);
        $seller_zones = fetchDetails(SellerStore::class, ['seller_id' => $seller_id, 'store_id' => $store_id], ['deliverable_type', 'deliverable_zones']);
        $seller_zones = isset($seller_zones) && !empty($seller_zones) ? $seller_zones[0] : [];

        $query = Zone::where('status', 1)
            ->where(function ($query) use ($search) {
                $query->where('id', 'like', '%' . $search . '%')
                    ->orWhere('name', 'like', '%' . $search . '%');
            });

        if ($seller_zones->deliverable_type == '2' || $seller_zones->deliverable_type == '3') {
            $zone_ids = explode(',', $seller_zones->deliverable_zones);
            // dd($zone_ids);
            $query->whereIn('id', $zone_ids);
        }

        $zones = $query->limit($limit)->get(['id', 'name', 'serviceable_city_ids', 'serviceable_zipcode_ids']);
        // dd($zones);
        $total = $query->count();

        $cities = [];
        $zipcodes = [];

        foreach ($zones as $zone) {
            $city_ids = explode(',', $zone->serviceable_city_ids);
            $zipcode_ids = explode(',', $zone->serviceable_zipcode_ids);

            $cities = array_unique(array_merge($cities, $city_ids));
            $zipcodes = array_unique(array_merge($zipcodes, $zipcode_ids));
        }

        $city_names = City::whereIn('id', $cities)->pluck('name', 'id')->toArray();

        $zipcode_names = Zipcode::whereIn('id', $zipcodes)->pluck('zipcode', 'id')->toArray();
        $language_code = app(TranslationService::class)->getLanguageCode();
        $response = [
            // dd('here'),
            'total' => $total,
            'results' => $zones->map(function ($zone) use ($city_names, $zipcode_names, $language_code) {
                $city_ids = explode(',', $zone->serviceable_city_ids);
                $zipcode_ids = explode(',', $zone->serviceable_zipcode_ids);

                return [
                    'id' => $zone->id,
                    'text' => app(TranslationService::class)->getDynamicTranslation(Zone::class, 'name', $zone->id, $language_code), // Translate zone name
                    'serviceable_cities' => implode(', ', array_map(function ($city_id) use ($city_names, $language_code) {
                        return app(TranslationService::class)->getDynamicTranslation(City::class, 'name', $city_id, $language_code) ?? ($city_names[$city_id] ?? null);
                    }, $city_ids)), // Translate city names
                    'serviceable_zipcodes' => implode(', ', array_map(function ($zipcode_id) use ($zipcode_names) {
                        return $zipcode_names[$zipcode_id] ?? null;
                    }, $zipcode_ids)), // Zipcode remains unchanged
                ];
            }),
        ];

        return response()->json($response);
    }

    public function stores()
    {
        $user_id = Auth::id();
        $seller_id = Seller::where('user_id', $user_id)->value('id');

        $stores = SellerStore::where('seller_id', $seller_id)
            ->where('user_id', $user_id)
            ->orderBy('store_id', 'asc')
            ->get();

        return view('seller.pages.tables.stores', compact('stores'));
    }

    public function createStore()
    {
        $user_id = Auth::id();
        $seller_id = Seller::where('user_id', $user_id)->value('id');

        if (!$seller_id) {
            return redirect()->route('seller.home')->with('error', 'Seller not found');
        }

        $store_id = app(StoreService::class)->getStoreId();
        $language_code = app(TranslationService::class)->getLanguageCode();
        $zipcodes = Zipcode::orderBy('id', 'desc')->get();
        $cities = City::orderBy('id', 'desc')->get();
        $available_stores = Store::where('status', 1)->get();

        $note_for_necessary_documents = fetchDetails(Store::class, ['id' => $store_id], 'note_for_necessary_documents');
        $note_for_necessary_documents = isset($note_for_necessary_documents) && $note_for_necessary_documents[0]->note_for_necessary_documents != null ? $note_for_necessary_documents[0]->note_for_necessary_documents : "Other Documents";

        return view('seller.pages.forms.create_store', compact('store_id', 'zipcodes', 'cities', 'available_stores', 'note_for_necessary_documents', 'language_code'));
    }

    public function storeStore(Request $request)
    {
        $user_id = Auth::id();
        $seller = Seller::where('user_id', $user_id)->first();

        if (!$seller) {
            return response()->json([
                'error' => true,
                'message' => 'Seller not found',
                'language_message_key' => 'seller_not_found'
            ], 404);
        }

        $rules = [
            'store_id' => 'required|exists:stores,id',
            'store_name' => 'required',
            'account_number' => 'required',
            'account_name' => 'required',
            'bank_name' => 'required',
            'bank_code' => 'required',
            'city' => 'required',
            'zipcode' => 'required',
            'deliverable_type' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['errors' => $validator->errors()->all()], 422);
            } else {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }
        }

        $store_id = $request->input('store_id');

        // Check if seller already has a store with this store_id
        $existing_store = SellerStore::where('seller_id', $seller->id)
            ->where('store_id', $store_id)
            ->where('user_id', $user_id)
            ->first();

        if ($existing_store) {
            return response()->json([
                'error' => true,
                'message' => labels('admin_labels.seller_already_registered', 'Seller already registered in this store.'),
                'language_message_key' => 'seller_already_registered'
            ], 422);
        }

        // Build store data (no media upload handling here to keep it simple/safe)
        $zones = is_array($request->deliverable_zones) ? implode(',', $request->deliverable_zones) : ($request->deliverable_zones ?? '');
        $seller_store_data = [
            'user_id' => $user_id,
            'seller_id' => $seller->id,
            'store_id' => $store_id,
            'store_name' => $request->store_name ?? "",
            'store_url' => $request->store_url ?? "",
            'store_description' => $request->description ?? "",
            'commission' => $request->global_commission ?? 0,
            'account_number' => $request->account_number ?? "",
            'account_name' => $request->account_name ?? "",
            'bank_name' => $request->bank_name ?? "",
            'bank_code' => $request->bank_code ?? "",
            'status' => 0,
            'tax_name' => $request->tax_name ?? "",
            'tax_number' => $request->tax_number ?? "",
            'category_ids' => $request->requested_categories ?? '',
            'permissions' => null,
            'slug' => generateSlug($request->input('store_name'), 'seller_store'),
            'latitude' => $request->latitude ?? "",
            'longitude' => $request->longitude ?? "",
            'city' => $request->city ?? "",
            'zipcode' => $request->zipcode ?? "",
            'disk' => 'public',
            'deliverable_type' => $request->deliverable_type ?? '',
            'deliverable_zones' => ($request->deliverable_type == '1' || $request->deliverable_type == '0') ? '' : $zones,
        ];

        $seller_store = SellerStore::insert($seller_store_data);

        // Fetch fresh data for response
        $store_data = fetchDetails(SellerStore::class, ['user_id' => $user_id], '*');
        $store_exists = !$store_data->isEmpty();
        $isPublicDisk = $store_exists && isset($store_data[0]->disk) && $store_data[0]->disk == 'public' ? 1 : 0;

        $output = [];
        $output['store_data'] = $store_exists
            ? app(\App\Services\SellerService::class)->formatStoreData($store_data, $isPublicDisk, app(\App\Services\TranslationService::class)->getLanguageCode())
            : [];

        if ($seller_store) {
            if (!$request->ajax() && !$request->wantsJson()) {
                return redirect()->route('seller.stores.index')->with('success', 'Store created successfully');
            }
            return response()->json([
                'error' => false,
                'message' => 'Store created successfully',
                'language_message_key' => 'store_registered_successfully',
                'data' => $output,
            ]);
        } else {
            if (!$request->ajax() && !$request->wantsJson()) {
                return redirect()->back()->withInput()->with('error', 'Failed to create store');
            }
            return response()->json([
                'error' => true,
                'message' => 'Failed to create store',
                'language_message_key' => 'something_went_wrong',
                'data' => [],
            ], 422);
        }
    }

    public function deactivateStore(Request $request)
    {
        $store_id = $request->input('store_id', app(StoreService::class)->getStoreId());
        $user_id = Auth::id();

        $seller_id = Seller::where('user_id', $user_id)->value('id');

        if (!$seller_id || !$store_id) {
            return response()->json([
                'error' => true,
                'message' => labels('admin_labels.seller_or_store_not_found', 'Seller or Store not found.'),
            ]);
        }

        $seller_store = SellerStore::where('seller_id', $seller_id)
            ->where('store_id', $store_id)
            ->where('user_id', $user_id)
            ->first();

        if (!$seller_store) {
            return response()->json([
                'error' => true,
                'message' => labels('admin_labels.store_not_found_for_seller', 'Store not found for this seller.'),
            ]);
        }

        if ($seller_store->status == 0) {
            return response()->json([
                'error' => true,
                'message' => labels('admin_labels.store_already_deactivated', 'Store is already deactivated.'),
            ]);
        }

        $seller_store->status = 0;
        $seller_store->save();

        return response()->json([
            'error' => false,
            'message' => labels('admin_labels.store_deactivated_successfully', 'Store deactivated successfully.'),
        ]);
    }

    public function deleteStore(Request $request)
    {
        $store_id = $request->input('store_id', app(StoreService::class)->getStoreId());
        $user_id = Auth::id();

        $seller_id = Seller::where('user_id', $user_id)->value('id');

        if (!$seller_id || !$store_id) {
            return response()->json([
                'error' => true,
                'message' => labels('admin_labels.seller_or_store_not_found', 'Seller or Store not found.'),
            ]);
        }

        $seller_store = SellerStore::where('seller_id', $seller_id)
            ->where('store_id', $store_id)
            ->where('user_id', $user_id)
            ->first();

        if (!$seller_store) {
            return response()->json([
                'error' => true,
                'message' => labels('admin_labels.store_not_found_for_seller', 'Store not found for this seller.'),
            ]);
        }

        // Check if store has any data
        $hasProducts = Product::where('seller_id', $seller_id)
            ->where('store_id', $store_id)
            ->exists();

        $hasComboProducts = ComboProduct::where('seller_id', $seller_id)
            ->where('store_id', $store_id)
            ->exists();

        $hasOrderItems = OrderItems::where('seller_id', $seller_id)
            ->where('store_id', $store_id)
            ->exists();

        $hasFavorites = Favorite::where('seller_id', $seller_id)->exists();

        $hasCommissions = SellerCommission::where('seller_id', $seller_id)
            ->where('store_id', $store_id)
            ->exists();

        if ($hasProducts || $hasComboProducts || $hasOrderItems || $hasFavorites || $hasCommissions) {
            $dataTypes = [];
            if ($hasProducts) {
                $dataTypes[] = 'products';
            }
            if ($hasComboProducts) {
                $dataTypes[] = 'combo products';
            }
            if ($hasOrderItems) {
                $dataTypes[] = 'orders';
            }
            if ($hasFavorites) {
                $dataTypes[] = 'favorites';
            }
            if ($hasCommissions) {
                $dataTypes[] = 'commissions';
            }

            return response()->json([
                'error' => true,
                'message' => labels(
                    'admin_labels.store_cannot_be_deleted_contains_data',
                    'Store cannot be deleted. It contains: ' . implode(', ', $dataTypes)
                ),
                'data' => [
                    'has_products' => $hasProducts,
                    'has_combo_products' => $hasComboProducts,
                    'has_orders' => $hasOrderItems,
                    'has_favorites' => $hasFavorites,
                    'has_commissions' => $hasCommissions,
                ],
            ]);
        }

        $seller_store->delete();

        return response()->json([
            'error' => false,
            'message' => labels('admin_labels.store_deleted_successfully', 'Store deleted successfully.'),
        ]);
    }

    public function activateStore(Request $request)
    {
        $store_id = $request->input('store_id');
        $user_id = Auth::id();

        if (!$store_id) {
            return response()->json([
                'error' => true,
                'message' => labels('admin_labels.store_not_found_for_seller', 'Store not found for this seller.'),
            ]);
        }

        $seller_id = Seller::where('user_id', $user_id)->value('id');

        if (!$seller_id) {
            return response()->json([
                'error' => true,
                'message' => labels('admin_labels.seller_or_store_not_found', 'Seller or Store not found.'),
            ]);
        }

        $seller_store = SellerStore::where('seller_id', $seller_id)
            ->where('store_id', $store_id)
            ->where('user_id', $user_id)
            ->first();

        if (!$seller_store) {
            return response()->json([
                'error' => true,
                'message' => labels('admin_labels.store_not_found_for_seller', 'Store not found for this seller.'),
            ]);
        }

        if ($seller_store->status == 1) {
            return response()->json([
                'error' => true,
                'message' => labels('admin_labels.store_already_active', 'Store is already active.'),
            ]);
        }

        $seller_store->status = 1;
        $seller_store->save();

        return response()->json([
            'error' => false,
            'message' => labels('admin_labels.store_activated_successfully', 'Store activated successfully.'),
        ]);
    }
}
