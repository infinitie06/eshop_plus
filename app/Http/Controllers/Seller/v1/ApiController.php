<?php

namespace App\Http\Controllers\Seller\v1;

use App\Http\Controllers\Admin\AddressController;
use App\Http\Controllers\Admin\CategoryController as AdmincategoryController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\PickupLocationController;
use App\Http\Controllers\Admin\SellerController;
use App\Http\Controllers\Admin\StoreController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Seller\AreaController;
use App\Http\Controllers\Seller\AttributeController;
use App\Http\Controllers\Seller\CategoryController;
use App\Http\Controllers\Seller\ComboProductController;
use App\Http\Controllers\Seller\ComboProductFaqController;
use App\Http\Controllers\Seller\ComboProductRatingController;
use App\Http\Controllers\Seller\MediaController;
use App\Http\Controllers\Seller\OrderController as SellerOrderController;
use App\Http\Controllers\Seller\PaymentRequestController;
use App\Http\Controllers\Seller\ProductController;
use App\Http\Controllers\Seller\ProductFaqController;
use App\Http\Controllers\Seller\ReportController;
use App\Http\Controllers\Seller\ReturnRequestController;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ComboProduct;
use App\Models\ComboProductFaq;
use App\Models\Currency;
use App\Models\Favorite;
use App\Models\Language;
use App\Models\Media;
use App\Models\Order;
use App\Models\OrderBankTransfers;
use App\Models\OrderItems;
use App\Models\OrderTracking;
use App\Models\Parcel;
use App\Models\PickupLocation;
use App\Models\Product;
use App\Models\Product_attributes;
use App\Models\Product_variants;
use App\Models\ProductFaq;
use App\Models\ReturnRequest;
use App\Models\Seller;
use App\Models\SellerCommission;
use App\Models\SellerStore;
use App\Models\StorageType;
use App\Models\CustomField;
use App\Models\Store;
use App\Models\Tax;
use App\Models\User;
use App\Models\UserFcm;
use App\Models\Zipcode;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use App\Services\ProductService;
use App\Services\ComboProductService;
use Illuminate\Support\Str;
use App\Services\TranslationService;
use App\Services\CartService;
use App\Services\SellerService;
use App\Traits\HandlesValidation;
use App\Services\MediaService;
use App\Services\ShiprocketService;
use App\Services\ParcelService;
use App\Services\SettingService;
use App\Services\StoreService;
use App\Services\OrderService;
use Illuminate\Validation\Rule;

/*
---------------------------------------------------------------------------
Defined Methods:-
---------------------------------------------------------------------------
1. login
2  register
3. update_user
4. verify_user
5. get_orders
6. get_order_items
7. update_order_item_status
8. get_categories
9. get_products
10. get_transactions
11. get_statistics
12. update_fcm
13. get_cities
14. get_zipcodes
15. get_taxes
16. send_withdrawal_request
17. get_withdrawal_request
18. get_attributes
19. get_attribute_values
20. get_media
21. add_products
22. get_seller_details
23. delete_product
24. update_products
25. get_delivery_boys
26. upload_media
27. get_product_rating
28. get_order_tracking
29. edit_order_tracking
30. get_sales_list
31. update_product_status
32. get_countries_data
33. get_brand_list
34. add_product_faqs
35. get_product_faqs
36. delete_product_faq
37. edit_product_faq
38. manage_stock
39. add_pickup_location
40. get_pickup_locations
41. create_shiprocket_order
42. generate_awb
43. send_pickup_request
44. generate_label
45. generate_invoice
46. cancel_shiprocket_order
47. download_label
48. download_invoice
49. shiprocket_order_tracking
50. get_shiprocket_order
51. delete_order
52. get_settings
53. delete_seller
54. get_stores
55. get_combo_products
56. add_combo_product
57. delete_combo_product
58. update_combo_product
59. get_languages
60. get_language_labels

<---- Newly Added for parcel ---->
61. get_all_parcels
62. create_order_parcel
63. delete_order_parcel
64. update_parcel_order_status
65. update_shiprocket_order_status
66. download_parcel_invoice
<---- Newly Added for parcel ---->

*/

class ApiController extends Controller
{
    use HandlesValidation;
    /**
     * Check if a seller's store is active.
     *
     * @param  int  $seller_id
     * @param  int  $store_id
     * @return \Illuminate\Http\JsonResponse|null
     */
    protected function ensureActiveStore($seller_id, $store_id)
    {
        $seller_store = SellerStore::where('seller_id', $seller_id)
            ->where('store_id', $store_id)
            ->first();

        if (!$seller_store) {
            return response()->json([
                'error' => true,
                'message' => 'Store not found for this seller',
                'language_message_key' => 'store_not_found_for_seller',
            ]);
        }

        if ((int) $seller_store->status !== 1) {
            return response()->json([
                'error' => true,
                'message' => 'Store is deactivated. Only orders can be accessed.',
                'language_message_key' => 'store_is_deactivated_only_orders_can_be_accessed',
            ]);
        }

        return null;
    }

    public function login(Request $request)
    {
        /*
        country_code: +91
        mobile: 9874565478
        password: 12345678
        fcm_id: FCM_ID // optional
    */

        $rules = [
            'country_code' => 'required|string',
            'mobile' => 'required|numeric',
            'password' => 'required',
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $language_code = $request->attributes->get('language_code');

        // Find user using country_code and mobile
        $user = \App\Models\User::where('country_code', $request->country_code)
            ->where('mobile', $request->mobile)
            ->where('role_id', 4)
            ->first();

        if ($user && Hash::check($request->password, $user->password)) {
            Auth::login($user);

            $fcm_ids = fetchDetails(UserFcm::class, ['user_id' => $user->id], 'fcm_id');
            $fcm_ids_array = array_map(fn($item) => $item->fcm_id, $fcm_ids->all());
            $token = $user->createToken('authToken')->plainTextToken;

            $userData = app(SellerService::class)->formatUserData($user, $fcm_ids_array);
            $seller_data = fetchDetails(Seller::class, ['user_id' => $user->id], '*')->toArray();
            $store_data = fetchDetails(SellerStore::class, ['user_id' => $user->id], '*');
            $store_exists = !$store_data->isEmpty();

            $data = array_merge($userData, (array) $seller_data);
            $output = $userData;
            unset($seller_data[0]['id']);
            $isPublicDisk = $store_exists && isset($store_data[0]->disk) && $store_data[0]->disk == 'public' ? 1 : 0;

            $output['store_data'] = $store_exists
                ? app(SellerService::class)->formatStoreData($store_data, $isPublicDisk, $language_code)
                : [];
            $output['seller_data'] = array_map(
                fn($seller) => (array) $seller,
                app(SellerService::class)->formatSellerData($seller_data, $isPublicDisk)
            );

            foreach ($data as $key => $value) {
                if (array_key_exists($key, !empty($seller_data) ? $seller_data[0] : '')) {
                    $output[$key] = $value;
                }
            }

            if ($user->role_id == 4) {
                if ($request->filled('fcm_id')) {
                    $existing_fcm = UserFcm::where('user_id', $user->id)
                        ->where('fcm_id', $request->fcm_id)
                        ->first();

                    if (!$existing_fcm) {
                        UserFcm::insert([
                            'fcm_id' => $request->fcm_id,
                            'user_id' => $user->id,
                        ]);
                    }
                }

                $messages = [
                    "0" => "Your account is deactivated",
                    "1" => "User Logged in successfully",
                    "2" => "Your account is not yet approved.",
                    "7" => "Your account has been removed by the admin. Contact admin for more information."
                ];
                $language_message_key = [
                    "0" => "account_deactivated",
                    "1" => "user_logged_in_successfully",
                    "2" => "account_not_yet_approved",
                    "7" => "account_removed_by_admin_contact_admin"
                ];

                // Get store_status from seller_store table
                $seller_store = SellerStore::where('user_id', $user->id)->first();
                $store_Status = $seller_store ? $seller_store->status : null;
                $store_add_endpoint = url('seller-api/add_seller_store');
                $status = $seller_data[0]['status'] ?? null;

                // Prepare store creation information if store doesn't exist
                $store_creation_info = null;
                if (!$store_exists && $status == 1) {
                    $store_creation_info = [
                        'can_create_store' => true,
                        'endpoint' => $store_add_endpoint,
                        'method' => 'POST',
                        'message' => 'You can create a store using the provided endpoint.',
                        'language_message_key' => 'can_create_store_using_endpoint',
                        'required_fields' => [
                            'store_id',
                            'mobile',
                            'store_name',
                            'account_number',
                            'account_name',
                            'bank_name',
                            'bank_code',
                            'city',
                            'zipcode',
                            'deliverable_type'
                        ]
                    ];
                }

                $response = [
                    'error' => isset($seller_data[0]['status']) && $seller_data[0]['status'] == 1 ? false : true,
                    'message' => $messages[$seller_data[0]['status']],
                    'language_message_key' => $language_message_key[$seller_data[0]['status']],
                    'token' => $token,
                    'data' => isset($seller_data[0]['status']) && $seller_data[0]['status'] == 1 ? $output : [],
                    'store_Status' => $store_Status,
                    'store_exists' => $store_exists,
                    'store_add_endpoint' => $store_add_endpoint,
                ];

                // Add store creation info if store doesn't exist
                if ($store_creation_info) {
                    $response['store_creation_info'] = $store_creation_info;
                }

                return response()->json($response);
            }

            return response()->json([
                'error' => true,
                'message' => 'Incorrect Login.',
                'language_message_key' => 'incorrect_login.',
            ]);
        }

        return response()->json([
            'error' => true,
            'message' => 'Invalid credentials',
            'language_message_key' => 'invalid_credentials',
        ], 401);
    }


    public function register(SellerController $sellerController, Request $request)
    {
        /*
        Example Inputs:
        name: test
        country_code: +91
        mobile: 9874565478
        email: test@gmail.com
        password: 12345
        confirm_password: 12345
        address: 237, TimeSquare
        address_proof: FILE
        national_identity_card: FILE
        store_ids: 1,3
        store_name: eshop store
        store_logo: FILE
        authorized_signature: FILE
        store_url: url
        store_description: test
        tax_name: GST
        tax_number: GSTIN6786
        pan_number: GNU876
        account_number: 123esdf
        account_name: name
        bank_code: INBsha23
        bank_name: bank name
        city: 1
        zipcode: 360001
        deliverable_type: all
    */

        $rules = [
            'name' => 'required|string|max:255',
            'country_code' => 'required|string|max:10',
            'mobile' => [
                'required',
                'numeric',
                Rule::unique('users', 'mobile')->where(function ($query) use ($request) {
                    return $query->where('country_code', $request->country_code);
                }),
            ],
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:5',
            'confirm_password' => 'required|same:password',
            'address' => 'required|string|max:500',
            'store_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'bank_code' => 'required|string|max:255',
            'city' => 'required',
            'zipcode' => 'required',
            'deliverable_type' => 'required',
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $seller_data = $sellerController->store($request, true);

        if (isset($seller_data->original['id']) && !empty($seller_data->original['id'])) {
            return response()->json([
                'error' => false,
                'message' => 'Seller registered successfully. Wait for admin approval.',
                'language_message_key' => 'seller_registered_successfully_wait_for_approval',
            ]);
        } else {
            return response()->json([
                'error' => $seller_data->original['error'] ?? true,
                'message' => $seller_data->original['message'] ?? ($seller_data->original['error_message'] ?? 'Something went wrong'),
                'language_message_key' => $seller_data->original['language_message_key'] ?? 'something_went_wrong',
            ]);
        }
    }

    public function update_user(Request $request, SellerController $sellerController)
    {
        /*
            id:34  {seller's user_id}
            name:hiten
            mobile:7852347890
            email:amangoswami@gmail.com
            old:12345                       //{if want to change password}
            new:345234                      //{if want to change password}
            address:test
            store_ids:1,2
            store_name:storename
            store_url:url
            store_description:test
            account_number:123esdf
            account_name:name
            bank_code:INBsha23
            bank_name:bank name
            latitude:+37648
            longitude:-478237
            tax_name:GST
            tax_number:GSTIN6786
            pan_number:GNU876
            status:1 | 0                  //{1: active | 0:deactive}
            store_logo: file              // {pass if want to change}
            national_identity_card: file              // {pass if want to change}
            address_proof: file              // {pass if want to change}
            authorized_signature:FILE // {pass if want to change}
        */


        if (!empty($request->input('old')) || !empty($request->input('new'))) {
            $rules = [
                'old' => 'required',
                'new' => 'required',
            ];
            if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
                return $response;
            }
        }

        $user_id = auth()->user()->id;

        if ($request->has('is_notification_on')) {
            User::where('id', $user_id)->update([
                'is_notification_on' => $request->input('is_notification_on')
            ]);
        }

        $store_id = $request->store_id;
        $request['store_id'] = $store_id;

        // dd($request->hasFile('profile_image'));
        $seller_data = $sellerController->update($request, $user_id, true);
        if ($seller_data instanceof \Illuminate\Http\JsonResponse) {
            return $seller_data;
        }
        $user = fetchDetails(User::class, ['id' => $user_id], '*')[0];
        $language_code = $request->attributes->get('language_code');
        $fcm_ids = fetchDetails(UserFcm::class, ['user_id' => $user->id], 'fcm_id');

        $fcm_ids_array = array_map(function ($item) {
            return $item->fcm_id;
        }, $fcm_ids->all());

        $userData = app(SellerService::class)->formatUserData($user, $fcm_ids_array);

        $seller_data = fetchDetails(Seller::class, ['user_id' => $user_id], '*');
        $seller_data = $seller_data->toArray();
        $store_data = fetchDetails(SellerStore::class, ['user_id' => $user->id], '*');
        // dd($store_data);
        $seller_data[0]['seller_id'] = $seller_data[0]['id'];
        $data = (array_merge($userData, (array) $seller_data));
        $output = $userData;
        unset($seller_data[0]['id']);
        $isPublicDisk = $store_data[0]->disk == 'public' ? 1 : 0;
        $output['store_data'] = app(SellerService::class)->formatStoreData($store_data, $isPublicDisk, $language_code);
        $output['seller_data'] = array_map(
            fn($seller) => (array) $seller,
            app(SellerService::class)->formatSellerData($seller_data, $isPublicDisk)
        );
        foreach ($data as $key => $value) {
            if (!empty($seller_data) && array_key_exists($key, $seller_data[0])) {
                $output[$key] = $value;
            }
        }

        unset($output['password']);

        if (!empty($seller_data)) {
            return response()->json([
                'error' => false,
                'message' => 'Seller Update Successfully.',
                'language_message_key' => 'seller_update_successfully',
                'data' => $output,
            ]);
        } else {
            return response()->json([
                'error' => true,
                'message' => 'Seller data not updated',
                'language_message_key' => 'seller_data_not_updated',
                'data' => $seller_data,
            ]);
        }
    }


    public function verify_user(Request $request)
    {
        /* Parameters to be passed
            mobile: 9874565478
            email: test@gmail.com
        */
        $rules = [
            'mobile' => 'required|numeric',
            'email' => 'sometimes|nullable|email',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            $language_code = $request->attributes->get('language_code');
            $mobile = $request->input('mobile');
            $email = $request->input('email');
            $user = null;
            if (isset($mobile) && isExist(['mobile' => $mobile], User::class)) {
                $user = User::where('mobile', $mobile)->first();
            } elseif (isset($email) && isExist(['email' => $email], User::class)) {
                $user = User::where('email', $email)->first();
            }

            if ($user) {
                $token = $user->createToken('authToken')->plainTextToken;
                $fcm_ids = fetchDetails(UserFcm::class, ['user_id' => $user->id], 'fcm_id');

                $fcm_ids_array = array_map(function ($item) {
                    return $item->fcm_id;
                }, $fcm_ids->all());

                $userData = app(SellerService::class)->formatUserData($user, $fcm_ids_array);

                $seller_data = fetchDetails(Seller::class, ['user_id' => $user->id], '*');
                $seller_data = $seller_data->toArray();

                // Include all stores (active + inactive) so deactivated ones are also returned
                $store_data = fetchDetails(SellerStore::class, ['user_id' => $user->id], '*');
                $store_exists = !$store_data->isEmpty();

                $seller_id = !empty($seller_data) ? $seller_data[0]['id'] : "";
                $data = (array_merge($userData, (array) $seller_data));
                $output = $userData;
                unset($seller_data[0]['id']);

                $isPublicDisk = $store_exists && isset($store_data[0]->disk) && $store_data[0]->disk == 'public' ? 1 : 0;
                $output['seller_data'] = array_map(
                    fn($seller) => (array) $seller,
                    app(SellerService::class)->formatSellerData($seller_data, $isPublicDisk)
                );

                $output['store_data'] = $store_exists
                    ? app(SellerService::class)->formatStoreData($store_data, $isPublicDisk, $language_code)
                    : [];


                foreach ($data as $key => $value) {
                    $sellerDataArray = (!empty($seller_data) && isset($seller_data[0])) ? $seller_data[0] : [];
                    if (array_key_exists($key, $sellerDataArray)) {
                        $output[$key] = $value;
                    }
                }

                if ($user->role_id == 4) {
                    if (isset($request->fcm_id) && $request->fcm_id != '') {

                        $fcm_data = [
                            'fcm_id' => $request->fcm_id,
                            'user_id' => $user->id,
                        ];
                        $existing_fcm = UserFcm::where('user_id', $user->id)
                            ->where('fcm_id', $request->fcm_id)
                            ->first();

                        if (!$existing_fcm) {
                            // If it doesn't exist, create a new entry
                            UserFcm::insert($fcm_data);
                        }
                    }
                    unset($data[0]->password);

                    $messages = [
                        "0" => "Your account is deactivated",
                        "1" => "User Logged in successfully",
                        "2" => "Your account is not yet approved.",
                        "7" => "Your account has been removed by the admin. Contact admin for more information."
                    ];

                    $language_message_key = [
                        "0" => "account_deactivated",
                        "1" => "user_logged_in_successfully",
                        "2" => "account_not_yet_approved",
                        "7" => "account_removed_by_admin_contact_admin"
                    ];

                    $status = $seller_data[0]['status'] ?? null;

                    // Get store_status from seller_store table
                    $seller_store = SellerStore::where('user_id', $user->id)->first();
                    $store_Status = $seller_store ? $seller_store->status : null;
                    $store_add_endpoint = url('seller-api/add_seller_store');

                    // Prepare store creation information if store doesn't exist
                    $store_creation_info = null;
                    if (!$store_exists && $status == 1) {
                        $store_creation_info = [
                            'can_create_store' => true,
                            'endpoint' => $store_add_endpoint,
                            'method' => 'POST',
                            'message' => 'You can create a store using the provided endpoint.',
                            'language_message_key' => 'can_create_store_using_endpoint',
                            'required_fields' => [
                                'store_id',
                                'mobile',
                                'store_name',
                                'account_number',
                                'account_name',
                                'bank_name',
                                'bank_code',
                                'city',
                                'zipcode',
                                'deliverable_type'
                            ]
                        ];
                    }

                    // Determine response code based on status
                    $responseCode = in_array($status, [0, 2, 7]) ? 401 : 200;

                    $response = [
                        'error' => $status != 1,
                        'message' => $messages[$status] ?? "Unknown status",
                        'language_message_key' => $language_message_key[$status] ?? "unknown_status",
                        'token' => $status == 1 ? $token : null,
                        'data' => $status == 1 ? $output : [],
                        'store_Status' => $store_Status,
                        'store_exists' => $store_exists,
                        'store_add_endpoint' => $store_add_endpoint,
                    ];

                    // Add store creation info if store doesn't exist
                    if ($store_creation_info) {
                        $response['store_creation_info'] = $store_creation_info;
                    }

                    return response()->json($response, $responseCode);
                } else {
                    return response()->json([
                        'error' => true,
                        'message' => 'Incorrect Login.',
                        'language_message_key' => 'incorrect_login.',
                    ]);
                }
            } else {
                return response()->json([
                    'error' => true,
                    'message' => 'Invalid credentials',
                    'language_message_key' => 'invalid_credentials',
                ], 401);
            }
        }
    }

    public function get_orders(Request $request)
    {
        /*
            store_id : 1
            id:101 { optional }
            city_id:1 { optional }
            area_id:1 { optional }
            user_id:101 { optional }
            start_date : 2020-09-07 or 2020/09/07 { optional }
            end_date : 2021-03-15 or 2021/03/15 { optional }
            search:keyword      // optional
            limit:25            // { default - 25 } optional
            offset:0            // { default - 0 } optional
            sort: id / created_at // { default - id } optional
            order:DESC/ASC      // { default - DESC } optional
            order_type : digital/simple // if type is simple simple and variable product orders are showen AND if type is digital only digital product orders are showen
            active_status: received  {received,delivered,cancelled,processed,returned}     // optional
        */

        $rules = [
            'user_id' => 'numeric|exists:users,id',
            'store_id' => 'required|numeric|exists:stores,id',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            if (auth()->check()) {
                $user_id = auth()->user()->id;
                $seller_id = Seller::where('user_id', $user_id)->value('id');
            }
            $store_id = $request->input('store_id') ? (int) $request->input('store_id') : '';
            $limit = $request->input('limit', 25);
            $offset = $request->input('offset', 0);
            $sort = $request->input('sort', 'o.id');
            $order = $request->input('order', 'DESC');
            $search = $request->input('search', '');
            $id = $request->input('id', false);
            $user_id = $request->input('user_id', false);
            $start_date = $request->input('start_date', false);
            $end_date = $request->input('end_date', false);
            $multiple_status = $request->input('active_status') ? explode(',', $request->input('active_status')) : false;
            $download_invoice = $request->input('download_invoice', 1);
            $city_id = $request->input('city_id', null);
            $area_id = $request->input('area_id', null);
            $order_type = strtolower($request->input('order_type', ''));
            $language_code = $request->attributes->get('language_code');
            $order_details = app(OrderService::class)->fetchOrders(
                $id,
                $user_id,
                $multiple_status,
                '',
                $limit,
                $offset,
                $sort,
                $order,
                $download_invoice,
                $start_date,
                $end_date,
                $search,
                $city_id,
                $area_id,
                $seller_id,
                $order_type,
                '',
                $store_id,
                $language_code
            );
            $items = array();
            if (!$order_details['order_data']->isEmpty()) {
                $response['error'] = false;
                $response['message'] = 'Data retrieved successfully';
                $response['language_message_key'] = 'data_retrieved_successfully';
                $response['total'] = $order_details['total'];
                $response['awaiting'] = strval(app(OrderService::class)->ordersCount("awaiting", $seller_id, $order_type, $store_id));
                $response['received'] = strval(app(OrderService::class)->ordersCount("received", $seller_id, $order_type, $store_id));
                $response['processed'] = strval(app(OrderService::class)->ordersCount("processed", $seller_id, $order_type, $store_id));
                $response['shipped'] = strval(app(OrderService::class)->ordersCount("shipped", $seller_id, $order_type, $store_id));
                $response['delivered'] = strval(app(OrderService::class)->ordersCount("delivered", $seller_id, $order_type, $store_id));
                $response['cancelled'] = strval(app(OrderService::class)->ordersCount("cancelled", $seller_id, $order_type, $store_id));
                $response['returned'] = strval(app(OrderService::class)->ordersCount("returned", $seller_id, $order_type, $store_id));
                $response['data'] = $order_details['order_data'];
            } else {
                $response['error'] = true;
                $response['message'] = 'Data Does Not Exists';
                $response['language_message_key'] = 'data_does_not_exists';
                $response['total'] = "0";
                $response['awaiting'] = "0";
                $response['received'] = "0";
                $response['processed'] = "0";
                $response['shipped'] = "0";
                $response['delivered'] = "0";
                $response['cancelled'] = "0";
                $response['returned'] = "0";
                $response['data'] = array();
            }
            return response()->json($response);
        }
    }

    public function get_order_items(Request $request)
    {
        /*
            store_id:1
            id:101 { optional }
            user_id:101 { optional }
            order_id:101 { optional }
            active_status: received  {received,delivered,cancelled,processed,returned}     // optional
            start_date : 2020-09-07 or 2020/09/07 { optional }
            end_date : 2021-03-15 or 2021/03/15 { optional }
            search:keyword      // optional
            limit:25            // { default - 25 } optional
            offset:0            // { default - 0 } optional
            sort: oi.id / oi.created_at // { default - id } optional
            order:DESC/ASC      // { default - DESC } optional
        */
        $rules = [
            'user_id' => 'numeric|exists:users,id',
            'store_id' => 'required|numeric|exists:stores,id',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            if (auth()->check()) {
                $user_id = auth()->user()->id;
                $seller_id = Seller::where('user_id', $user_id)->value('id');
            }
            $store_id = $request->input('store_id') ? (int) $request->input('store_id') : '';
            $language_code = $request->attributes->get('language_code');
            $limit = $request->input('limit', 25);
            $offset = $request->input('offset', 0);
            $sort = $request->input('sort', 'oi.id');
            $order = $request->input('order', 'DESC');
            $search = $request->input('search', '');
            $id = $request->input('id', false);
            $userId = $request->input('user_id', false);
            $order_id = $request->input('order_id', false);
            $start_date = $request->input('start_date', false);
            $end_date = $request->input('end_date', false);
            $activeStatus = $request->input('active_status');

            // Check if active_status is present and not empty, then split it
            $multipleStatus = (!empty($activeStatus)) ? explode(',', $activeStatus) : false;

            $order_details = app(OrderService::class)->fetchOrderItems($id, $userId, $multipleStatus, false, $limit, $offset, $sort, $order, $start_date, $end_date, $search, $seller_id, $order_id, $store_id, $language_code);

            if (!empty($order_details['order_data'])) {
                $response['error'] = false;
                $response['message'] = 'Data retrieved successfully';
                $response['language_message_key'] = 'data_retrieved_successfully';
                $response['total'] = $order_details['total'];
                $response['awaiting'] = strval(app(OrderService::class)->ordersCount("awaiting", $seller_id));
                $response['received'] = strval(app(OrderService::class)->ordersCount("received", $seller_id));
                $response['processed'] = strval(app(OrderService::class)->ordersCount("processed", $seller_id));
                $response['shipped'] = strval(app(OrderService::class)->ordersCount("shipped", $seller_id));
                $response['delivered'] = strval(app(OrderService::class)->ordersCount("delivered", $seller_id));
                $response['cancelled'] = strval(app(OrderService::class)->ordersCount("cancelled", $seller_id));
                $response['returned'] = strval(app(OrderService::class)->ordersCount("returned", $seller_id));
                $response['data'] = $order_details['order_data'];
            } else {
                $response['error'] = true;
                $response['message'] = 'Data Does Not Exists';
                $response['language_message_key'] = 'data_does_not_exists';
                $response['total'] = "0";
                $response['awaiting'] = "0";
                $response['received'] = "0";
                $response['processed'] = "0";
                $response['shipped'] = "0";
                $response['delivered'] = "0";
                $response['cancelled'] = "0";
                $response['returned'] = "0";
                $response['data'] = array();
            }
            return response()->json($response);
        }
    }

    public function update_order_item_status(Request $request, SellerOrderController $SellerOrderController)
    {
        /*
            order_item_id[]:1 // only when status is cancelled / returned
            order_id:991
            seller_id : 8
            status : received / processed / shipped / delivered / cancelled / returned
            deliver_by: 15 {optional} //pass delivery_boy id
        */
        $rules = [
            'deliver_by' => 'numeric',
            'order_id' => 'numeric|required|exists:orders,id',
        ];
        if ($request->input('status') === 'cancelled' || $request->input('status') === 'returned') {
            $rules = [
                'order_item_id' => 'required',
            ];
        }
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            if (auth()->check()) {
                $user_id = auth()->user()->id;
                $seller_id = Seller::where('user_id', $user_id)->value('id');
            }
            $request['seller_id'] = $seller_id;
            $request['order_item_id'] = explode(',', $request['order_item_id']);
            $orderData = $SellerOrderController->update_order_status($request);
            return response()->json($orderData->original);
        }
    }

