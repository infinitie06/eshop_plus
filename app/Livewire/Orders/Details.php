<?php

namespace App\Livewire\Orders;

use App\Models\Currency;
use App\Models\Media;
use App\Models\Order;
use App\Models\OrderBankTransfers;
use App\Models\OrderItems;
use App\Models\OrderTracking;
use App\Models\StorageType;
use App\Models\Transaction;
use Exception;
use File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use App\Services\ProductService;
use App\Services\OrderService;
use Illuminate\Auth\AuthenticationException;

class Details extends Component
{
    protected $listeners = ['refreshComponent' => '$refresh'];

    public function mount()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
    }

    public function render(Request $request)
    {
        $user = Auth::user();
        $store_id = session('store_id');
        $order_id = $request->segment(2);

        $user_orders = app(OrderService::class)->fetchOrders(order_id: $order_id, user_id: $user->id, store_id: $store_id);
        // dd($user_orders);
        if (count($user_orders['order_data']) < 1) {
            abort(404);
        }
        $user_orders_transaction_data = json_decode(json_encode($user_orders['order_data']), true);
        foreach ($user_orders_transaction_data as &$user_order) {
            foreach ($user_order['order_items'] as &$user_order_item) {
                $order_item_id = $user_order_item['id'];

                // Assuming you have a Transaction model
                $transaction = Transaction::where('order_item_id', $order_item_id)->first();

                if ($transaction) {
                    // If a transaction is found, add it to the order item data
                    $user_order_item['transaction'] = $transaction->toArray();
                } else {
                    // If no transaction is found, you can set a default value or handle it as needed
                    $user_order_item['transaction'] = null;
                }
            }
        }
        $bank_transfer = '';
        if ($user_orders['order_data'][0]->payment_method == "bank_transfer") {
            $bank_transfer = fetchDetails(OrderBankTransfers::class, ['order_id' => $user_orders['order_data'][0]->id]);
        }
        // dd($bank_transfer);

        $currency_id = $user_orders['order_data'][0]->order_payment_currency_id ?? null;
        $currency_symbol = "";
        if ($currency_id != null) {
            $currency = fetchDetails(Currency::class, ['id' => $currency_id]);
            $currency_symbol = $currency[0]->symbol;
        }
        $tracking_data = OrderTracking::where('order_id', $order_id)->get();
        // dd(($tracking_data));
        // dd($user_orders_transaction_data);
        return view('livewire.' . config('constants.theme') . '.orders.details', [
            'user_orders' => $user_orders,
            'bank_transfer' => $bank_transfer,
            'order_transaction' => $user_orders_transaction_data,
            'currency_symbol' => $currency_symbol,
            'tracking_data' => $tracking_data,
        ])->title("Orders Detail |");
    }

    public function update_order_item_status(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'order_status' => 'required',
            'order_item_id' => 'required',
        ]);
        if ($validator->fails()) {
            $response = [
                'error' => true,
                'message' => $validator->errors()->all(),
                'code' => 102,
            ];
            return response()->json($response);
        }
        $order_item = OrderItems::find($request['order_item_id']);
        if (!$order_item) {
            return response()->json([
                'error' => true,
                'message' => 'Order item not found',
            ]);
        }
        if ($request['order_status'] == 'cancelled') {
            app(OrderService::class)->update_order_item($order_item['id'], $request['order_status'], 1);

            app(ProductService::class)->updateStock($order_item['product_variant_id'], $order_item['quantity'], 'plus');
            app(OrderService::class)->process_refund($order_item['id'], $request['order_status']);
            $response = [
                'error' => false,
                'message' => 'Order Item Status Updated Successfully',
            ];
            return response()->json($response);
        }
        // if ($request['order_status'] == 'returned') {
        //     $res = app(OrderService::class)->validateOrderStatus($request['order_item_id'], $request['order_status'], 'order_items', '', true);

        //     if ($res['error']) {
        //         $response['error'] = (isset($res['return_request_flag'])) ? false : true;
        //         $response['message'] = $res['message'];
        //         $response['data'] = $res['data'];
        //         print_r(json_encode($response));
        //         return false;
        //     }
        //     $request['order_status'] = 'return_request_pending';
        //     if (app(OrderService::class)->updateOrder(['status' => $request['order_status']], ['id' => $order_item['id']], true)) {
        //         app(OrderService::class)->updateOrder(['active_status' => $request['order_status']], ['id' => $order_item['id']], false);
        //         $response = [
        //             'error' => false,
        //             'message' => 'Order Status Updated Successfully',
        //         ];
        //         return response()->json($response);
        //     }
        // }

        if ($request['order_status'] == 'returned') {

            $res = app(OrderService::class)->validateOrderStatus(
                $request['order_item_id'],
                $request['order_status'],
                'order_items',
                OrderItems::class, // ✅ put it here (instead of '')
                true
            );

            if ($res['error']) {
                $response['error'] = (isset($res['return_request_flag'])) ? false : true;
                $response['message'] = $res['message'];
                $response['data'] = $res['data'];

                return response()->json($response); // ✅ replace print_r
            }

            $request['order_status'] = 'return_request_pending';

            if (app(OrderService::class)->updateOrder(
                ['status' => $request['order_status']],
                ['id' => $order_item['id']],
                true,
                'order_items',
                false,
                0,
                \App\Models\OrderItems::class // ✅ ADD THIS HERE
            )) {
                app(OrderService::class)->updateOrder(
                    ['active_status' => $request['order_status']],
                    ['id' => $order_item['id']],
                    false
                );

                return response()->json([
                    'error' => false,
                    'message' => 'Order Status Updated Successfully',
                ]);
            }
        }
    }

    public function send_bank_receipt(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|numeric|exists:orders,id',
        ]);
        if ($validator->fails()) {
            $response = [
                'error' => true,
                'message' => $validator->errors()->all(),
                'code' => 102,
            ];
            return response()->json($response);
        }

        $order_id = ($request->input('order_id') != null) ? $request->input('order_id') : 0;
        $order = fetchDetails(Order::class, ['id' => $order_id], 'id');

        if ($order->isEmpty()) {
            return response()->json([
                'error' => true,
                'message' => 'Order not found!',
                'language_message_key' => 'order_not_found'
            ]);
        }

        if (!File::exists('storage/bank_transfer_proof')) {
            File::makeDirectory('storage/bank_transfer_proof', 0755, true);
        }

        try {
            $media_storage_settings = fetchDetails(StorageType::class, ['is_default' => 1], '*');
            $mediaStorageType = !$media_storage_settings->isEmpty() ? $media_storage_settings[0]->id : 1;
            $disk = !$media_storage_settings->isEmpty() ? $media_storage_settings[0]->name : 'public';

            $media = StorageType::find($mediaStorageType);

            $mediaIds = [];
            $uploaded_images = [];

            if ($request->hasFile('attachments')) {

                $files = $request->file('attachments');

                foreach ($files as $file) {
                    $mediaItem = $media->addMedia($file)
                        ->sanitizingFileName(function ($fileName) use ($media) {
                            // Replace special characters and spaces with hyphens
                            $sanitizedFileName = strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));

                            // Generate a unique identifier based on timestamp and random component
                            $uniqueId = time() . '_' . mt_rand(1000, 9999);

                            $extension = pathinfo($sanitizedFileName, PATHINFO_EXTENSION);
                            $baseName = pathinfo($sanitizedFileName, PATHINFO_FILENAME);

                            return "{$baseName}-{$uniqueId}.{$extension}";
                        })
                        ->toMediaCollection('bank_transfer_proof', $disk);

                    $mediaIds[] = $mediaItem->id;

                    if ($disk == 'public') {
                        $uploaded_images[] = [
                            'image_path' => 'bank_transfer_proof/' . $mediaItem->file_name,
                        ];
                    }
                }
            }
            if ($disk == 's3') {
                $media_list = $media->getMedia('bank_transfer_proof');
                for ($i = 0; $i < count($mediaIds); $i++) {
                    $media_url = $media_list[($media_list->count()) - (count($mediaIds) - $i)]->getUrl();

                    $uploaded_images[] = [
                        'image_path' => $media_url,
                    ];

                    Media::destroy($mediaIds[$i]);
                }
            }
        } catch (Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ]);
        }

        $data = array(
            'order_id' => $order_id,
            'attachments' => $uploaded_images,
            'disk' => $disk,
        );

        if (app(OrderService::class)->addBankTransferProof($data)) {

            return response()->json([
                'error' => false,
                'message' => 'Bank Transfer Proof Added Successfully!',
            ]);
        } else {
            return response()->json([
                'error' => true,
                'message' => 'Something went wrong',
            ]);
        }
    }
}
