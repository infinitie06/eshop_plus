<?php

namespace App\Http\Controllers\Delivery_boy\v1;

use App\Http\Controllers\Admin\Delivery_boyController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Delivery_boy\CashCollectionController;
use App\Http\Controllers\Delivery_boy\OrderController;
use App\Http\Controllers\Seller\AreaController;
use App\Http\Controllers\Seller\PaymentRequestController;
use App\Models\Currency;
use App\Models\Language;
use App\Models\OrderItems;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserFcm;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use App\Services\TranslationService;
use App\Traits\HandlesValidation;
use App\Services\MediaService;
use App\Services\ParcelService;
use App\Services\SettingService;
use App\Services\OrderService;

class ApiController extends Controller
{
    use HandlesValidation;
    /*
---------------------------------------------------------------------------
Defined Methods:-
---------------------------------------------------------------------------
1. login
2. register
3. get_zipcodes
4. get_delivery_boy_details
<---- Newly changes for parcels ---->
5. get_orders
<---- Newly changes for parcels ---->
6. get_fund_transfers
7. update_fcm
8. update_user
9. get_notifications
10.verify_user
11.get_settings
12.send_withdrawal_request
13.get_withdrawal_request
14.update_order_item_status
15.get_delivery_boy_cash_collection
16.delete_delivery_boy
17.get_wallet_transaction
<---- Newly changes for return order ---->
18. view_return_order_items
19. update_return_order_item_status
<---- Newly changes for return order ---->
<---- Newly changes for availability ---->
20. toggle_availability
21. get_availability_status
<---- Newly changes for availability ---->

*/