    public function get_categories(Request $request, CategoryController $categoryController)
    {
        $rules = [
            'store_id' => 'required|numeric|exists:stores,id',
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        if (auth()->check()) {
            $user_id = auth()->user()->id;
            $seller_id = Seller::where('user_id', $user_id)->value('id');
        }

        $request['seller_id'] = $seller_id;
        $language_code = $request->attributes->get('language_code');
        $cat_res = $categoryController->get_seller_categories($request, $language_code);

        $categories = $cat_res->original['categories'] ?? [];
        $total = $cat_res->original['total'] ?? 0;

        // Filter: categories belonging to seller + status 0 or 2
        $requested_categories = Category::where('store_id', $request->store_id)
            ->where('seller_id', $seller_id)
            ->where('status', 2) // MAIN FILTER
            ->with([
                'children' => function ($q) use ($request) {
                    $q->where('store_id', $request->store_id)
                        ->where('status', 2) // CHILD FILTER
                        ->orderBy('created_at', 'desc');
                }
            ])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($category) use ($language_code) {

                // skip if parent has wrong status (safety check)
                if ($category->status != 2) {
                    return null;
                }

                $translated_name = app(TranslationService::class)
                    ->getDynamicTranslation(Category::class, 'name', $category->id, $language_code);

                $image_url = app(MediaService::class)->getImageUrl($category->image, 'thumb', 'sm');
                $banner_url = app(MediaService::class)->getImageUrl($category->banner, 'thumb', 'md');

                return [
                    "id" => $category->id,
                    "seller_id" => $category->seller_id,
                    "store_id" => $category->store_id,
                    "name" => $translated_name,
                    "parent_id" => $category->parent_id,
                    "slug" => $category->slug,
                    "image" => app(MediaService::class)->dynamic_image($image_url, 400),
                    "banner" => app(MediaService::class)->dynamic_image($banner_url, 400),
                    "style" => $category->style,
                    "row_order" => $category->row_order,
                    "status" => $category->status,
                    "affiliate_commission" => $category->affiliate_commission,
                    "is_in_affiliate" => $category->is_in_affiliate,
                    "clicks" => $category->clicks,
                    "created_at" => $category->created_at,
                    "updated_at" => $category->updated_at,

                    "children" => $category->children->map(function ($child) use ($language_code) {

                        if ($child->status != 2) {
                            return null;
                        }

                        $child_translated_name = app(TranslationService::class)
                            ->getDynamicTranslation(Category::class, 'name', $child->id, $language_code);

                        $child_image = app(MediaService::class)->getImageUrl($child->image, 'thumb', 'sm');
                        $child_banner = app(MediaService::class)->getImageUrl($child->banner, 'thumb', 'md');

                        return [
                            "id" => $child->id,
                            "seller_id" => $child->seller_id,
                            "store_id" => $child->store_id,
                            "name" => $child_translated_name,
                            "parent_id" => $child->parent_id,
                            "slug" => $child->slug,
                            "image" => app(MediaService::class)->dynamic_image($child_image, 400),
                            "banner" => app(MediaService::class)->dynamic_image($child_banner, 400),
                            "style" => $child->style,
                            "row_order" => $child->row_order,
                            "status" => $child->status,
                            "affiliate_commission" => $child->affiliate_commission,
                            "is_in_affiliate" => $child->is_in_affiliate,
                            "clicks" => $child->clicks,
                            "created_at" => $child->created_at,
                            "updated_at" => $child->updated_at,
                            "children" => [],
                            "text" => $child_translated_name,
                            "state" => ["opened" => true],
                            "icon" => "jstree-folder",
                            "level" => 1,
                            "seller_commission" => $child->seller_commission,
                        ];
                    })->filter(), // remove null children
    
                    "text" => $translated_name,
                    "state" => ["opened" => true],
                    "icon" => "jstree-folder",
                    "level" => 0,
                    "seller_commission" => $category->seller_commission ?? 0,
                ];
            })
            ->filter() // remove null parents
            ->values();




        $response = [
            'error' => empty($categories),
            'message' => empty($categories) ? 'Category does not exist' : 'Category retrieved successfully',
            'language_message_key' => empty($categories) ? 'categories_does_not_exist' : 'categories_retrived_successfully',
            'total' => $total,
            'data' => $categories,
            'requested_categories' => $requested_categories, // <-- NEW ARRAY
        ];

        return response()->json($response);
    }

    public function get_all_categories(AdminCategoryController $AdmincategoryController, Request $request)
    {
        /*
            store_id:3
            id:15               // optional
            limit:25            // { default - 25 } optional
            offset:0            // { default - 0 } optional
            sort:               id / name // { default -row_id } optional
            order:DESC/ASC      // { default - ASC } optional
            has_child_or_item:false { default - true}  optional
                                */
        $rules = [
            'id' => 'numeric|exists:categories,id',
            'limit' => 'numeric',
            'offset' => 'numeric',
            'store_id' => 'required|exists:stores,id',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {

            $store_id = $request->input('store_id') ? (int) $request->input('store_id') : '';

            $id = $request->filled('id') ? (int) $request->input('id') : '';
            $ids = $request->filled('ids') ? $request->input('ids') : '';
            $search = $request->filled('search') ? trim($request->input('search')) : '';
            $limit = $request->filled('limit') ? (int) $request->input('limit') : 25;
            $offset = $request->filled('offset') ? (int) $request->input('offset') : 0;
            $sort = $request->filled('sort') ? $request->input('sort') : 'row_order';
            $order = $request->filled('order') ? $request->input('order') : 'ASC';
            $has_child_or_item = $request->filled('has_child_or_item') ? $request->input('has_child_or_item') : 'true';

            $response = ['message' => 'Category(s) retrieved successfully'];
            $cat_res = $AdmincategoryController->get_categories($id, $limit, $offset, $sort, $order, $has_child_or_item, '', '', '', $store_id, $search, $ids);
            $popular_categories = $AdmincategoryController->get_categories(NULL, "", "", 'clicks', 'DESC', 'false', "", "", "", $store_id);

            return response()->json([
                'error' => $cat_res->original['categories']->isEmpty() ? true : false,
                'total' => $cat_res->original['total'],
                'message' => $cat_res->original['categories']->isEmpty() ? 'Category does not exist' : 'Category retrieved successfully',
                'language_message_key' => $cat_res->original['categories']->isEmpty() ? 'categories_does_not_exist' : 'categories_retrived_successfully',
                'data' => $cat_res->original['categories'],
                'popular_categories' => $popular_categories->original['categories'],
            ]);
        }
    }

    public function delete_requested_category(Request $request)
    {
        $rules = [
            'category_id' => 'required|numeric|exists:categories,id',
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $user_id = auth()->user()->id;
        $seller_id = Seller::where('user_id', $user_id)->value('id');

        $category = Category::find($request->category_id);

        if (!$category) {
            return response()->json([
                'error' => true,
                'message' => 'Category not found.',
                'language_message_key' => 'category_not_found'
            ]);
        }

        // Verify the category belongs to this seller
        if ($category->seller_id != $seller_id) {
            return response()->json([
                'error' => true,
                'message' => 'You are not authorized to delete this category.',
                'language_message_key' => 'not_authorized_to_delete_category'
            ]);
        }

        // Only allow deletion if status is 2 (requested)
        if ((int) $category->status !== 2) {
            return response()->json([
                'error' => true,
                'message' => 'Only requested categories can be deleted.',
                'language_message_key' => 'only_requested_category_can_be_deleted'
            ]);
        }

        // Delete the category
        if ($category->delete()) {
            return response()->json([
                'error' => false,
                'message' => 'Requested category deleted successfully.',
                'language_message_key' => 'requested_category_deleted_successfully'
            ]);
        }

        return response()->json([
            'error' => true,
            'message' => 'Something went wrong.',
            'language_message_key' => 'something_went_wrong'
        ]);
    }
    public function delete_requested_brand(Request $request)
    {
        $rules = [
            'brand_id' => 'required|numeric|exists:brands,id',
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $user_id = auth()->user()->id;
        $seller_id = Seller::where('user_id', $user_id)->value('id');

        $category = Brand::find($request->brand_id);

        if (!$category) {
            return response()->json([
                'error' => true,
                'message' => 'Brand not found.',
                'language_message_key' => 'category_not_found'
            ]);
        }

        // Verify the category belongs to this seller
        if ($category->seller_id != $seller_id) {
            return response()->json([
                'error' => true,
                'message' => 'You are not authorized to delete this brand.',
                'language_message_key' => 'not_authorized_to_delete_category'
            ]);
        }

        // Only allow deletion if status is 2 (requested)
        if ((int) $category->status !== 2) {
            return response()->json([
                'error' => true,
                'message' => 'Only requested categories can be deleted.',
                'language_message_key' => 'only_requested_category_can_be_deleted'
            ]);
        }

        // Delete the category
        if ($category->delete()) {
            return response()->json([
                'error' => false,
                'message' => 'Requested Brand deleted successfully.',
                'language_message_key' => 'requested_category_deleted_successfully'
            ]);
        }

        return response()->json([
            'error' => true,
            'message' => 'Something went wrong.',
            'language_message_key' => 'something_went_wrong'
        ]);
    }

    public function get_products(Request $request)
    {
        /*
            store_id : 1;
            id:101              // optional
            category_id:29      // optional
            user_id:15          // optional
            search:keyword      // optional
            tags:multiword tag1, tag2, another tag      // optional
            flag:low/sold      // optional
            attribute_value_ids : 34,23,12 // { Use only for filteration } optional
            limit:25            // { default - 25 } optional
            offset:0            // { default - 0 } optional
            sort:p.id / p.created_at / pv.price
            order:DESC/ASC      // { default - DESC } optional
            is_similar_products:1 // { default - 0 } optional
            top_rated_product: 1 // { default - 0 } optional
            show_only_active_products:0 { default - 1 } optional
            show_only_stock_product:0 { default - 1 } optional
        */

        $rules = [
            'store_id' => 'required|numeric|exists:stores,id',
            'id' => 'numeric|exists:products,id',
            'category_id' => 'numeric|exists:categories,id',
            'sort' => 'string',
            'limit' => 'numeric',
            'offset' => 'numeric',
            'is_similar_products' => 'numeric',
            'top_rated_product' => 'numeric',
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            if (auth()->check()) {
                $user_id = auth()->user()->id;
                $seller_id = Seller::where('user_id', $user_id)->value('id');
            }
            $limit = $request->has('limit') ? $request->input('limit') : 25;
            $id = $request->has('id') ? $request->input('id') : '';
            $offset = $request->has('offset') ? $request->input('offset') : 0;
            $order = $request->has('order') && trim($request->input('order')) !== '' ? $request->input('order') : 'DESC';
            $store_id = $request->input('store_id');
            $sort = $request->filled('sort') ? $request->input('sort') : 'products.id';
            switch ($sort) {
                case 'p.id':
                    $sort = 'products.id';
                    break;
                case 'p.created_at':
                    $sort = 'products.created_at';
                    break;
                case 'p.total_sales':
                    $sort = 'products.total_sales';
                    break;
                case 'p.average_rating':
                    $sort = 'products.average_rating';
                    break;
                case 'pv.price':
                    $sort = 'product_variants.price';
                    break;
            }
            $is_detailed_data = $request->has('is_detailed_data') ? $request->input('is_detailed_data') : 0;
            $type = $request->has('type') ? $request->input('type') : '';

            $filters = [
                'search' => $request->input('search', null),
                'tags' => $request->input('tags', ''),
                'flag' => $request->has('flag') && $request->input('flag') !== '' ? $request->input('flag') : '',
                'attribute_value_ids' => $request->input('attribute_value_ids', null),
                'is_similar_products' => $request->input('is_similar_products', null),
                'product_type' => $request->input('top_rated_product') == 1 ? 'top_rated_product_including_all_products' : null,
                'show_only_active_products' => $request->input('show_only_active_products', true),
                'show_only_stock_product' => $request->input('show_only_stock_product', false),

            ];

            $category_id = $request->input('category_id', null);
            $product_id = $request->input('id', null);
            $user_id = $request->input('user_id', null);
            $language_code = $request->attributes->get('language_code');
            $products = app(ProductService::class)->fetchProduct($user_id, (isset($filters)) ? $filters : $id, $product_id, $category_id, $limit, $offset, $sort, $order, null, null, $seller_id, '', $store_id, $is_detailed_data, $type, 1, $language_code);


            if (!empty($products['product'])) {
                $filtered_brand_ids = array_filter($products['brand_ids'], function ($value) {
                    return !empty($value);
                });
                $brand_ids = implode(',', $filtered_brand_ids);
                $response['error'] = false;
                $response['message'] = "Products retrieved successfully !";
                $response['language_message_key'] = "products_retrived_successfully";
                $response['category_ids'] = isset($products['category_ids']) && !empty($products['category_ids']) ? implode(',', $products['category_ids']) : '';
                $response['brand_ids'] = isset($products['brand_ids']) && !empty($products['brand_ids']) ? $brand_ids : '';
                $response['filters'] = (isset($products['filters']) && !empty($products['filters'])) ? $products['filters'] : [];
                $response['total'] = (isset($products['total'])) ? strval($products['total']) : '';
                $response['offset'] = $offset;
                $response['data'] = $products['product'];
            } else {
                $response['error'] = true;
                $response['message'] = "Products Not Found !";
                $response['language_message_key'] = "products_not_found";
                $response['data'] = array();
            }
            return response()->json($response);
        }
    }

    public function get_transactions(Request $request)
    {
        /*
            id: 1001                // { optional}
            type : credit / debit - for wallet // { optional }
            search : Search keyword // { optional }
            limit:25                // { default - 25 } optional
            offset:0                // { default - 0 } optional
            sort: id / date_created // { default - id } optional
            order:DESC/ASC          // { default - DESC } optional
        */
        $rules = [
            'transaction_type' => 'string',
            'type' => 'string',
            'sort' => 'string',
            'limit' => 'numeric',
            'offset' => 'numeric',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            $user_id = auth('sanctum')->id();
            $id = $request->filled('id') && is_numeric($request->input('id')) ? $request->input('id') : '';
            $type = $request->filled('type') ? $request->input('type') : '';
            $search = $request->filled('search') ? trim($request->input('search')) : '';
            $limit = $request->filled('limit') && is_numeric($request->input('limit')) ? $request->input('limit') : 25;
            $offset = $request->filled('offset') && is_numeric($request->input('offset')) ? $request->input('offset') : 0;
            $order = $request->filled('order') ? $request->input('order') : 'DESC';
            $sort = $request->filled('sort') ? $request->input('sort') : 'id';

            $res = getTransactions($id, $user_id, 'wallet', $type, $search, $offset, $limit, $sort, $order);
            $response['error'] = !$res['data']->isEmpty() ? false : true;
            $response['message'] = !$res['data']->isEmpty() ? 'Transactions Retrieved Successfully' : 'Transactions does not exists';
            $response['language_message_key'] = !$res['data']->isEmpty() ? 'transactions_retrieved_successfully' : 'transaction_not_exist';
            $response['total'] = !$res['data']->isEmpty() ? $res['total'] : 0;
            $response['data'] = !$res['data']->isEmpty() ? $res['data'] : [];
            return response()->json($response);
        }
    }

    public function get_statistics(Request $request)
    {
        $rules = [
            'store_id' => 'required|numeric|exists:stores,id',
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        if (auth()->check()) {
            $user_id = auth()->user()->id;
            $seller_id = Seller::where('user_id', $user_id)->value('id');
        }

        $store_id = $request->input('store_id');
        $currencyDetails = fetchDetails(Currency::class, ['is_default' => 1], 'symbol');
        $currency = !$currencyDetails->isEmpty() ? $currencyDetails[0]->symbol : '';

        $bulkData = [
            'error' => false,
            'message' => 'Data retrieved successfully',
            'language_message_key' => 'data_retrieved_successfully',
            'currency_symbol' => $currency ?: '',
        ];

        // Category-wise product count
        $categories = Category::withCount([
            'products' => function ($query) use ($seller_id, $store_id) {
                $query->where('status', 1)
                    ->where('store_id', $store_id)
                    ->where('seller_id', $seller_id);
            }
        ])
            ->where('status', 1)
            ->where('store_id', $store_id)
            ->having('products_count', '>', 0)
            ->get();

        $language_code = $request->attributes->get('language_code');
        $bulkData['category_wise_product_count'] = [
            'cat_name' => $categories->map(function ($category) use ($language_code) {
                return app(TranslationService::class)->getDynamicTranslation(Category::class, 'name', $category->id, $language_code);
            })->toArray(),
            'counter' => $categories->pluck('products_count')->toArray(),
        ];

        // Earnings data
        $tempRow1 = [];
        $tempRow1['overall_sale'] = OrderItems::where('seller_id', $seller_id)
            ->where('store_id', $store_id)
            ->where('active_status', 'delivered')
            ->sum('sub_total') ?? 0;

        // Daily earnings
        $startDate = Carbon::now()->subDays(29);
        $dayRes = OrderItems::selectRaw("DAY(created_at) as date, SUM(sub_total) as total_sale")
            ->where('seller_id', $seller_id)
            ->where('store_id', $store_id)
            ->where('created_at', '>=', $startDate)
            ->groupByRaw('DAY(created_at)')
            ->get();

        $tempRow1['daily_earnings'] = [
            'total_sale' => $dayRes->pluck('total_sale')->map(fn($value) => (int) $value)->toArray(),
            'day' => $dayRes->pluck('date')->toArray(),
        ];

        // Weekly earnings
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        $weekRes = OrderItems::selectRaw("DATE_FORMAT(created_at, '%d-%b') as date, SUM(sub_total) as total_sale")
            ->where('seller_id', $seller_id)
            ->where('store_id', $store_id)
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->groupByRaw('DAY(created_at)')
            ->get();

        $tempRow1['weekly_earnings'] = [
            'total_sale' => $weekRes->pluck('total_sale')->map(fn($value) => (int) $value)->toArray(),
            'week' => $weekRes->pluck('date')->toArray(),
        ];

        // Monthly earnings
        $monthRes = OrderItems::selectRaw('SUM(sub_total) AS total_sale, DATE_FORMAT(created_at, "%b") AS month_name')
            ->where('seller_id', $seller_id)
            ->where('store_id', $store_id)
            ->groupByRaw('YEAR(CURDATE()), MONTH(created_at)')
            ->orderByRaw('YEAR(CURDATE()), MONTH(created_at)')
            ->get();

        $tempRow1['monthly_earnings'] = [
            'total_sale' => $monthRes->pluck('total_sale')->map(fn($value) => (int) $value)->toArray(),
            'month_name' => $monthRes->pluck('month_name')->toArray(),
        ];

        $bulkData['earnings'] = [$tempRow1];

        // Order and product counts
        $tempRow2 = [
            'order_counter' => strval(app(OrderService::class)->ordersCount("", $seller_id, '', $store_id)),
            'delivered_orders_counter' => strval(app(OrderService::class)->ordersCount("delivered", $seller_id, '', $store_id)),
            'cancelled_orders_counter' => strval(app(OrderService::class)->ordersCount("cancelled", $seller_id, '', $store_id)),
            'returned_orders_counter' => strval(app(OrderService::class)->ordersCount("returned", $seller_id, '', $store_id)),
            'received_orders_counter' => strval(app(OrderService::class)->ordersCount("received", $seller_id, '', $store_id)),
            'product_counter' => app(ProductService::class)->countProducts($seller_id, $store_id),
            'user_counter' => app(SellerService::class)->getSellerPermission($seller_id, $store_id, 'customer_privacy') ? count_new_user() : "0",
            'permissions' => app(SellerService::class)->getSellerPermission($seller_id, $store_id),
            'count_products_low_status' => strval(countProductsStockLowStatus($seller_id, $store_id)),
            'count_products_sold_out_status' => strval(app(ProductService::class)->countProductsAvailabilityStatus($seller_id, $store_id) ?? 0),
        ];

        $bulkData['counts'] = [$tempRow2];

        return response()->json($bulkData);
    }


    public function update_fcm(Request $request)
    {
        // Validation rules

        $rules = [
            'fcm_id' => 'required',
            'is_delete' => 'sometimes|boolean',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        // Check if the user is authenticated
        $user_id = auth('sanctum')->check() ? auth('sanctum')->id() : null;

        // Get fcm_id from request
        $fcm_id = $request->input('fcm_id') ? $request->input('fcm_id') : '';
        $is_delete = $request->input('is_delete'); // New delete parameter

        // If the delete parameter is set to 1, handle deletion
        if ($is_delete == 1) {
            if (isset($user_id) && !empty($user_id) && !empty($fcm_id)) {
                // Delete the entry from user_fcm table
                $deleted = UserFcm::where('user_id', $user_id)
                    ->where('fcm_id', $fcm_id)
                    ->delete();

                if ($deleted) {
                    $response = [
                        'error' => false,
                        'message' => 'FCM ID deleted successfully',
                        'language_message_key' => 'deleted_successfully',
                        'data' => [],
                    ];
                } else {
                    $response = [
                        'error' => true,
                        'message' => 'No entry found to delete!',
                        'language_message_key' => 'no_entry_found',
                        'data' => [],
                    ];
                }
            } else {
                // Handle case where user_id or fcm_id is not set
                $response = [
                    'error' => true,
                    'message' => 'User ID and FCM ID are required for deletion!',
                    'language_message_key' => 'user_id_fcm_id_required',
                    'data' => [],
                ];
            }
        } else {
            // Handle insertion logic
            if (!empty($fcm_id)) {
                if (isset($user_id) && !empty($user_id)) {
                    // Prepare the data for insertion
                    $fcm_data = [
                        'fcm_id' => $fcm_id,
                        'user_id' => $user_id,
                    ];

                    // Check if the FCM ID already exists for the user
                    $existing_fcm = UserFcm::where('user_id', $user_id)
                        ->where('fcm_id', $fcm_id)
                        ->first();

                    if (!$existing_fcm) {
                        // If it doesn't exist, create a new entry
                        $user_res = UserFcm::insert($fcm_data);

                        // Prepare the response
                        if ($user_res) {
                            $response = [
                                'error' => false,
                                'message' => 'FCM ID stored successfully',
                                'language_message_key' => 'stored_successfully',
                                'data' => [],
                            ];
                        } else {
                            $response = [
                                'error' => true,
                                'message' => 'Insertion Failed!',
                                'language_message_key' => 'insertion_failed',
                                'data' => [],
                            ];
                        }
                    } else {
                        // If the FCM ID already exists, prepare a response indicating this
                        $response = [
                            'error' => true,
                            'message' => 'FCM ID already exists for this user.',
                            'language_message_key' => 'fcm_id_exists',
                            'data' => [],
                        ];
                    }
                } else {
                    // Handle case where user_id is not set
                    $response = [
                        'error' => true,
                        'message' => 'User ID is required!',
                        'language_message_key' => 'user_id_required',
                        'data' => [],
                    ];
                }
            }
        }

        return response()->json($response);
    }
    public function get_cities(Request $request, AreaController $areaController)
    {
        /*
           sort:               // { c.name / c.id } optional
           order:DESC/ASC      // { default - ASC } optional
           search:value        // {optional}
           offset: 0 {optional}
           limit: 10 {optional}
       */

        $rules = [
            'sort' => 'string',
            'limit' => 'numeric',
            'offset' => 'numeric',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            $language_code = $request->attributes->get('language_code');
            $city_data = $areaController->city_list($request, $language_code);
            if (empty($city_data->original['rows']) || $city_data->original['total'] == 0) {
                $response['error'] = true;
                $response['message'] = 'Data Does Not Exists  !';
                $response['language_message_key'] = 'data_does_not_exists';
                $response['data'] = array();
            } else {
                $response['error'] = false;
                $response['message'] = 'Cities retrieved successfully!';
                $response['language_message_key'] = 'cities_retrived_successfully';
                $response['total'] = $city_data->original['total'];
                $response['data'] = $city_data->original['rows'];
            }
            return response()->json($response);
        }
    }

    public function get_zipcodes(Request $request, AreaController $areaController)
    {
        /*
           sort:               // { c.name / c.id } optional
           order:DESC/ASC      // { default - ASC } optional
           search:value        // {optional}
           offset: 0 {optional}
           limit: 10 {optional}
       */

        $rules = [
            'sort' => 'string',
            'limit' => 'numeric',
            'offset' => 'numeric',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            $language_code = $request->attributes->get('language_code');
            $zipcode_data = $areaController->zipcode_list($request, $language_code);


            if ($zipcode_data) {
                $response['error'] = false;
                $response['message'] = 'Zipcode retrieved successfully!';
                $response['language_message_key'] = 'zipcodes_retrieved_successfully!';
                $response['total'] = $zipcode_data->original['total'];
                $response['data'] = $zipcode_data->original['rows'];
            } else {
                $response['error'] = true;
                $response['message'] = 'Zipcode(s) does not exist!';
                $response['language_message_key'] = 'zipcodes_not_exist';
                $response['data'] = array();
            }
            return response()->json($response);
        }
    }

    public function get_taxes(Request $request)
    {
        $language_code = $request->attributes->get('language_code');

        $taxes = Tax::select('id', 'title', 'percentage', 'status')
            ->where('status', 1)
            ->get();
        $taxes = $taxes->map(function ($tax) use ($language_code) {
            $tax->title = app(TranslationService::class)->getDynamicTranslation(Tax::class, 'title', $tax->id, $language_code);
            return $tax;
        });

        if ($taxes->isNotEmpty()) {
            $response['error'] = false;
            $response['message'] = 'Taxes retrieved successfully!';
            $response['language_message_key'] = 'taxes_retrieved_successfully';
            $response['data'] = $taxes;
        } else {
            $response['error'] = true;
            $response['message'] = 'Taxes do not exist!';
            $response['language_message_key'] = 'taxes_not_exist';
            $response['data'] = [];
        }

        return response()->json($response);
    }


    public function send_withdrawal_request(Request $request, PaymentRequestController $paymentRequest)
    {
        /*
            payment_address: 12343535
            amount: 56
        */

        $rules = [
            'payment_address' => 'required',
            'amount' => 'required|numeric|min:0',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            if (auth()->check()) {
                $user_id = auth()->user()->id;
            }

            $request['user_id'] = $user_id;
            $data = $paymentRequest->add_withdrawal_request($request);
            $response['error'] = $data->original['error'];
            $response['message'] = isset($data->original['message']) ? $data->original['message'] : $data->original['error_message'];
            $response['data'] = $data->original['data'];
            return response()->json($response);
        }
    }

    public function get_withdrawal_request(Request $request, PaymentRequestController $paymentRequest)
    {
        /*
           sort:               // { c.name / c.id } optional
           order:DESC/ASC      // { default - ASC } optional
           search:value        // {optional}
           offset: 0 {optional}
           limit: 10 {optional}
       */

        $rules = [
            'sort' => 'string',
            'limit' => 'numeric',
            'offset' => 'numeric',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            $user_id = auth('sanctum')->id();

            $data = $paymentRequest->get_payment_request_list($request, $user_id);
            $response['error'] = $data->original['rows']->isEmpty() ? true : false;
            $response['message'] = $data->original['rows']->isEmpty() ? 'No data found' : 'Withdrawal Request Retrieved Successfully';
            $response['language_message_key'] = $data->original['rows']->isEmpty() ? 'no_data_found' : 'withdrawal_request_retrieved_successfully';
            $response['total'] = $data->original['total'];
            $response['data'] = $data->original['rows'];

            return response()->json($response);
        }
    }

    public function get_attributes(Request $request, AttributeController $attributeController)
    {
        $rules = [
            'store_id' => 'required|exists:stores,id',
            'sort' => 'nullable|string',
            'order' => 'nullable|in:ASC,DESC',
            'limit' => 'nullable|numeric',
            'offset' => 'nullable|numeric',
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        // Pass filters to list()
        $request['attribute_ids'] = $request->input('attribute_ids', '');
        $request['attribute_value_ids'] = $request->input('attribute_value_ids', '');

        $data = $attributeController->list($request);

        // Safely convert JSON response to array
        $data_array = $data->getData(true);

        $rows = $data_array['rows'] ?? [];
        $total = $data_array['total'] ?? 0;

        $response = [
            'error' => false,
            'message' => 'Attribute Retrieved Successfully',
            'language_message_key' => 'attribute_retrieved_successfully',
            'total' => $total,
            'data' => $rows,
        ];

        return response()->json($response);
    }


    public function get_attribute_values(Request $request, AttributeController $attrubuteController)
    {
        /*
            store_id :1
            attribute_id : 5 // optional
            sort: a.name              // { a.name / a.id } optional
            order:DESC/ASC      // { default - ASC } optional
            search:value        // {optional}
            limit:10  {optional}
            offset:10  {optional}
        */

        $rules = [
            'store_id' => 'required|exists:stores,id',
            'attribute_id' => 'numeric|exists:attributes,id',
            'sort' => 'string',
            'limit' => 'numeric',
            'offset' => 'numeric',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            $data = $attrubuteController->getAttributeValue($request);
            // dd($data->original);
            return response()->json($data->original);
        }
    }

    public function get_media(Request $request, MediaController $mediaController)
    {
        /*
            store_id : 1
            limit:25            // { default - 25 } optional
            offset:0            // { default - 0 } optional
            sort:               // { id } optional
            order:DESC/ASC      // { default - DESC } optional
            search:value        // {optional}
            type:image          // {documents,spreadsheet,archive,video,audio,image}
        */
        $rules = [
            'store_id' => 'required|exists:stores,id',

        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            if (auth()->check()) {
                $user_id = auth()->user()->id;
                $seller_id = Seller::where('user_id', $user_id)->value('id');
            }
            $request['seller_id'] = $seller_id;

            $media = $mediaController->list($request);

            $rows = [];
            foreach ($media->original['rows'] as $row) {
                $tempRow['id'] = $row['id'];
                $tempRow['name'] = $row['name'];
                $tempRow['image'] = $row['media_image'];
                $tempRow['size'] = $row['size'];
                $tempRow['extension'] = $row['extension'];
                $tempRow['type'] = $row['type'];
                $tempRow['sub_directory'] = $row['sub_directory'];
                $rows[] = $tempRow;
            }

            if (!empty($rows)) {
                $response['error'] = false;
                $response['message'] = 'Media Retrieved Successfully';
                $response['language_message_key'] = 'media_retrieved_successfully';
                $response['total'] = $media->original['total'];
                $response['data'] = $rows;
            } else {
                $response['error'] = true;
                $response['message'] = 'Media not found !';
                $response['language_message_key'] = 'media_not_found';
                $response['total'] = 0;
                $response['data'] = $rows;
            }
            return response()->json($response);
        }
    }

    public function add_products(Request $request, ProductController $productController)
    {
        /*
            store_id:1
            pro_input_name: product name
            short_description: description
            tags:tag1,tag2,tag3     //{comma saprated}
            pro_input_tax[]:tax_id // you can add multiple tax ids like 1,2,3
            indicator:1             //{ 0 - none | 1 - veg | 2 - non-veg }
            made_in: india          //{optional}
            hsn_code: 456789        //{optional}
            brand: 1          //note : pass brand ID {optional}
            total_allowed_quantity:100
            minimum_order_quantity:12
            quantity_step_size:1
            warranty_period:1 month     {optional}
            guarantee_period:1 month   {optional}
            deliverable_type:1        //{0:none, 1:all, 2:include, 3:exclude}
            deliverable_zones[]:1,2,3  //{NULL: if deliverable_type = 0 or 1}
            is_prices_inclusive_tax:0   //{1: inclusive | 0: exclusive}
            cod_allowed:1               //{ 1:allowed | 0:not-allowed }
            download_allowed:1               //{ 1:allowed | 0:not-allowed }
            download_link_type:self_hosted             //{ values : self_hosted | add_link }
            pro_input_zip:file              //when download type is self_hosted add file for download
            download_link : url             //{URL of download file}
            is_returnable:1             // { 1:returnable | 0:not-returnable }
            is_cancelable:1             //{1:cancelable | 0:not-cancelable}
            is_attachment_required:1             //{1:yes | 0:no}
            cancelable_till:            //{received,processed,shipped}
            pro_input_image:file
            other_images: files
            video_type:                 // {values: vimeo | youtube}
            video:                      //{URL of video}
            pro_input_video: file
            pro_input_description:product's description
            extra_input_description:product's extra description
            category_id:99
            attribute_values:1,2,3,4,5
            minimum_free_delivery_order_qty:5 // used when product wise delivery charge is ON
            delivery_charges:10 // used when product wise delivery charge is ON

            pickup_location_id : 1 {optional} // ID from pickup_locations table
            status:1/0 {optional}
            --------------------------------------------------------------------------------
            till above same params
            --------------------------------------------------------------------------------
            --------------------------------------------------------------------------------
            common param for simple and variable product
            --------------------------------------------------------------------------------
            product_type:simple_product | variable_product  |  digital_product
            variant_stock_level_type:product_level | variable_level
            variant_stock_status: 0             {optional}//{0 =>'Simple_Product_Stock_Active'}

            if(product_type == variable_product):
                variants_ids:3 5,4 5,1 2
                variant_price:100,200
                variant_special_price:90,190
                variant_images:files              //{optional}
                weight : 1,2,3  {optional}
                height :  1,2,3 {optional}
                breadth :  1,2,3 {optional}
                length :  1,2,3 {optional}

                sku_variant_type:test            //{if (variant_stock_level_type == product_level)}
                total_stock_variant_type:100     //{if (variant_stock_level_type == product_level)}
                variant_status:1                 //{if (variant_stock_level_type == product_level)}

                variant_sku:test,test             //{if(variant_stock_level_type == variable_level)}
                variant_total_stock:120,300       //{if(variant_stock_level_type == variable_level)}
                variant_level_stock_status:1,1    //{if(variant_stock_level_type == variable_level)}

            if(product_type == simple_product):
                simple_product_stock_status:null|0|1   {1=in stock | 0=out stock}
                simple_price:100
                simple_special_price:90
                weight : 1  {optional}
                height : 1 {optional}
                breadth : 1 {optional}
                length : 1 {optional}
                product_sku:test                    {optional}
                product_total_stock:100             {optional}


           if(product_type == digital_product):
                simple_price:100
                simple_special_price:90

                for multi language

                translated_product_name: {"hn": "हिंदी उत्पाद नाम","fr": "Nom du produit français"},
                translated_product_short_description": {hn": "हिंदी विवरण","fr": "Description française"}


       */
        $rules = [
            'store_id' => 'required|numeric|exists:stores,id',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            if (auth()->check()) {
                $user_id = auth()->user()->id;
                $seller_id = Seller::where('user_id', $user_id)->value('id');
            }

            // Block product creation if store is deactivated
            if ($guard = $this->ensureActiveStore($seller_id, (int) $request->store_id)) {
                return $guard;
            }
            // dd($user_id);
            // $seller_id =18;
            $language_code = $request->attributes->get('language_code');
            $request['seller_id'] = $seller_id;
            $request['variant_price'] = (isset($request['variant_price']) && !empty($request['variant_price'])) ? explode(",", $request['variant_price']) : NULL;
            $request['variant_special_price'] = (isset($request['variant_special_price']) && !empty($request['variant_special_price'])) ? explode(",", $request['variant_special_price']) : NULL;
            $request['variants_ids'] = (isset($request['variants_ids']) && !empty($request['variants_ids'])) ? explode(",", $request['variants_ids']) : NULL;
            $request['variant_sku'] = (isset($request['variant_sku']) && !empty($request['variant_sku'])) ? explode(",", $request['variant_sku']) : NULL;
            $request['variant_total_stock'] = (isset($request['variant_total_stock']) && !empty($request['variant_total_stock'])) ? explode(",", $request['variant_total_stock']) : NULL;
            $request['variant_level_stock_status'] = (isset($request['variant_level_stock_status']) && !empty($request['variant_level_stock_status'])) ? explode(",", $request['variant_level_stock_status']) : NULL;
            $request['other_images'] = (isset($request['other_images']) && !empty($request['other_images'])) ? explode(",", $request['other_images']) : NULL;
            $request['variant_images'] = (isset($request['variant_images']) && !empty($request['variant_images'])) ? json_decode($request['variant_images'], true) : NULL;

            $request['status'] = (isset($request['status']) && ($request['status'] != '')) ? $request['status'] : 1;

            // Conditional validation based on enabled shipping methods
            $shipping_settings = app(SettingService::class)->getSettings('shipping_method', true);
            $shipping_settings = json_decode($shipping_settings, true);
            $is_local_shipping = isset($shipping_settings['local_shipping_method']) && 
                                $shipping_settings['local_shipping_method'] == 1;
            $is_standard_shipping = isset($shipping_settings['shiprocket_shipping_method']) && 
                                    $shipping_settings['shiprocket_shipping_method'] == 1;

            // Check if at least one shipping method is enabled
            if (!$is_local_shipping && !$is_standard_shipping) {
                return response()->json([
                    'error' => true,
                    'message' => 'At least one shipping method must be enabled in settings',
                    'language_message_key' => 'shipping_method_required',
                ]);
            }

            // Only validate for non-digital products
            if ($request['product_type'] != 'digital_product') {
                // If ONLY local shipping is enabled
                if ($is_local_shipping && !$is_standard_shipping) {
                    // Validate deliverable_type is provided
                    if (!isset($request['deliverable_type']) || $request['deliverable_type'] === '' || $request['deliverable_type'] === null) {
                        return response()->json([
                            'error' => true,
                            'message' => 'Deliverable type is required for local shipping',
                            'language_message_key' => 'deliverable_type_required_for_local_shipping',
                        ]);
                    }

                    // Validate deliverable_zones when deliverable_type is 2 (include) or 3 (exclude)
                    if (in_array($request['deliverable_type'], [2, 3, '2', '3'])) {
                        if (!isset($request['deliverable_zones']) || empty($request['deliverable_zones'])) {
                            return response()->json([
                                'error' => true,
                                'message' => 'Deliverable zones are required when deliverable type is set to include or exclude',
                                'language_message_key' => 'deliverable_zones_required',
                            ]);
                        }
                    }
                }

                // If ONLY Shiprocket is enabled
                if ($is_standard_shipping && !$is_local_shipping) {
                    // Validate pickup location is provided for standard shipping
                    if (!isset($request['pickup_location_id']) || empty($request['pickup_location_id']) || $request['pickup_location_id'] == 'NULL') {
                        return response()->json([
                            'error' => true,
                            'message' => 'Pickup location is required for standard shipping',
                            'language_message_key' => 'pickup_location_required_for_standard_shipping',
                        ]);
                    }
                    
                    if (strtolower($request['product_type']) == 'simple_product') {
                        // Validate simple product dimensions
                        $dimension_rules = [
                            'weight' => 'required|numeric|gt:0',
                            'height' => 'required|numeric|gt:0',
                            'breadth' => 'required|numeric|gt:0',
                            'length' => 'required|numeric|gt:0',
                        ];
                        
                        if ($response = $this->HandlesValidation($request, $dimension_rules, [
                            'weight.required' => 'Weight is required for standard shipping',
                            'weight.gt' => 'Weight must be greater than 0',
                            'height.required' => 'Height is required for standard shipping',
                            'height.gt' => 'Height must be greater than 0',
                            'breadth.required' => 'Breadth is required for standard shipping',
                            'breadth.gt' => 'Breadth must be greater than 0',
                            'length.required' => 'Length is required for standard shipping',
                            'length.gt' => 'Length must be greater than 0',
                        ], null, true)) {
                            return $response;
                        }
                    } elseif (strtolower($request['product_type']) == 'variable_product') {
                        // For variable products, check if dimensions are provided as comma-separated values
                        $weight_values = isset($request['weight']) && !empty($request['weight']) ? explode(",", $request['weight']) : [];
                        $height_values = isset($request['height']) && !empty($request['height']) ? explode(",", $request['height']) : [];
                        $breadth_values = isset($request['breadth']) && !empty($request['breadth']) ? explode(",", $request['breadth']) : [];
                        $length_values = isset($request['length']) && !empty($request['length']) ? explode(",", $request['length']) : [];
                        
                        if (empty($weight_values) || empty($height_values) || empty($breadth_values) || empty($length_values)) {
                            return response()->json([
                                'error' => true,
                                'message' => 'Weight, height, breadth, and length are required for all variants when using standard shipping',
                                'language_message_key' => 'dimensions_required_for_standard_shipping',
                            ]);
                        }
                        
                        // Validate each dimension value is greater than 0
                        foreach ($weight_values as $weight) {
                            if (!is_numeric($weight) || $weight <= 0) {
                                return response()->json([
                                    'error' => true,
                                    'message' => 'All weight values must be greater than 0',
                                    'language_message_key' => 'weight_must_be_greater_than_zero',
                                ]);
                            }
                        }
                        foreach ($height_values as $height) {
                            if (!is_numeric($height) || $height <= 0) {
                                return response()->json([
                                    'error' => true,
                                    'message' => 'All height values must be greater than 0',
                                    'language_message_key' => 'height_must_be_greater_than_zero',
                                ]);
                            }
                        }
                        foreach ($breadth_values as $breadth) {
                            if (!is_numeric($breadth) || $breadth <= 0) {
                                return response()->json([
                                    'error' => true,
                                    'message' => 'All breadth values must be greater than 0',
                                    'language_message_key' => 'breadth_must_be_greater_than_zero',
                                ]);
                            }
                        }
                        foreach ($length_values as $length) {
                            if (!is_numeric($length) || $length <= 0) {
                                return response()->json([
                                    'error' => true,
                                    'message' => 'All length values must be greater than 0',
                                    'language_message_key' => 'length_must_be_greater_than_zero',
                                ]);
                            }
                        }
                    }
                    
                    // Validate stock management is enabled for standard shipping (Shiprocket)
                    if (strtolower($request['product_type']) == 'simple_product') {
                        // For simple products, check if stock management is enabled
                        if (!isset($request['simple_product_stock_status']) || $request['simple_product_stock_status'] === '' || $request['simple_product_stock_status'] === null) {
                            return response()->json([
                                'error' => true,
                                'message' => 'Stock management is required for products using standard shipping. Please enable stock management and provide stock status.',
                                'language_message_key' => 'stock_management_required_for_standard_shipping',
                            ]);
                        }
                    } elseif (strtolower($request['product_type']) == 'variable_product') {
                        // For variable products, check if stock management type is selected
                        if (!isset($request['variant_stock_level_type']) || empty($request['variant_stock_level_type'])) {
                            return response()->json([
                                'error' => true,
                                'message' => 'Stock management is required for products using standard shipping. Please select stock management type (Product Level or Variable Level).',
                                'language_message_key' => 'stock_management_required_for_standard_shipping',
                            ]);
                        }
                    }
                }

                // If BOTH shipping methods are enabled, all fields are optional
                // Seller can choose which method to use per product
            }


            if (isset($request['product_type']) && strtolower($request['product_type']) == 'simple_product') {
                $request['weight'] = (isset($request['weight']) && !empty($request['weight'])) ? $request['weight'] : 0.0;
                $request['height'] = (isset($request['height']) && !empty($request['height'])) ? $request['height'] : 0.0;
                $request['breadth'] = (isset($request['breadth']) && !empty($request['breadth'])) ? $request['breadth'] : 0.0;
                $request['length'] = (isset($request['length']) && !empty($request['length'])) ? $request['length'] : 0.0;
            } else {
                $request['weight'] = (isset($request['weight']) && !empty($request['weight'])) ? explode(",", $request['weight']) : 0.0;
                $request['height'] = (isset($request['height']) && !empty($request['height'])) ? explode(",", $request['height']) : 0.0;
                $request['breadth'] = (isset($request['breadth']) && !empty($request['breadth'])) ? explode(",", $request['breadth']) : 0.0;
                $request['length'] = (isset($request['length']) && !empty($request['length'])) ? explode(",", $request['length']) : 0.0;
            }

            // process image and other images

            $request['zipcodes'] = (!empty($request['deliverable_zones'])) ? $request['deliverable_zones'] : NULL;
            $request['extra_input_description'] = (isset($request['extra_input_description']) && $request['extra_input_description'] != 'NULL' && !empty($request['extra_input_description']) ? $request['extra_input_description'] : '');
            // Validate and set pickup_location_id
            if (isset($request['pickup_location_id']) && $request['pickup_location_id'] != 'NULL' && !empty($request['pickup_location_id'])) {
                $pickupLocation = PickupLocation::where('id', $request['pickup_location_id'])
                    ->where('seller_id', $seller_id)
                    ->where('status', 1)
                    ->first();

                if (!$pickupLocation) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Invalid pickup location or not approved',
                        'language_message_key' => 'invalid_pickup_location_or_not_approved',
                    ]);
                }
                $request['pickup_location'] = $request['pickup_location_id'];
            } else {
                $request['pickup_location'] = '';
            }
            //    dd($request->deliverable_type);
            $product = $productController->store($request, true, $language_code);
            // dd($product);

            $response['error'] = $product->original['error'];
            $response['message'] = $product->original['message'];
            $response['data'] = isset($product->original['data']) ? $product->original['data'] : [];
            return response()->json($response);
        }
    }

    public function get_seller_details(Request $request)
    {

        if (auth()->check()) {
            $user = Auth::user();
            $fcm_ids = fetchDetails(UserFcm::class, ['user_id' => $user->id], 'fcm_id');

            $fcm_ids_array = array_map(function ($item) {
                return $item->fcm_id;
            }, $fcm_ids->all());

            $userData = app(SellerService::class)->formatUserData($user, $fcm_ids_array);
            $language_code = $request->attributes->get('language_code');
            $seller_data = fetchDetails(Seller::class, ['user_id' => $user->id], '*');
            $seller_data = $seller_data->toArray();

            $store_data = fetchDetails(SellerStore::class, ['user_id' => $user->id], '*');
            $seller_data[0]['seller_id'] = $seller_data[0]['id'];
            $data = (array_merge($userData, (array) $seller_data));
            $output = $userData;
            unset($seller_data[0]['id']);

            $isPublicDisk = $store_data[0]->disk == 'public' ? 1 : 0;

            $output['store_data'] = app(SellerService::class)->formatStoreData($store_data, $isPublicDisk, $language_code);
            $output['seller_data'] = array_map(
                fn($seller) => (array) $seller,
                app(SellerService::class)->formatSellerData($seller_data, $isPublicDisk)
            );
            foreach ($data as $key => $value) {
                if (array_key_exists($key, !empty($seller_data) ? $seller_data[0] : '')) {
                    $output[$key] = $value;
                }
            }

            if ($user->role_id == 4) {

                unset($data[0]->password);

                return response()->json([
                    'error' => false,
                    'message' => 'Data retrived successfully',
                    'language_message_key' => 'data_retrieved_successfully',
                    'data' => isset($output) ? $output : [],

                ]);
            }
        }
    }

    public function delete_product(Request $request)
    {
        /* Parameters to be passed
            product_id:28
        */

        $rules = [
            'product_id' => 'numeric|required|exists:products,id',

        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            $product_id = $request->input('product_id', 25);

            // Block product delete if store is deactivated
            $product = Product::find($product_id);
            if ($product && auth()->check()) {
                $user_id = auth()->user()->id;
                $seller_id = Seller::where('user_id', $user_id)->value('id');
                if ($guard = $this->ensureActiveStore($seller_id, (int) $product->store_id)) {
                    return $guard;
                }
            }

            if (deleteDetails(['product_id' => $product_id], Product_variants::class)) {

                deleteDetails(['id' => $product_id], Product::class);
                deleteDetails(['product_id' => $product_id], Product_attributes::class);
                $response['error'] = false;
                $response['message'] = 'Deleted Successfully';
                $response['language_message_key'] = 'deleted_successfully';
            } else {
                $response['error'] = true;
                $response['message'] = 'Something Went Wrong';
                $response['language_message_key'] = 'something_went_wrong';
            }
            return response()->json($response);
        }
    }

    public function update_products(Request $request, ProductController $productController)
    {
        /*
            edit_product_id:74
            edit_variant_id:104,105
            variants_ids: new created with new attributes added
            seller_id:1255
            pro_input_name: product name
            short_description: description
            tags:tag1,tag2,tag3     //{comma saprated}
            pro_input_tax[]:tax_id // you can add multiple tax ids like 1,2,3
            indicator:1             //{ 0 - none | 1 - veg | 2 - non-veg }
            made_in: india          //{optional}
            hsn_code: 123456         //{optional}
            brand: adidas          //{optional}
            total_allowed_quantity:100
            minimum_order_quantity:12
            quantity_step_size:1
            warranty_period:1 month
            guarantee_period:1 month
            deliverable_type:1        //{0:none, 1:all, 2:include, 3:exclude}
            deliverable_zones[]:1,2,3  //{NULL: if deliverable_type = 0 or 1}
            is_prices_inclusive_tax:0   //{1: inclusive | 0: exclusive}
            cod_allowed:1               //{ 1:allowed | 0:not-allowed }
            download_allowed:1               //{ 1:allowed | 0:not-allowed }
            download_link_type:self_hosted             //{ values : self_hosted | add_link }
            pro_input_zip:file              //when download type is self_hosted add file for download
            download_link : url             //{URL of download file}
            is_returnable:1             // { 1:returnable | 0:not-returnable }
            is_cancelable:1             //{1:cancelable | 0:not-cancelable}
            is_attachment_required:1             //{1:yes | 0:no}
            cancelable_till:            //{received,processed,shipped}
            pro_input_image:file
            other_images: files
            video_type:                 // {values: vimeo | youtube}
            video:                      //{URL of video}
            pro_input_video: file
            pro_input_description:product's description
            extra_input_description:product's extra description
            category_id:99

            pickup_location_id : 1 {optional} // ID from pickup_locations table
            attribute_values:1,2,3,4,5
            status :1/0 {optional}
            --------------------------------------------------------------------------------
            till above same params
            --------------------------------------------------------------------------------
            --------------------------------------------------------------------------------
            common param for simple and variable product
            --------------------------------------------------------------------------------
            product_type:simple_product | variable_product
            variant_stock_level_type:product_level | variable_level

            if(product_type == variable_product):
                variants_ids:3 5,4 5,1 2
                variant_price:100,200
                variant_special_price:90,190
                variant_images:files              //{optional}
                weight : 1,2,3  {optional}
                height :  1,2,3 {optional}
                breadth :  1,2,3 {optional}
                length :  1,2,3 {optional}

                sku_variant_type:test            //{if (variant_stock_level_type == product_level)}
                total_stock_variant_type:100     //{if (variant_stock_level_type == product_level)}
                variant_status:1                 //{if (variant_stock_level_type == product_level)}

                variant_sku:test,test             //{if(variant_stock_level_type == variable_level)}
                variant_total_stock:120,300       //{if(variant_stock_level_type == variable_level)}
                variant_level_stock_status:1,1    //{if(variant_stock_level_type == variable_level)}

            if(product_type == simple_product):
                simple_product_stock_status:null|0|1   {1=in stock | 0=out stock}
                simple_price:100
                simple_special_price:90
                product_sku:test
                product_total_stock:100
                variant_stock_status: 0            //{0 =>'Simple_Product_Stock_Active' 1 => "Product_Level" 2 => "Variable_Level"	}
                weight : 1  {optional}
                height : 1 {optional}
                breadth : 1 {optional}
                length : 1 {optional}
            if(product_type == digital_product):
                simple_price:100
                simple_special_price:90

                for multi language

                translated_product_name: {"hn": "हिंदी उत्पाद नाम","fr": "Nom du produit français"},
                translated_product_short_description": {hn": "हिंदी विवरण","fr": "Description française"}
       */
        $rules = [
            'edit_product_id' => 'numeric|required|exists:products,id',
            'store_id' => 'required|numeric|exists:stores,id',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            if (auth()->check()) {
                $user_id = auth()->user()->id;
                $seller_id = Seller::where('user_id', $user_id)->value('id');
            }

            // Block product update if store is deactivated
            if ($guard = $this->ensureActiveStore($seller_id, (int) $request->store_id)) {
                return $guard;
            }
            $language_code = $request->attributes->get('language_code');
            $request['seller_id'] = $seller_id;
            $request['variant_price'] = (isset($request['variant_price']) && !empty($request['variant_price'])) ? explode(",", $request['variant_price']) : NULL;
            $request['variant_special_price'] = (isset($request['variant_special_price']) && !empty($request['variant_special_price'])) ? explode(",", $request['variant_special_price']) : NULL;
            $request['variants_ids'] = (isset($request['variants_ids']) && !empty($request['variants_ids'])) ? explode(",", $request['variants_ids']) : NULL;
            $request['edit_variant_id'] = (isset($request['edit_variant_id']) && !empty($request['edit_variant_id'])) ? explode(",", $request['edit_variant_id']) : NULL;
            $request['variant_sku'] = (isset($request['variant_sku']) && !empty($request['variant_sku'])) ? explode(",", $request['variant_sku']) : NULL;
            $request['variant_total_stock'] = (isset($request['variant_total_stock']) && !empty($request['variant_total_stock'])) ? explode(",", $request['variant_total_stock']) : NULL;
            $request['variant_level_stock_status'] = (isset($request['variant_level_stock_status']) && !empty($request['variant_level_stock_status'])) ? explode(",", $request['variant_level_stock_status']) : NULL;
            $request['other_images'] = (isset($request['other_images']) && !empty($request['other_images'])) ? explode(",", $request['other_images']) : [];
            $request['variant_images'] = (isset($request['variant_images']) && !empty($request['variant_images'])) ? json_decode($request['variant_images'], true) : NULL;

            // Conditional validation based on enabled shipping methods
            $shipping_settings = app(SettingService::class)->getSettings('shipping_method', true);
            $shipping_settings = json_decode($shipping_settings, true);
            $is_local_shipping = isset($shipping_settings['local_shipping_method']) && 
                                $shipping_settings['local_shipping_method'] == 1;
            $is_standard_shipping = isset($shipping_settings['shiprocket_shipping_method']) && 
                                    $shipping_settings['shiprocket_shipping_method'] == 1;

            // Check if at least one shipping method is enabled
            if (!$is_local_shipping && !$is_standard_shipping) {
                return response()->json([
                    'error' => true,
                    'message' => 'At least one shipping method must be enabled in settings',
                    'language_message_key' => 'shipping_method_required',
                ]);
            }

            // Only validate for non-digital products
            if ($request['product_type'] != 'digital_product') {
                // If ONLY local shipping is enabled
                if ($is_local_shipping && !$is_standard_shipping) {
                    // Validate deliverable_type is provided
                    if (!isset($request['deliverable_type']) || $request['deliverable_type'] === '' || $request['deliverable_type'] === null) {
                        return response()->json([
                            'error' => true,
                            'message' => 'Deliverable type is required for local shipping',
                            'language_message_key' => 'deliverable_type_required_for_local_shipping',
                        ]);
                    }

                    // Validate deliverable_zones when deliverable_type is 2 (include) or 3 (exclude)
                    if (in_array($request['deliverable_type'], [2, 3, '2', '3'])) {
                        if (!isset($request['deliverable_zones']) || empty($request['deliverable_zones'])) {
                            return response()->json([
                                'error' => true,
                                'message' => 'Deliverable zones are required when deliverable type is set to include or exclude',
                                'language_message_key' => 'deliverable_zones_required',
                            ]);
                        }
                    }
                }

                // If ONLY Shiprocket is enabled
                if ($is_standard_shipping && !$is_local_shipping) {
                    // Validate pickup location is provided for standard shipping
                    if (!isset($request['pickup_location_id']) || empty($request['pickup_location_id']) || $request['pickup_location_id'] == 'NULL') {
                        return response()->json([
                            'error' => true,
                            'message' => 'Pickup location is required for standard shipping',
                            'language_message_key' => 'pickup_location_required_for_standard_shipping',
                        ]);
                    }
                    
                    if (strtolower($request['product_type']) == 'simple_product') {
                        // Validate simple product dimensions
                        $dimension_rules = [
                            'weight' => 'required|numeric|gt:0',
                            'height' => 'required|numeric|gt:0',
                            'breadth' => 'required|numeric|gt:0',
                            'length' => 'required|numeric|gt:0',
                        ];
                        
                        if ($response = $this->HandlesValidation($request, $dimension_rules, [
                            'weight.required' => 'Weight is required for standard shipping',
                            'weight.gt' => 'Weight must be greater than 0',
                            'height.required' => 'Height is required for standard shipping',
                            'height.gt' => 'Height must be greater than 0',
                            'breadth.required' => 'Breadth is required for standard shipping',
                            'breadth.gt' => 'Breadth must be greater than 0',
                            'length.required' => 'Length is required for standard shipping',
                            'length.gt' => 'Length must be greater than 0',
                        ], null, true)) {
                            return $response;
                        }
                    } elseif (strtolower($request['product_type']) == 'variable_product') {
                        // For variable products, check if dimensions are provided as comma-separated values
                        $weight_values = isset($request['weight']) && !empty($request['weight']) ? explode(",", $request['weight']) : [];
                        $height_values = isset($request['height']) && !empty($request['height']) ? explode(",", $request['height']) : [];
                        $breadth_values = isset($request['breadth']) && !empty($request['breadth']) ? explode(",", $request['breadth']) : [];
                        $length_values = isset($request['length']) && !empty($request['length']) ? explode(",", $request['length']) : [];
                        
                        if (empty($weight_values) || empty($height_values) || empty($breadth_values) || empty($length_values)) {
                            return response()->json([
                                'error' => true,
                                'message' => 'Weight, height, breadth, and length are required for all variants when using standard shipping',
                                'language_message_key' => 'dimensions_required_for_standard_shipping',
                            ]);
                        }
                        
                        // Validate each dimension value is greater than 0
                        foreach ($weight_values as $weight) {
                            if (!is_numeric($weight) || $weight <= 0) {
                                return response()->json([
                                    'error' => true,
                                    'message' => 'All weight values must be greater than 0',
                                    'language_message_key' => 'weight_must_be_greater_than_zero',
                                ]);
                            }
                        }
                        foreach ($height_values as $height) {
                            if (!is_numeric($height) || $height <= 0) {
                                return response()->json([
                                    'error' => true,
                                    'message' => 'All height values must be greater than 0',
                                    'language_message_key' => 'height_must_be_greater_than_zero',
                                ]);
                            }
                        }
                        foreach ($breadth_values as $breadth) {
                            if (!is_numeric($breadth) || $breadth <= 0) {
                                return response()->json([
                                    'error' => true,
                                    'message' => 'All breadth values must be greater than 0',
                                    'language_message_key' => 'breadth_must_be_greater_than_zero',
                                ]);
                            }
                        }
                        foreach ($length_values as $length) {
                            if (!is_numeric($length) || $length <= 0) {
                                return response()->json([
                                    'error' => true,
                                    'message' => 'All length values must be greater than 0',
                                    'language_message_key' => 'length_must_be_greater_than_zero',
                                ]);
                            }
                        }
                    }
                    
                    // Validate stock management is enabled for standard shipping (Shiprocket)
                    if (strtolower($request['product_type']) == 'simple_product') {
                        // For simple products, check if stock management is enabled
                        if (!isset($request['simple_product_stock_status']) || $request['simple_product_stock_status'] === '' || $request['simple_product_stock_status'] === null) {
                            return response()->json([
                                'error' => true,
                                'message' => 'Stock management is required for products using standard shipping. Please enable stock management and provide stock status.',
                                'language_message_key' => 'stock_management_required_for_standard_shipping',
                            ]);
                        }
                    } elseif (strtolower($request['product_type']) == 'variable_product') {
                        // For variable products, check if stock management type is selected
                        if (!isset($request['variant_stock_level_type']) || empty($request['variant_stock_level_type'])) {
                            return response()->json([
                                'error' => true,
                                'message' => 'Stock management is required for products using standard shipping. Please select stock management type (Product Level or Variable Level).',
                                'language_message_key' => 'stock_management_required_for_standard_shipping',
                            ]);
                        }
                    }
                }

                // If BOTH shipping methods are enabled, all fields are optional
                // Seller can choose which method to use per product
            }


            if (isset($request['product_type']) && strtolower($request['product_type']) == 'simple_product') {
                $request['weight'] = (isset($request['weight']) && !empty($request['weight'])) ? $request['weight'] : 0.0;
                $request['height'] = (isset($request['height']) && !empty($request['height'])) ? $request['height'] : 0.0;
                $request['breadth'] = (isset($request['breadth']) && !empty($request['breadth'])) ? $request['breadth'] : 0.0;
                $request['length'] = (isset($request['length']) && !empty($request['length'])) ? $request['length'] : 0.0;
            } else {
                $request['weight'] = (isset($request['weight']) && !empty($request['weight'])) ? explode(",", $request['weight']) : 0.0;
                $request['height'] = (isset($request['height']) && !empty($request['height'])) ? explode(",", $request['height']) : 0.0;
                $request['breadth'] = (isset($request['breadth']) && !empty($request['breadth'])) ? explode(",", $request['breadth']) : 0.0;
                $request['length'] = (isset($request['length']) && !empty($request['length'])) ? explode(",", $request['length']) : 0.0;
            }

            // process image and other images

            $request['zipcodes'] = (!empty($request['deliverable_zones'])) ? $request['deliverable_zones'] : NULL;
            $request['extra_input_description'] = (isset($request['extra_input_description']) && $request['extra_input_description'] != 'NULL' && !empty($request['extra_input_description']) ? $request['extra_input_description'] : '');
            // Validate and set pickup_location_id
            if (isset($request['pickup_location_id']) && $request['pickup_location_id'] != 'NULL' && !empty($request['pickup_location_id'])) {
                $pickupLocation = PickupLocation::where('id', $request['pickup_location_id'])
                    ->where('seller_id', $seller_id)
                    ->where('status', 1)
                    ->first();

                if (!$pickupLocation) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Invalid pickup location or not approved',
                        'language_message_key' => 'invalid_pickup_location_or_not_approved',
                    ]);
                }
                $request['pickup_location'] = $request['pickup_location_id'];
            } else {
                $request['pickup_location'] = '';
            }
            $product = $productController->update($request, $request['edit_product_id'], true, $language_code);

            $response['error'] = $product->original['error'];
            $response['message'] = $product->original['message'];
            $response['data'] = isset($product->original['data']) ? $product->original['data'] : [];
            return response()->json($response);
        }
    }