    public function login(Request $request)
    {


        $rules = [
            'mobile' => 'required|numeric',
            'password' => 'required',
            'country_code' => 'required|string',
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        $credentials = [
            'mobile' => $request->mobile,
            'country_code' => $request->country_code,
            'password' => $request->password,
        ];

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'error' => true,
                'message' => 'Invalid credentials',
                'language_message_key' => 'invalid_credentials',
            ], 200);
        }

        $user = Auth::user();

        if ($user->role_id != 3) {
            return response()->json([
                'error' => true,
                'message' => 'Incorrect Login.',
                'language_message_key' => 'incorrect_login',
            ]);
        }

        $token = $user->createToken('authToken')->plainTextToken;

        // FCM ID
        if (!empty($request->fcm_id)) {
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

        $fcm_ids = fetchDetails(UserFcm::class, ['user_id' => $user->id], 'fcm_id');
        $fcm_ids_array = array_map(fn($item) => $item->fcm_id, $fcm_ids->all());

        $language_code = $request->attributes->get('language_code');
        $zone_ids = explode(',', $user->serviceable_zones);
        $zones = Zone::whereIn('id', $zone_ids)->get();

        $translated_zones = $zones->map(
            fn($zone) =>
            app(TranslationService::class)->getDynamicTranslation(Zone::class, 'name', $zone->id, $language_code)
        )->toArray();

        $zones = implode(',', $translated_zones) ?? '';

        $userData = [
            'user_id' => $user->id,
            'username' => $user->username ?? '',
            'email' => $user->email ?? '',
            'mobile' => $user->mobile ?? '',
            'country_code' => $user->country_code ?? '',
            'image' => !empty($user->image)
                ? app(MediaService::class)->getMediaImageUrl($user->image, 'DELIVERY_BOY_IMG_PATH')
                : '',
            'country_code' => $user->country_code ?? '',
            'is_available' => isset($user->is_available) ? (int)$user->is_available : 1,
            'address' => $user->address ?? '',
            'latitude' => $user->latitude ?? '',
            'longitude' => $user->longitude ?? '',
            'zones' => $zones ?? '',
            'fcm_id' => array_values($fcm_ids_array),
            'front_licence_image' => !empty($user->front_licence_image)
                ? app(MediaService::class)->getMediaImageUrl($user->front_licence_image, 'DELIVERY_BOY_IMG_PATH')
                : '',
            'back_licence_image' => !empty($user->back_licence_image)
                ? app(MediaService::class)->getMediaImageUrl($user->back_licence_image, 'DELIVERY_BOY_IMG_PATH')
                : '',
        ];

        $messages = [
            "0" => "Your account is pending verification by the admin.",
            "1" => "Logged in successfully",
            "2" => "Your account is not yet approved.",
            "7" => "Your account has been removed by the admin. Contact admin for more information.",
        ];

        $language_message_key = [
            "0" => "account_deactivated",
            "1" => "user_logged_in_successfully",
            "2" => "account_not_yet_approved",
            "7" => "account_removed_by_admin_contact_admin",
        ];

        return response()->json([
            'error' => $user->status != 1,
            'message' => $messages[$user->status] ?? 'Unknown status',
            'language_message_key' => $language_message_key[$user->status] ?? '',
            'token' => $token,
            'data' => $user->status == 1 ? $userData : [],
        ]);
    }



    public function register(Delivery_boyController $deliveryBoyController, Request $request)
    {
        /*
        country_code: +91
        name: hiten
        mobile: 7852347890
        email: amangoswami@gmail.com
        password: 12345678
        confirm_password: 12345678
        address: test
        serviceable_zones[]: 1,2
        front_licence_image: FILE
        back_licence_image: FILE
        profile_image: FILE
        bonus_type: percentage_per_order/fixed_amount_per_order
        bonus_amount: 20 // required when type is fixed_amount_per_order
        bonus_percentage: 20 // required when type is percentage_per_order
        */

        $rules = [
            'country_code' => 'required|string',
            'name' => 'required',
            'mobile' => [
                'required',
                'numeric',
                Rule::unique('users', 'mobile')->where(function ($query) use ($request) {
                    return $query->where('country_code', $request->country_code);
                }),
            ],
            'email' => 'required|email|unique:users,email',
            'password' => 'required',
            'confirm_password' => 'required|same:password',
            'address' => 'required',
            'front_licence_image' => 'required|file',
            'profile_image' => 'required|file',
            'back_licence_image' => 'required|file',
            'serviceable_zones' => 'required|array',
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        // Append country code to request for saving in user creation
        $request->merge(['country_code' => $request->country_code]);

        $delivery_boy_data = $deliveryBoyController->store($request, true);

        if ($delivery_boy_data->original['error'] != true) {
            return response()->json([
                'error' => false,
                'message' => 'Delivery Boy registered successfully. Wait for admin approval.',
                'language_message_key' => 'delivery_boy_registered_successfully_wait_for_approval',
            ]);
        }

        return response()->json([
            'error' => $delivery_boy_data->original['error'],
            'message' => $delivery_boy_data->original['message'],
            'data' => [],
        ]);
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
            $zipcode_data = $areaController->zipcode_list($request);


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

    public function get_delivery_boy_details(Request $request)
    {
        if (auth()->check()) {
            $user = Auth::user();
            $fcm_ids = fetchDetails(UserFcm::class, ['user_id' => $user->id], 'fcm_id');

            $fcm_ids_array = array_map(function ($item) {
                return $item->fcm_id;
            }, $fcm_ids->all());
            $language_code = $request->attributes->get('language_code');
            $zone_ids = explode(',', $user->serviceable_zones);
            $zones = Zone::whereIn('id', $zone_ids)->get();

            $translated_zones = $zones->map(function ($zone) use ($language_code) {
                return app(TranslationService::class)->getDynamicTranslation(Zone::class, 'name', $zone->id, $language_code);
            })->toArray();

            $zones = implode(',', $translated_zones) ?? '';
            $userData = [
                'user_id' => $user->id ?? '',
                'ip_address' => $user->ip_address ?? '',
                'username' => $user->username ?? '',
                'email' => $user->email ?? '',
                'mobile' => $user->mobile ?? '',
                'image' => !empty($user->image) ? app(MediaService::class)->getMediaImageUrl($user->image, 'DELIVERY_BOY_IMG_PATH') : '',
                'activation_selector' => $user->activation_selector ?? '',
                'activation_code' => $user->activation_code ?? '',
                'forgotten_password_selector' => $user->forgotten_password_selector ?? '',
                'forgotten_password_code' => $user->forgotten_password_code ?? '',
                'forgotten_password_time' => $user->forgotten_password_time ?? '',
                'remember_selector' => $user->remember_selector ?? '',
                'remember_code' => $user->remember_code ?? '',
                'created_on' => $user->created_on ?? '',
                'last_login' => $user->last_login ?? '',
                'active' => $user->active ?? '',
                'is_notification_on' => $user->is_notification_on ?? '',
                'balance' => $user->balance ?? '',
                'company' => $user->company ?? '',
                'address' => $user->address ?? '',
                'bonus' => $user->bonus ?? '',
                'bonus_type' => $user->bonus_type ?? '',
                'serviceable_zones' => $user->serviceable_zones ?? '',
                'zones' => $zones ?? '',
                'cash_received' => $user->cash_received ?? '0.00',
                'dob' => $user->dob ?? '',
                'country_code' => $user->country_code ?? '',
                'city' => $user->city ?? '',
                'area' => $user->area ?? '',
                'street' => $user->street ?? '',
                'pincode' => $user->pincode ?? '',
                'apikey' => $user->apikey ?? '',
                'referral_code' => $user->referral_code ?? '',
                'friends_code' => $user->friends_code ?? '',
                'fcm_id' => array_values($fcm_ids_array) ?? '',
                'latitude' => $user->latitude ?? '',
                'longitude' => $user->longitude ?? '',
                'created_at' => $user->created_at ?? '',
                'type' => $user->type ?? '',
                'front_licence_image' => !empty($user->front_licence_image) ? app(MediaService::class)->getMediaImageUrl($user->front_licence_image, 'DELIVERY_BOY_IMG_PATH') : '',
                'back_licence_image' => !empty($user->back_licence_image) ? app(MediaService::class)->getMediaImageUrl($user->back_licence_image, 'DELIVERY_BOY_IMG_PATH') : '',
                'is_available' => isset($user->is_available) ? (int)$user->is_available : 1,
            ];

            if ($user->role_id == 3) {

                unset($user->password);

                return response()->json([
                    'error' => false,
                    'message' => 'Data retrived successfully',
                    'language_message_key' => 'data_retrieved_successfully',
                    'data' => isset($userData) ? $userData : [],
                ]);
            }
        }
    }

    public function get_orders(Request $request)
    {
        /*
            parcel_type : combo_order/regular_order
            parcel_id: 123      // { parcel id } optional
            active_status: received  {received,delivered,cancelled,processed,returned}     // optional
            limit:25            // { default - 25 } optional
            offset:0            // { default - 0 } optional
            sort: id / date_added // { default - id } optional
            order:DESC/ASC      // { default - DESC } optional
        */

        $rules = [
            'limit' => 'numeric',
            'offset' => 'numeric',
            'parcel_id' => 'numeric',
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            if (auth()->check()) {
                $user_id = auth()->user()->id;
            }
            $limit = $request->input('limit', 25);
            $offset = $request->input('offset', 0);
            $order_id = $request->input('id', 0);
            $sort = $request->input('sort', 'o.id');
            $order = $request->input('order', 'DESC');
            $activeStatus = $request->input('active_status');
            $parcel_type = $request->input('parcel_type');
            $parcel_id = $request->input('parcel_id', '');

            $delivery_boy_id = auth::id();

            $res = app(ParcelService::class)->viewAllParcels($order_id, $parcel_id, '', $offset, $limit, $order, '1', $delivery_boy_id, $activeStatus, '', $parcel_type, 1);

            if (isset($res->original) && empty($res->original['data'])) {
                $response['error'] = true;
                $response['message'] = "Parcel Not Found.";
                $response['data'] = [];
                return response()->json($response);
            }
            $res = !$res->isempty() ? $res->original : "";

            // foreach ($res['data'] as $key => $parcel) {
            //     $subtotal = 0;
            //     foreach ($parcel['items'] as $items) {


            //         $subtotal = $items['unit_price'] * $items['quantity'];
            //     }
            //     $res['data'][$key]['total'] = $subtotal;
            //     $delivery_charge = $res['data'][$key]['delivery_charge'];
            //     $promo_discount = $res['data'][$key]['promo_discount'];
            //     $tax_amount = $res['data'][$key]['tax_amount'];
            //     $final_total = $subtotal + $delivery_charge + $tax_amount - $promo_discount;
            //     $res['data'][$key]['sub_total'] = (string) intval($subtotal);
            //     $res['data'][$key]['final_total'] = (string) intval($final_total);
            //     $res['data'][$key]['total_payable'] = (string) intval($final_total);
            // }

            if (!empty($res)) {
                $response['error'] = false;
                $response['message'] = 'Data retrieved successfully';
                $response['language_message_key'] = 'data_retrieved_successfully';
                $response['total'] = $res['total'];
                $response['awaiting'] = strval(app(OrderService::class)->deliveryBoyOrdersCount("awaiting", $user_id, 'parcels'));
                $response['received'] = strval(app(OrderService::class)->deliveryBoyOrdersCount("received", $user_id, 'parcels'));
                $response['processed'] = strval(app(OrderService::class)->deliveryBoyOrdersCount("processed", $user_id, 'parcels'));
                $response['shipped'] = strval(app(OrderService::class)->deliveryBoyOrdersCount("shipped", $user_id, 'parcels'));
                $response['delivered'] = strval(app(OrderService::class)->deliveryBoyOrdersCount("delivered", $user_id, 'parcels'));
                $response['cancelled'] = strval(app(OrderService::class)->deliveryBoyOrdersCount("cancelled", $user_id, 'parcels'));
                $response['returned'] = strval(app(OrderService::class)->deliveryBoyOrdersCount("returned", $user_id, 'parcels'));
                $response['data'] = $res['data'];
            } else {
                $response['error'] = true;
                $response['message'] = 'Order Does Not Exists';
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
    public function get_fund_transfers(Request $request, CashCollectionController $cashCollectionController)
    {
        /*
            active_status: received  {received,delivered,cancelled,processed,returned}     // optional
            limit:25            // { default - 25 } optional
            offset:0            // { default - 0 } optional
            sort: id / date_added // { default - id } optional
            order:DESC/ASC      // { default - DESC } optional
        */
        $rules = [
            'limit' => 'numeric',
            'offset' => 'numeric',
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            if (auth()->check()) {
                $user_id = auth()->user()->id;
            }
            $limit = $request->input('limit', 25);
            $offset = $request->input('offset', 0);
            $sort = $request->input('sort', 'o.id');
            $order = $request->input('order', 'DESC');

            $fundTransferData = $cashCollectionController->fund_transfers_list($request);
            if ($fundTransferData->original['total'] != 0) {
                $response['error'] = false;
                $response['message'] = 'Data retrieved successfully';
                $response['language_message_key'] = 'data_retrieved_successfully';
                $response['total'] = strval($fundTransferData->original['total']);
                $response['data'] = $fundTransferData->original['rows'];
            } else {
                $response['error'] = true;
                $response['message'] = 'No fund transfer has been made yet';
                $response['language_message_key'] = 'data_does_not_exists';
                $response['total'] = strval($fundTransferData->original['total']);
                $response['data'] = $fundTransferData->original['rows'];
            }
            return response()->json($response);
        }
    }
    public function update_fcm(Request $request)
    {
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

    public function update_user(Request $request, Delivery_boyController $deliveryBoyController)
    {
        /*
            user_id:34
            username:hiten
            mobile:7852347890 {optional}
            email:amangoswami@gmail.com	{optional}
            //optional parameters
            password:12345 {optional}
            confirm_password:345234 {optional}
            front_licence_image : FILE {optional}
            back_licence_image : FILE {optional}
            profile_image : FILE {optional}
        */

        if (auth()->check()) {
            $user_id = auth()->user()->id;
        }
        $data = $deliveryBoyController->update($request, $user_id);
        $language_code = $request->attributes->get('language_code');

        $zone_ids = explode(',', $data->original['data']->serviceable_zones);
        $zones = Zone::whereIn('id', $zone_ids)->pluck('name')->toArray();

        $translated_zones = array_map(function ($zoneJson) use ($language_code) {
            $decoded = json_decode($zoneJson, true);
            return $decoded[$language_code] ?? '';
        }, $zones);

        $zones = implode(', ', $translated_zones);

        $data->original['data']->zones = $zones ?? '';

        $data->original['data']['front_licence_image'] = !empty($data->original['data']['front_licence_image']) ? app(MediaService::class)->getMediaImageUrl($data->original['data']['front_licence_image'], 'DELIVERY_BOY_IMG_PATH') : '';
        $data->original['data']['back_licence_image'] = !empty($data->original['data']['back_licence_image']) ? app(MediaService::class)->getMediaImageUrl($data->original['data']['back_licence_image'], 'DELIVERY_BOY_IMG_PATH') : '';
        $data->original['data']['image'] = !empty($data->original['data']['image']) ? app(MediaService::class)->getMediaImageUrl($data->original['data']['image'], 'DELIVERY_BOY_IMG_PATH') : '';
        $response['error'] = $data->original['error'];
        $response['message'] = $data->original['message'];
        $response['data'] = $data->original['data'] ?? [];

        return response()->json($response);
    }

    public function get_notifications(Request $request, NotificationController $NotificationController)
    {
        /*
            limit:25            // { default - 25 } optional
            offset:0            // { default - 0 } optional
            sort: id / date_added // { default - id } optional
            order:DESC/ASC      // { default - DESC } optional
        */

        $rules = [
            'sort' => 'nullable|sometimes|string',
            'limit' => 'nullable|sometimes|numeric',
            'offset' => 'nullable|sometimes|numeric',
            'order' => 'nullable|sometimes|string',
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }
        $limit = $request->input('limit', 25);
        $offset = $request->input('offset', 0);
        $order = $request->input('order', 'DESC');
        $sort = $request->input('sort', 'id');
        $user_id = auth('sanctum')->check() ? auth('sanctum')->id() : "";

        $language_code = $request->attributes->get('language_code');
        $res = $NotificationController->get_notifications($offset, $limit, $sort, $order, $user_id, $language_code);

        return response()->json([
            'error' => empty($res['data']) ? true : false,
            'message' => empty($res['data']) ? 'Notification not found' : 'Notification Retrieved Successfully',
            'language_message_key' => empty($res['data']) ? 'no_notification_found' : 'notification_retrieved_successfully',
            'total' => $res['total'],
            'data' => $res['data'],
        ]);
    }

    public function verify_user(Request $request)
    {
        /* Parameters to be passed
            mobile: 9874565478
            email: test@gmail.com // { optional }
        */

        $rules = [
            'mobile' => 'required|numeric',
            'email' => 'sometimes|nullable|email',
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            $mobile = $request->input('mobile');
            $email = $request->input('email');

            if (isset($mobile) && isExist(['mobile' => $mobile], User::class)) {
                $user_id = fetchDetails(User::class, ['mobile' => $mobile], 'role_id')[0];

                //Check if this mobile no. is registered as a delivery boy or not.
                if ($user_id->role_id != 3) {
                    $response = [
                        'error' => true,
                        'message' => 'Mobile number / email could not be found!',
                        'language_message_key' => 'mobile_or_email_not_found',
                        'data' => [],
                    ];
                } else {
                    $response = [
                        'error' => true,
                        'message' => 'Mobile is already registered.Please login again !',
                        'language_message_key' => 'mobile_already_registered_login_again',
                        'data' => [],
                    ];
                }
                return response()->json($response);
            }

            if (isset($email) && isExist(['email' => $email], User::class)) {
                $response = [
                    'error' => true,
                    'message' => 'Email is already registered.Please login again !',
                    'language_message_key' => 'email_already_registered_login_again',

                    'data' => [],
                ];
                return response()->json($response);
            }
            $response = [
                'error' => true,
                'message' => 'Mobile number / email could not be found!',
                'language_message_key' => 'mobile_or_email_not_found',
                'data' => [],
            ];
            return response()->json($response);
        }
    }

    public function get_settings(Request $request)
    {
        /*
            type : delivery_boy_privacy_policy / delivery_boy_terms_conditions
        */

        // Validate the 'type' parameter
        $rules = [
            'type' => 'sometimes|in:delivery_boy_privacy_policy,delivery_boy_terms_and_conditions',
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }
        // Fetch system settings
        $settings = json_decode(app(SettingService::class)->getSettings('system_settings', true), true);
        $contact_us = json_decode(app(SettingService::class)->getSettings('contact_us', true), true);
        $about_us = json_decode(app(SettingService::class)->getSettings('about_us', true), true);
        // Default settings to unset
        $fields_to_unset = [
            'enable_cart_button_on_product_list_view',
            'expand_product_image',
            'tax_name',
            'tax_number',
            'google',
            'facebook',
            'apple',
            'refer_and_earn_status',
            'minimum_refer_and_earn_amount',
            'minimum_refer_and_earn_bonus',
            'refer_and_earn_method',
            'max_refer_and_earn_amount',
            'number_of_times_bonus_given_to_customer',
            'wallet_balance_status',
            'wallet_balance_amount',
            'authentication_method',
            'supported_locals',
            'store_currency',
            'decimal_point',
            'single_seller_order_system',
            'customer_app_maintenance_status',
            'seller_app_maintenance_status',
            'message_for_customer_app',
            'message_for_seller_app',
            'sidebar_color',
            'sidebar_type',
            'navbar_fixed',
            'theme_mode',
            'current_version_of_ios_app',
            'current_version_of_android_app',
            'current_version_of_android_app_for_seller',
            'current_version_of_ios_app_for_seller',
            'storage_type',
            'minimum_cart_amount',
            'maximum_item_allowed_in_cart',
            'low_stock_limit',
            'max_days_to_return_item',
            'ai_setting',
        ];

        // Unset unnecessary fields
        foreach ($fields_to_unset as $field) {
            unset($settings[$field]);
        }

        // Get currency symbol
        $currencyDetails = fetchDetails(Currency::class, ['is_default' => 1], 'symbol');
        $currency = !$currencyDetails->isEmpty() ? $currencyDetails[0]->symbol : '';

        // Handle case when 'type' is not passed
        $type = $request->input('type', '');
        if (empty($type)) {
            $terms_and_conditions = json_decode(app(SettingService::class)->getSettings('delivery_boy_terms_and_conditions'), true);
            $privacy_policy = json_decode(app(SettingService::class)->getSettings('delivery_boy_privacy_policy'), true);

            $data = [
                'delivery_boy_terms_and_conditions' => $terms_and_conditions['delivery_boy_terms_and_conditions'] ?? '',
                'delivery_boy_privacy_policy' => $privacy_policy['delivery_boy_privacy_policy'] ?? '',
                'contact_us' => $contact_us['contact_us'] ?? '',
                'about_us' => $about_us['about_us'] ?? '',
            ];

            $this->formatMediaUrls($settings);

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

            return response()->json([
                'error' => false,
                'message' => 'Settings retrieved successfully',
                'language_message_key' => 'settings_retrieved_successfully',
                'data' => $data,
                'currency' => $currency,
                'languages' => $languages,
                // 'delivery_boy_app_maintenance_status' => $settings['delivery_boy_app_maintenance_status'],
                // 'message_for_delivery_boy_app' => $settings['message_for_delivery_boy_app'],
                'system_settings' => $settings,

            ]);
        }

        // Handle specific setting based on 'type'
        $allowed_settings = ['delivery_boy_terms_and_conditions', 'delivery_boy_privacy_policy', 'currency'];
        if (!in_array($type, $allowed_settings)) {
            return response()->json([
                'error' => false,
                'message' => 'Currency',
                'data' => [],
            ]);
        }

        $settings_res = json_decode(app(SettingService::class)->getSettings($type), true);
        if (!empty($settings_res)) {
            $data = [$type => [$settings_res[$type] ?? '']];

            $this->formatMediaUrls($settings);

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

            return response()->json([
                'error' => false,
                'message' => 'Settings retrieved successfully',
                'language_message_key' => 'settings_retrieved_successfully',
                'data' => $data,
                'currency' => $currency,
                'languages' => $languages,
                'delivery_boy_app_maintenance_status' => $settings['delivery_boy_app_maintenance_status'],
                'message_for_delivery_boy_app' => $settings['message_for_delivery_boy_app'],
                'system_settings' => $settings,

            ]);
        }

        return response()->json([
            'error' => true,
            'message' => 'Settings Not Found',
            'language_message_key' => 'settings_not_found',
            'data' => [],
        ]);
    }

    // Helper function to format media URLs and set null fields to empty strings
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
            $data = $paymentRequest->add_withdrawal_request($request, true);
            $response['error'] = $data->original['error'];
            $response['message'] = isset($data->original['message']) ? $data->original['message'] : $data->original['error_message'];
            $response['amount'] = $data->original['amount'];
            $response['data'] = $data->original['data'];
            return response()->json($response);
        }
    }

    public function get_withdrawal_request(Request $request, PaymentRequestController $paymentRequest)
    {
        /*
           sort:               // { payment_requests.id } optional
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
            if (auth()->check()) {
                $user_id = auth()->user()->id;
            }

            $data = $paymentRequest->get_payment_request_list($request, $user_id);

            $response['error'] = $data->original['rows']->isEmpty() ? true : false;
            $response['message'] = $data->original['rows']->isEmpty() ? 'Withdrawal Request does not exist' : 'Withdrawal Request Retrieved Successfully';
            $response['language_message_key'] = $data->original['rows']->isEmpty() ? 'withdrawal_request_does_not_exist' : 'withdrawal_request_retrieved_successfully';
            $response['total'] = $data->original['total'];
            $response['data'] = $data->original['rows'];

            return response()->json($response);
        }
    }

    public function update_order_item_status(Request $request, OrderController $orderController)
    {
        /*
            id:1
            status : received / processed / shipped / delivered / cancelled / returned
            delivery_boy_id: 15
            otp:value      //{required when status is delivered}
         */

        $rules = [
            'id' => 'required|numeric',
            'otp' => 'nullable|numeric',
            'status' => [
                'required',
                Rule::in(['received', 'processed', 'shipped', 'delivered', 'cancelled', 'returned']),
            ],
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {

            $request['table'] = 'parcels';
            $res = $orderController->update_order_item_status($request);
            return response()->json($res->original);
        }
    }

    public function get_delivery_boy_cash_collection(Request $request, CashCollectionController $cashCollectionController)
    {
        /*
            status:             // {delivery_boy_cash (delivery boy collected) | delivery_boy_cash_collection (admin collected)}
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
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            if (auth()->check()) {
                $user_id = auth()->user()->id;
            }
            $limit = $request->input('limit', 25);
            $offset = $request->input('offset', 0);
            $sort = $request->input('sort', 'transactions.id');
            $order = $request->input('order', 'DESC');
            $search = $request->input('search', '');
            $filters['delivery_boy_id'] = $user_id;
            $filters['status'] = (isset($request['status']) && !empty(trim($request['status']))) ? $request['status'] : '';

            $data = $cashCollectionController->get_delivery_boy_cash_collection($limit, $offset, $sort, $order, $search, $filters);

            if (isset($data['data']) && !empty($data['data'])) {
                foreach ($data['data'] as $row) {



                    $tmpRow['id'] = $row['id'];
                    $tmpRow['name'] = $row['name'];
                    $tmpRow['mobile'] = $row['mobile'];
                    $tmpRow['order_id'] = $row['order_id'];
                    $tmpRow['parcel_id'] = $row['parcel_id'];
                    $tmpRow['cash_received'] = $row['cash_received'];
                    $tmpRow['type'] = $row['type'];
                    $tmpRow['amount'] = $row['amount'];
                    $tmpRow['message'] = $row['message'];
                    $tmpRow['transaction_date'] = $row['transaction_date'];
                    $tmpRow['date'] = $row['date'];

                    if (isset($row['order_id']) && !empty($row['order_id']) && $row['order_id'] != "") {

                        $order_data = app(OrderService::class)->fetchOrders($row['order_id']);


                        $tmpRow['order_details'] = isset($order_data['order_data'][0]) ? $order_data['order_data'][0] : "";
                    } else {
                        $tmpRow['order_details'] = "";
                    }
                    $rows[] = $tmpRow;
                }
                if ($data['error'] == false) {
                    $data['data'] = $rows;
                } else {
                    $data['data'] = array();
                }
            }

            return response()->json($data);
        }
    }

    public function delete_delivery_boy(Request $request)
    {
        /*
            mobile:9874563214
            password:12345695
        */
        $rules = [
            'mobile' => 'required|numeric',
            'email' => 'sometimes|nullable|email',
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        } else {
            if (auth()->check()) {
                $user_id = auth()->user()->id;
            }


            $user_data = fetchDetails(User::class, ['id' => $user_id, 'mobile' => $request['mobile']], ['id', 'username', 'password', 'active', 'mobile']);
            if ($user_data) {
                if (auth()->check()) {
                    $user = Auth::user();

                    if ($user['role_id'] == '3') {
                        deleteDetails(['id' => $user_id], User::class);

                        //delete delivery boy's images
                        $frontLicenceImagePath = str_replace('\\', '/', public_path(config('constants.' . 'MEDIA_PATH') . $user['front_licence_image']));
                        $backLicenceImagePath = str_replace('\\', '/', public_path(config('constants.' . 'MEDIA_PATH') . $user['back_licence_image']));


                        if (File::exists($frontLicenceImagePath)) {
                            unlink($frontLicenceImagePath);
                        }

                        if (File::exists($backLicenceImagePath)) {
                            unlink($backLicenceImagePath);
                        }

                        $response['error'] = false;
                        $response['message'] = 'Delivery Boy Deleted Successfully';
                        $response['language_message_key'] = 'delivery_boy_deleted_successfully';
                    } else {
                        $response['error'] = true;
                        $response['message'] = 'Details Does\'s Match';
                        $response['language_message_key'] = 'details_does_not_match';
                    }
                } else {
                    $response['error'] = true;
                    $response['message'] = 'Details Does\'s Match';
                    $response['language_message_key'] = 'details_does_not_match';
                }
            } else {
                $response['error'] = true;
                $response['message'] = 'User Not Found';
                $response['message'] = 'User Not Found';
            }
        }
        return response()->json($response);
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
        return 'delivery';
    }

    /**
     * Get file metadata for a language file without loading all labels
     */
    private function getLanguageFileMetadata($languageCode, $fileType)
    {
        $filename = match($fileType) {
            'app' => 'app_labels.json',
            'panel', 'admin' => 'panel_labels.json',
            'web' => 'web_labels.json',
            'seller' => 'seller_labels.json',
            'delivery' => 'delivery_labels.json',
            default => 'delivery_labels.json'
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
            $filename = match($file_type) {
                'app' => 'app_labels.json',
                'panel', 'admin' => 'panel_labels.json',
                'web' => 'web_labels.json',
                'seller' => 'seller_labels.json',
                'delivery' => 'delivery_labels.json',
                default => 'delivery_labels.json'
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
        try {
            // Fetch system settings
            $settings = json_decode(app(SettingService::class)->getSettings('system_settings', true), true);
            $contact_us = json_decode(app(SettingService::class)->getSettings('contact_us', true), true);
            $about_us = json_decode(app(SettingService::class)->getSettings('about_us', true), true);
            $terms_and_conditions = json_decode(app(SettingService::class)->getSettings('delivery_boy_terms_and_conditions'), true);
            $privacy_policy = json_decode(app(SettingService::class)->getSettings('delivery_boy_privacy_policy'), true);

            // Default settings to unset
            $fields_to_unset = [
                'enable_cart_button_on_product_list_view',
                'expand_product_image',
                'tax_name',
                'tax_number',
                'google',
                'facebook',
                'apple',
                'refer_and_earn_status',
                'minimum_refer_and_earn_amount',
                'minimum_refer_and_earn_bonus',
                'refer_and_earn_method',
                'max_refer_and_earn_amount',
                'number_of_times_bonus_given_to_customer',
                'wallet_balance_status',
                'wallet_balance_amount',
                'authentication_method',
                'supported_locals',
                'store_currency',
                'decimal_point',
                'single_seller_order_system',
                'customer_app_maintenance_status',
                'seller_app_maintenance_status',
                'message_for_customer_app',
                'message_for_seller_app',
                'sidebar_color',
                'sidebar_type',
                'navbar_fixed',
                'theme_mode',
                'current_version_of_ios_app',
                'current_version_of_android_app',
                'current_version_of_android_app_for_seller',
                'current_version_of_ios_app_for_seller',
                'storage_type',
                'minimum_cart_amount',
                'maximum_item_allowed_in_cart',
                'low_stock_limit',
                'max_days_to_return_item',
                'ai_setting',
            ];

            // Unset unnecessary fields
            foreach ($fields_to_unset as $field) {
                unset($settings[$field]);
            }

            // Format media URLs
            foreach ($settings as $key => $value) {
                if ($value === null) {
                    $settings[$key] = "";
                } elseif (in_array($key, ['logo', 'favicon']) && !empty($value)) {
                    $settings[$key] = app(MediaService::class)->getMediaImageUrl($value);
                }
            }

            // Get currency symbol
            $currencyDetails = fetchDetails(Currency::class, ['is_default' => 1], 'symbol');
            $currency = !$currencyDetails->isEmpty() ? $currencyDetails[0]->symbol : '';

            $general_settings = [
                'delivery_boy_terms_and_conditions' => $terms_and_conditions['delivery_boy_terms_and_conditions'] ?? '',
                'delivery_boy_privacy_policy' => $privacy_policy['delivery_boy_privacy_policy'] ?? '',
                'contact_us' => $contact_us['contact_us'] ?? '',
                'about_us' => $about_us['about_us'] ?? '',
                'currency' => $currency,
                'system_settings' => $settings,
            ];
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

    public function get_wallet_transaction()
    {
        if (auth()->check()) {
            $user_id = auth()->user()->id;
        }
        $offset = request()->input('offset', 0);
        $limit = request()->input('limit', 10);
        $sort = request()->input('sort', 'id');
        $order = request()->input('order', 'DESC');

        $transactionsQuery = Transaction::where('transactions.user_id', $user_id)
            ->whereIn('transactions.type', ['credit', 'debit']);

        if (request()->has('search') && trim(request()->input('search')) !== '') {
            $search = trim(request()->input('search'));
            $transactionsQuery->where(function ($query) use ($search) {
                $query->where('transactions.id', $search)
                    ->orWhere('transactions.amount', $search)
                    ->orWhere('transactions.created_at', $search)
                    ->orWhere('transactions.type', $search)
                    ->orWhere('transactions.status', $search)
                    ->orWhere('transactions.txn_id', $search);
            });
        }

        $totalQuery = clone $transactionsQuery;
        $total = $totalQuery->count();

        $txn_search_res = $transactionsQuery->select('transactions.*')
            ->orderBy($sort, $order)
            ->skip($offset)
            ->take($limit)
            ->get();

        $formattedTransactions = $txn_search_res->map(function ($row) {
            return [
                'id' => $row->id,
                'type' => $row->type,
                'payu_txn_id' => $row->payu_txn_id,
                'amount' => $row->amount,
                'status' => $row->status,
                'message' => $row->message,
                'created_at' => date('Y-m-d', strtotime($row->created_at)),
            ];
        });

        $response['error'] = $total == 0 ? true : false;
        $response['message'] = $total == 0 ? labels('delivery_labels.no_wallet_transaction_found', 'No Wallet Transaction Found') : labels('delivery_labels.data_retrived_successfully', 'Wallet Transaction Retrived Successfully');
        // $response['message'] = $total == 0 ? 'No Wallet Transaction Found' : 'Wallet Transaction Retrived Successfully';
        // labels('delivery_labels.cash_collection_not_exist', 'Cash collection does not exist')
        $response['language_message_key'] = $total == 0 ? 'no_wallet_transaction_found' : 'data_retrived_successfully';
        $response['total'] = $total;
        $response['data'] = $formattedTransactions;

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
            $city_data = $areaController->city_list($request);

            if ($city_data) {
                $response['error'] = false;
                $response['message'] = 'Cities retrieved successfully!';
                $response['language_message_key'] = 'cities_retrived_successfully';
                $response['total'] = $city_data->original['total'];
                $response['data'] = $city_data->original['rows'];
            } else {
                $response['error'] = true;
                $response['message'] = 'Data Does Not Exists  !';
                $response['language_message_key'] = 'data_does_not_exists';
                $response['data'] = array();
            }
            return response()->json($response);
        }
    }
    public function get_zones(Request $request)
    {
        $language_code = $request->attributes->get('language_code');
        // dd($language_code);
        return getZones($request, $language_code);
    }
    public function get_returned_order_items(Request $request)
    {

        /*
           sort:               // { oi.id } optional
           order:DESC/ASC      // { default - DESC } optional
           search:value        // {optional}
           offset: 0 {optional}
           limit: 10 {optional}
       */

        if (auth()->check()) {
            $delivery_boy_id = auth()->user()->id;
        }
        $delivery_boy_id = Auth::id();
        $language_code = $request->attributes->get('language_code');

        $res = app(OrderService::class)->fetchOrderItems($request->input('order_item_id', ''), '', ['return_pickedup', 'return_request_approved', 'returned'], $delivery_boy_id, $request->input('limit', 10), $request->input('offset', 0), $request->input('sort', 'oi.id'), $request->input('order', 'DESC'), '', '', $request->input('search', ''), $request->input('seller_id'), '', '');

        $data = $res;
        if (!empty($data)) {
            return response()->json([
                'error' => false,
                'message' => 'Orders retrieved successfully!',
                'language_message_key' => 'orders_retrived_successfully',
                'total' => $data['total'],
                'data' => $data['order_data'],
            ]);
        } else {
            return response()->json([
                'error' => true,
                'message' => 'Data Does Not Exists!',
                'language_message_key' => 'data_does_not_exists',
                'total' => '',
                'data' => [],
            ]);
        }
    }
    public function update_returned_order_item_status(Request $request)
    {

        /*
           order_item_id: 1
           status:  return_pickedup
       */
        $rules = [
            'order_item_id' => 'required|numeric',
            'status' => 'required',
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }
        $status = $request->status ?? "";
        $order_item_id = $request->order_item_id ?? "";
        if ($status !== 'return_pickedup') {
            return response()->json([
                'error' => true,
                'message' =>
                labels('admin_labels.invalid_status_passed', 'Invalid Status Passed.'),
                'data' => [],
            ]);
        }
        
        // Validate that the authenticated delivery boy is assigned to this order item
        $delivery_boy_id = Auth::id();
        $order_item = fetchDetails(OrderItems::class, ['id' => $order_item_id], ['delivery_boy_id', 'active_status']);
        
        if (empty($order_item)) {
            return response()->json([
                'error' => true,
                'message' => labels('admin_labels.order_item_not_found', 'Order item not found.'),
                'language_message_key' => 'order_item_not_found',
                'data' => [],
            ]);
        }
        
        if ($order_item[0]->delivery_boy_id != $delivery_boy_id) {
            return response()->json([
                'error' => true,
                'message' => labels('admin_labels.not_authorized_to_update_this_order', 'You are not authorized to update this order. This order is assigned to another delivery boy.'),
                'language_message_key' => 'not_authorized_to_update_this_order',
                'data' => [],
            ]);
        }
        
        $current_status = fetchDetails(OrderItems::class, ['id' => $order_item_id], 'status');
        $current_status = isset($current_status) && !empty($current_status) ? $current_status[0]->status : "";
        $current_status = json_decode($current_status, true);

        if (!is_array($current_status)) {
            $current_status = [];
        }
        $last_status = end($current_status);
        if ($last_status[0] == 'returned') {
            return response()->json([
                'error' => true,
                'message' =>
                labels('admin_labels.status_is_already_returned_you_can_not_set_it_as_pickedup', 'Status is already returned you can not set it as pickedup.'),
                'data' => [],
            ]);
        }
        if ($last_status[0] == 'return_pickedup') {
            return response()->json([
                'error' => true,
                'message' =>
                labels('admin_labels.status_already_updated', 'Status already updated.'),
                'data' => [],
            ]);
        }
        $current_time = date("d-m-Y h:i:sa");
        $new_entry = [$status, $current_time];
        $current_status[] = $new_entry;
        $updated_status = json_encode($current_status);

        $update_data = [
            'active_status' => $status,
            'status' => $updated_status
        ];
        $result = app(OrderService::class)->updateOrderItemStatus($order_item_id, $update_data);
        if ($result) {
            $order_item_data = app(OrderService::class)->getReturnOrderItemsList(
                '',
                $request->input('search', ''),
                $request->input('offset', 0),
                $request->input('limit', 10),
                $request->input('sort', 'id'),
                $request->input('order', 'DESC'),
                $request->input('seller_id'),
                $request->input('fromApp', '1'),
                $request->input('order_item_id', $order_item_id),
                $request->input('isPrint', '1')
            );

            return response()->json([
                'error' => false,
                'message' =>
                labels('admin_labels.status_updated_successfully', 'Status Updated Successfully'),
                'data' => !empty($order_item_data) ? $order_item_data[0] : [],
            ]);
        } else {
            return response()->json([
                'error' => true,
                'message' =>
                labels('admin_labels.something_went_wrong', 'Something went wrong'),
                'data' => [],
            ]);
        }
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

    public function toggle_availability(Request $request)
    {
        /*
            is_available: 1 or 0
            1 = Available
            0 = Not Available
        */
        $rules = [
            'is_available' => 'required|in:0,1',
        ];

        if ($response = $this->HandlesValidation($request, $rules, [], null, true)) {
            return $response;
        }

        if (!auth()->check()) {
            return response()->json([
                'error' => true,
                'message' => 'Unauthorized access',
                'language_message_key' => 'unauthorized_access',
                'data' => [],
            ], 401);
        }

        $user = Auth::user();

        if ($user->role_id != 3) {
            return response()->json([
                'error' => true,
                'message' => 'Unauthorized access. Only delivery boys can update availability.',
                'language_message_key' => 'unauthorized_access',
                'data' => [],
            ], 403);
        }

        $is_available = (int)$request->input('is_available');
        $user->is_available = $is_available;
        $user->save();

        return response()->json([
            'error' => false,
            'message' => $is_available == 1 ? 'Availability status updated to Available' : 'Availability status updated to Not Available',
            'language_message_key' => $is_available == 1 ? 'availability_updated_to_available' : 'availability_updated_to_not_available',
            'data' => [
                'is_available' => $user->is_available,
                'status_text' => $is_available == 1 ? 'Available' : 'Not Available',
            ],
        ]);
    }

    public function get_availability_status(Request $request)
    {
        /*
            Get current availability status of the logged-in delivery boy
        */
        if (!auth()->check()) {
            return response()->json([
                'error' => true,
                'message' => 'Unauthorized access',
                'language_message_key' => 'unauthorized_access',
                'data' => [],
            ], 401);
        }

        $user = Auth::user();

        if ($user->role_id != 3) {
            return response()->json([
                'error' => true,
                'message' => 'Unauthorized access. Only delivery boys can check availability.',
                'language_message_key' => 'unauthorized_access',
                'data' => [],
            ], 403);
        }

        $is_available = isset($user->is_available) ? (int)$user->is_available : 1;

        return response()->json([
            'error' => false,
            'message' => 'Availability status retrieved successfully',
            'language_message_key' => 'availability_status_retrieved_successfully',
            'data' => [
                'is_available' => $is_available,
                'status_text' => $is_available == 1 ? 'Available' : 'Not Available',
            ],
        ]);
    }
}