    public function get_delivery_boys(Request $request)
    {
        $rules = [
            'id' => 'numeric',
            'sort' => 'string',
            'limit' => 'numeric',
            'offset' => 'numeric',
            'store_id' => 'required|numeric|exists:stores,id',

        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            if (auth()->check()) {
                $user_id = auth()->user()->id;
                $seller_id = Seller::where('user_id', $user_id)->value('id');
            }
            $store_id = $request->input('store_id');
            $store_deliverability_type = fetchDetails(Store::class, ['id' => $store_id], 'product_deliverability_type');
            $store_deliverability_type = isset($store_deliverability_type) && !empty($store_deliverability_type) ? $store_deliverability_type[0]->product_deliverability_type : "";


            // Get seller's city and pincode
            $seller_store = SellerStore::where('user_id', $user_id)->where('store_id', $store_id)->select('city', 'zipcode', 'deliverable_zones', 'deliverable_type')->get();


            $seller_zone_ids = isset($seller_store) ? explode(',', $seller_store[0]->deliverable_zones) : [];
            $deliverable_type = isset($seller_store) ? $seller_store[0]->deliverable_type : 1;
            $seller_city = isset($seller_store) ? $seller_store[0]->city : "";
            $seller_zipcode = isset($seller_store) ? $seller_store[0]->zipcode : "";

            $limit = $request->input('limit', 25);
            $offset = $request->input('offset', 0);
            $sort = $request->input('sort', 'users.id');
            $order = $request->input('order', 'DESC');
            $search = $request->input('search', '');
            $id = $request->input('id', false);

            $data = getDeliveryBoys($id, $search, $offset, $limit, $sort, $order, $seller_city, $seller_zipcode, $store_deliverability_type, $seller_zone_ids, $deliverable_type);
            return response()->json($data);
        }
    }

    public function upload_media(Request $request, MediaController $mediaController)
    {
        /*
            store_id = 1
            documents:file
        */

        $rules = [
            'store_id' => 'required|numeric|exists:stores,id',
            'documents' => 'required',
        ];

        $messages = [
            'documents.required' => 'Upload at least one media file!',
        ];

        // Pass $messages only if it's not empty
        if ($response = $this->HandlesValidation($request, $rules, !empty($messages) ? $messages : [])) {
            return $response;
        } else {
            if (auth()->check()) {
                $user_id = auth()->user()->id;
                $seller_id = Seller::where('user_id', $user_id)->value('id');
            }
            $request['seller_id'] = $seller_id;

            $media = $mediaController->upload($request);
            $response = [
                'error' => $media->original['error'],
                'message' => $media->original['message'],
                'data' => $media->original['media_paths'],
                'type' => $media->original['type'],
                'file_mime' => $media->original['file_mime'],
            ];
            return response()->json($response);
        }
    }

    public function get_product_rating(Request $request)
    {
        /*
            product_id: 1001
            user_id: 10 // { optional }
            limit:25                // { default - 25 } optional
            offset:0                // { default - 0 } optional
            sort: id // { default - id} optional
            order:DESC/ASC          // { default - DESC } optional
        */
        $rules = [
            'product_id' => 'required|numeric|exists:products,id',
            'user_id' => 'numeric|exists:users,id',
            'sort' => 'string',
            'limit' => 'numeric',
            'offset' => 'numeric',

        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            $product_id = $request->input('product_id');
            $user_id = $request->filled('user_id') ? $request->input('user_id') : '';
            $limit = $request->filled('limit') ? $request->input('limit') : 25;
            $offset = $request->filled('offset') ? $request->input('offset') : 0;
            $sort = $request->filled('sort') ? $request->input('sort') : 'id';
            $order = $request->filled('order') ? $request->input('order') : 'DESC';
            $has_images = $request->filled('has_images') ? 1 : 0;

            // update category clicks
            $category_id = fetchDetails(Product::class, ['id' => $product_id], 'category_id')[0]->category_id;
            if ($category_id !== null) {
                $category = Category::find($category_id);
                if ($category) {
                    $category->increment('clicks');
                }
            }
            $rating = $request->input('rating') != null ? $request->input('rating') : '';
            $pr_rating = fetchDetails(Product::class, ['id' => $product_id], 'rating');
            $rating = app(ProductService::class)->fetchRating($product_id, $user_id, $limit, $offset, $sort, $order, '', $has_images, 'true', $rating);
            if (!empty($rating['product_rating'])) {
                $response['error'] = false;
                $response['message'] = 'Rating retrieved successfully';
                $response['language_message_key'] = 'rating_retrieved_successfully';
                $response['no_of_rating'] = (!empty($rating['no_of_rating'])) ? $rating['no_of_rating'] : 0;
                $response['no_of_reviews'] = (!empty($rating['no_of_reviews'])) ? $rating['no_of_reviews'] : 0;
                $response['total'] = $rating['total_reviews'];
                $response['star_1'] = $rating['star_1'];
                $response['star_2'] = $rating['star_2'];
                $response['star_3'] = $rating['star_3'];
                $response['star_4'] = $rating['star_4'];
                $response['star_5'] = $rating['star_5'];
                $response['total_images'] = $rating['total_images'];
                $response['product_rating'] = (!empty($pr_rating)) ? $pr_rating[0]->rating : "0";
                $response['data'] = $rating['product_rating'];
            } else {
                $response['error'] = true;
                $response['message'] = 'No ratings found !';
                $response['no_of_rating'] = (!empty($rating['no_of_rating'])) ? $rating['no_of_rating'] : 0;
                $response['no_of_reviews'] = (!empty($rating['no_of_reviews'])) ? $rating['no_of_reviews'] : 0;

                $response['star_1'] = $rating['star_1'];
                $response['star_2'] = $rating['star_2'];
                $response['star_3'] = $rating['star_3'];
                $response['star_4'] = $rating['star_4'];
                $response['star_5'] = $rating['star_5'];
                $response['total_images'] = $rating['total_images'];
                $response['product_rating'] = (!empty($pr_rating)) ? $pr_rating[0]->rating : "0";
                $response['data'] = array();
            }
            return response()->json($response);
        }
    }
    public function get_combo_product_rating(Request $request, ComboProductRatingController $ProductRatingController)
    {
        $rules = [
            'product_id' => 'required|numeric|exists:combo_products,id',
            'user_id' => 'numeric|exists:users,id',
            'sort' => 'string',
            'limit' => 'numeric',
            'offset' => 'numeric',
            'order' => 'string',
            'has_images' => 'boolean',

        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $user_id = $request->input('user_id');
        $product_id = $request->input('product_id');

        $limit = $request->input('limit', 25);
        $offset = $request->input('offset', 0);
        $sort = $request->input('sort', 'id');
        $order = $request->input('order', 'DESC');
        $has_images = $request->input('has_images', false);
        // update category click

        $category_id = fetchDetails(Product::class, ['id' => $product_id], 'category_id');

        Category::where('id', $category_id[0]->category_id)->increment('clicks');


        $pr_rating = fetchDetails(Product::class, ['id' => $product_id], 'rating');

        $rating = $request->input('rating') != null ? $request->input('rating') : '';
        $rating = $ProductRatingController->fetch_rating(($request->input('product_id') != null) ? $request->input('product_id') : '', $user_id, $limit, $offset, $sort, $order, '', $has_images, $rating);

        if (!empty($rating['product_rating'])) {
            $response['error'] = false;
            $response['message'] = 'Rating retrieved successfully';
            $response['language_message_key'] = 'ratings_retrived_successfully';
            $response['no_of_rating'] = (!empty($rating['rating'][0]['no_of_rating'])) ? $rating['rating'][0]['no_of_rating'] : 0;
            $response['total'] = $rating['total_reviews'];
            $response['star_1'] = $rating['star_1'];
            $response['star_2'] = $rating['star_2'];
            $response['star_3'] = $rating['star_3'];
            $response['star_4'] = $rating['star_4'];
            $response['star_5'] = $rating['star_5'];
            $response['total_images'] = $rating['total_images'];
            $response['product_rating'] = (!empty($pr_rating)) ? $pr_rating[0]->rating : "0";
            $response['data'] = $rating['product_rating'];
        } else {
            $response['error'] = true;
            $response['message'] = 'No ratings found !';
            $response['language_message_key'] = 'no_ratings_found';
            $response['no_of_rating'] = array();
            $response['data'] = array();
        }
        return $response;
    }
    public function get_order_tracking(Request $request, SellerOrderController $orderController)
    {
        /*
            order_id:10
            limit:25            // { default - 25 } optional
            offset:0            // { default - 0 } optional
            sort:               // { id } optional
            order:DESC/ASC      // { default - DESC } optional
            search:value        // {optional}
        */
        $rules = [
            'order_id' => 'required|numeric|exists:orders,id',
            'sort' => 'string',
            'limit' => 'numeric',
            'offset' => 'numeric',

        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            $order_tracking_data = $orderController->getSellerOrderTrackingList($request);
            // dd($order_tracking_data['rows']);

            $response['error'] = empty($order_tracking_data['rows']) ? true : false;
            $response['message'] = !empty($order_tracking_data['rows']) ? 'Data retrived successfully' : 'No order tracking data found !';
            $response['language_message_key'] = !empty($order_tracking_data['rows']) ? 'data_retrieved_successfully' : 'no_order_tracking_data_found';
            $response['total'] = $order_tracking_data['total'];
            $response['data'] = !empty($order_tracking_data['rows']) ? $order_tracking_data['rows'] : [];


            // $response['error'] = false;
            // $response['message'] = 'Data retrived successfully !';
            // $response['language_message_key'] = 'data_retrieved_successfully';
            // $response['total'] = $order_tracking_data['total'];

            // $response['data'] = isset($order_tracking_data['rows']) ? $order_tracking_data['rows'] : [];
        }
        return response()->json($response);
    }

    public function edit_order_tracking(Request $request, SellerOrderController $orderController)
    {
        /*
            order_id:57
            parcel_id:1
            courier_agency:asd agency
            tracking_id:t_id123
            url:http://test.com
        */

        $data = $orderController->update_order_tracking($request);
        $response['error'] = $data->original['error'];
        $response['message'] = $data->original['message'];

        return response()->json($response);
    }

    public function get_sales_list(Request $request, SellerOrderController $orderController, ReportController $reportController)
    {
        /*
          start_date : 2020-09-07 or 2020/09/07 { optional }
          end_date : 2021-03-15 or 2021/03/15 { optional }
          limit:25            // { default - 25 } optional
          offset:0            // { default - 0 } optional
          sort:               // { id } optional
          order:DESC/ASC      // { default - DESC } optional
          search:value        // {optional}
        */

        $rules = [
            'sort' => 'string',
            'limit' => 'numeric',
            'offset' => 'numeric',
            'store_id' => 'required|numeric|exists:stores,id',

        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            if (auth()->check()) {
                $user_id = auth()->user()->id;
                $seller_id = Seller::where('user_id', $user_id)->value('id');
            }
            $request['seller_id'] = $seller_id;

            $data = $reportController->get_sales_list($request);

            return response()->json($data);
        }
    }

    public function update_product_status(Request $request)
    {
        /*
            product_id:10
            status:1     {1: active | 0: de-active}
        */

        $rules = [
            'product_id' => 'required|numeric|exists:products,id',
            'status' => 'required|numeric|in:0,1',
            'store_id' => 'required|numeric',

        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            $status = $request->input('status');
            $product_id = $request->input('product_id');
            $store_id = $request->input('store_id');
            if (auth()->check()) {
                $user_id = auth()->user()->id;
                $seller_id = Seller::where('user_id', $user_id)->value('id');
            }

            // Block product status change if store is deactivated
            if ($guard = $this->ensureActiveStore($seller_id, (int) $store_id)) {
                return $guard;
            }
            $seller_data = fetchDetails(SellerStore::class, ['seller_id' => $seller_id, 'store_id' => $store_id], ['category_ids', 'permissions']);
            $permissions = !$seller_data->isEmpty() ? json_decode($seller_data[0]->permissions, true) : [];
            // dd($permissions);
            if ($permissions['require_products_approval'] == 1) {
                $response['error'] = true;
                $response['message'] = "Seller does not have permission to update status.";
                $response['language_message_key'] = 'seller_does_not_have_permission_to_update_status';
                return response()->json($response);
            } else {
                if (updateDetails(['status' => $status], ['id' => $product_id], Product::class)) {
                    $response['error'] = false;
                    $response['message'] = "Status Updated Successfully";
                    $response['language_message_key'] = 'status_updated_successfully';
                } else {
                    $response['error'] = true;
                    $response['message'] = "Status not Updated.";
                    $response['language_message_key'] = 'status_not_updated';
                }
                return response()->json($response);
            }
        }
    }

    public function get_countries_data(Request $request, ProductController $productController)
    {
        /*

          limit:25            // { default - 25 } optional
          offset:0            // { default - 0 } optional
          search:value        // {optional}
        */

        $rules = [
            'limit' => 'numeric',
            'offset' => 'numeric',

        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {

            $query = DB::table('countries')->get();
            $total = $query->count();
            $contries = $productController->get_countries($request, true);

            if (!$contries->isEmpty()) {
                $response['error'] = false;
                $response['message'] = "Countries Retrived Successfully";
                $response['language_message_key'] = 'countries_retrieved_successfully';
                $response['total'] = $total;
                $response['data'] = $contries;
            } else {
                $response['error'] = true;
                $response['message'] = "Countries Not Found";
                $response['language_message_key'] = "countries_not_found";
                $response['total'] = "";
                $response['data'] = [];
            }

            return response()->json($response);
        }
    }

    function get_brand_list(Request $request, ProductController $productController)
    {
        /*
          store_id :1
          limit:25            // { default - 25 } optional
          offset:0            // { default - 0 } optional
          search:value        // {optional}
        */
        $rules = [
            'store_id' => 'required|numeric|exists:stores,id',
            'limit' => 'numeric',
            'offset' => 'numeric',

        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            $store_id = $request->store_id ?? '';
            $language_code = $request->attributes->get('language_code');
            $query = Brand::where('store_id', $store_id)->where('status', '1');
            $total = $query->count();
            $brands = $productController->get_brands($request, $request->search ?? '', true);
            foreach ($brands as $row) {
                $row->image = app(MediaService::class)->getMediaImageUrl($row->image);
                $row->name = app(TranslationService::class)->getDynamicTranslation(Brand::class, 'name', $row->id, $language_code);
            }
            $user_id = Auth::user()->id;

            $seller_id = Seller::where('user_id', $user_id)->value('id');
            $requested_brands = Brand::where('store_id', $store_id)
                ->where('seller_id', $seller_id)
                ->where('status', 2)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($row) use ($language_code) {

                    $translated = app(TranslationService::class)
                        ->getDynamicTranslation(Brand::class, 'name', $row->id, $language_code);

                    return [
                        "id" => $row->id,
                        "seller_id" => $row->seller_id,
                        "store_id" => $row->store_id,
                        "name" => $translated,
                        "slug" => $row->slug,
                        "image" => app(MediaService::class)->getMediaImageUrl($row->image),
                        "status" => $row->status,
                        "created_at" => $row->created_at,
                        "updated_at" => $row->updated_at,

                        "text" => $translated,
                        "state" => ["opened" => true],
                        "icon" => "jstree-folder",
                        "level" => 0,
                    ];
                });
            if (!$brands->isEmpty()) {
                $response['error'] = false;
                $response['message'] = "Brands Retrived Successfully";
                $response['language_message_key'] = 'brands_retrieved_successfully';
                $response['total'] = $total;
                $response['data'] = $brands;
                $response['requested_brands'] = $requested_brands;
            } else {
                $response['error'] = true;
                $response['message'] = "Brands Not Found";
                $response['language_message_key'] = 'brands_not_found';
                $response['data'] = [];
            }

            return response()->json($response);
        }
    }

    public function add_product_faqs(Request $request)
    {
        /*
            product_id:25
            question:this is test question?
            answer: this is test answer.
            product_type:regular // {regular / combo}
        */
        $rules = [
            'product_id' => 'required|numeric',
            'question' => 'required|string',
            'answer' => 'required|string',
            'product_type' => 'required'

        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            if (auth()->check()) {
                $user_id = auth()->user()->id;
                $seller_id = Seller::where('user_id', $user_id)->value('id');
            }
            $request['seller_id'] = $seller_id;
            $product_id = $request->input('product_id');
            $product_type = request('product_type') != null ? Str::lower(request('product_type')) : "";
            $answer = $request->input('answer');
            $question = $request->input('question');
            $faq_data = [];
            if ($product_type == 'regular') {
                $product = Product::find($product_id);
                if (!$product) {
                    $response = [
                        'error' => true,
                        'message' => 'Product not available.',
                        'language_message_key' => 'product_not_available',
                        'data' => [],
                    ];
                    return response()->json($response);
                }
                $product_name = $product->name;
                $product_type = 'regular';
                $product_faqs = new ProductFaq([
                    'product_id' => $product_id,
                    'seller_id' => $seller_id,
                    'user_id' => $user_id,
                    'question' => $question,
                    'answer' => $answer,
                    'answered_by' => $seller_id,
                ]);

                $product_faqs->save();

                $result = ProductFaq::where('id', $product_faqs->id)
                    ->where('product_id', $product_id)
                    ->where('user_id', $user_id)
                    ->get();
            }
            if ($product_type == 'combo') {
                $combo_product = ComboProduct::find($product_id);
                if (!$combo_product) {
                    $response = [
                        'error' => true,
                        'message' => 'Product not available.',
                        'language_message_key' => 'product_not_available',
                        'data' => [],
                    ];
                    return response()->json($response);
                }
                $product_name = $combo_product->title;
                $product_type = 'combo';
                $product_faqs = new ComboProductFaq([
                    'seller_id' => $seller_id,
                    'product_id' => $product_id,
                    'user_id' => $user_id,
                    'question' => $question,
                    'answer' => $answer,
                    'answered_by' => $seller_id,
                ]);

                $product_faqs->save();

                $result = ComboProductFaq::where('id', $product_faqs->id)
                    ->where('product_id', $product_id)
                    ->where('user_id', $user_id)
                    ->get();
            }

            foreach ($result as $value) {
                $fields = [
                    'id',
                    'user_id',
                    'seller_id',
                    'product_id',
                    'votes',
                    'question',
                    'answer',
                    'answered_by',
                    'created_at',
                    'updated_at',
                ];

                foreach ($fields as $field) {
                    $faq_data[$field] = ($value->$field == null) ? "" : $value->$field;
                }
                $seller_user_id = Seller::where('id', $value->answered_by)->value('user_id');
                $answered_by_user = User::find($seller_user_id);
                $faq_data['answered_by'] = $answered_by_user ? $answered_by_user->username : '';
                $decoded = json_decode($product_name, true);

                $faq_data['product_name'] = $decoded['en'];
                $faq_data['type'] = $product_type;
            }

            return response()->json([
                'error' => false,
                'message' => 'FAQs added successfully',
                'language_message_key' => 'faqs_added_successfully',
                'data' => $faq_data ? $faq_data : []
            ]);
        }
    }


    public function get_product_faqs(Request $request)
    {
        $rules = [
            'id' => 'nullable|numeric',
            'product_id' => 'nullable|numeric',
            'seller_id' => 'nullable|numeric',
            'limit' => 'nullable|numeric',
            'offset' => 'nullable|numeric',
            'type' => 'nullable|string|in:regular,combo',
            'search' => 'nullable|string',
            'sort' => 'nullable|string',
            'order' => 'nullable|string|in:ASC,DESC',
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $limit = $request->input('limit', 25);
        $offset = $request->input('offset', 0);
        $sort = $request->input('sort', 'id');
        $order = $request->input('order', 'DESC');
        $search = $request->input('search', '');
        $id = $request->input('id');
        $product_id = $request->input('product_id');
        $type = $request->input('type');
        $auth_seller_id = auth()->user()->id;
        $language_code = $request->attributes->get('language_code');
        $seller_id = Seller::where('user_id', $auth_seller_id)->value('id');

        $faqQuery = null;

        if ($type == 'regular' || !$type) {
            $faqQuery = ProductFaq::with(['answeredBy.user', 'product'])
                ->where('seller_id', $seller_id)
                ->when($id, fn($query) => $query->where('id', $id))
                ->when($product_id, fn($query) => $query->where('product_id', $product_id))
                ->when($search, fn($query) => $query->where('question', 'like', "%$search%"));
        }

        if ($type == 'combo') {
            $faqQuery = ComboProductFaq::with(['answeredBy.user', 'comboProduct'])
                ->where('seller_id', $seller_id)
                ->when($id, fn($query) => $query->where('id', $id))
                ->when($product_id, fn($query) => $query->where('product_id', $product_id))
                ->when($search, fn($query) => $query->where('question', 'like', "%$search%"));
        }

        if ($faqQuery) {
            // Get total count before applying limit/offset (clone to preserve original query)
            $total = (clone $faqQuery)->count();

            // Apply sorting and pagination
            $faqs = $faqQuery->orderBy($sort, $order)
                ->offset($offset)
                ->limit($limit)
                ->get()
                ->map(function ($faq) use ($language_code) {
                    $faq->type = $faq instanceof ProductFaq ? 'regular' : 'combo';

                    // Get product name translation
                    $faq->product_name = $faq->type === 'regular'
                        ? app(TranslationService::class)->getDynamicTranslation(Product::class, 'name', $faq->product_id, $language_code)
                        : app(TranslationService::class)->getDynamicTranslation(ComboProduct::class, 'title', $faq->product_id, $language_code);

                    // Get answered by username
                    $faq->answered_by = optional(optional($faq->answeredBy)->user)->username;
                    $faq->seller_user_id = optional($faq->answeredBy)->user_id;
                    // Optional: remove full relations to clean up JSON
                    unset($faq->product, $faq->votes, $faq->comboProduct, $faq->answeredBy);

                    return $faq;
                });

            return response()->json([
                'error' => $total > 0 ? false : true,
                'message' => $total > 0 ? 'FAQs retrieved successfully' : 'No FAQs found',
                'total' => $total,
                'data' => $faqs,
            ]);
        }
    }



    public function delete_product_faq(Request $request, ProductFaqController $productFaqContrller, ComboProductFaqController $ComboProductFaqController)
    {
        /*
            id:2    // {optional} Product FAQ Id

        */
        $rules = [
            'id' => 'required|numeric',
            'type' => 'required|string|in:regular,combo'
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            $id = $request->input('id');
            $type = $request->input('type') ?? "";
            if (isset($type) && $type = 'regular') {

                $data = $productFaqContrller->destroy($id);
            } else {
                $data = $ComboProductFaqController->destroy($id);
            }

            return response()->json($data->original);
        }
    }

    public function edit_product_faq(Request $request, ProductFaqController $productFaqController)
    {
        /*
          edit_id:1 // product FAQ id
          answer: this is test answer.
          type: regular | combo // Product type
        */

        $rules = [
            'edit_id' => 'required|numeric',
            'answer' => 'required',
            'type' => 'required',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            if (auth()->check()) {
                $user_id = auth()->user()->id;
                $seller_id = Seller::where('user_id', $user_id)->value('id');
            }
            $request['seller_id'] = $seller_id;

            // Update the product FAQ using the provided controller method
            $data = $productFaqController->update($request, $request['edit_id'], true);

            // If update fails
            if (empty($data)) {
                $response = [
                    'error' => true,
                    'message' => "Not Updated. Try again later.",
                    'language_message_key' => "update_failed_try_again_later",
                    'data' => [],
                ];
                return response()->json($response);
            }

            // Fetch the product name and type after updating
            $product_id = $data->product_id;
            $product_type = $request->input('type'); // Fetch type from request

            // Fetch the product details based on type
            if ($product_type == 'regular') {
                $product = Product::find($product_id);
                $product_name = $product ? $product->name : '';
            } else if ($product_type == 'combo') {
                $combo_product = ComboProduct::find($product_id);
                $product_name = $combo_product ? $combo_product->title : '';
            } else {
                $product_name = ''; // In case the type is invalid
            }

            // Prepare the response with product details
            $response = [
                'error' => false,
                'message' => "Product FAQ Updated Successfully.",
                'language_message_key' => "product_faq_updated_successfully",
                'data' => [
                    'id' => $data->id,
                    'question' => $data->question,
                    'answer' => $data->answer,
                    'product_name' => $product_name,
                    'type' => $product_type,
                    'answered_by' => $data->answered_by,
                    'created_at' => $data->created_at,
                    'updated_at' => $data->updated_at,
                ],
            ];

            return response()->json($response);
        }
    }


    public function manage_stock(Request $request)
    {
        /*
            product_variant_id:156
            quantity:5
            type:add/subtract
        */

        $rules = [
            'product_variant_id' => 'required|numeric|exists:product_variants,id',
            'quantity' => 'required|numeric',
            'type' => 'required',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            // Block stock changes if store is deactivated
            $variant = Product_variants::find($request['product_variant_id']);
            if ($variant && auth()->check()) {
                $product = Product::find($variant->product_id);
                if ($product) {
                    $user_id = auth()->user()->id;
                    $seller_id = Seller::where('user_id', $user_id)->value('id');
                    if ($guard = $this->ensureActiveStore($seller_id, (int) $product->store_id)) {
                        return $guard;
                    }
                }
            }

            if ((isset($request['type']) && $request['type'] == 'add')) {
                app(ProductService::class)->updateStock([$request['product_variant_id']], [$request['quantity']], 'plus');
                $product_id = fetchDetails(Product_variants::class, ['id' => $request['product_variant_id']], 'product_id');
                $product_id = isset($product_id) && !empty($product_id) ? $product_id[0]->product_id : "";
                $product_details = app(ProductService::class)->fetchProduct('', '', $product_id);
                $product_details = !empty($product_details['product']) ? $product_details['product'] : '';
                $response['error'] = false;
                $response['message'] = 'Stock Updated Successfully';
                $response['language_message_key'] = 'stock_updated_successfully';
                $response['data'] = $product_details;
                return response()->json($response);
            } else if (isset($request['type']) && $request['type'] == 'subtract') {
                if ($request['quantity'] > $request['current_stock']) {
                    $response['error'] = true;
                    $response['message'] = "Subtracted stock cannot be greater than current stock";
                    $response['language_message_key'] = 'subtract_stock_greater_than_current_stock';
                    $response['data'] = array();
                    return response()->json($response);
                }
                app(ProductService::class)->updateStock([$request['product_variant_id']], [$request['quantity']]);
                $product_id = fetchDetails(Product_variants::class, ['id' => $request['product_variant_id']], 'product_id');
                $product_id = isset($product_id) && !empty($product_id) ? $product_id[0]->product_id : "";
                $product_details = app(ProductService::class)->fetchProduct('', '', $product_id);
                $product_details = !empty($product_details['product']) ? $product_details['product'] : '';
                $response['error'] = false;
                $response['message'] = 'Stock Updated Successfully';
                $response['language_message_key'] = 'stock_updated_successfully';
                $response['data'] = $product_details;
                return response()->json($response);
            } else {
                $response['error'] = true;
                $response['message'] = 'Stock Not Updated';
                $response['language_message_key'] = 'stock_not_updated';
                $response['data'] = array();
                return response()->json($response);
            }
        }
    }

    public function manage_combo_stock(Request $request)
    {

        $rules = [
            'product_id' => 'required|numeric|exists:combo_products,id',
            'quantity' => 'required|numeric|min:1',
            'type' => 'required|in:add,subtract',
            'current_stock' => 'required_if:type,subtract|numeric|min:0',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $product_id = $request->input('product_id');
        $quantity = $request->input('quantity');
        $type = $request->input('type');
        $current_stock = $request->input('current_stock');



        // Block stock changes if store is deactivated
        $comboProduct = ComboProduct::find($product_id);
        if ($comboProduct && auth()->check()) {
            $user_id = auth()->user()->id;
            $seller_id = Seller::where('user_id', $user_id)->value('id');
            if ($guard = $this->ensureActiveStore($seller_id, (int) $comboProduct->store_id)) {
                return $guard;
            }
        }

        // Handle stock operations
        if ($type === 'add') {
            app(ComboProductService::class)->updateComboStock($product_id, $quantity, 'add');
            // Fetch product details
            $product_details = app(ComboProductService::class)->fetchComboProduct('', '', $product_id);
            $product_details = $product_details['combo_product'] ?? '';
            return response()->json([
                'error' => false,
                'message' => 'Stock Updated Successfully',
                'language_message_key' => 'stock_updated_successfully',
                'data' => $product_details,
            ]);
        }

        if ($type === 'subtract') {
            // Check if subtraction is possible
            if ($quantity > $current_stock) {
                return response()->json([
                    'error' => true,
                    'message' => 'Subtracted stock cannot be greater than current stock',
                    'language_message_key' => 'subtract_stock_greater_than_current_stock',
                    'data' => [],
                ]);
            }

            app(ComboProductService::class)->updateComboStock($product_id, $quantity, 'subtract');
            // Fetch product details
            $product_details = app(ComboProductService::class)->fetchComboProduct('', '', $product_id);
            $product_details = $product_details['combo_product'] ?? '';
            return response()->json([
                'error' => false,
                'message' => 'Stock Updated Successfully',
                'language_message_key' => 'stock_updated_successfully',
                'data' => $product_details,
            ]);
        }

        return response()->json([
            'error' => true,
            'message' => 'Stock Not Updated',
            'language_message_key' => 'stock_not_updated',
            'data' => [],
        ]);
    }


    public function add_pickup_location(Request $request, PickupLocationController $pickupLocationController)
    {
        /*

         pickup_location : Croma Digital
         name:admin // shipper's name
         email : admin123@gmail.com
         phone : 1234567890
         address : 201,time square,mirjapar hignway // note : must add specific address like plot_no/street_no/office_no etc.
         address2 : near prince lawns
         city : bhuj
         state : gujarat
         country : india
         pincode : 370001
         latitude : 23.5643445644
         longitude : 69.312531534
         status : 0/1 {default :0}
        */

        $rules = [
            'pickup_location' => 'required|string|min:3|max:100',
            'name' => 'required|string|min:2|max:100',
            'email' => 'required|email|max:100',
            'phone' => 'required|numeric|digits_between:4,15',
            'address' => 'required|string|min:10|max:255|regex:/^(?=.*\d)(?=.*[a-zA-Z]).+$/',
            'address2' => 'required|string|min:3|max:255',
            'city' => 'required|string|min:2|max:100|regex:/^[a-zA-Z\s\-]+$/',
            'state' => 'required|string|min:2|max:100|regex:/^[a-zA-Z\s\-]+$/',
            'country' => 'required|string|min:2|max:100|regex:/^[a-zA-Z\s\-]+$/',
            'pincode' => 'required|string|min:3|max:10|regex:/^[a-zA-Z0-9\s\-]+$/',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ];

        $messages = [
            'pickup_location.required' => 'Pickup location name is required (3-100 characters)',
            'pickup_location.min' => 'Pickup location name must be at least 3 characters',
            'pickup_location.max' => 'Pickup location name cannot exceed 100 characters',
            'name.required' => 'Shipper name is required (2-100 characters)',
            'name.min' => 'Shipper name must be at least 2 characters',
            'name.max' => 'Shipper name cannot exceed 100 characters',
            'email.required' => 'Email address is required (valid email format, max 100 characters)',
            'email.email' => 'Please enter a valid email address',
            'email.max' => 'Email address cannot exceed 100 characters',
            'phone.required' => 'Phone number is required (4-15 digits, numbers only)',
            'phone.numeric' => 'Phone number must contain only digits',
            'phone.digits_between' => 'Phone number must be between 4 and 15 digits',
            'address.required' => 'Address must be at least 10 characters with both letters and numbers (e.g., Plot 12, Main Street)',
            'address.min' => 'Address must be at least 10 characters with both letters and numbers',
            'address.max' => 'Address cannot exceed 255 characters',
            'address.regex' => 'Address must contain both letters and numbers (e.g., Plot 12, Main Street)',
            'address2.required' => 'Address line 2 is required (3-255 characters)',
            'address2.min' => 'Address line 2 must be at least 3 characters',
            'address2.max' => 'Address line 2 cannot exceed 255 characters',
            'city.required' => 'City is required (2-100 characters, letters only)',
            'city.min' => 'City name must be at least 2 characters',
            'city.max' => 'City name cannot exceed 100 characters',
            'city.regex' => 'City name must contain only letters, spaces, and hyphens',
            'state.required' => 'State is required (2-100 characters, letters only)',
            'state.min' => 'State name must be at least 2 characters',
            'state.max' => 'State name cannot exceed 100 characters',
            'state.regex' => 'State name must contain only letters, spaces, and hyphens',
            'country.required' => 'Country is required (2-100 characters, letters only)',
            'country.min' => 'Country name must be at least 2 characters',
            'country.max' => 'Country name cannot exceed 100 characters',
            'country.regex' => 'Country name must contain only letters, spaces, and hyphens',
            'pincode.required' => 'Pincode is required (3-10 characters, alphanumeric)',
            'pincode.min' => 'Pincode must be at least 3 characters',
            'pincode.max' => 'Pincode cannot exceed 10 characters',
            'pincode.regex' => 'Pincode must contain only letters, numbers, spaces, and hyphens',
            'latitude.required' => 'Latitude is required (number between -90 and 90)',
            'latitude.numeric' => 'Latitude must be a valid number',
            'latitude.between' => 'Latitude must be between -90 and 90',
            'longitude.required' => 'Longitude is required (number between -180 and 180)',
            'longitude.numeric' => 'Longitude must be a valid number',
            'longitude.between' => 'Longitude must be between -180 and 180',
        ];

        if ($response = $this->HandlesValidation($request, $rules, $messages, null, true)) {
            return $response;
        } else {
            if (auth()->check()) {
                $user_id = auth()->user()->id;
                $seller_id = Seller::where('user_id', $user_id)->value('id');
                $request['seller_id'] = $seller_id;
                try {
                    $result = $pickupLocationController->store($request);
                    if ($result instanceof \Illuminate\Http\JsonResponse) {
                        $data = $result->getData(true);
                    } else {
                        $data = $result;
                    }
                } catch (\Illuminate\Validation\ValidationException $e) {
                    // Extract error from validation exception
                    $responseData = $e->response->getData(true);
                    $errorString = $responseData['message'] ?? json_encode($e->errors());

                    // Try to decode if it's a JSON string
                    $decoded = json_decode($errorString, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $firstField = array_key_first($decoded);
                        if (isset($decoded[$firstField]) && is_array($decoded[$firstField]) && isset($decoded[$firstField][0])) {
                            $errorString = $decoded[$firstField][0];
                        } elseif (isset($decoded[$firstField]) && is_string($decoded[$firstField])) {
                            $errorString = $decoded[$firstField];
                        }
                    }

                    $data = ['errors' => $errorString];
                }
                if (isset($data['success']) && $data['success'] == true) {
                    $response['error'] = false;
                    $response['message'] = 'Pickup Location added successfully';
                    $response['language_message_key'] = 'pickup_location_added_successfully';
                    $response['data'] = $data;
                } else {
                    // 1. Extract raw error message/object from various possible keys
                    $rawError = 'Failed to add pickup location';

                    if (isset($data['errors']) && !empty($data['errors'])) {
                        $rawError = $data['errors'];
                    } elseif (isset($data['message']) && !empty($data['message'])) {
                        $rawError = $data['message'];
                    }

                    // 2. Resolve to a string (if array)
                    $errorString = $rawError;
                    if (is_array($rawError)) {
                        $firstField = array_key_first($rawError);
                        if (isset($rawError[$firstField])) {
                            $errorVal = $rawError[$firstField];
                            $errorString = is_array($errorVal) ? ($errorVal[0] ?? json_encode($errorVal)) : $errorVal;
                        } else {
                            $errorString = json_encode($rawError);
                        }
                    }

                    // 3. Try to decode if it's a JSON string
                    // Loop to handle double encoding or nested JSON in message
                    // e.g. "{\"pickup_location\":[\"Error\"]}"
                    for ($i = 0; $i < 2; $i++) {
                        if (is_string($errorString) && (str_starts_with($errorString, '{') || str_starts_with($errorString, '['))) {
                            $decoded = json_decode($errorString, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $firstField = array_key_first($decoded);
                                if (isset($decoded[$firstField])) {
                                    $val = $decoded[$firstField];
                                    // If val is array ["Error message"], take first
                                    if (is_array($val) && isset($val[0])) {
                                        $errorString = $val[0];
                                    } elseif (is_string($val)) {
                                        $errorString = $val;
                                    } else {
                                        // Fallback if structure is unknown
                                        $errorString = json_encode($val);
                                    }
                                }
                            }
                        }
                    }

                    $response['error'] = true;
                    $response['message'] = $errorString;
                }

                return response()->json($response);
            }
        }
    }

    public function get_pickup_locations(Request $request, PickupLocationController $pickupLocationController)
    {
        /*
            seller_id:1
            search : Search keyword // { optional }
            limit:25                // { default - 10 } optional
            offset:0                // { default - 0 } optional
            sort: id                // { default - id } optional
            order:DESC/ASC          // { default - DESC } optional
            status:1           optional
        */

        $rules = [
            'limit' => 'numeric',
            'offset' => 'numeric',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        if (auth()->check()) {
            $user_id = auth()->user()->id;
            $seller_id = Seller::where('user_id', $user_id)->value('id');
        } else {
            return response()->json([
                'error' => true,
                'message' => 'Unauthorized',
                'language_message_key' => 'unauthorized',
            ]);
        }

        // Pagination and sorting settings
        $search = trim($request->input('search', ''));
        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);
        $sort = $request->input('sort', 'id');
        $order = $request->input('order', 'DESC');
        $status = $request->input('status', '');

        // Build the query using Eloquent
        $query = PickupLocation::query();

        // Search filters
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('pickup_location', 'LIKE', "%$search%")
                    ->orWhere('email', 'LIKE', "%$search%")
                    ->orWhere('phone', 'LIKE', "%$search%");
            });
        }

        // Seller-specific filter
        $query->where('seller_id', $seller_id);

        // Status filter - exclude deleted (status = 3) by default
        if ($status !== '') {
            $query->where('status', $status);
        } else {
            // By default, exclude deleted pickup locations (status = 3)
            $query->where('status', '!=', 3);
        }

        // Count total records
        $total = $query->count();

        // Fetch the data with pagination
        $location_data = $query->orderBy($sort, $order)
            ->skip($offset)
            ->take($limit)
            ->get();

        // Format the data for API response
        $data = $location_data->map(function ($row) {
            return [
                'id' => $row->id,
                'pickup_location' => $row->pickup_location,
                'name' => $row->name,
                'email' => $row->email,
                'phone' => $row->phone,
                'address' => $row->address,
                'address2' => $row->address2,
                'city' => $row->city,
                'state' => $row->state,
                'country' => $row->country,
                'pincode' => $row->pincode,
                'latitude' => $row->latitude,
                'longitude' => $row->longitude,
                'status' => $row->status,
            ];
        })->toArray();

        return response()->json([
            'error' => empty($data) ? true : false,
            'message' => !empty($data) ? 'Pickup Location retrieved successfully' : 'No pickup location found!',
            'language_message_key' => !empty($data) ? 'pickup_location_retrieved_successfully' : 'no_pickup_location_found',
            'total' => $total,
            'data' => $data,
        ]);
    }

    public function delete_pickup_location(Request $request)
    {
        /*
            pickup_location_id:1
        */

        $rules = [
            'pickup_location_id' => 'required|numeric|exists:pickup_locations,id',
        ];

        $messages = [
            'pickup_location_id.required' => 'Pickup location ID is required',
            'pickup_location_id.numeric' => 'Pickup location ID must be a number',
            'pickup_location_id.exists' => 'Pickup location not found',
        ];

        if ($response = $this->HandlesValidation($request, $rules, $messages, null, true)) {
            return $response;
        } else {
            if (auth()->check()) {
                $user_id = auth()->user()->id;
                $seller_id = Seller::where('user_id', $user_id)->value('id');

                // Find the pickup location
                $pickupLocation = PickupLocation::find($request['pickup_location_id']);

                if (!$pickupLocation) {
                    $response['error'] = true;
                    $response['message'] = 'Pickup location not found';
                    $response['language_message_key'] = 'pickup_location_not_found';
                    return response()->json($response);
                }

                // Verify that this pickup location belongs to the authenticated seller
                if ($pickupLocation->seller_id != $seller_id) {
                    $response['error'] = true;
                    $response['message'] = 'You are not authorized to delete this pickup location';
                    $response['language_message_key'] = 'unauthorized_pickup_location_delete';
                    return response()->json($response);
                }

                // Remove pickup location reference from all associated products
                Product::where('pickup_location', $pickupLocation->id)->update(['pickup_location' => null]);
                
                // Remove pickup location reference from all associated combo products
                \App\Models\ComboProduct::where('pickup_location', $pickupLocation->id)->update(['pickup_location' => null]);

                // Soft delete: Set status to 3
                $pickupLocation->status = 3;
                $pickupLocation->save();

                $response['error'] = false;
                $response['message'] = 'Pickup location deleted successfully';
                $response['language_message_key'] = 'pickup_location_deleted_successfully';
                $response['data'] = [];

                return response()->json($response);
            }
        }
    }

    public function create_shiprocket_order(Request $request, SellerOrderController $ordercController)
    {
        /*
            order_id:120
            user_id:1
            pickup_location:croma digital
            parcel_weight:1 (in kg)
            parcel_height:1 (in cms)
            parcel_breadth:1 (in cms)
            parcel_length:1 (in cms)
        */
        $rules = [
            'order_id' => 'required|numeric',
           
            'pickup_location' => 'required',
            'parcel_weight' => 'required',
            'parcel_height' => 'required',
            'parcel_breadth' => 'required',
            'parcel_length' => 'required',
            'parcel_id' => 'required',
            'store_id' => 'required',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {

            if (auth()->check()) {
                $user_id = auth()->user()->id;
                $request['user_id'] = $user_id;
                $seller_id = Seller::where('user_id', $user_id)->value('id');
            }
   
            $request['shiprocket_seller_id'] = $seller_id;
            $res = app(OrderService::class)->getOrderDetails(['o.id' => $request['order_id']]);
            $request['order_items'] = $res;
            $data = $ordercController->create_shiprocket_order($request, true);
            $response['error'] = $data->original['error'];
            $response['message'] = $data->original['message'];
            $response['data'] = Arr::except($data->original['data'], ['error', 'message']); //use for remove error and message key from response array
            if ($response['error'] == false) {
                $parcel_id = $request->input('parcel_id');
                $order_id = $request->input('order_id');
                $store_id = $request->input('store_id');
                $parcel_data = app(ParcelService::class)->viewAllParcels($order_id, $parcel_id, $seller_id, 0, 1, 'DESC', 1, '', '', $store_id);
                $response['data']['parcel'] = isset($parcel_data->original['data'][0]) ? $parcel_data->original['data'][0] : [];
            }
            return response()->json($response);
        }
    }

    public function generate_awb(Request $request)
    {
        /*
            shipment_id:120
        */

        $rules = [
            'shipment_id' => 'required|numeric',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            $res = app(ShiprocketService::class)->generateAwb($request['shipment_id']);
            if (!empty($res) && isset($res['awb_assign_status']) && $res['awb_assign_status'] == 1) {
                $response['error'] = false;
                $response['message'] = 'AWB generated successfully';
                $response['language_message_key'] = 'awb_generated_successfully';
                $response['data'] = $res;

                $seller_id = 0;
                if (auth()->check()) {
                    $user_id = auth()->user()->id;
                     $request['user_id'] = $user_id;
                    $seller_id = Seller::where('user_id', $user_id)->value('id');
                }
                $order_tracking = OrderTracking::where('shipment_id', $request['shipment_id'])->first();
                if ($order_tracking) {
                    $parcel_id = $order_tracking->parcel_id;
                    $order_id = $order_tracking->order_id;
                    $store_id = Parcel::where('id', $parcel_id)->value('store_id');
                    $parcel_data = app(ParcelService::class)->viewAllParcels($order_id, $parcel_id, $seller_id, 0, 1, 'DESC', 1, '', '', $store_id);
                    $response['data']['parcel'] = isset($parcel_data->original['data'][0]) ? $parcel_data->original['data'][0] : [];
                }
            } else {
                $response['error'] = true;
                $response['message'] = app(ShiprocketService::class)->extractErrorMessage($res, 'AWB not generated');
                $response['language_message_key'] = 'awb_not_generated';
                $response['data'] = $res;
            }
            return response()->json($response);
        }
    }

    public function send_pickup_request(Request $request)
    {
        /*
            shipment_id:120
        */

        $rules = [
            'shipment_id' => 'required|numeric',
        ];
        
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            $res = app(ShiprocketService::class)->sendPickupRequest($request['shipment_id']);

            if (!empty($res)) {
                $response['error'] = false;
                $response['message'] = 'Request send successfully';
                $response['language_message_key'] = 'request_sent_successfully';
                $response['data'] = $res;

                $seller_id = 0;
                if (auth()->check()) {
                    $user_id = auth()->user()->id;
                     $request['user_id'] = $user_id;
                    $seller_id = Seller::where('user_id', $user_id)->value('id');
                }
                $order_tracking = OrderTracking::where('shipment_id', $request['shipment_id'])->first();
                if ($order_tracking) {
                    $parcel_id = $order_tracking->parcel_id;
                    $order_id = $order_tracking->order_id;
                    $store_id = Parcel::where('id', $parcel_id)->value('store_id');
                    $parcel_data = app(ParcelService::class)->viewAllParcels($order_id, $parcel_id, $seller_id, 0, 1, 'DESC', 1, '', '', $store_id);
                    $response['data']['parcel'] = isset($parcel_data->original['data'][0]) ? $parcel_data->original['data'][0] : [];
                }
            } else {
                $response['error'] = true;
                $response['message'] = 'Request not sent';
                $response['language_message_key'] = 'request_not_sent';
                $response['data'] = array();
            }
            return response()->json($response);
        }
    }

    public function generate_label(Request $request)
    {
        /*
            shipment_id:120
        */
        $rules = [
            'shipment_id' => 'required|numeric',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            $res = app(ShiprocketService::class)->generateLabel($request['shipment_id']);
            if (!empty($res)) {
                $response['error'] = false;
                $response['message'] = 'Label generated successfully';
                $response['language_message_key'] = 'label_generated_successfully';
                $response['data'] = $res;
            } else {
                $response['error'] = true;
                $response['message'] = 'Label not generated';
                $response['language_message_key'] = 'label_not_generated';
                $response['data'] = array();
            }
            return response()->json($response);
        }
    }

    public function generate_invoice(Request $request)
    {
        /*
            shiprocket_order_id:120
        */

        $rules = [
            'shiprocket_order_id' => 'required',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {

            $res = app(ShiprocketService::class)->generateInvoice($request['shiprocket_order_id']);
            if (!empty($res) && isset($res['is_invoice_created']) && $res['is_invoice_created'] == 1) {
                $response['error'] = false;
                $response['message'] = 'Invoice generated successfully';
                $response['language_message_key'] = 'invoice_generated_successfully';
                $response['data'] = $res;
            } else {
                $response['error'] = true;
                $response['message'] = 'Invoice not generated';
                $response['language_message_key'] = 'invoice_not_generated';
                $response['data'] = array();
            }
            return response()->json($response);
        }
    }

    public function cancel_shiprocket_order(Request $request)
    {
        /*
            shiprocket_order_id:120
        */
        $rules = [
            'shiprocket_order_id' => 'required',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            $res = app(ShiprocketService::class)->cancelShiprocketOrder($request['shiprocket_order_id']);

           
            if (!empty($res) && (isset($res['status']) && $res['status'] == 200 || $res['status_code'] == 200)) {
                $response['error'] = false;
                $response['message'] = 'Order cancelled successfully';
                $response['language_message_key'] = 'order_cancelled_successfully';
                $response['data'] = $res['data'];
            } else {
                $response['error'] = true;
                $response['message'] = 'Order not cancelled';
                $response['language_message_key'] = 'order_not_cancelled';
                $response['data'] = array();
            }
            return response()->json($response);
        }
    }

    public function download_label(Request $request)
    {
        /*
            shipment_id:120
        */

        $rules = [
            'shipment_id' => 'required',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            $res = fetchDetails(OrderTracking::class, ['shipment_id' => $request['shipment_id']], 'label_url')[0]->label_url;
            if (isset($res) && !empty($res)) {
                $response['error'] = false;
                $response['message'] = 'Data retrived successfully';
                $response['language_message_key'] = 'data_retrieved_successfully';
                $response['data'] = $res;
            } else {
                $response['error'] = true;
                $response['message'] = 'Data not retrived';
                $response['language_message_key'] = 'data_not_retrieved';
                $response['data'] = array();
            }
            return response()->json($response);
        }
    }

    public function download_invoice(Request $request)
    {
        /*
            shipment_id:120
        */
        $rules = [
            'shipment_id' => 'required',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            $res = fetchDetails(OrderTracking::class, ['shipment_id' => $request['shipment_id']], 'invoice_url')[0]->invoice_url;
            if (isset($res) && !empty($res)) {
                $response['error'] = false;
                $response['message'] = 'Data retrived successfully';
                $response['language_message_key'] = 'data_retrieved_successfully';
                $response['data'] = $res;
            } else {
                $response['error'] = true;
                $response['language_message_key'] = 'data_not_retrieved';
                $response['data'] = array();
            }
            return response()->json($response);
        }
    }

    public function shiprocket_order_tracking(Request $request)
    {
        /*
            awb_code:120
        */

        $rules = [
            'awb_code' => 'required',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            $res = "https://shiprocket.co/tracking/" . $request['awb_code'];
            if (isset($res) && !empty($res)) {
                $response['error'] = false;
                $response['message'] = 'Data retrived successfully';
                $response['language_message_key'] = 'data_retrieved_successfully';
                $response['data'] = $res;
            } else {
                $response['error'] = true;
                $response['message'] = 'Data not retrived';
                $response['language_message_key'] = 'data_not_retrieved';
                $response['data'] = array();
            }
            return response()->json($response);
        }
    }
    public function get_shiprocket_order(Request $request)
    {
        /*
            shiprocket_order_id:120
        */

        $rules = [
            'shiprocket_order_id' => 'required',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            $shiprocket_order = app(ShiprocketService::class)->getShiprocketOrder($request['shiprocket_order_id']);

            
            if (!isset($shiprocket_order['error_id']) && isset($shiprocket_order) && !empty($shiprocket_order)) {
                $response['error'] = false;
                $response['message'] = 'Data retrived successfully';
                $response['language_message_key'] = 'data_retrieved_successfully';
                $response['data']['status'] = $shiprocket_order['data']['status'];
            } else {
                $response['error'] = true;
                $response['message'] = 'Data not retrived';
                $response['language_message_key'] = 'data_not_retrieved';
                $response['data'] = array();
            }
            return response()->json($response);
        }
    }

    public function delete_order(Request $request)
    {
        /*
            order_id:120
        */
        $rules = [
            'order_id' => 'required|numeric|exists:orders,id',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            $order_id = $request['order_id'];
            
            // Fetch order items and restore stock before deletion
            $order_items = fetchDetails(OrderItems::class, ['order_id' => $order_id], ['product_variant_id', 'quantity', 'order_type']);
            foreach ($order_items as $order_item) {
                if ($order_item->order_type == 'regular_order') {
                    app(\App\Services\ProductService::class)->updateStock($order_item->product_variant_id, $order_item->quantity, 'plus');
                }
                if ($order_item->order_type == 'combo_order') {
                    app(\App\Services\ComboProductService::class)->updateComboStock($order_item->product_variant_id, $order_item->quantity, 'plus');
                }
            }
            
            deleteDetails(['id' => $order_id], Order::class);
            deleteDetails(['order_id' => $order_id], OrderItems::class);

            $response['error'] = false;
            $response['message'] = 'Order deleted successfully';
            $response['language_message_key'] = 'order_deleted_successfully';
            $response['data'] = array();
            return response()->json($response);
        }
    }

    public function get_settings(AddressController $addressController, Request $request)
    {
        /*
            type : payment_method // { default : all  } optional
            limit:25            // { default - 25 } optional
            offset:0            // { default - 0 } optional
        */
        $rules = [
            'type' => 'sometimes|in:payment_method,store_setting',
            'store_id' => 'sometimes|numeric|exists:stores,id',
            'limit' => 'sometimes|numeric',
            'offset' => 'sometimes|numeric',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {

            $type = $request->input('type', 'all');
            $limit = $request->input('limit', 25);
            $offset = $request->input('offset', 0);
            $tags = $general_settings = array();
            $user_id = auth('sanctum')->check() ? auth('sanctum')->id() : '';

            $store_id = $request->input('store_id', '');
            if ($type == 'store_setting') {
                $rules = [
                    'store_id' => 'sometimes|numeric|required',
                ];
                if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
                    return $response;
                }
            }

            if ($type == 'all' || $type == 'payment_method' || $type == 'store_setting') {


                $filter['tags'] = $request->input('tags', '');

                $products = app(ProductService::class)->fetchProduct(null, $filter, null, null, $limit, $offset, 'products.id', 'DESC', null);

                for ($i = 0; $i < count($products); $i++) {
                    if (!empty($products['product'][$i]->tags)) {
                        $tags = array_merge($tags, $products['product'][$i]->tags);
                    }
                }
                $settings = [
                    'logo' => 0,
                    'seller_privacy_policy' => 1,
                    'seller_terms_and_conditions' => 1,
                    'fcm_server_key' => 1,
                    'contact_us' => 1,
                    'payment_method' => 1,
                    'about_us' => 1,
                    'currency' => 0,
                    'time_slot_config' => 1,
                    'user_data' => 0,
                    'system_settings' => 1,
                    'shipping_policy' => 1,
                    'return_policy' => 1,
                    'shipping_method' => 1,
                    'pusher_settings' => 1,
                    'admin_preference' => 1,
                ];
                if ($type == 'payment_method') {
                    $settings_res['payment_method'] = app(SettingService::class)->getSettings($type, $settings[$type]);
                    $settings_res['payment_method'] = json_decode($settings_res['payment_method'], true);

                    if (isset($user_id) && !empty($user_id)) {
                        $cart_total_response = app(CartService::class)->getCartTotal($user_id, false, 0, '', $store_id);

                        $cod_allowed = isset($cart_total_response[0]->is_cod_allowed) ? $cart_total_response[0]->is_cod_allowed : 1;
                        $settings_res['is_cod_allowed'] = $cod_allowed;
                    } else {
                        $settings_res['is_cod_allowed'] = 1;
                    }

                    $general_settings = $settings_res;
                } elseif ($type == 'store_setting') {
                } else {

                    foreach ($settings as $type => $isjson) {
                        if ($type == 'payment_method') {
                            continue;
                        }

                        $general_settings[$type] = [];

                        $settings_res = app(SettingService::class)->getSettings($type, $isjson);
                        $settings_res = json_decode($settings_res, true);

                        if ($type == 'logo') {
                            $logo_setting = app(SettingService::class)->getSettings('system_settings', true);
                            $logo_setting = json_decode($logo_setting, true);
                            $settings_res = app(MediaService::class)->getMediaImageUrl($logo_setting['logo']);
                        }
                        if ($type == 'user_data' && isset($user_id) && !empty($user_id)) {
                            $cart_total_response = app(CartService::class)->getCartTotal($user_id, false, 0, '', $store_id);
                            $res = $addressController->getAddress($user_id, null, false, true);
                            if (!empty($res[0])) {
                                $zipcode_id = fetchDetails(Zipcode::class, ['zipcode' => $res[0]->pincode], 'id')[0]->id;
                                if (!empty($zipcode_id)) {
                                    $zipcode = fetchDetails(Zipcode::class, ['id' => $zipcode_id], 'zipcode')[0]->zipcode;
                                }
                            }
                            $settings_res = fetchUsers($user_id);
                            $settings_res = [
                                'cities' => $settings_res->cities,
                                'street' => $settings_res->street,
                                'area' => $settings_res->area,
                                'cart_total_items' => 0, // Initialize to 0, you can update it later
                                'pincode' => isset($zipcode) ? $zipcode : '',
                            ];
                        } elseif ($type == 'user_data' && !isset($user_id)) {
                            $settings_res = '';
                        }
                        // //Strip tags in case of terms_conditions and privacy_policy

                        if ($isjson && isset($settings_res[$type])) {
                            array_push($general_settings[$type], $settings_res[$type]);
                        } else {
                            array_push($general_settings[$type], $settings_res);
                        }
                    }

                    $general_settings['system_settings'][0]['store_currency'] = isset($general_settings['system_settings'][0]['store_currency']) && $general_settings['system_settings'][0]['store_currency'] !== null ? $general_settings['system_settings'][0]['store_currency'] : '';
                    $general_settings['system_settings'][0]['sidebar_color'] = isset($general_settings['system_settings'][0]['sidebar_color']) && $general_settings['system_settings'][0]['sidebar_color'] !== null ? $general_settings['system_settings'][0]['sidebar_color'] : '';
                    $general_settings['system_settings'][0]['sidebar_type'] = isset($general_settings['system_settings'][0]['sidebar_type']) && $general_settings['system_settings'][0]['sidebar_type'] !== null ? $general_settings['system_settings'][0]['sidebar_type'] : '';
                    $general_settings['user_data'] = (isset($general_settings['user_data'][0]) && !empty($general_settings['user_data'][0])) ? $general_settings['user_data'][0] : [];

                    $currencyDetails = fetchDetails(Currency::class, ['is_default' => 1], 'symbol');
                    $currency = !$currencyDetails->isEmpty() ? $currencyDetails[0]->symbol : '';
                    $general_settings['currency'] = $currency;

                    // Only unset ai_setting if user is not authenticated, otherwise include it in response
                    if (!auth('sanctum')->check() || empty($user_id)) {
                        unset($general_settings['system_settings'][0]['ai_setting']);
                    }

                    if (isset($general_settings['shipping_method'][0])) {
                        unset($general_settings['shipping_method'][0]['password']);
                        unset($general_settings['shipping_method'][0]['email']);
                        unset($general_settings['shipping_method'][0]['webhook_token']);
                    }

                    if (isset($general_settings['system_settings'][0]['on_boarding_image']) && !empty($general_settings['system_settings'][0]['on_boarding_image'])) {
                        $onboarding_images = $general_settings['system_settings'][0]['on_boarding_image'];
                        if (isset($onboarding_images) && !empty($onboarding_images)) {
                            foreach ($onboarding_images as &$image) {
                                $image = app(MediaService::class)->getImageUrl($image, "", "", 'image', 'MEDIA_PATH');
                            }
                        }
                    } else {
                        $onboarding_images = [];
                    }
                    $general_settings['system_settings'][0]['on_boarding_image'] = $onboarding_images;

                    $onboarding_videos = [];
                    if (isset($general_settings['system_settings'][0]['on_boarding_video']) && !empty($general_settings['system_settings'][0]['on_boarding_video'])) {
                        $onboarding_videos = $general_settings['system_settings'][0]['on_boarding_video'];

                        if (isset($onboarding_videos) && !empty($onboarding_videos)) {
                            foreach ($onboarding_videos as &$video) {
                                $video = app(MediaService::class)->getImageUrl($video, "", "", 'image', 'MEDIA_PATH');
                            }
                        }
                    }

                    $general_settings['system_settings'][0]['on_boarding_video'] = $onboarding_videos;
                }
                // Fetch languages to include in settings response
                $languages = Language::select('id', 'language', 'code', 'native_language', 'is_rtl')->get();

                // Auto-detect file type based on controller, allow override via parameter
                $file_type = $request->input('file_type', $this->detectFileType());

                // Convert is_rtl to integer and add file metadata
                $languages = $languages->map(function ($language) use ($file_type) {
                    $language->is_rtl = intval($language->is_rtl);

                    // Get file metadata using helper method
                    $metadata = $this->getLanguageFileMetadata($language->code, $file_type);

                    // Convert to array to avoid Laravel's timestamp casting
                    $languageArray = $language->toArray();

                    // Add file metadata (without labels)
                    $languageArray['updated_at'] = $metadata['updated_at'];
                    $languageArray['missing_labels_count'] = $metadata['missing_labels_count'];
                    $languageArray['total_labels'] = $metadata['total_labels'];
                    $languageArray['file_exists'] = $metadata['file_exists'];

                    return (object) $languageArray;
                });

                $response = [
                    'error' => false,
                    'message' => 'Settings retrieved successfully',
                    'language_message_key' => 'settings_retrieved_successfully',
                    'data' => $general_settings,
                ];
                $response['data']['languages'] = $languages;
            } else {
                $response = [
                    'error' => true,
                    'message' => 'Settings Not Found',
                    'language_message_key' => 'settings_not_found',
                    'data' => [],
                ];
            }
            return response()->json($response);
        }
    }
    public function delete_seller(Request $request)
    {
        $rules = [
            'mobile' => 'required|numeric',
            'password' => 'required',
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        if (!auth()->check()) {
            return response()->json([
                'error' => true,
                'message' => 'Unauthorized',
                'language_message_key' => 'unauthorized_user'
            ]);
        }

        $user = auth()->user();
        $user_id = $user->id;

        // Confirm credentials
        $user_data = fetchDetails(User::class, ['id' => $user_id, 'mobile' => $request['mobile']], ['id', 'username', 'password', 'active', 'mobile']);
        if (!$user_data || !Hash::check($request->password, $user_data[0]->password)) {
            return response()->json([
                'error' => true,
                'message' => 'Invalid credentials',
                'language_message_key' => 'invalid_credentials'
            ]);
        }

        if ($user->role_id != 4) {
            return response()->json([
                'error' => true,
                'message' => 'Details do not match',
                'language_message_key' => 'details_does_not_match'
            ]);
        }

        $seller_id = Seller::where('user_id', $user_id)->value('id');

        // Check for return requests
        $returnRequests = ReturnRequest::whereHas('product', function ($q) use ($seller_id) {
            $q->where('seller_id', $seller_id);
        })->exists();

        if ($returnRequests) {
            return response()->json([
                'error' => true,
                'message' => 'Seller could not be deleted. Return requests are pending. Finalize those before deleting.',
                'language_message_key' => 'seller_not_deleted_return_requests_are_pending_finalize_those_before_deleting_it'
            ]);
        }

        $delete = [
            "media" => 0,
            "payment_requests" => 0,
            "products" => 0,
            "product_attributes" => 0,
            "product_variants" => 0,
            "order_items" => 0,
            "orders" => 0,
            "order_bank_transfer" => 0,
            "seller_commission" => 0,
            "seller_data" => 0,
        ];

        // Delete media files
        $seller_media = fetchDetails(Seller::class, ['user_id' => $user_id], ['national_identity_card', 'authorized_signature']);
        if (!empty($seller_media)) {
            foreach (['authorized_signature', 'national_identity_card'] as $field) {
                $path = public_path(config('constants.MEDIA_PATH') . $seller_media[0]->$field);
                if (File::exists($path)) {
                    @unlink($path);
                }
            }
        }
        if (updateDetails(['seller_id' => 0], ['seller_id' => $seller_id], Media::class)) {
            $delete['media'] = 1;
        }

        // Delete product-related data
        $product_ids = Product::where('seller_id', $seller_id)->pluck('id');

        if (deleteDetails(['seller_id' => $seller_id], Product::class)) {
            $delete['products'] = 1;
        }

        foreach ($product_ids as $pid) {
            if (deleteDetails(['product_id' => $pid], Product_attributes::class)) {
                $delete['product_attributes'] = 1;
            }
            if (deleteDetails(['product_id' => $pid], Product_variants::class)) {
                $delete['product_variants'] = 1;
            }
        }

        // Order cleanup
        $order_items = OrderItems::where('seller_id', $seller_id)->get();
        $order_ids = $order_items->pluck('order_id')->unique();

        if (deleteDetails(['seller_id' => $seller_id], OrderItems::class)) {
            $delete['order_items'] = 1;
        }

        foreach ($order_ids as $order_id) {
            $hasOtherSellers = OrderItems::where('order_id', $order_id)
                ->where('seller_id', '!=', $seller_id)->exists();

            if (!$hasOtherSellers) {
                if (deleteDetails(['id' => $order_id], Order::class)) {
                    $delete['orders'] = 1;
                }
                if (deleteDetails(['order_id' => $order_id], OrderBankTransfers::class)) {
                    $delete['order_bank_transfer'] = 1;
                }
            }
        }

        // Commission and seller info
        if (deleteDetails(['seller_id' => $seller_id], SellerCommission::class)) {
            $delete['seller_commission'] = 1;
        }

        if (deleteDetails(['user_id' => $user_id], Seller::class)) {
            $delete['seller_data'] = 1;
        }

        // Delete user
        deleteDetails(['id' => $user_id], User::class);

        return response()->json([
            'error' => false,
            'message' => 'Seller Deleted Successfully',
            'language_message_key' => 'seller_deleted_successfully',
        ]);
    }


    public function get_stores(Request $request, StoreController $AdminStoreController)
    {
        $search = $request->input('search', null);
        $limit = $request->input('limit', 25);
        $offset = $request->input('offset', 0);
        $order = $request->input('order', 'DESC');
        $sort = $request->input('sort', 'id');
        $language_code = $request->attributes->get('language_code');

        $data = $AdminStoreController->getStores($limit, $offset, $sort, $order, $search, "", $language_code);

        return response()->json($data);
    }


    public function get_seller_stores(Request $request)
    {
        // Get the authenticated user
        $user = Auth::user();
        $user_id = auth()->user()->id;

        // Find the seller using Eloquent
        $seller = Seller::where('user_id', $user_id)->first();

        if (!$seller) {
            return response()->json([
                'error' => true,
                'message' => 'Seller not found',
                'language_message_key' => 'seller_not_found',
                'data' => []
            ]);
        }

        // Get the store details for this seller with the store settings from the pivot table
        // Include inactive stores so the client can manage them as well.
        $store_details = $seller->stores()->get();

        $rows = [];
        $language_code = $request->attributes->get('language_code');

        // Check if stores exist
        if ($store_details->isNotEmpty()) {
            foreach ($store_details as $store) {
                // dd($store->store_settings);
                // Access store_settings from the pivot table and decode the JSON
                $store_settings = $store->store_settings ?? [];

                // Check if 'category_section_title' is set and handle based on language_code
                if (isset($store_settings['category_section_title'])) {
                    $category_section_title = $store_settings['category_section_title'];
                    if (is_array($category_section_title)) {
                        // If it's an array, select the language-based title
                        $store_settings['category_section_title'] = $category_section_title[$language_code]
                            ?? $category_section_title['en']
                            ?? reset($category_section_title);
                    }
                }
                $customFields = $customFields = CustomField::where('store_id', $store->id)
                    ->where('active', 1)
                    ->get();

                // Prepare the store details to be returned
                $temp = [
                    'id' => $store->id,
                    'name' => app(TranslationService::class)->getDynamicTranslation(Store::class, 'name', $store->id, $language_code),
                    'description' => app(TranslationService::class)->getDynamicTranslation(Store::class, 'description', $store->id, $language_code),
                    'image' => app(MediaService::class)->getMediaImageUrl($store->image, 'STORE_IMG_PATH'),
                    'banner_image' => app(MediaService::class)->getMediaImageUrl($store->banner_image, 'STORE_IMG_PATH'),
                    'banner_image_for_most_selling_product' => app(MediaService::class)->getMediaImageUrl($store->banner_image_for_most_selling_product, 'STORE_IMG_PATH'),
                    'stack_image' => app(MediaService::class)->getMediaImageUrl($store->stack_image, 'STORE_IMG_PATH'),
                    'login_image' => app(MediaService::class)->getMediaImageUrl($store->login_image, 'STORE_IMG_PATH'),
                    'is_single_seller_order_system' => $store->is_single_seller_order_system,
                    'is_default_store' => $store->is_default_store,
                    'disk' => $store->disk ?? '',
                    'note_for_necessary_documents' => $store->note_for_necessary_documents ?? '',
                    'primary_color' => $store->primary_color ?? '',
                    'secondary_color' => $store->secondary_color ?? '',
                    'hover_color' => $store->hover_color ?? '',
                    'active_color' => $store->active_color ?? '',
                    'background_color' => $store->background_color ?? '',
                    'delivery_charge_type' => $store->delivery_charge_type ?? '',
                    'delivery_charge_amount' => $store->delivery_charge_amount ?? '0',
                    'minimum_free_delivery_amount' => $store->minimum_free_delivery_amount ?? '0',
                    'product_deliverability_type' => $store->product_deliverability_type ?? '',
                    'rating' => $store->rating ?? '0',
                    'no_of_ratings' => $store->no_of_ratings ?? '0',
                    // Status values come from seller_store pivot to reflect seller-store mapping
                    'status' => $store->pivot->status ?? $store->status,
                    'store_status' => $store->pivot->status ?? $store->status,
                    'seller_store_status' => $store->pivot->status ?? null,
                    'store_settings' => $store_settings,
                    'permissions' => app(SellerService::class)->getSellerPermission($seller->id, $store->id),
                    'custom_fields' => $customFields
                        ->map(function ($field) {
                            return [
                                'id' => $field->id,
                                'name' => $field->name,
                                'type' => $field->type,
                                'field_length' => $field->field_length,
                                'min' => $field->min,
                                'max' => $field->max,
                                'required' => $field->required,
                                'active' => $field->active,
                                'options' => is_array($field->options)
                                    ? $field->options
                                    : (json_decode($field->options, true) ?? []),
                            ];
                        })->values(),
                ];
                $rows[] = $temp;
            }
        }

        // Prepare the response
        $response['error'] = $store_details->isEmpty();
        $response['message'] = $store_details->isEmpty() ? 'No store found for this seller' : 'Store detail retrieved successfully!';
        $response['language_message_key'] = $store_details->isEmpty() ? 'no_store_found_for_seller' : 'store_detail_retrieved_successfully';
        $response['data'] = $rows;

        return response()->json($response);
    }

    public function deactivate_store(Request $request)
    {
        $rules = [
            'store_id' => 'required|exists:stores,id',
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $user_id = Auth::id();
        $seller = Seller::where('user_id', $user_id)->first();

        if (!$seller) {
            return response()->json([
                'error' => true,
                'message' => 'Seller not found',
                'language_message_key' => 'seller_not_found'
            ]);
        }

        $store_id = $request->input('store_id');
        $seller_store = SellerStore::where('seller_id', $seller->id)
            ->where('store_id', $store_id)
            ->where('user_id', $user_id)
            ->first();

        if (!$seller_store) {
            return response()->json([
                'error' => true,
                'message' => 'Store not found for this seller',
                'language_message_key' => 'store_not_found_for_seller'
            ]);
        }

        if ($seller_store->status == 0) {
            return response()->json([
                'error' => true,
                'message' => 'Store is already deactivated',
                'language_message_key' => 'store_already_deactivated'
            ]);
        }

        $seller_store->status = 0;
        $seller_store->save();

        return response()->json([
            'error' => false,
            'message' => 'Store deactivated successfully',
            'language_message_key' => 'store_deactivated_successfully'
        ]);
    }

    public function activate_store(Request $request)
    {
        $rules = [
            'store_id' => 'required|exists:stores,id',
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $user_id = Auth::id();
        $seller = Seller::where('user_id', $user_id)->first();

        if (!$seller) {
            return response()->json([
                'error' => true,
                'message' => 'Seller not found',
                'language_message_key' => 'seller_not_found'
            ]);
        }

        $store_id = $request->input('store_id');
        $seller_store = SellerStore::where('seller_id', $seller->id)
            ->where('store_id', $store_id)
            ->where('user_id', $user_id)
            ->first();

        if (!$seller_store) {
            return response()->json([
                'error' => true,
                'message' => 'Store not found for this seller',
                'language_message_key' => 'store_not_found_for_seller'
            ]);
        }

        if ($seller_store->status == 1) {
            return response()->json([
                'error' => true,
                'message' => 'Store is already active',
                'language_message_key' => 'store_already_active'
            ]);
        }

        $seller_store->status = 1;
        $seller_store->save();

        return response()->json([
            'error' => false,
            'message' => 'Store activated successfully',
            'language_message_key' => 'store_activated_successfully'
        ]);
    }

    public function delete_store(Request $request)
    {
        $rules = [
            'store_id' => 'required|exists:stores,id',
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $user_id = Auth::id();
        $seller = Seller::where('user_id', $user_id)->first();

        if (!$seller) {
            return response()->json([
                'error' => true,
                'message' => 'Seller not found',
                'language_message_key' => 'seller_not_found'
            ]);
        }

        $store_id = $request->input('store_id');
        $seller_store = SellerStore::where('seller_id', $seller->id)
            ->where('store_id', $store_id)
            ->where('user_id', $user_id)
            ->first();

        if (!$seller_store) {
            return response()->json([
                'error' => true,
                'message' => 'Store not found for this seller',
                'language_message_key' => 'store_not_found_for_seller'
            ]);
        }

        // Check if store has any data
        $hasProducts = Product::where('seller_id', $seller->id)
            ->where('store_id', $store_id)
            ->exists();

        $hasComboProducts = ComboProduct::where('seller_id', $seller->id)
            ->where('store_id', $store_id)
            ->exists();

        // $hasOrderItems = OrderItems::where('seller_id', $seller->id)
        //     ->where('store_id', $store_id)
        //     ->exists();

        // $hasFavorites = Favorite::where('seller_id', $seller->id)
        //     ->exists();

        // $hasCommissions = SellerCommission::where('seller_id', $seller->id)
        //     ->where('store_id', $store_id)
        //     ->exists();

        if ($hasProducts || $hasComboProducts) {
            $dataTypes = [];
            if ($hasProducts)
                $dataTypes[] = 'products';
            if ($hasComboProducts)
                $dataTypes[] = 'combo products';


            return response()->json([
                'error' => true,
                'message' => 'Store cannot be deleted. It contains: ' . implode(', ', $dataTypes),
                'language_message_key' => 'store_cannot_be_deleted_contains_data',
                'data' => [
                    'has_products' => $hasProducts,
                    'has_combo_products' => $hasComboProducts,
                    'has_orders' => $hasOrderItems,
                    'has_favorites' => $hasFavorites,
                    'has_commissions' => $hasCommissions
                ]
            ]);
        }

        // Delete the store
        $seller_store->delete();

        return response()->json([
            'error' => false,
            'message' => 'Store deleted successfully',
            'language_message_key' => 'store_deleted_successfully'
        ]);
    }

    public function get_combo_products(Request $request)
    {

        $rules = [
            'store_id' => 'required|exists:stores,id',
            'id' => 'sometimes|numeric|exists:combo_products,id',
            'search' => 'sometimes|string',
            'attribute_value_ids' => 'sometimes',
            'sort' => 'sometimes|string',
            'limit' => 'sometimes|numeric',
            'offset' => 'sometimes|numeric',
            'order' => 'sometimes|string|alpha',
            'top_rated_product' => 'sometimes|numeric',
            'discount' => 'sometimes|numeric',
            'is_similar_products' => 'numeric'
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            $user_id = auth()->user()->id;
            // dd($user_id);
            $seller_id = Seller::where('user_id', $user_id)->value('id');
            $limit = $request->input('limit', 25);
            $offset = $request->input('offset', 0);
            $order = $request->filled('order') ? $request->input('order') : 'DESC';
            $sort = $request->filled('sort') ? $request->input('sort') : 'combo_products.id';
            $id = $request->filled('id') ? $request->input('id') : '';
            $category_id = $request->filled('category_id') ? $request->input('category_id') : '';
            $type = $request->has('type') ? $request->input('type') : '';
            $brand_id = $request->filled('brand_id') ? $request->input('brand_id') : '';
            $filters['minimum_price'] = $request->filled('minimum_price') ? $request->input('minimum_price') : '';
            $filters['maximum_price'] = $request->filled('maximum_price') ? $request->input('maximum_price') : '';
            $filters['discount'] = $request->filled('discount') ? $request->input('discount', 0) : 0;
            $filters['most_popular_products'] = $request->filled('most_popular_products') ? $request->input('most_popular_products') : '';
            $filters = [
                'search' => $request->input('search', null),
                'tags' => $request->input('tags', ''),
                'flag' => $request->has('flag') && $request->input('flag') !== '' ? $request->input('flag') : '',
                'attribute_value_ids' => $request->input('attribute_value_ids', null),
                'is_similar_products' => $request->input('is_similar_products', null),
                'product_type' => $request->input('top_rated_product') == 1 ? 'top_rated_product_including_all_products' : $request->input('product_type'),
                'show_only_active_products' => $request->input('show_only_active_products', true),
                'show_only_stock_product' => $request->input('show_only_stock_product', false),
                'minimum_price' => $request->input('minimum_price', ''),
                'maximum_price' => $request->input('maximum_price', ''),
                'discount' => $request->input('discount', 0),
                'most_popular_products' => $request->input('most_popular_products', ''),
            ];
            $language_code = $request->attributes->get('language_code');
            $store_id = $request->input('store_id') ? (int) $request->input('store_id') : '';
            //    dd($order);
            $products = app(ComboProductService::class)->fetchComboProduct('', $filters, $id, $limit, $offset, $sort, $order, '', '', $seller_id, $store_id, $category_id, $brand_id, $type, 1, $language_code);

            $filtered_brand_ids = array_filter($products['brand_ids'], function ($value) {
                return !empty($value);
            });
            $brand_ids = implode(',', $filtered_brand_ids);
            $isEmpty = empty($products['combo_product'])
                || (is_array($products['combo_product']) && count($products['combo_product']) === 0)
                || ($products['combo_product'] instanceof \Illuminate\Support\Collection && $products['combo_product']->isEmpty());

            $response = [
                'error' => $isEmpty,
                'message' => !$isEmpty ? 'Products retrieved successfully!' : 'No products found',
                'language_message_key' => !$isEmpty ? 'products_retrieved_successfully' : 'no_products_found',
                'total' => isset($products['total']) ? strval($products['total']) : 0,
                'category_ids' => isset($products['category_ids']) && !empty($products['category_ids']) ? implode(',', $products['category_ids']) : '',
                'brand_ids' => isset($products['brand_ids']) && !empty($products['brand_ids']) ? $brand_ids : '',
                'data' => $products['combo_product'] ?? [],
            ];

            return response()->json($response);
        }
    }
    public function add_combo_product(Request $request, ComboProductController $ComboProductController)
    {

        $rules = [
            'title' => 'required',
            'short_description' => 'required',
            'description' => 'required',
            'image' => 'required',
            'product_type_in_combo' => 'required',
            'simple_price' => 'required',
            'simple_special_price' => 'required',
            'store_id' => 'required|exists:stores,id',
        ];
        if ($request->simple_stock_management_status == 'on') {
            $rules = [
                'product_sku' => 'required',
                'product_total_stock' => 'required',
            ];
        }
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            $user_id = auth()->user()->id;
            $request['user_id'] = $user_id;
            $language_code = $request->attributes->get('language_code');
            $store_id = $request->input('store_id') ? (int) $request->input('store_id') : '';

            // Block combo product creation if store is deactivated
            $seller_id = Seller::where('user_id', $user_id)->value('id');
            if ($guard = $this->ensureActiveStore($seller_id, $store_id)) {
                return $guard;
            }
            $request['store_id'] = $store_id;
            $request['selected_product'] = $request->input('selected_product');
            $request['physical_product_variant_id'] = explode(",", $request['physical_product_variant_id']);
            $request['digital_product_id'] = explode(",", $request['digital_product_id']);
            $request['similar_product_id'] = isset($request['similar_product_ids']) ? explode(",", $request['similar_product_ids']) : "";
            $request['other_images'] = (isset($request['other_images']) && !empty($request['other_images'])) ? explode(",", $request['other_images']) : NULL;
            // dd($store_id);
            $product_data = $ComboProductController->store($request, true, $language_code);


            if (!empty($product_data)) {
                // Safely access data from store()
                $response_data = $product_data->original['data'] ?? null;

                return response()->json([
                    'error' => false,
                    'message' => 'Product Added Successfully',
                    'language_message_key' => 'product_added_successfully',
                    'data' => $response_data,
                ]);
            } else {
                return response()->json([
                    'error' => true,
                    'message' => 'Something went wrong',
                    'language_message_key' => 'something_went_wrong',
                ]);
            }
        }
    }
    public function delete_combo_product(Request $request, ComboProductController $ComboProductController)
    {
        $rules = [
            'product_id' => 'required|exists:combo_products,id',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            // Block combo product delete if store is deactivated
            $comboProduct = ComboProduct::find($request->input('product_id'));
            if ($comboProduct && auth()->check()) {
                $user_id = auth()->user()->id;
                $seller_id = Seller::where('user_id', $user_id)->value('id');
                if ($guard = $this->ensureActiveStore($seller_id, (int) $comboProduct->store_id)) {
                    return $guard;
                }
            }

            $product_data = deleteDetails(['id' => $request->input('product_id')], ComboProduct::class);

            if (!empty($product_data)) {
                return response()->json([
                    'error' => false,
                    'message' => 'Product Deleted Successfully',
                    'language_message_key' => 'product_deleted_successfully',
                ]);
            } else {
                return response()->json([
                    'error' => true,
                    'message' => 'Something went wrong',
                    'language_message_key' => 'something_went_wrong',
                    'data' => $product_data,
                ]);
            }
        }
    }
    public function update_combo_product(Request $request, ComboProductController $ComboProductController)
    {
        $rules = [
            'id' => 'required|exists:combo_products,id',
            'title' => 'required',
            'short_description' => 'required',
            'description' => 'required',
            'image' => 'required',
            'product_type_in_combo' => 'required',
            'product_id' => 'required|exists:combo_products,id',
            'simple_price' => 'required',
            'simple_special_price' => 'required',
            'store_id' => 'required|exists:stores,id',
        ];

        if ($request->simple_stock_management_status == 'on') {
            $rules = array_merge($rules, [
                'product_sku' => 'required',
                'product_total_stock' => 'required',
            ]);
        }

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $id = (int) $request->input('id');
        $store_id = (int) $request->input('store_id');
        $language_code = $request->attributes->get('language_code');

        // Explicit product existence check
        $comboProduct = ComboProduct::find($id);
        if (!$comboProduct) {
            return response()->json([
                'error' => true,
                'message' => 'Product not found.',
                'language_message_key' => 'product_not_found',
                'data' => null,
            ], 404);
        }

        // Block combo product update if store is deactivated
        if (auth()->check()) {
            $user_id = auth()->user()->id;
            $seller_id = Seller::where('user_id', $user_id)->value('id');
            if ($guard = $this->ensureActiveStore($seller_id, $store_id)) {
                return $guard;
            }
        }

        // Prepare request data
        $request['store_id'] = $store_id;
        $request['selected_product'] = $request->input('selected_product');
        $request['physical_product_variant_id'] = explode(",", $request['physical_product_variant_id'] ?? '');
        $request['similar_product_id'] = !empty($request['similar_product_ids']) ? explode(",", $request['similar_product_ids']) : [];
        $request['other_images'] = !empty($request['other_images']) ? explode(",", $request['other_images']) : [];

        $product_data = $ComboProductController->update($request, $id, true, $language_code);

        if (!empty($product_data)) {
            return response()->json([
                'error' => false,
                'message' => 'Product Updated Successfully',
                'language_message_key' => 'product_updated_successfully',
                'data' => $product_data->original['data'],
            ]);
        }

        return response()->json([
            'error' => true,
            'message' => 'Something went wrong',
            'language_message_key' => 'something_went_wrong',
            'data' => $product_data,
        ]);
    }

    /**
     * Auto-detect file type based on controller namespace
     * App\v1\ApiController -> 'app'
     * Seller\v1\ApiController -> 'seller'
     * Delivery_boy\v1\ApiController -> 'delivery'
     */
    private function detectFileType()
    {
        $className = get_class($this);

        if (strpos($className, 'App\\v1\\') !== false) {
            return 'app';
        } elseif (strpos($className, 'Seller\\v1\\') !== false) {
            return 'seller';
        } elseif (strpos($className, 'Delivery_boy\\v1\\') !== false) {
            return 'delivery';
        } elseif (strpos($className, 'Admin\\') !== false) {
            return 'panel';
        }

        // Default fallback
        return 'seller';
    }

    /**
     * Get file metadata for a language file without loading all labels
     */
    private function getLanguageFileMetadata($languageCode, $fileType)
    {
        $filename = match ($fileType) {
            'app' => 'app_labels.json',
            'panel', 'admin' => 'panel_labels.json',
            'web' => 'web_labels.json',
            'seller' => 'seller_labels.json',
            'delivery' => 'delivery_labels.json',
            default => 'seller_labels.json'
        };

        $filePath = resource_path("lang/{$languageCode}/{$filename}");

        $metadata = [
            'updated_at' => null,
            'missing_labels_count' => 0,
            'total_labels' => 0,
            'file_exists' => file_exists($filePath),
        ];

        if ($metadata['file_exists']) {
            // Use file modification time for updated_at (same approach as get_language_labels)
            $file_modified_time = filemtime($filePath);
            $metadata['updated_at'] = date('d-m-Y H:i:s', $file_modified_time); // dd-mm-yyyy hh:mm:ss format

            // Read file to get metadata counts
            $content = file_get_contents($filePath);
            $data = json_decode($content, true);

            if (isset($data['_metadata'])) {
                $metadata['missing_labels_count'] = $data['_metadata']['missing_labels_count'] ?? 0;
                $metadata['total_labels'] = $data['_metadata']['total_labels'] ?? count($data);
            } else {
                $metadata['total_labels'] = count($data);
            }
        }

        return $metadata;
    }

    public function get_languages(Request $request)
    {
        // Fetch languages from the database
        $languages = Language::select('id', 'language', 'code', 'native_language', 'is_rtl')->get();

        // Auto-detect file type based on controller, allow override via parameter
        $file_type = $request->input('file_type', $this->detectFileType());

        // Convert is_rtl to integer and add file metadata
        $languages = $languages->map(function ($language) use ($file_type) {
            $language->is_rtl = intval($language->is_rtl);

            // Get file metadata for this language and file type
            $filename = match ($file_type) {
                'app' => 'app_labels.json',
                'panel', 'admin' => 'panel_labels.json',
                'web' => 'web_labels.json',
                'seller' => 'seller_labels.json',
                'delivery' => 'delivery_labels.json',
                default => 'seller_labels.json'
            };

            $filePath = resource_path("lang/{$language->code}/{$filename}");
            $labels = [];
            $updated_at = null;
            $missing_labels_count = 0;
            $total_labels = 0;

            // Load labels and metadata if file exists
            if (file_exists($filePath)) {
                $content = file_get_contents($filePath);
                $data = json_decode($content, true);

                if (isset($data['_metadata'])) {
                    $updated_at = $data['_metadata']['updated_at'] ?? null;
                    $missing_labels_count = $data['_metadata']['missing_labels_count'] ?? 0;
                    $total_labels = $data['_metadata']['total_labels'] ?? count($data);
                    unset($data['_metadata']);
                } else {
                    $total_labels = count($data);
                }

                if (isset($data['_missing_labels'])) {
                    unset($data['_missing_labels']);
                }

                $labels = $data;
            }

            // Add file metadata to language object
            $language->updated_at = $updated_at;
            $language->missing_labels_count = $missing_labels_count;
            $language->total_labels = $total_labels;
            $language->file_exists = file_exists($filePath);
            $language->labels = $labels; // Include labels in response

            return $language;
        });

        // Get system settings to merge with language response
        $user_id = auth('sanctum')->check() ? auth('sanctum')->user()->id : null;
        $store_id = $request->input('store_id');

        try {
            $settings = [
                'logo' => 0,
                'seller_privacy_policy' => 1,
                'seller_terms_and_conditions' => 1,
                'fcm_server_key' => 1,
                'contact_us' => 1,
                'about_us' => 1,
                'currency' => 0,
                'time_slot_config' => 1,
                'user_data' => 0,
                'system_settings' => 1,
                'shipping_policy' => 1,
                'return_policy' => 1,
                'pusher_settings' => 1,
                'admin_preference' => 1,
            ];

            $general_settings = [];
            foreach ($settings as $type => $isjson) {
                if ($type == 'payment_method') {
                    continue;
                }
                $general_settings[$type] = [];
                $settings_res = app(SettingService::class)->getSettings($type, $isjson);
                $settings_res = json_decode($settings_res, true);

                if ($type == 'logo') {
                    $logo_setting = app(SettingService::class)->getSettings('system_settings', true);
                    $logo_setting = json_decode($logo_setting, true);
                    $settings_res = app(MediaService::class)->getMediaImageUrl($logo_setting['logo']);
                }

                if ($isjson && isset($settings_res[$type])) {
                    array_push($general_settings[$type], $settings_res[$type]);
                } else {
                    array_push($general_settings[$type], $settings_res);
                }
            }

            $currencyDetails = fetchDetails(Currency::class, ['is_default' => 1], 'symbol');
            $currency = !$currencyDetails->isEmpty() ? $currencyDetails[0]->symbol : '';
            $general_settings['currency'] = $currency;

            // Only unset ai_setting if user is not authenticated
            if (!auth('sanctum')->check() || empty($user_id)) {
                if (isset($general_settings['system_settings'][0]['ai_setting'])) {
                    unset($general_settings['system_settings'][0]['ai_setting']);
                }
            }

            // Handle system settings specific fields
            if (isset($general_settings['system_settings'][0])) {
                $general_settings['system_settings'][0]['store_currency'] = isset($general_settings['system_settings'][0]['store_currency']) && $general_settings['system_settings'][0]['store_currency'] !== null ? $general_settings['system_settings'][0]['store_currency'] : '';
                $general_settings['system_settings'][0]['sidebar_color'] = isset($general_settings['system_settings'][0]['sidebar_color']) && $general_settings['system_settings'][0]['sidebar_color'] !== null ? $general_settings['system_settings'][0]['sidebar_color'] : '';
                $general_settings['system_settings'][0]['sidebar_type'] = isset($general_settings['system_settings'][0]['sidebar_type']) && $general_settings['system_settings'][0]['sidebar_type'] !== null ? $general_settings['system_settings'][0]['sidebar_type'] : '';
            }
        } catch (\Exception $e) {
            // If settings fail to load, continue without them
            $general_settings = [];
        }

        // Return the fetched languages merged with system settings
        return response()->json([
            'error' => $languages->isEmpty() ? true : false,
            'message' => $languages->isEmpty() ? 'Languages not found' : 'Languages retrieved successfully',
            'language_message_key' => $languages->isEmpty() ? 'languages_not_found' : 'languages_retrieved_successfully',
            'data' => $languages,
            'system_settings' => $general_settings,
            'file_type' => $file_type
        ], 200);
    }
    public function get_language_labels(Request $request)
    {
        $rules = [
            'language_code' => 'required',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $language_code = $request->input('language_code');
        // Auto-detect file type based on controller, allow override via parameter
        $file_type = $request->input('file_type', $this->detectFileType());

        // Try JSON file first (preferred)
        $json_filename = match ($file_type) {
            'app' => 'app_labels.json',
            'panel', 'admin' => 'panel_labels.json',
            'web' => 'web_labels.json',
            'seller' => 'seller_labels.json',
            'delivery' => 'delivery_labels.json',
            default => 'app_labels.json'
        };

        $json_file_path = resource_path('lang/' . $language_code . '/' . $json_filename);

        $labels = [];
        $actual_file_path = null; // Track which file was actually used

        // Try to load from JSON file first for the requested language
        if (file_exists($json_file_path)) {
            $jsonContent = file_get_contents($json_file_path);
            $labels = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $labels = [];
            } else {
                $actual_file_path = $json_file_path; // Track that we used this file
                // Remove metadata from labels before returning
                if (isset($labels['_metadata'])) {
                    unset($labels['_metadata']);
                }
                if (isset($labels['_missing_labels'])) {
                    unset($labels['_missing_labels']);
                }
            }
        }

        // If JSON file doesn't exist or is empty, fallback to English (en) JSON file
        if (empty($labels) && $language_code !== 'en') {
            $en_json_file_path = resource_path('lang/en/' . $json_filename);
            if (file_exists($en_json_file_path)) {
                $jsonContent = file_get_contents($en_json_file_path);
                $labels = json_decode($jsonContent, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $labels = [];
                } else {
                    $actual_file_path = $en_json_file_path; // Track that we used English file
                    // Remove metadata from labels before returning
                    if (isset($labels['_metadata'])) {
                        unset($labels['_metadata']);
                    }
                    if (isset($labels['_missing_labels'])) {
                        unset($labels['_missing_labels']);
                    }
                }
            }
        }

        // If still empty, fallback to admin_labels.php for the requested language
        if (empty($labels)) {
            $php_file_path = resource_path('lang/' . $language_code . '/admin_labels.php');
            if (file_exists($php_file_path)) {
                $labels = include $php_file_path;
                if (isset($labels['langcode'])) {
                    unset($labels['langcode']);
                }
            }
        }

        // If still empty and not English, fallback to English admin_labels.php
        if (empty($labels) && $language_code !== 'en') {
            $en_php_file_path = resource_path('lang/en/admin_labels.php');
            if (file_exists($en_php_file_path)) {
                $labels = include $en_php_file_path;
                if (isset($labels['langcode'])) {
                    unset($labels['langcode']);
                }
            }
        }

        if (empty($labels)) {
            return response()->json([
                'error' => true,
                'message' => 'Language file not found',
                'language_message_key' => 'language_file_not_found',
                'data' => [],
            ]);
        }

        // Calculate missing labels by comparing with English reference file
        $missing_labels_count = 0;
        if (!empty($labels) && $language_code !== 'en') {
            // Load English reference file for comparison
            $en_reference_file = resource_path('lang/en/' . $json_filename);
            if (file_exists($en_reference_file)) {
                $enContent = file_get_contents($en_reference_file);
                $en_data = json_decode($enContent, true);

                if (is_array($en_data) && json_last_error() === JSON_ERROR_NONE) {
                    // Remove metadata from reference data for comparison
                    if (isset($en_data['_metadata'])) {
                        unset($en_data['_metadata']);
                    }
                    if (isset($en_data['_missing_labels'])) {
                        unset($en_data['_missing_labels']);
                    }

                    // Compare to find missing labels
                    $missing_labels = array_diff_key($en_data, $labels);
                    $missing_labels_count = count($missing_labels);
                }
            }
        }

        // Generate metadata dynamically from the actual file that was used
        $metadata = null;
        if ($actual_file_path && file_exists($actual_file_path)) {
            $file_modified_time = filemtime($actual_file_path);
            $total_labels = count($labels);

            $metadata = [
                'updated_at' => date('d-m-Y H:i:s', $file_modified_time), // dd-mm-yyyy hh:mm:ss format
                'missing_labels_count' => $missing_labels_count,
                'total_labels' => $total_labels,
            ];
        } elseif (!empty($labels)) {
            // If labels came from PHP file, generate metadata without file info
            $metadata = [
                'updated_at' => null,
                'missing_labels_count' => $missing_labels_count,
                'total_labels' => count($labels),
            ];
        }

        $response = [
            'error' => false,
            'message' => 'Language labels retrieved successfully',
            'language_message_key' => 'language_labels_retrieved_successfully',
            'data' => $labels,
        ];

        // Include metadata if available (for client-side storage and re-fetch tracking)
        if ($metadata) {
            $response['metadata'] = $metadata;
        }

        return response()->json($response);
    }

    public function reset_password(Request $request)
    {

        $rules = [
            'email' => 'required|email',
        ];

        $messages = [
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email address.',
        ];

        if ($response = $this->HandlesValidation($request, $rules, $messages, null, true)) {
            return $response;
        }

        $email = $request->input('email');

        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'error' => true,
                'message' => 'User does not exist!',
                'language_message_key' => 'user_does_not_exist',
                'data' => [],
            ]);
        }

        $status = Password::broker()->sendResetLink(['email' => $email]);

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'error' => false,
                'message' => 'Password reset link sent successfully!',
                'language_message_key' => 'password_reset_link_sent_successfully',
                'data' => [],
            ]);
        }

        return response()->json([
            'error' => true,
            'message' => 'Unable to send password reset link.',
            'language_message_key' => 'unable_to_send_password_reset_link',
            'data' => [],
        ]);
    }
    public function add_seller_store(Request $request, SellerController $SellerController)
    {

        $rules = [
            'store_id' => 'required|exists:stores,id',
            'mobile' => 'required',
            'store_name' => 'required',
            'account_number' => 'required',
            'account_name' => 'required',
            'bank_name' => 'required',
            'bank_code' => 'required',
            'city' => 'required',
            'zipcode' => 'required',
            'deliverable_type' => 'required',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $language_code = $request->attributes->get('language_code');
        $user = User::where('mobile', $request->mobile)->where('role_id', 4)->first();
        $store_id = $request->input('store_id') ?? "";
        $seller_store_details = SellerStore::select('store_id')->where('user_id', $user->id)->get();
        $seller_store_details = isset($seller_store_details) && !empty($seller_store_details) ? $seller_store_details[0]->store_id : "";
        $seller = Seller::where('user_id', $user->id)->first();
        if ($seller_store_details == $store_id) {
            return response()->json([
                'error' => true,
                'message' => labels('admin_labels.seller_already_registered', 'Seller already registered in this store.'),
                'language_message_key' => 'seller_already_registered'
            ]);
        } else {
            $seller_store_data = [];
            $media_storage_settings = fetchDetails(StorageType::class, ['is_default' => 1], '*');
            $mediaStorageType = !$media_storage_settings->isEmpty() ? $media_storage_settings[0]->id : 1;
            $disk = !$media_storage_settings->isEmpty() ? $media_storage_settings[0]->name : 'public';

            $media = StorageType::find($mediaStorageType);

            try {
                if ($request->hasFile('other_documents')) {
                    foreach ($request->file('other_documents') as $file) {
                        $other_documents = $media->addMedia($file)
                            ->sanitizingFileName(function ($fileName) use ($media) {
                                $sanitizedFileName = strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));
                                $uniqueId = time() . '_' . mt_rand(1000, 9999);
                                $extension = pathinfo($sanitizedFileName, PATHINFO_EXTENSION);
                                $baseName = pathinfo($sanitizedFileName, PATHINFO_FILENAME);

                                return "{$baseName}-{$uniqueId}.{$extension}";
                            })
                            ->toMediaCollection('sellers', $disk);
                        $other_document_file_names[] = $other_documents->file_name;
                        $mediaIds[] = $other_documents->id;
                    }
                }
                if ($request->hasFile('address_proof')) {

                    $addressProofFile = $request->file('address_proof');

                    $address_proof = $media->addMedia($addressProofFile)
                        ->sanitizingFileName(function ($fileName) use ($media) {
                            // Replace special characters and spaces with hyphens
                            $sanitizedFileName = strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));
                            // Generate a unique identifier based on timestamp and random component
                            $uniqueId = time() . '_' . mt_rand(1000, 9999);
                            $extension = pathinfo($sanitizedFileName, PATHINFO_EXTENSION);
                            $baseName = pathinfo($sanitizedFileName, PATHINFO_FILENAME);

                            return "{$baseName}-{$uniqueId}.{$extension}";
                        })
                        ->toMediaCollection('sellers', $disk);

                    $mediaIds[] = $address_proof->id;
                }
                if ($request->hasFile('store_logo')) {

                    $storeLogoFile = $request->file('store_logo');

                    $store_logo = $media->addMedia($storeLogoFile)
                        ->sanitizingFileName(function ($fileName) use ($media) {
                            // Replace special characters and spaces with hyphens
                            $sanitizedFileName = strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));
                            // Generate a unique identifier based on timestamp and random component
                            $uniqueId = time() . '_' . mt_rand(1000, 9999);
                            $extension = pathinfo($sanitizedFileName, PATHINFO_EXTENSION);
                            $baseName = pathinfo($sanitizedFileName, PATHINFO_FILENAME);

                            return "{$baseName}-{$uniqueId}.{$extension}";
                        })
                        ->toMediaCollection('sellers', $disk);

                    $mediaIds[] = $store_logo->id;
                }

                if ($request->hasFile('store_thumbnail')) {

                    $storeThumbnailFile = $request->file('store_thumbnail');

                    $store_thumbnail = $media->addMedia($storeThumbnailFile)
                        ->sanitizingFileName(function ($fileName) use ($media) {
                            // Replace special characters and spaces with hyphens
                            $sanitizedFileName = strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));
                            // Generate a unique identifier based on timestamp and random component
                            $uniqueId = time() . '_' . mt_rand(1000, 9999);
                            $extension = pathinfo($sanitizedFileName, PATHINFO_EXTENSION);
                            $baseName = pathinfo($sanitizedFileName, PATHINFO_FILENAME);

                            return "{$baseName}-{$uniqueId}.{$extension}";
                        })
                        ->toMediaCollection('sellers', $disk);

                    $mediaIds[] = $store_thumbnail->id;
                }


                if ($request->hasFile('authorized_signature')) {

                    $authorizedSignatureFile = $request->file('authorized_signature');

                    $authorized_signature = $media->addMedia($authorizedSignatureFile)
                        ->sanitizingFileName(function ($fileName) use ($media) {
                            // Replace special characters and spaces with hyphens
                            $sanitizedFileName = strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));
                            // Generate a unique identifier based on timestamp and random component
                            $uniqueId = time() . '_' . mt_rand(1000, 9999);
                            $extension = pathinfo($sanitizedFileName, PATHINFO_EXTENSION);
                            $baseName = pathinfo($sanitizedFileName, PATHINFO_FILENAME);

                            return "{$baseName}-{$uniqueId}.{$extension}";
                        })
                        ->toMediaCollection('sellers', $disk);

                    $mediaIds[] = $authorized_signature->id;
                }

                if ($request->hasFile('national_identity_card')) {

                    $nationalIdentityCardFile = $request->file('national_identity_card');

                    $national_identity_card = $media->addMedia($nationalIdentityCardFile)
                        ->sanitizingFileName(function ($fileName) use ($media) {
                            // Replace special characters and spaces with hyphens
                            $sanitizedFileName = strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));
                            // Generate a unique identifier based on timestamp and random component
                            $uniqueId = time() . '_' . mt_rand(1000, 9999);
                            $extension = pathinfo($sanitizedFileName, PATHINFO_EXTENSION);
                            $baseName = pathinfo($sanitizedFileName, PATHINFO_FILENAME);

                            return "{$baseName}-{$uniqueId}.{$extension}";
                        })
                        ->toMediaCollection('sellers', $disk);

                    $mediaIds[] = $national_identity_card->id;
                }

                //code for storing s3 object url for media

                if ($disk == 's3') {
                    $media_list = $media->getMedia('sellers');
                    for ($i = 0; $i < count($mediaIds); $i++) {
                        $media_url = $media_list[($media_list->count()) - (count($mediaIds) - $i)]->getUrl();

                        switch ($i) {
                            case 0:
                                $address_proof_url = $media_url;
                                break;
                            case 1:
                                $logo_url = $media_url;
                                break;
                            case 2:
                                $store_thumbnail_url = $media_url;
                                break;
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

            $seller_store_data['address_proof'] = $disk == 's3' ? (isset($address_proof_url) ? $address_proof_url : '') : (isset($address_proof->file_name) ? '/' . $address_proof->file_name : '');

            $seller_store_data['logo'] = $disk == 's3' ? (isset($logo_url) ? $logo_url : '') : (isset($store_logo->file_name) ? '/' . $store_logo->file_name : '');

            $seller_store_data['store_thumbnail'] = $disk == 's3' ? (isset($store_thumbnail_url) ? $store_thumbnail_url : '') : (isset($store_thumbnail->file_name) ? '/' . $store_thumbnail->file_name : '');

            $seller_store_data['other_documents'] = $disk == 's3' ? (isset($other_documents_url) ? ($other_documents_url) : '') : (isset($other_documents->file_name) ? json_encode($other_document_file_names) : '');
            $zones = implode(',', (array) $request->deliverable_zones);
            $requested_categories = $request->requested_categories;
            $seller_store_data = array_merge($seller_store_data, [
                'user_id' => $user->id,
                'seller_id' => $seller->id,
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
                'category_ids' => $requested_categories ?? '',
                'permissions' => (isset($permmissions) && $permmissions != "") ? json_encode($permmissions) : null,
                'slug' => generateSlug($request->input('store_name'), 'seller_store'),
                'store_id' => $store_id,
                'latitude' => $request->latitude ?? "",
                'longitude' => $request->longitude ?? "",
                'city' => $request->city ?? "",
                'zipcode' => $request->zipcode ?? "",
                'disk' => isset($address_proof->disk) && !empty($address_proof->disk) ? $address_proof->disk : 'public',
                'deliverable_type' => isset($request->deliverable_type) && !empty($request->deliverable_type) ? $request->deliverable_type : '',
                'deliverable_zones' => ($request->deliverable_type == '1' || $request->deliverable_type == '0') ? '' : $zones,
            ]);

            $seller_store = SellerStore::insert($seller_store_data);

            if (isset($request->requested_categories) && !empty($request->requested_categories)) {
                $requested_commission_category_ids = explode(',', $request->requested_categories);
                foreach ($requested_commission_category_ids as $category_id) {
                    SellerCommission::create([
                        'seller_id' => $seller->id,
                        'store_id' => $store_id,
                        'category_id' => $category_id,
                        'commission' => 0,
                    ]);
                }
            }

            $user_id = auth()->user()->id;
            $userDetails = fetchDetails(User::class, ['id' => $user_id], '*');
            if ($userDetails->isEmpty()) {
                return response()->json([
                    'error' => true,
                    'message' => 'User not found',
                    'language_message_key' => 'user_not_found'
                ], 404);
            }
            $user = $userDetails->first();
            $fcm_ids = fetchDetails(UserFcm::class, ['user_id' => $user->id], 'fcm_id');

            $fcm_ids_array = array_map(function ($item) {
                return $item->fcm_id;
            }, $fcm_ids->all());

            $userData = app(SellerService::class)->formatUserData($user, $fcm_ids_array);

            $seller_collection = fetchDetails(Seller::class, ['user_id' => $user_id], '*');
            if ($seller_collection->isEmpty()) {
                return response()->json([
                    'error' => true,
                    'message' => 'Seller data not found',
                    'language_message_key' => 'seller_data_not_found'
                ], 404);
            }
            $seller_array = array_values($seller_collection->toArray());
            $seller_first = $seller_array[0] ?? null;
            if (!$seller_first) {
                return response()->json([
                    'error' => true,
                    'message' => 'Seller data is invalid',
                    'language_message_key' => 'seller_data_invalid'
                ], 404);
            }
            $seller_first['seller_id'] = $seller_first['id'] ?? null;
            unset($seller_first['id']);
            $seller_array[0] = $seller_first;

            $store_data = fetchDetails(SellerStore::class, ['user_id' => $user->id], '*');
            $store_exists = !$store_data->isEmpty();
            $isPublicDisk = $store_exists && isset($store_data[0]->disk) && $store_data[0]->disk == 'public' ? 1 : 0;

            $data = array_merge($userData, $seller_first);
            $output = $data;
            $output['store_data'] = $store_exists
                ? app(SellerService::class)->formatStoreData($store_data, $isPublicDisk, $language_code)
                : [];
            $output['seller_data'] = array_map(
                fn($seller) => (array) $seller,
                app(SellerService::class)->formatSellerData($seller_array, $isPublicDisk)
            );
            if ($seller_store) {
                $response = [
                    'error' => false,
                    'message' => 'Store registered successfully wait for admin approvel!',
                    'language_message_key' => 'store_registered_successfully',
                    'data' => $output,
                ];
            } else {
                $response = [
                    'error' => true,
                    'message' => 'Something went wrong.',
                    'language_message_key' => 'something_went_wrong',
                    'data' => [],
                ];
            }
            return response()->json($response);
        }
    }
    public function get_total_data(Request $request)
    {
        $rules = [
            'store_id' => 'required|exists:stores,id',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $user_id = Auth::id();
        $seller_id = Seller::where('user_id', $user_id)->value('id');
        $store_id = $request->input('store_id') ?? '';

        $total_balance = fetchDetails(User::class, ['id' => $user_id], 'balance')[0]->balance;
        $totalSale = OrderItems::where('seller_id', $seller_id)
            ->where('store_id', $store_id)
            ->where('active_status', 'delivered')
            ->sum('sub_total');
        $totalCommission = OrderItems::where('seller_id', $seller_id)
            ->where('store_id', $store_id)
            ->sum('seller_commission_amount');
        // dd($totalCommission);
        $overallSale = $totalSale ?? 0;

        $total_commission_amount = $totalCommission ?? 0;

        $total_orders = app(OrderService::class)->ordersCount('', $seller_id, '', $store_id);

        $total_products = app(ProductService::class)->countProducts($seller_id, $store_id);

        $low_stock_products = countProductsStockLowStatus($seller_id, $store_id);

        $response = [
            'error' => false,
            'message' => 'Data retrived successfully',
            'language_message_key' => 'data_retrived_successfully',
            'data' => [
                'total_balance' => $total_balance,
                'total_sales' => $overallSale,
                'total_orders' => $total_orders,
                'total_products' => $total_products,
                'total_commission_amount' => $total_commission_amount,
                'low_stock_products' => $low_stock_products,
            ]
        ];
        return response()->json($response);
    }

    public function get_overview_statistic(Request $request)
    {
        $rules = [
            'store_id' => 'required|exists:stores,id',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $user_id = Auth::id();
        $seller_id = Seller::where('user_id', $user_id)->value('id');
        $store_id = $request->input('store_id');

        $sales = [];

        // Monthly Earnings using Eloquent
        $monthRes = OrderItems::selectRaw('SUM(quantity) AS total_sale, SUM(sub_total) AS total_revenue, COUNT(*) AS total_orders, DATE_FORMAT(created_at, "%b") AS month_name')
            ->where('seller_id', $seller_id)
            ->where('store_id', $store_id)
            ->groupBy(DB::raw('YEAR(created_at), MONTH(created_at)'))
            ->orderBy(DB::raw('YEAR(created_at), MONTH(created_at)'))
            ->get()
            ->toArray();

        $allMonths = array_fill_keys(['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'], [
            'total_sale' => 0,
            'total_orders' => 0,
            'total_revenue' => 0,
        ]);

        foreach ($monthRes as $month) {
            $monthName = $month['month_name'];
            $allMonths[$monthName] = [
                'total_sale' => intval($month['total_sale']),
                'total_orders' => intval($month['total_orders']),
                'total_revenue' => intval($month['total_revenue']),
            ];
        }

        $monthWiseSales = [
            'total_sale' => array_column($allMonths, 'total_sale'),
            'total_orders' => array_column($allMonths, 'total_orders'),
            'total_revenue' => array_column($allMonths, 'total_revenue'),
            'month_name' => array_keys($allMonths),
        ];

        $sales['monthly'] = $monthWiseSales;

        // Weekly Earnings using Eloquent
        $startDate = Carbon::now()->startOfWeek();
        $endDate = Carbon::now()->endOfWeek();

        $weekWiseSales = [
            'total_sale' => [],
            'total_revenue' => [],
            'total_orders' => [],
            'day' => [],
        ];

        for ($i = 0; $i < 7; $i++) {
            $currentDate = $startDate->copy()->addDays($i);
            $dayName = $currentDate->englishDayOfWeek;

            $dayRes = OrderItems::selectRaw("SUM(quantity) as total_sale, SUM(sub_total) as total_revenue, COUNT(*) as total_orders")
                ->where('seller_id', $seller_id)
                ->where('store_id', $store_id)
                ->whereDate('created_at', $currentDate)
                ->first();

            $weekWiseSales['total_sale'][] = $dayRes ? intval($dayRes->total_sale) : 0;
            $weekWiseSales['total_revenue'][] = $dayRes ? intval($dayRes->total_revenue) : 0;
            $weekWiseSales['total_orders'][] = $dayRes ? intval($dayRes->total_orders) : 0;
            $weekWiseSales['day'][] = $dayName;
        }

        $sales['weekly'] = $weekWiseSales;

        // Today's Earnings - Modified to return today's data using Eloquent
        $today = Carbon::today();

        $dayWiseSales = [
            'total_sale' => 0,
            'total_revenue' => 0,
            'total_orders' => 0,
            'day' => $today->format('j-n-y'),
        ];

        $todayRes = OrderItems::selectRaw("SUM(quantity) as total_sale, SUM(sub_total) as total_revenue, COUNT(*) as total_orders")
            ->where('seller_id', $seller_id)
            ->where('store_id', $store_id)
            ->whereDate('created_at', $today)
            ->first();

        $dayWiseSales['total_sale'] = $todayRes ? intval($todayRes->total_sale) : 0;
        $dayWiseSales['total_revenue'] = $todayRes ? intval($todayRes->total_revenue) : 0;
        $dayWiseSales['total_orders'] = $todayRes ? intval($todayRes->total_orders) : 0;

        // Add today's sales to the sales array
        $sales['today'] = $dayWiseSales;

        return response()->json([
            'error' => false,
            'message' => 'Data retrieved successfully',
            'language_message_key' => 'data_retrieved_successfully',
            'data' => $sales
        ]);
    }

    public function most_selling_categories(Request $request)
    {
        $rules = [
            'store_id' => 'required|exists:stores,id',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $user_id = Auth::id();
        $seller_id = Seller::where('user_id', $user_id)->value('id');
        $store_id = $request->input('store_id');
        $language_code = $request->attributes->get('language_code');
        $most_selling_categories = [];

        // Helper function to get data for a time range
        $getCategoryData = function ($startDate, $endDate) use ($seller_id, $store_id, $language_code) {
            return OrderItems::with(['productVariant.product.category'])
                ->where('seller_id', $seller_id)
                ->where('store_id', $store_id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get()
                ->groupBy(function ($item) {
                    return optional(optional(optional($item->productVariant)->product)->category)->id;
                })
                ->map(function ($items, $category_id) {
                    $firstItem = $items->first();
                    $productVariant = optional($firstItem)->productVariant;
                    $product = optional($productVariant)->product;
                    $category = optional($product)->category;

                    return [
                        'category_id' => optional($category)->id,
                        'category_name' => optional($category)->name,
                        'total_sold' => $items->sum('quantity')
                    ];
                })
                ->filter(fn($item) => !is_null($item['category_id']))
                ->sortByDesc('total_sold')
                ->take(5)
                ->values();
        };

        // Monthly
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $monthly = $getCategoryData($startOfMonth, $endOfMonth);
        $most_selling_categories['monthly']['total_sold'] = $monthly->map(fn($item) => (string) $item['total_sold']);
        $most_selling_categories['monthly']['category_names'] = $monthly->map(function ($item) use ($language_code) {
            return app(TranslationService::class)->getDynamicTranslation(Category::class, 'name', $item['category_id'], $language_code);
        });

        // Yearly
        $startOfYear = Carbon::now()->startOfYear();
        $endOfYear = Carbon::now()->endOfYear();
        $yearly = $getCategoryData($startOfYear, $endOfYear);
        $most_selling_categories['yearly']['total_sold'] = $yearly->map(fn($item) => (string) $item['total_sold']);
        $most_selling_categories['yearly']['category_names'] = $yearly->map(function ($item) use ($language_code) {
            return app(TranslationService::class)->getDynamicTranslation(Category::class, 'name', $item['category_id'], $language_code);
        });

        // Weekly
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        $weekly = $getCategoryData($startOfWeek, $endOfWeek);
        $most_selling_categories['weekly']['total_sold'] = $weekly->map(fn($item) => (string) $item['total_sold']);
        $most_selling_categories['weekly']['category_names'] = $weekly->map(function ($item) use ($language_code) {
            return app(TranslationService::class)->getDynamicTranslation(Category::class, 'name', $item['category_id'], $language_code);
        });

        return response()->json([
            'error' => false,
            'message' => 'Data retrieved successfully',
            'language_message_key' => 'data_retrived_successfully',
            'most_selling_categories' => $most_selling_categories,
        ]);
    }

    public function top_selling_products(Request $request)
    {
        $rules = [
            'store_id' => 'required|exists:stores,id',
            'category_id' => 'required|numeric|exists:categories,id',
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $user_id = Auth::id();
        $seller_id = Seller::where('user_id', $user_id)->value('id');
        $store_id = $request->input('store_id');
        $category_id = $request->input('category_id');
        $language_code = $request->attributes->get('language_code');

        // Get top selling products using Eloquent
        $top_selling_products = OrderItems::with(['productVariant.product'])
            ->where('seller_id', $seller_id)
            ->where('store_id', $store_id)
            ->when($category_id, function ($query) use ($category_id) {
                $query->whereHas('productVariant.product', function ($q) use ($category_id) {
                    $q->where('category_id', $category_id);
                });
            })
            ->select('product_variant_id', DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('product_variant_id')
            ->orderByDesc('total_sold')
            ->take(5)
            ->get()
            ->map(function ($orderItem) use ($language_code) {
                $product = $orderItem->productVariant->product;
                return (object) [
                    'product_id' => $product->id,
                    'category_id' => $product->category_id,
                    'brand_id' => $product->brand,
                    'image' => app(MediaService::class)->getMediaImageUrl($product->image),
                    'name' => app(TranslationService::class)->getDynamicTranslation(Product::class, 'name', $product->id, $language_code),
                    'total_sold' => $orderItem->total_sold,
                ];
            });

        return response()->json([
            'error' => $top_selling_products->isEmpty(),
            'message' => !$top_selling_products->isEmpty() ? 'Data retrieved successfully' : 'No products found',
            'language_message_key' => !$top_selling_products->isEmpty() ? 'data_retrived_successfully' : 'no_products_found',
            'category_ids' => implode(',', $top_selling_products->pluck('category_id')->unique()->toArray()),
            'brand_ids' => implode(',', $top_selling_products->pluck('brand_id')->filter()->unique()->toArray()),
            'data' => $top_selling_products,
        ]);
    }
    public function get_user_details(Request $request)
    {
        $rules = [
            'id' => 'required|exists:users,id',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }
        $id = $request->input('id') ?? '';

        $user = User::where('id', $id)->first();
        $fcm_ids = fetchDetails(UserFcm::class, ['user_id' => $user->id], 'fcm_id');

        $fcm_ids_array = array_map(function ($item) {
            return $item->fcm_id;
        }, $fcm_ids->all());

        $userData = app(SellerService::class)->formatUserData($user, $fcm_ids_array);

        return response()->json([
            'error' => false,
            'message' => 'Dats retrived successfully',
            'language_message_key' => 'data_retrivrd_successfully',
            'data' => $userData,
        ]);
    }

    public function download_order_invoice(Request $request)
    {
        $rules = [
            'order_id' => 'required|numeric|exists:orders,id',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {

            if (auth()->check()) {
                $user_id = auth()->user()->id;
                $seller_id = Seller::where('user_id', $user_id)->value('id');
            }
            $order_id = $request->input('order_id');

            if (!isExist(['id' => $order_id], Order::class)) {
                $response = [
                    'error' => true,
                    'message' => 'No order found!',
                    'language_message_key' => 'no_order_found',
                    'data' => [],
                ];
                return response()->json($response);
            }

            // Generating the URL to download the invoice
            $invoice_url = route('seller.orders.generatInvoicePDF', ['id' => $order_id, 'seller_id' => $seller_id]);

            $response = [
                'error' => false,
                'message' => 'Invoice URL generated successfully',
                'invoice_url' => $invoice_url,  // Return the generated URL
            ];

            return response()->json($response);
        }
    }


    public function download_parcel_invoice(Request $request, SellerOrderController $SellerOrderController)
    {
        /*
            id:154
        */
        $rules = [
            'id' => 'required|numeric|exists:parcels,id',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            $id = $request->input('id');

            if (!isExist(['id' => $id], Parcel::class)) {
                $response = [
                    'error' => true,
                    'message' => 'No order found!',
                    'language_message_key' => 'no_order_found',
                    'data' => [],
                ];
                return response()->json($response);
            }

            // Generating the URL to download the invoice
            $invoice_url = route('seller.orders.generatParcelInvoicePDF', ['id' => $id]);

            $response = [
                'error' => false,
                'message' => 'Invoice URL generated successfully',
                'invoice_url' => $invoice_url,  // Return the generated URL
            ];

            return response()->json($response);
        }
    }
    public function get_zones(Request $request)
    {
        $language_code = $request->attributes->get('language_code');
        return getZones($request, $language_code);
    }

    public function get_all_parcels(Request $request)
    {
        // order_id:10 // optional
        // parcel_id:107 // optional
        // in_detail:0 // by default 0, if product detail needed than pass 1
        // limit:10 // optional
        // offset:0 // optional
        // order:desc // optional
        // parcel_type:combo_order/regular_order
        // store_id:required
        $rules = [
            'store_id' => 'required|numeric|exists:stores,id',
            'order_id' => 'numeric|exists:orders,id',
            'parcel_id' => 'numeric|exists:parcels,id',
            'parcel_type' => 'string|in:combo_order,regular_order',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $user_id = Auth::id();
        $seller_id = Seller::where('user_id', $user_id)->value('id');
        $order_id = $request->input('order_id') ?? "";
        $store_id = $request->input('store_id') ?? "";
        $in_detail = $request->input('in_detail') ?? 1;
        $parcel_id = $request->input('parcel_id') ?? "";
        $offset = $request->input('offset') ?? 0;
        $limit = $request->input('limit') ?? 10;
        $order = $request->input('order') ?? "desc";
        $parcel_type = $request->input('parcel_type');
        // $res = viewAllParcelsOld($order_id, $parcel_id, $seller_id, $offset, $limit, $order, $in_detail, '', '', $store_id, $parcel_type);
        $res = app(ParcelService::class)->viewAllParcels($order_id, $parcel_id, $seller_id, $offset, $limit, $order, $in_detail, '', '', $store_id, $parcel_type);
        // dd($res);
        return response()->json([
            'error' => $res->original['error'],
            'message' => $res->original['message'],
            'language_message_key' => 'data_retrivrd_successfully',
            'total' => $res->original['total'],
            'data' => $res->original['data'],
        ]);
    }
    public function create_order_parcel(Request $request)
    {
        /*
            order_id:154
            selected_items:123,565
            parcel_title:parcel 1
            parcel_order_type:regular_order/combo_order
            pickup_location_id:1 {optional} // ID from pickup_locations table
        */

        $rules = [
            'selected_items' => 'required',
            'selected_items.*' => 'required|distinct',
            'parcel_title' => 'required|string|max:255',
            'order_id' => 'required|string|max:255',
            'parcel_order_type' => 'required|string|max:255',
            'pickup_location_id' => 'nullable|integer|exists:pickup_locations,id',
        ];

        $messages = [
            'pickup_location_id.integer' => 'Pickup location ID must be a number',
            'pickup_location_id.exists' => 'Pickup location not found',
        ];

        if ($response = $this->HandlesValidation($request, $rules, $messages, null, true)) {
            return $response;
        }

        $user_id = Auth::id();
        $seller_id = Seller::where('user_id', $user_id)->value('id');

        // Validate and set pickup_location_id
        if (isset($request['pickup_location_id']) && $request['pickup_location_id'] != 'NULL' && !empty($request['pickup_location_id'])) {
            $pickupLocation = PickupLocation::where('id', $request['pickup_location_id'])
                ->where('seller_id', $seller_id)
                ->first();

            if (!$pickupLocation) {
                return response()->json([
                    'error' => true,
                    'message' => 'You are not authorized to use this pickup location or it does not exist',
                    'language_message_key' => 'unauthorized_pickup_location',
                    'data' => []
                ]);
            }

            $request['pickup_location'] = $request['pickup_location_id'];
        }

        $request['seller_id'] = $seller_id;
        $request['selected_items'] = explode(',', $request->selected_items);
        $res = app(ParcelService::class)->createParcel($request);
        if ($res['error'] == true) {
            $response['error'] = $res['error'];
            $response['message'] = $res['message'];
            $response['data'] = [];
            return response()->json($response);
        }
        $parcel_type = $request->parcel_order_type;


        $parcel_res = app(ParcelService::class)->viewAllParcels('', $res['data'][0]['parcel_id'], offset: 0, limit: 10, parcel_type: $parcel_type);

        if ($res['error'] == false) {
            $response['error'] = $res['error'];
            $response['message'] = $res['message'];
            $response['data'] = $parcel_res->original['data'];
            return response()->json($response);
        }
        $response['error'] = $res['error'];
        $response['message'] = $res['message'];
        return response()->json($response);
    }
    public function delete_order_parcel(Request $request)
    {
        /*
            id:154
        */

        $rules = [
            'id' => 'required|numeric',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }
        $user_id = Auth::id();
        $seller_id = Seller::where('user_id', $user_id)->value('id');

        $parcel_id = $request->id ?? "";
        // dd($parcel_id);
        $res = app(ParcelService::class)->deleteParcel($parcel_id);
        return response()->json([
            'error' => $res['error'],
            'message' => $res['message'],
        ]);
    }
    public function update_parcel_order_status(Request $request, SellerOrderController $SellerOrderController)
    {
        // if type is digital order
        /*
            status : received/delivered
            order_id : 1
            order_item_ids : 1,2
            type : digital
        */
        /*
            status : received,processed,shipped,delivered,cancelled,returned
            deliver_by : 1
            parcel_id : 1
        */
        if (auth()->check()) {
            $user_id = auth()->user()->id;
            $seller_id = Seller::where('user_id', $user_id)->value('id');
        }
        $request['seller_id'] = $seller_id;
        $request['order_item_ids'] = explode(',', $request['order_item_ids']);
        $orderData = $SellerOrderController->update_order_status($request);
        return response()->json($orderData->original);
    }
    public function update_shiprocket_order_status(Request $request, SellerOrderController $SellerOrderController)
    {
        /*
            tracking_id : abcd1234
        */
        $rules = [
            'tracking_id' => 'required',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }
        $user_id = auth()->user()->id;
        $seller_id = Seller::where('user_id', $user_id)->value('id');
        $in_detail = $request->input('in_detail') ?? "";
        $offset = $request->input('offset') ?? 0;
        $limit = $request->input('limit') ?? 10;
        $order = $request->input('order') ?? "desc";
        $tracking_id = $request->tracking_id ?? "";
        $res = app(ShiprocketService::class)->updateShiprocketOrderStatus($tracking_id);
        $result = fetchDetails(OrderTracking::class, ['tracking_id' => $tracking_id], 'parcel_id');
        $details = "";
        if (isset($result[0]->parcel_id) && !empty($result[0]->parcel_id)) {
            $details = app(ParcelService::class)->viewAllParcels('', $result[0]->parcel_id, $seller_id, $offset, $limit, $order, $in_detail, '', '');
        }
        return response()->json([
            'error' => ($res['error'] == false) ? false : true,
            'message' => $res['message'],
            'data' => !empty($details) ? $details->original['data'][0] : "",
        ]);
    }

    private function formatMediaUrls(&$settings)
    {
        foreach ($settings as $key => $value) {
            if ($value === null) {
                $settings[$key] = "";
            } elseif (in_array($key, ['logo', 'favicon']) && !empty($value)) {
                $settings[$key] = app(MediaService::class)->getMediaImageUrl($value);
            }
        }

        // Handle onboarding media separately
        if (isset($settings['on_boarding_image']) && !empty($settings['on_boarding_image'])) {
            foreach ($settings['on_boarding_image'] as &$image) {
                $image = app(MediaService::class)->getMediaImageUrl($image);
            }
        } else {
            $settings['on_boarding_image'] = [];
        }

        if (isset($settings['on_boarding_video']) && !empty($settings['on_boarding_video'])) {
            foreach ($settings['on_boarding_video'] as &$video) {
                $video = app(MediaService::class)->getMediaImageUrl($video);
            }
        } else {
            $settings['on_boarding_video'] = [];
        }
    }
    public function get_notifications(Request $request, NotificationController $NotificationController)
    {

        $rules = [
            'sort' => 'nullable|sometimes|string',
            'limit' => 'nullable|sometimes|numeric',
            'offset' => 'nullable|sometimes|numeric',
            'order' => 'nullable|sometimes|string',
            'store_id' => 'required|numeric',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }
        $limit = $request->input('limit', 25);
        $offset = $request->input('offset', 0);
        $order = $request->input('order', 'DESC');
        $sort = $request->input('sort', 'id');
        $user_id = auth('sanctum')->check() ? auth('sanctum')->id() : "";


        $res = $NotificationController->get_seller_notifications($offset, $limit, $sort, $order, $user_id);
        return response()->json([
            'error' => count($res['data']) == 0 ? true : false,
            'message' => count($res['data']) == 0 ? 'Notification not found' : 'Notification Retrieved Successfully',
            'language_message_key' => count($res['data']) == 0 ? 'no_notification_found' : 'notification_retrieved_successfully',
            'total' => $res['total'],
            'data' => $res['data'],
        ]);
    }

    public function update_product_deliverability(Request $request)
    {
        $rules = [
            'product_id' => 'required|string',
            'deliverable_type' => 'required',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $product_ids = explode(',', $request->product_id);

        $valid_products = Product::whereIn('id', $product_ids)->pluck('id')->toArray();
        if (count($valid_products) !== count($product_ids)) {
            return response()->json([
                'error' => true,
                'message' => 'Some product IDs are invalid.',
            ], 422);
        }

        // Block deliverability changes if any related store is deactivated
        if (auth()->check()) {
            $user_id = auth()->user()->id;
            $seller_id = Seller::where('user_id', $user_id)->value('id');
            $storeIds = Product::whereIn('id', $product_ids)->pluck('store_id')->unique();
            foreach ($storeIds as $storeId) {
                if ($guard = $this->ensureActiveStore($seller_id, (int) $storeId)) {
                    return $guard;
                }
            }
        }

        $zones = is_array($request->deliverable_zones) ? implode(',', $request->deliverable_zones) : '';
        $deliverable_zones = ($request->deliverable_type == '1' || $request->deliverable_type == '0') ? '' : $zones;

        // Bulk update
        Product::whereIn('id', $product_ids)->update([
            'deliverable_type' => $request->deliverable_type,
            'deliverable_zones' => $deliverable_zones,
        ]);

        return response()->json([
            'error' => false,
            'message' => 'Deliverability updated successfully!',
        ], 200);
    }
    public function update_combo_product_deliverability(Request $request)
    {
        $rules = [
            'product_id' => 'required|string',
            'deliverable_type' => 'required',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }
        $product_ids = explode(',', $request->product_id);

        $valid_products = ComboProduct::whereIn('id', $product_ids)->pluck('id')->toArray();
        if (count($valid_products) !== count($product_ids)) {
            return response()->json([
                'error' => true,
                'message' => 'Some product IDs are invalid.',
            ], 422);
        }

        // Block deliverability changes if any related store is deactivated
        if (auth()->check()) {
            $user_id = auth()->user()->id;
            $seller_id = Seller::where('user_id', $user_id)->value('id');
            $storeIds = ComboProduct::whereIn('id', $product_ids)->pluck('store_id')->unique();
            foreach ($storeIds as $storeId) {
                if ($guard = $this->ensureActiveStore($seller_id, (int) $storeId)) {
                    return $guard;
                }
            }
        }

        $zones = is_array($request->deliverable_zones) ? implode(',', $request->deliverable_zones) : '';
        $deliverable_zones = ($request->deliverable_type == '1' || $request->deliverable_type == '0') ? '' : $zones;

        // Bulk update
        ComboProduct::whereIn('id', $product_ids)->update([
            'deliverable_type' => $request->deliverable_type,
            'deliverable_zones' => $deliverable_zones,
        ]);

        return response()->json([
            'error' => false,
            'message' => 'Deliverability updated successfully!',
        ], 200);
    }
    public function add_brands(Request $request)
    {
        $rules = [
            'store_id' => 'required|string',
            'brand_name' => 'required',
            'image' => 'required',
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }
        $user_id = Auth::user()->id;

        $seller_id = Seller::where('user_id', $user_id)->value('id');
        $storeId = $request['store_id'];
        $brandData = $request->all();

        // Check English name existence
        $existingBrand = Brand::where('store_id', $storeId)
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.en')) = ?", [$brandData['brand_name']])
            ->first();

        if ($existingBrand) {
            return response()->json([
                'error' => true,
                'message' => 'Brand name already exists.',
                'language_message_key' => 'brand_name_exists',
            ], 422);
        }

        // Build translations
        $translations = ['en' => $brandData['brand_name']];

        if (!empty($request['translated_brand_name'])) {
            $decoded = json_decode($request['translated_brand_name'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $translations = array_merge($translations, $decoded);
            }
        }

        // Prepare data for DB
        $brandData['name'] = json_encode($translations, JSON_UNESCAPED_UNICODE);
        $brandData['slug'] = generateSlug($translations['en'], 'brands');
        $brandData['status'] = 2;
        $brandData['store_id'] = $storeId;

        unset($brandData['brand_name'], $brandData['translated_brand_name']);

        // Save
        $brand = new Brand();
        $brand->fill($brandData);
        $brand->save();

        // Return only EN name + other fields
        return response()->json([
            'error' => false,
            'message' => 'Brand created successfully, Wait for approval of admin!',
            "data" => [
                "id" => $brand->id,
                "name" => $translations['en'],   // ONLY ENGLISH NAME
                "slug" => $brandData['slug'],
                "image" => app(MediaService::class)->dynamic_image($brandData['image'], 400),
                "store_id" => $brandData['store_id'],
                "status" => $brandData['status'],
                "seller_id" => $seller_id
            ]
        ], 200);
    }


    public function add_categories(Request $request)
    {
        // translated_category_name: {"hn": "हिंदी उत्पाद नाम","fr": "Nom du produit français"},
        // store_id:1
        // name: category name
        // category_image: relative path
        // banner: relative path
        // parent_id: 1

        $rules = [
            'name' => 'required|string',
            'category_image' => 'required',
            'banner' => 'required',
        ];
        $user_id = Auth::user()->id;

        $seller_id = Seller::where('user_id', $user_id)->value('id');
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }
        $storeId = $request['store_id'] ?? '';
        $categoryData = $request->only(array_keys($rules));

        $existingCategory = Category::where('store_id', $storeId)
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.en')) = ?", [$categoryData['name']])
            ->first();

        if ($existingCategory) {
            return response()->json([
                'error' => true,
                'message' => 'Category name already exists.',
                'language_message_key' => 'category_name_exists',
            ], 400);
        }

        $translations = [
            'en' => $categoryData['name']
        ];
        if (!empty($request['translated_category_name'])) {
            $decoded = json_decode($request['translated_category_name'], true);
            if (json_last_error() == JSON_ERROR_NONE && is_array($decoded)) {
                $translations = array_merge($translations, $decoded);
            }
        }

        $categoryData = [
            'name' => json_encode($translations, JSON_UNESCAPED_UNICODE),
            'slug' => generateSlug($translations['en'], 'categories'),
            'image' => $request->category_image,

            'banner' => $request->banner,
            'parent_id' => $request->parent_id ?? 0,
            'status' => 2,
            'store_id' => $storeId,
            'seller_id' => $seller_id
        ];

        $cateogry_cre = Category::create($categoryData);

        return response()->json([
            'error' => false,
            'message' => 'Category created successfully, Wait for approval of admin!',
            'data' => [
                "id" => $cateogry_cre->id ?? '',
                'name' => $translations['en'],
                'slug' => $categoryData['slug'],
                'image' => app(MediaService::class)->dynamic_image($categoryData['image'], 400),
                'banner' => $categoryData['banner'],
                'parent_id' => $categoryData['parent_id'],
                'status' => $categoryData['status'],
                'store_id' => $categoryData['store_id'],
                'seller_id' => $seller_id
            ]
        ], 200);
    }

    /*
        search:keyword      // optional
        limit:25            // { default - 25 } optional
        offset:0            // { default - 0 } optional
        sort: id / created_at // { default - id } optional
        order:DESC/ASC      // { default - DESC } optional
    */
    public function get_return_requests(Request $request)
    {
        $rules = [
            'sort' => 'string',
            'limit' => 'numeric',
            'offset' => 'numeric',
        ];
        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }
        if (auth()->check()) {
            $user_id = auth()->user()->id;
            $seller_id = Seller::where('user_id', $user_id)->value('id');
        }
        $language_code = $request->attributes->get('language_code');

        $limit = $request->input('limit', 25);
        $offset = $request->input('offset', 0);
        $sort = $request->input('sort', 'o.id');
        $order = $request->input('order', 'DESC');
        $search = $request->input('search', '');

        $res = getReturnRequest($limit, $offset, $sort, $order, $search, $seller_id, $language_code);

        return response()->json([
            'error' => count($res['data']) == 0 ? true : false,
            'message' => count($res['data']) == 0 ? 'Retuen Request not found' : 'Retuen Request Retrieved Successfully',
            'language_message_key' => count($res['data']) == 0 ? 'retuen_request_not_found' : 'retuen_request_retrieved_successfully',
            'total' => $res['total'],
            'data' => $res['data'],
        ]);
    }

    /*
       status : 0  (0 = pending | 1 = approved | 2 = rejected | 3 = returned | 8 = return_pickedup)
       return_request_id = 1
       order_item_id = 111
       deliver_by = 10 // pass only when status is 1
   */
    public function update_return_requests(Request $request, ReturnRequestController $returnRequestController)
    {
        $rules = [
            'return_request_id' => 'required|numeric',
            'status' => 'required|numeric',
            'order_item_id' => 'required|numeric',
            'update_remarks' => 'nullable|string',
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }
        $request['from_app'] = 1;

        $res = $returnRequestController->update($request);

        return response()->json([
            'error' => $res->original['error'],
            'message' => $res->original['message'] ?? $res->original['error_message'],
            // 'language_message_key' => $res->original['error'] ? 'no_data_found' : 'return_request_updated_successfully',
        ]);
    }



    public function bulk_update_product_pickup_locations(Request $request)
    {
        /*
            product_ids: 1,2,3      // required - comma separated product IDs
            pickup_location_id: 1   // required - pickup location ID
        */

        $rules = [
            'product_ids' => 'required|string',
            'pickup_location_id' => 'required|integer|exists:pickup_locations,id',
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        if (auth()->check()) {
            $user_id = auth()->user()->id;
            $seller_id = Seller::where('user_id', $user_id)->value('id');
        } else {
            return response()->json([
                'error' => true,
                'message' => 'Unauthorized',
                'language_message_key' => 'unauthorized',
            ]);
        }

        // Verify pickup location belongs to seller and is approved
        $pickupLocation = PickupLocation::where('id', $request->pickup_location_id)
            ->where('seller_id', $seller_id)
            ->where('status', 1)
            ->first();

        if (!$pickupLocation) {
            return response()->json([
                'error' => true,
                'message' => 'Invalid pickup location or not approved',
                'language_message_key' => 'invalid_pickup_location_or_not_approved',
            ]);
        }

        $product_ids = explode(',', $request->product_ids);

        // Verify all products belong to this seller
        $valid_products = Product::whereIn('id', $product_ids)
            ->where('seller_id', $seller_id)
            ->pluck('id')
            ->toArray();

        if (count($valid_products) !== count($product_ids)) {
            return response()->json([
                'error' => true,
                'message' => 'Some product IDs are invalid or do not belong to you',
                'language_message_key' => 'invalid_product_ids',
            ]);
        }

        // Update products
        Product::whereIn('id', $product_ids)->update([
            'pickup_location' => $request->pickup_location_id,
        ]);

        return response()->json([
            'error' => false,
            'message' => 'Pickup locations updated successfully',
            'language_message_key' => 'pickup_locations_updated_successfully',
        ]);
    }
    public function bulk_update_combo_product_pickup_locations(Request $request)
    {
        /*
            product_ids: 1,2,3      // required - comma separated combo product IDs
            pickup_location_id: 1   // required - pickup location ID
        */

        $rules = [
            'product_ids' => 'required|string',
            'pickup_location_id' => 'required|integer|exists:pickup_locations,id',
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        if (auth()->check()) {
            $user_id = auth()->user()->id;
            $seller_id = Seller::where('user_id', $user_id)->value('id');
        } else {
            return response()->json([
                'error' => true,
                'message' => 'Unauthorized',
                'language_message_key' => 'unauthorized',
            ]);
        }

        // Verify pickup location belongs to seller and is approved
        $pickupLocation = PickupLocation::where('id', $request->pickup_location_id)
            ->where('seller_id', $seller_id)
            ->where('status', 1)
            ->first();

        if (!$pickupLocation) {
            return response()->json([
                'error' => true,
                'message' => 'Invalid pickup location or not approved',
                'language_message_key' => 'invalid_pickup_location_or_not_approved',
            ]);
        }

        $product_ids = explode(',', $request->product_ids);

        // Verify all combo products belong to this seller
        $valid_products = ComboProduct::whereIn('id', $product_ids)
            ->where('seller_id', $seller_id)
            ->pluck('id')
            ->toArray();

        if (count($valid_products) !== count($product_ids)) {
            return response()->json([
                'error' => true,
                'message' => 'Some combo product IDs are invalid or do not belong to you',
                'language_message_key' => 'invalid_combo_product_ids',
            ]);
        }

        // Update combo products
        ComboProduct::whereIn('id', $product_ids)->update([
            'pickup_location' => $request->pickup_location_id,
        ]);

        return response()->json([
            'error' => false,
            'message' => 'Pickup locations updated successfully',
            'language_message_key' => 'pickup_locations_updated_successfully',
        ]);
    }
}

