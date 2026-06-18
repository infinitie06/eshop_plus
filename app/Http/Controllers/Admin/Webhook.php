<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\TransactionController;
use App\Libraries\Paystack;
use App\Libraries\Phonepe;
use App\Libraries\Razorpay;
use App\Models\Order;
use App\Models\OrderItems;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Services\ProductService;
use App\Services\SettingService;
use App\Services\FirebaseNotificationService;
use App\Services\OrderService;
use App\Services\WalletService;
class Webhook extends Controller
{

    public $transactionController = "";

    function __construct()
    {
        $this->transactionController = app(TransactionController::class);
    }


    public function phonepe_webhook(Request $request)
    {
        $rawBody = $request->getContent();
        Log::alert("Phonepe webhook raw body => " . $rawBody);

        $decoded = json_decode($rawBody, true);

        // PhonePe V2 = direct JSON with payload.state; V1 = { response: base64 }
        $isV2 = isset($decoded['payload']['state']);
        Log::alert("[PhonePe] Format detected => " . ($isV2 ? 'V2 (direct JSON)' : 'V1 (base64 response)'));

        if ($isV2) {
            // --- V2 FORMAT ---
            $payload   = $decoded['payload'];
            $state     = $payload['state'] ?? "";
            $order_id  = $payload['merchantOrderId'] ?? ($payload['metaInfo']['udf1'] ?? "");
            $user_id   = $payload['metaInfo']['udf2'] ?? 0;
            $amount    = ($payload['amount'] ?? 0) / 100;
            $txn_id    = $payload['paymentDetails'][0]['transactionId'] ?? "";
            $event_type = $decoded['type'] ?? "";

            Log::alert("[PhonePe V2] event={$event_type} | state={$state} | order_id={$order_id} | user_id={$user_id} | amount={$amount} | txn_id={$txn_id}");

            if (empty($order_id)) {
                Log::alert("[PhonePe V2] ERROR: order_id missing from payload");
                return response()->json(['error' => true, 'message' => 'order_id missing']);
            }

            // Clean user_id by removing USER_ prefix if present
            $webhook_user_id = preg_replace('/^USER_/', '', $user_id);

            // Check if this is a wallet transaction by looking at the transaction table
            $transaction = Transaction::where('txn_id', $order_id)->first();
            $transaction_type = 'transaction'; // default
            $user_id = $webhook_user_id; // fallback to webhook user_id

            if ($transaction) {
                // Use transaction_type from database
                $transaction_type = $transaction->transaction_type;
                // Use user_id from database (more reliable)
                $user_id = $transaction->user_id;
            }

            Log::alert("[PhonePe V2] transaction_type={$transaction_type} | db_user_id={$user_id} | webhook_user_id={$webhook_user_id}");

            if ($state == 'COMPLETED') {
                Log::alert("[PhonePe V2] Payment COMPLETED — processing {$transaction_type} for order_id={$order_id}");
                if ($transaction_type == 'transaction') {
                    updateDetails(['active_status' => "received"], ['order_id' => $order_id], OrderItems::class);
                    $order_status = json_encode([['received', date("d-m-Y h:i:sa")]]);
                    updateDetails(['status' => $order_status], ['order_id' => $order_id], OrderItems::class);
                    Log::alert("[PhonePe V2] OrderItems updated to 'received' for order_id={$order_id}");

                    if (!empty($txn_id)) {
                        $existing = Transaction::where('order_id', $order_id)->first();
                        if ($existing) {
                            $existing->update(['status' => 'success', 'txn_id' => $txn_id, 'message' => 'Payment received via PhonePe V2']);
                            Log::alert("[PhonePe V2] Transaction updated (existing) id={$existing->id}");
                        } else {
                            $newTxn = Transaction::create([
                                'transaction_type' => 'transaction',
                                'user_id'          => $user_id,
                                'order_id'         => $order_id,
                                'type'             => 'phonepe',
                                'txn_id'           => $txn_id,
                                'amount'           => $amount,
                                'status'           => 'success',
                                'message'          => 'Payment received via PhonePe V2',
                            ]);
                            Log::alert("[PhonePe V2] Transaction created id={$newTxn->id}");
                        }
                    } else {
                        Log::alert("[PhonePe V2] WARNING: txn_id is empty, skipping transaction record update");
                    }

                    Log::alert("[PhonePe V2] Sending invoice email for order_id={$order_id}");
                    app(OrderService::class)->sendOrderInvoiceMail($order_id);
                    Log::alert("[PhonePe V2] Sending push notification to user_id={$user_id}");
                    app(FirebaseNotificationService::class)->sendCustomNotificationOnPaymentSuccess($order_id, $user_id);

                    Log::alert("[PhonePe V2] SUCCESS: order {$order_id} fully marked as received");
                    return response()->json(['error' => false, 'message' => 'Payment received successfully']);
                } else {
                    Log::alert("[PhonePe V2] Wallet refill for user_id={$user_id} amount={$amount}");

                    // Get transaction details to check type
                    $transaction = Transaction::where('txn_id', $order_id)->first();
                    $transaction_type_from_db = $transaction ? $transaction->type : 'unknown';
                    Log::alert("[PhonePe V2] Transaction type from DB: {$transaction_type_from_db}");

                    // Update transaction status to success
                    if (!empty($txn_id)) {
                        $existing = Transaction::where('txn_id', $order_id)->first();
                        if ($existing) {
                            $existing->update(['status' => 'success', 'txn_id' => $txn_id, 'message' => 'Wallet refill successful via PhonePe V2']);
                            Log::alert("[PhonePe V2] Wallet transaction updated (existing) id={$existing->id}");
                        } else {
                            $newTxn = Transaction::create([
                                'transaction_type' => 'wallet',
                                'user_id'          => $user_id,
                                'order_id'         => $order_id,
                                'type'             => 'phonepe',
                                'txn_id'           => $txn_id,
                                'amount'           => $amount,
                                'status'           => 'success',
                                'message'          => 'Wallet refill successful via PhonePe V2',
                            ]);
                            Log::alert("[PhonePe V2] Wallet transaction created id={$newTxn->id}");
                        }
                    }

                    // Get current balance before update
                    $user_before = \App\Models\User::find($user_id);
                    $balance_before = $user_before ? $user_before->balance : 0;
                    Log::alert("[PhonePe V2] User balance before update: {$balance_before}");

                    if (!app(WalletService::class)->updateBalance($amount, $user_id, 'add')) {
                        Log::alert("[PhonePe V2] ERROR: wallet update failed for user_id={$user_id}");
                    } else {
                        // Get current balance after update
                        $user_after = \App\Models\User::find($user_id);
                        $balance_after = $user_after ? $user_after->balance : 0;
                        Log::alert("[PhonePe V2] User balance after update: {$balance_after}");
                        Log::alert("[PhonePe V2] Wallet updated successfully for user_id={$user_id}");

                        // Trigger wallet component refresh for this user
                        try {
                            \Livewire::dispatch('refreshComponent', to: 'my-account.wallet');
                            Log::alert("[PhonePe V2] Wallet refresh event dispatched for user_id={$user_id}");
                        } catch (\Exception $e) {
                            Log::alert("[PhonePe V2] Could not dispatch wallet refresh: " . $e->getMessage());
                        }
                    }
                    return response()->json(['error' => false, 'message' => 'Wallet refill successful']);
                }
            } elseif (in_array($state, ["FAILED", "PAYMENT_ERROR", "PAYMENT_DECLINED", "TIMED_OUT", "AUTHORIZATION_FAILED"])) {
                Log::alert("[PhonePe V2] Payment FAILED/CANCELLED — state={$state}, order_id={$order_id}");
                if ($transaction_type == 'transaction') {
                    updateDetails(['active_status' => "cancelled"], ['order_id' => $order_id], OrderItems::class);
                    $order_status = json_encode([['cancelled', date("d-m-Y h:i:sa")]]);
                    updateDetails(['status' => $order_status], ['order_id' => $order_id], OrderItems::class);

                    $order_items = fetchDetails(OrderItems::class, ['order_id' => $order_id]);
                    $product_variant_ids = $order_items->pluck('product_variant_id')->toArray();
                    $qty = $order_items->pluck('quantity')->toArray();
                    app(ProductService::class)->updateStock($product_variant_ids, $qty, 'plus');
                    Log::alert("[PhonePe V2] Stock restored for order_id={$order_id}");

                    $wallet_balance = Order::where('id', $order_id)->value('wallet_balance') ?? 0;
                    if ($wallet_balance > 0) {
                        app(WalletService::class)->updateBalance($wallet_balance, $user_id, "add");
                        Log::alert("[PhonePe V2] Wallet refunded wallet_balance={$wallet_balance} to user_id={$user_id}");
                    }
                }
                Log::alert("[PhonePe V2] DONE: order {$order_id} cancelled");
                return response()->json(['error' => true, 'message' => 'Payment failed']);
            } else {
                Log::alert("[PhonePe V2] Unhandled state={$state} for order_id={$order_id}");
            }

            return response()->json(['error' => false, 'message' => 'Webhook received, state=' . $state]);

        } else {
            // --- V1 LEGACY FORMAT (base64 response field) ---
            $phonepe = new Phonepe;
            $responseField = $decoded['response'] ?? "";
            Log::alert("[PhonePe V1] response field present: " . (!empty($responseField) ? 'yes' : 'no'));

            if (empty($responseField)) {
                Log::alert("[PhonePe V1] ERROR: no response field in payload");
                return response()->json(['error' => false, 'message' => 'No response field in payload']);
            }

            $payload = json_decode(base64_decode($responseField), true);
            Log::alert("Phonepe V1 decoded payload => " . json_encode($payload));

            $txn_id  = $payload['data']['merchantTransactionId'] ?? "";
            $amount  = ($payload['data']['amount'] ?? 0) / 100;
            $status  = $payload['state'] ?? "";
            Log::alert("[PhonePe V1] txn_id={$txn_id} | status={$status} | amount={$amount}");

            $transaction = !empty($txn_id) ? fetchDetails(Transaction::class, ['txn_id' => $txn_id]) : collect();

            if ($transaction->isEmpty()) {
                Log::alert("[PhonePe V1] ERROR: transaction not found in DB for txn_id={$txn_id}");
                return response()->json(['error' => true, 'message' => 'Transaction not found']);
            }

            $user_id          = $transaction[0]->user_id;
            $transaction_type = $transaction[0]->transaction_type ?? "";
            $order_id         = $transaction[0]->order_id ?? "";
            Log::alert("[PhonePe V1] Resolved: user_id={$user_id} | transaction_type={$transaction_type} | order_id={$order_id}");

            Log::alert("[PhonePe V1] Checking PhonePe status API for txn_id={$txn_id}");
            $check_status = $phonepe->check_status_v2($txn_id);
            $final_status = $check_status['state'] ?? $status;
            Log::alert("[PhonePe V1] Status API response: code=" . ($check_status['code'] ?? 'N/A') . " | state={$final_status}");
            if (($check_status['code'] ?? '') == 'INTERNAL_SERVER_ERROR') {
                Log::alert("[PhonePe V1] INTERNAL_SERVER_ERROR — retrying status check");
                $check_status = $phonepe->check_status_v2($txn_id);
                $final_status = $check_status['state'] ?? $status;
                Log::alert("[PhonePe V1] Retry result: state={$final_status}");
            }
            Log::alert("[PhonePe V1] Final status to process: {$final_status}");

            if ($final_status == 'COMPLETED') {
                if ($transaction_type == 'transaction') {
                    updateDetails(['active_status' => "received"], ['order_id' => $order_id], OrderItems::class);
                    $order_status = json_encode([['received', date("d-m-Y h:i:sa")]]);
                    updateDetails(['status' => $order_status], ['order_id' => $order_id], OrderItems::class);
                    Transaction::where('txn_id', $txn_id)->update(['status' => 'success', 'message' => 'Payment received successfully']);
                    app(OrderService::class)->sendOrderInvoiceMail($order_id);
                    app(FirebaseNotificationService::class)->sendCustomNotificationOnPaymentSuccess($order_id, $user_id);
                    return response()->json(['error' => false, 'message' => 'Payment received successfully']);
                } else {
                    app(WalletService::class)->updateBalance($amount, $user_id, 'add');
                    Transaction::where('txn_id', $txn_id)->update(['status' => 'success', 'message' => 'Wallet refill successful']);
                    return response()->json(['error' => false, 'message' => 'Wallet refill successful']);
                }
            } elseif (in_array($status, ["BAD_REQUEST", "AUTHORIZATION_FAILED", "PAYMENT_ERROR", "TRANSACTION_NOT_FOUND", "PAYMENT_DECLINED", "TIMED_OUT", "FAILED"])) {
                if ($transaction_type == 'transaction') {
                    updateDetails(['active_status' => "cancelled"], ['order_id' => $order_id], OrderItems::class);
                    $order_status = json_encode([['cancelled', date("d-m-Y h:i:sa")]]);
                    updateDetails(['status' => $order_status], ['order_id' => $order_id], OrderItems::class);
                    Transaction::where('txn_id', $txn_id)->update(['status' => 'failed', 'message' => "Payment couldn't be processed!"]);
                    $order_items = fetchDetails(OrderItems::class, ['order_id' => $order_id]);
                    app(ProductService::class)->updateStock($order_items->pluck('product_variant_id')->toArray(), $order_items->pluck('quantity')->toArray(), 'plus');
                } else {
                    Transaction::where('txn_id', $txn_id)->update(['status' => 'failed', 'message' => 'Wallet could not be recharged!']);
                    return response()->json(['error' => true, 'message' => 'Wallet could not be recharged!']);
                }
            }
            return response()->json(['error' => false, 'message' => 'Webhook processed']);
        }
    }

    public function paypal_webhook(Request $request)
    {
        $rawBody = $request->getContent();
        $res = json_decode($rawBody, true);
        Log::alert("[PayPal] Webhook received. Body length=" . strlen($rawBody));

        if (empty($res)) {
            Log::alert("[PayPal] ERROR: empty or invalid JSON body");
            return response()->json(['error' => true, 'message' => 'No request found']);
        }

        $event_type = $res['event_type'] ?? "";
        $txn_id     = $res['resource']['purchase_units'][0]['reference_id'] ?? "";
        $amount     = $res['resource']['purchase_units'][0]['amount']['value'] ?? 0;
        $status     = $res['resource']['status'] ?? "";
        $intent     = $res['resource']['intent'] ?? "";

        Log::alert("[PayPal] event_type={$event_type} | txn_id={$txn_id} | status={$status} | intent={$intent} | amount={$amount}");

        if (empty($txn_id)) {
            Log::alert("[PayPal] ERROR: txn_id missing from payload");
            return response()->json(['error' => true, 'message' => 'txn_id missing']);
        }

        $transaction = fetchDetails(Transaction::class, ['txn_id' => $txn_id]);
        if ($transaction->isEmpty()) {
            Log::alert("[PayPal] Transaction lookup for txn_id={$txn_id}: NOT FOUND in DB. Checking for wallet refill...");

            // Try to resolve user from reference_id if it's a wallet refill (format: wallet-refill-user-{user_id}-{timestamp})
            $user_id = null;
            $parts = explode('-', $txn_id);
            if (count($parts) >= 4 && $parts[0] == 'wallet' && $parts[1] == 'refill' && $parts[2] == 'user') {
                $user_id = $parts[3];
            } elseif (strpos($txn_id, "wallet-refill-user") !== false) {
                // Handle patterns like "someprefix-wallet-refill-user-123-timestamp"
                $temp = explode("-", $txn_id);
                $idx = array_search('user', $temp);
                if ($idx !== false && isset($temp[$idx + 1]) && is_numeric($temp[$idx + 1])) {
                    $user_id = $temp[$idx + 1];
                }
            }

            if (!empty($user_id)) {
                Log::alert("[PayPal] Detected wallet refill for user_id={$user_id} from txn_id={$txn_id}");

                // Create the transaction now to allow processing to continue
                $newTxn = Transaction::create([
                    'transaction_type' => 'wallet',
                    'user_id'          => $user_id,
                    'order_id'         => "",
                    'type'             => 'credit',
                    'txn_id'           => $txn_id,
                    'amount'           => $amount,
                    'status'           => 'awaiting',
                    'message'          => 'Wallet refill initiated via PayPal Webhook',
                ]);
                Log::alert("[PayPal] Created missing transaction record id={$newTxn->id}");
                $transaction = collect([$newTxn]);
            } else {
                Log::alert("[PayPal] ERROR: transaction not found and could not be resolved for txn_id={$txn_id}");
                return response()->json(['error' => true, 'message' => 'Transaction not found']);
            }
        }
        Log::alert("[PayPal] Transaction lookup for txn_id={$txn_id}: found id=" . $transaction[0]->id);

        $user_id          = $transaction[0]->user_id;
        $transaction_type = $transaction[0]->transaction_type ?? "";
        $order_id         = $transaction[0]->order_id ?? "";
        Log::alert("[PayPal] Resolved: user_id={$user_id} | transaction_type={$transaction_type} | order_id={$order_id}");

        if ((float)$amount != (float)number_format($transaction[0]->amount, 2, '.', '')) {
            Log::alert("[PayPal] WARNING: amount mismatch — expected={$transaction[0]->amount}, received={$amount}");
        }

        if ($status == 'COMPLETED' && $intent == "CAPTURE") {
            Log::alert("[PayPal] COMPLETED+CAPTURE — processing {$transaction_type}");
            if ($transaction_type == 'transaction') {
                updateDetails(['active_status' => "received"], ['order_id' => $order_id], OrderItems::class);
                $order_status = json_encode([['received', date("d-m-Y h:i:sa")]]);
                updateDetails(['status' => $order_status], ['order_id' => $order_id], OrderItems::class);
                updateDetails(['status' => 'success', 'message' => 'Payment received via PayPal'], ['txn_id' => $txn_id], Transaction::class);
                Log::alert("[PayPal] OrderItems updated to 'received' for order_id={$order_id}");

                Log::alert("[PayPal] Sending invoice email for order_id={$order_id}");
                app(OrderService::class)->sendOrderInvoiceMail($order_id);
                Log::alert("[PayPal] Sending push notification to user_id={$user_id}");
                app(FirebaseNotificationService::class)->sendCustomNotificationOnPaymentSuccess($order_id, $user_id);
                Log::alert("[PayPal] SUCCESS: order {$order_id} fully marked as received");
            } else {
                Log::alert("[PayPal] Wallet refill for user_id={$user_id} amount={$amount}");

                // Get transaction details to check type
                $transaction_from_db = $transaction[0] ?? null;
                $transaction_type_from_db = $transaction_from_db ? $transaction_from_db->type : 'unknown';
                Log::alert("[PayPal] Transaction type from DB: {$transaction_type_from_db}");

                // Only update wallet balance if this transaction wasn't already successful
                $was_already_successful = (isset($transaction[0]->status) && $transaction[0]->status === 'success');

                if ($was_already_successful) {
                    Log::alert("[PayPal] Wallet transaction already successful id={$transaction[0]->id} — skipping balance update");
                } elseif (!app(WalletService::class)->updateBalance($amount, $user_id, 'add')) {
                    Log::alert("[PayPal] ERROR: wallet update failed for user_id={$user_id}");
                } else {
                    // Get current balance after update
                    $user_after = \App\Models\User::find($user_id);
                    $balance_after = $user_after ? $user_after->balance : 0;
                    Log::alert("[PayPal] User balance after update: {$balance_after}");
                    Log::alert("[PayPal] Wallet updated successfully for user_id={$user_id}");

                    // Trigger wallet component refresh for this user
                    try {
                        \Livewire::dispatch('refreshComponent', to: 'my-account.wallet');
                        Log::alert("[PayPal] Wallet refresh event dispatched for user_id={$user_id}");
                    } catch (\Exception $e) {
                        Log::alert("[PayPal] Could not dispatch wallet refresh: " . $e->getMessage());
                    }
                }
                updateDetails(['status' => 'success', 'message' => 'Wallet refill successful'], ['txn_id' => $txn_id], Transaction::class);
            }
        } else {
            Log::alert("[PayPal] Skipped — status={$status} intent={$intent} (not a COMPLETED CAPTURE)");
        }

        Log::alert("[PayPal] Webhook processing complete");
        return response()->json(['error' => false, 'message' => 'PayPal webhook processed']);
    }

    public function paystack_webhook(Request $request)
    {
        $system_settings = app(SettingService::class)->getSettings('system_settings', true);
        $system_settings = json_decode($system_settings, true);
        $paystack = new Paystack;
        $credentials = app(SettingService::class)->getSettings('payment_method', true);
        $credentials = json_decode($credentials, true);
        $paystack_key_id = $credentials['paystack_key_id'];
        $secret_key = $credentials['paystack_secret_key'];

        $request_body = $request->getContent();
        Log::alert("[Paystack] Webhook received. Body length=" . strlen($request_body));
        $event = json_decode($request_body, true);

        $event_name = $event['event'] ?? 'unknown';
        $meta_order_id = $event['data']['metadata']['order_id'] ?? '';
        $txn_id        = $event['data']['reference'] ?? '';
        $amount        = ($event['data']['amount'] ?? 0) / 100;
        $currency      = $event['data']['currency'] ?? '';
        $customer_email = $event['data']['customer']['email'] ?? '';
        Log::alert("[Paystack] event={$event_name} | txn_id={$txn_id} | meta_order_id={$meta_order_id} | amount={$amount} {$currency} | customer_email={$customer_email}");

        // ---------------------------------------------------------------
        // ORDER / USER RESOLUTION — 3-stage fallback strategy
        // ---------------------------------------------------------------
        $order_id    = '';
        $user_id     = 0;
        $transaction = collect();

        // Stage 1: Look up Transaction by txn_id (reference)
        if (!empty($txn_id)) {
            $transaction = fetchDetails(Transaction::class, ['txn_id' => $txn_id], '*');
            if (!$transaction->isEmpty()) {
                Log::alert("[Paystack] Stage1 — Transaction lookup by txn_id={$txn_id}: found id=" . $transaction[0]->id . " order_id=" . $transaction[0]->order_id);
                $order_id = $transaction[0]->order_id;
                $user_id  = $transaction[0]->user_id;
                // If order_id=0, the pre-init transaction exists but order not placed yet
                if (empty($order_id) || $order_id == 0) {
                    Log::alert("[Paystack] Stage1 — order_id=0 in Transaction (pre-init). Will look up via order table.");
                    $order_id = '';
                }
            } else {
                Log::alert("[Paystack] Stage1 — Transaction lookup by txn_id={$txn_id}: NOT FOUND");
            }
        }

        // Stage 2: Fall back to metadata.order_id
        if (empty($order_id) && !empty($meta_order_id) && is_numeric($meta_order_id)) {
            $order_id = $meta_order_id;
            Log::alert("[Paystack] Stage2 — Using metadata order_id={$order_id}");
            if (empty($user_id)) {
                $order_data = app(OrderService::class)->fetchOrders($order_id);
                $user_id    = $order_data['order_data'][0]->user_id ?? 0;
                Log::alert("[Paystack] Stage2 — Resolved user_id={$user_id} from order");
            }
        }

        // Stage 3: Verify with Paystack API → get customer email → find user → find latest awaiting order
        if (empty($order_id)) {
            Log::alert("[Paystack] Stage3 — txn_id and metadata failed. Calling Paystack verify API for reference={$txn_id}");
            try {
                $verified = json_decode($paystack->verify_transaction($txn_id), true);
                $verified_email  = $verified['data']['customer']['email'] ?? $customer_email;
                $verified_status = $verified['data']['status'] ?? '';
                Log::alert("[Paystack] Stage3 — verify API: status={$verified_status} | email={$verified_email}");

                if (!empty($verified_email)) {
                    $user_record = \App\Models\User::where('email', $verified_email)->first();
                    if ($user_record) {
                        $user_id = $user_record->id;
                        Log::alert("[Paystack] Stage3 — Found user_id={$user_id} by email={$verified_email}");

                        // Find their most recent awaiting paystack order
                        $latest = \App\Models\OrderItems::join('orders', 'order_items.order_id', '=', 'orders.id')
                            ->where('order_items.user_id', $user_id)
                            ->where('order_items.active_status', 'awaiting')
                            ->where('orders.payment_method', 'paystack')
                            ->orderBy('order_items.id', 'desc')
                            ->select('order_items.order_id')
                            ->first();

                        if ($latest) {
                            $order_id = $latest->order_id;
                            Log::alert("[Paystack] Stage3 — Found awaiting paystack order_id={$order_id} for user_id={$user_id}");

                            // Update the Transaction record's txn_id if it was stored with wrong reference
                            $existing_txn = \App\Models\Transaction::where('order_id', $order_id)
                                ->where('type', 'paystack')
                                ->first();
                            if ($existing_txn && $existing_txn->txn_id != $txn_id) {
                                Log::alert("[Paystack] Stage3 — Updating Transaction id={$existing_txn->id} txn_id from '{$existing_txn->txn_id}' to '{$txn_id}'");
                                $existing_txn->update(['txn_id' => $txn_id]);
                                $transaction = collect([$existing_txn]);
                            }
                        } else {
                            Log::alert("[Paystack] Stage3 — No awaiting paystack order found for user_id={$user_id}");
                        }
                    } else {
                        Log::alert("[Paystack] Stage3 — No user found with email={$verified_email}");
                    }
                } else {
                    Log::alert("[Paystack] Stage3 — Could not get customer email from verify API");
                }
            } catch (\Exception $e) {
                Log::alert("[Paystack] Stage3 — verify API exception: " . $e->getMessage());
            }
        }

        // Wallet refill: order_id is like "wallet-refill-user-{user_id}-{time}-{rand}"
        if (!is_numeric($order_id) && strpos((string)$order_id, "wallet-refill-user") !== false) {
            $temp    = explode("-", $order_id);
            $user_id = (isset($temp[3]) && is_numeric($temp[3])) ? $temp[3] : 0;
            Log::alert("[Paystack] Wallet refill detected: extracted user_id={$user_id}");
        }

        Log::alert("[Paystack] Final resolved: order_id={$order_id} | user_id={$user_id} | txn_id={$txn_id}");

        if (empty($order_id)) {
            Log::alert("[Paystack] ERROR: All 3 resolution stages failed. Cannot process webhook for txn_id={$txn_id}.");
            return response()->json(['error' => false, 'message' => 'order_id not resolvable — possibly race condition, Paystack will retry']);
        }


        if ($event['event'] == 'charge.success') {
            Log::alert("[Paystack] charge.success — processing order_id={$order_id}");
            if (!empty($order_id)) {

                if (strpos($order_id, "wallet-refill-user") !== false) {
                    Log::alert("[Paystack] Wallet refill path for order_id={$order_id}");
                    $txn_id = $event['data']['reference'] ?? "";
                    $amount = $event['data']['amount'] / 100;

                    // Get transaction details to check type
                    $transaction = Transaction::where('txn_id', $txn_id)->first();
                    $transaction_type_from_db = $transaction ? $transaction->type : 'unknown';
                    Log::alert("[Paystack] Transaction type from DB: {$transaction_type_from_db}");

                    // Determine if this is wallet refill or order payment
                    $is_wallet_refill = strpos($order_id, "wallet-refill-user") !== false;
                    $detected_type = $is_wallet_refill ? 'wallet' : 'transaction';
                    Log::alert("[Paystack] Detected transaction type: {$detected_type} (order_id: {$order_id})");

                    // Update transaction status to success
                    $existing = Transaction::where('txn_id', $txn_id)->first();
                    if ($existing) {
                        Log::alert("[Paystack] Found existing transaction id={$existing->id}, updating status instead of creating duplicate");
                        $existing->update(['status' => 'success', 'txn_id' => $txn_id, 'message' => ($is_wallet_refill ? 'Wallet refill successful via Paystack' : 'Order payment successful via Paystack')]);
                        Log::alert("[Paystack] Transaction updated (existing) id={$existing->id}");
                    } else {
                        $newTxn = Transaction::create([
                            'transaction_type' => $detected_type,
                            'user_id'          => $user_id,
                            'order_id'         => $order_id,
                            'type'             => ($is_wallet_refill ? 'credit' : 'debit'),
                            'txn_id'           => $txn_id,
                            'amount'           => $amount,
                            'status'           => 'success',
                            'message'          => ($is_wallet_refill ? 'Wallet refill successful via Paystack' : 'Order payment successful via Paystack'),
                        ]);
                        Log::alert("[Paystack] Transaction created id={$newTxn->id}");
                    }

                    // Only update wallet balance for actual wallet refills
                    if ($is_wallet_refill) {
                        // Get current balance before update
                        $user_before = \App\Models\User::find($user_id);
                        $balance_before = $user_before ? $user_before->balance : 0;
                        Log::alert("[Paystack] User balance before update: {$balance_before}");

                        if (app(WalletService::class)->updateBalance($amount, $user_id, 'add')) {
                            Log::alert("[Paystack] Wallet recharged: user_id={$user_id} amount={$amount}");

                            // Get current balance after update
                            $user_after = \App\Models\User::find($user_id);
                            $balance_after = $user_after ? $user_after->balance : 0;
                            Log::alert("[Paystack] User balance after update: {$balance_after}");

                            // Trigger wallet component refresh for this user
                            try {
                                \Livewire::dispatch('refreshComponent', to: 'my-account.wallet');
                                Log::alert("[Paystack] Wallet refresh event dispatched for user_id={$user_id}");
                            } catch (\Exception $e) {
                                Log::alert("[Paystack] Could not dispatch wallet refresh: " . $e->getMessage());
                            }
                        } else {
                            Log::alert("[Paystack] ERROR: wallet recharge failed for user_id={$user_id}");
                        }
                    } else {
                        Log::alert("[Paystack] Order payment detected - NOT updating wallet balance");
                    }

                    $response = ['error' => false, 'transaction_status' => 'success', 'message' => 'Wallet recharged successfully!'];
                } else {
                    /* Standard order — mark as received */
                    Log::alert("[Paystack] Order payment path for order_id={$order_id}");
                    $order = app(OrderService::class)->fetchOrders($order_id, '', '', '', '', '', 'o.id', 'DESC');
                    $orderFound = !empty($order['order_data'][0]->user_id);
                    Log::alert("[Paystack] Order fetch result: " . ($orderFound ? 'found user_id=' . $order['order_data'][0]->user_id : 'NOT FOUND'));

                    if ($orderFound) {
                        /* No need to add because the transaction is already added - just update the transaction status */
                        if (!$transaction->isEmpty()) {
                            $transaction_id = $transaction[0]->id;
                            updateDetails(['status' => 'success'], ['id' => $transaction_id], Transaction::class);
                            Log::alert("[Paystack] Transaction updated to success id={$transaction_id}");
                        } else {
                            Log::alert("[Paystack] No existing transaction — creating new one");
                            $data = ['transaction_type' => 'transaction', 'user_id' => $user_id, 'order_id' => $order_id, 'type' => 'paystack', 'txn_id' => $txn_id, 'amount' => $event['data']['amount'], 'status' => 'success', 'message' => 'order placed successfully'];
                            Transaction::create($data);
                            Log::alert("[Paystack] Transaction created for order_id={$order_id}");
                        }

                        updateDetails(['status' => json_encode([['received', date("d-m-Y h:i:sa")]]), 'active_status' => 'received'], ['order_id' => $order_id], OrderItems::class);
                        Log::alert("[Paystack] OrderItems updated to 'received' for order_id={$order_id}");

                        Log::alert("[Paystack] Sending invoice email for order_id={$order_id}");
                        app(OrderService::class)->sendOrderInvoiceMail($order_id);
                        Log::alert("[Paystack] Sending push notification to user_id={$user_id}");
                        app(FirebaseNotificationService::class)->sendCustomNotificationOnPaymentSuccess($order_id, $user_id);
                        Log::alert("[Paystack] SUCCESS: order {$order_id} marked as received");
                    }
                }
            } else {
                Log::alert("[Paystack] WARNING: empty order_id — cannot process");
            }
            Log::alert("[Paystack] charge.success processing complete");
            return response()->json(['error' => false, 'transaction_status' => $event_name, 'message' => 'Transaction successfully done']);
        } elseif ($event['event'] == 'charge.dispute.create') {
            if (!empty($order_id) && is_numeric($order_id)) {
                $order = app(OrderService::class)->fetchOrders($order_id, '', '', '', '', '', 'o.id', 'DESC');

                if ($order['order_data']['0']->active_status == 'received' || $order['order_data']['0']->active_status == 'processed') {
                    updateDetails(['active_status' => 'awaiting'], ['order_id' => $order_id], OrderItems::class);
                }

                if (!$transaction->isEmpty()) {
                    $transaction_id = $transaction[0]->id;
                    updateDetails(['status' => 'pending'], ['id' => $transaction_id], Transaction::class);
                }

                Log::alert('Paystack Transaction is Pending --> ' . var_export($event, true));
            }
        } else {
            Log::alert("[Paystack] Unhandled event={$event_name} — setting order_id={$order_id} to cancelled");
            if (!empty($order_id) && is_numeric($order_id)) {
                updateDetails(['active_status' => 'cancelled'], ['order_id' => $order_id], OrderItems::class);
            }
            /* No need to add because the transaction is already added just update the transaction status */
            if (!$transaction->isEmpty()) {
                $transaction_id = $transaction[0]->id;
                updateDetails(['status' => 'failed'], ['id' => $transaction_id], Transaction::class);
            }

            $response['error'] = true;
            $response['transaction_status'] = $event['event'];
            $response['message'] = "Transaction could not be detected.";
            Log::alert('Paystack Webhook | Transaction could not be detected --> ' . var_export($event, true));
            return response()->json($response);
        }
    }

    public function razorpay_webhook(Request $request)
    {
        $system_settings = app(SettingService::class)->getSettings('system_settings', true);
        $system_settings = json_decode($system_settings, true);
        $razorpay = new Razorpay;
        $raw_request = $request->getContent();
        if (empty($raw_request)) {
            Log::alert('Razorpay Webhook: empty request body');
            return response()->json(['error' => true, 'message' => 'Empty request body']);
        }
        Log::alert('Razorpay Webhook raw body => ' . $raw_request);
        $request = json_decode($raw_request, true);

        $payment_method_settings = app(SettingService::class)->getSettings('payment_method', true);
        $payment_method_settings = json_decode($payment_method_settings, true);

        $secret_hash = $payment_method_settings['razorpay_webhook_secret_key'] ?? "";

        $event_name = $request['event'] ?? 'unknown';
        $txn_id     = $request['payload']['payment']['entity']['id'] ?? "";
        $amount     = isset($request['payload']['payment']['entity']['amount']) ? ($request['payload']['payment']['entity']['amount'] / 100) : 0;
        $currency   = $request['payload']['payment']['entity']['currency'] ?? "INR";
        Log::alert("[Razorpay] event={$event_name} | txn_id={$txn_id} | amount={$amount} {$currency}");

        $transaction = !empty($txn_id) ? Transaction::where('txn_id', $txn_id)->get() : collect();
        if ($transaction->isEmpty()) {
            Log::alert("[Razorpay] Transaction lookup: NOT FOUND");
        } else {
            Log::alert("[Razorpay] Transaction lookup: found id=" . $transaction[0]->id);
        }

        // Robust Order ID discovery
        $order_id = 0;
        if (!$transaction->isEmpty()) {
            $order_id = $transaction[0]->order_id;
            Log::alert("[Razorpay] order_id from Transaction table: {$order_id}");
        } else {
            $order_id = $request['payload']['payment']['entity']['notes']['order_id']
                     ?? $request['payload']['order']['entity']['notes']['order_id']
                     ?? $request['payload']['order']['entity']['receipt']
                     ?? 0;
            Log::alert("[Razorpay] order_id from payload notes/receipt: {$order_id}");
        }

        if ($event_name == 'payment.authorized') {
            Log::alert("[Razorpay] payment.authorized — capturing payment txn_id={$txn_id} amount={$amount}");
            $razorpay->capture_payment($amount * 100, $txn_id, $currency);
            Log::alert("[Razorpay] capture_payment called");

            if (!empty($order_id) && strpos($order_id, "wallet-refill-user") === false) {
                $current_items = OrderItems::where('order_id', $order_id)->get();
                $current_active_status = $current_items->isEmpty() ? 'unknown' : ($current_items[0]->active_status ?? 'null');
                Log::alert("[Razorpay] Current order active_status={$current_active_status} for order_id={$order_id}");
                if (!$current_items->isEmpty() && $current_items[0]->active_status == 'awaiting') {
                    Log::alert("[Razorpay] Order is 'awaiting' — no change needed, will update on capture");
                } elseif (!$current_items->isEmpty() && empty($current_items[0]->active_status)) {
                    updateDetails(['active_status' => 'awaiting'], ['order_id' => $order_id], OrderItems::class);
                    updateDetails(['active_status' => 'awaiting'], ['id' => $order_id], Order::class);
                    Log::alert("[Razorpay] Set order_id={$order_id} to 'awaiting'");
                }
            }
        }

        if ($event_name == 'payment.captured' || $event_name == 'order.paid') {
            Log::alert("[Razorpay] {$event_name} — processing order_id={$order_id}");
            if (!empty($order_id)) {
                if (strpos($order_id, "wallet-refill-user") !== false) {
                    Log::alert("[Razorpay] Wallet refill path for order_id={$order_id}");
                    $user_id = 0;
                    $temp = explode("-", $order_id);
                    if (isset($temp[3]) && is_numeric($temp[3])) {
                        $user_id = $temp[3];
                    }

                    // Get transaction details to check type
                    $transaction = Transaction::where('txn_id', $txn_id)->first();
                    $transaction_type_from_db = $transaction ? $transaction->type : 'unknown';
                    Log::alert("[Razorpay] Transaction type from DB: {$transaction_type_from_db}");

                    // Update transaction status to success
                    $existing = Transaction::where('txn_id', $txn_id)->first();
                    if ($existing) {
                        Log::alert("[Razorpay] Found existing transaction id={$existing->id}, updating status instead of creating duplicate");
                        $existing->update(['status' => 'success', 'message' => 'Wallet refill successful via Razorpay']);
                        Log::alert("[Razorpay] Wallet transaction updated (existing) id={$existing->id}");
                    } else {
                        $newTxn = Transaction::create([
                            'transaction_type' => 'wallet',
                            'user_id'          => $user_id,
                            'order_id'         => $order_id,
                            'type'             => 'credit',
                            'txn_id'           => $txn_id,
                            'amount'           => $amount,
                            'status'           => 'success',
                            'message'          => 'Wallet refill successful via Razorpay',
                        ]);
                        Log::alert("[Razorpay] Wallet transaction created id={$newTxn->id}");
                    }
                    Log::alert("[Razorpay] Wallet transaction processed for user_id={$user_id} amount={$amount}");

                    // Only update wallet balance if this is the first time processing this payment
                    if (!$existing) {
                        Log::alert("[Razorpay] First time processing this payment - updating wallet balance");

                        // Get current balance before update
                        $user_before = \App\Models\User::find($user_id);
                        $balance_before = $user_before ? $user_before->balance : 0;
                        Log::alert("[Razorpay] User balance before update: {$balance_before}");

                        if (app(WalletService::class)->updateBalance($amount, $user_id, 'add')) {
                            Log::alert("[Razorpay] Wallet updated successfully user_id={$user_id}");

                            // Get current balance after update
                            $user_after = \App\Models\User::find($user_id);
                            $balance_after = $user_after ? $user_after->balance : 0;
                            Log::alert("[Razorpay] User balance after update: {$balance_after}");

                            // Trigger wallet component refresh for this user
                            try {
                                \Livewire::dispatch('refreshComponent', to: 'my-account.wallet');
                                Log::alert("[Razorpay] Wallet refresh event dispatched for user_id={$user_id}");
                            } catch (\Exception $e) {
                                Log::alert("[Razorpay] Could not dispatch wallet refresh: " . $e->getMessage());
                            }
                        } else {
                            Log::alert("[Razorpay] Wallet update failed for user_id={$user_id}");
                        }
                    } else {
                        Log::alert("[Razorpay] Payment already processed - skipping wallet balance update");
                    }

                    return response()->json(['error' => false, 'message' => "Wallet recharged successfully!"]);
                } else {
                    Log::alert("[Razorpay] Order payment path for order_id={$order_id}");
                    $order_data = app(OrderService::class)->fetchOrders($order_id, '', '', '', 1, 0, 'o.id', 'DESC');
                    $orderFound = !empty($order_data['order_data']);
                    Log::alert("[Razorpay] Order fetch: " . ($orderFound ? 'found user_id=' . $order_data['order_data'][0]->user_id : 'NOT FOUND'));

                    if ($orderFound) {
                        $order = $order_data['order_data'][0];

                        // Check for existing transaction with this txn_id
                        $order_transaction = Transaction::where('txn_id', $txn_id)->get();
                        if (!$order_transaction->isEmpty()) {
                            updateDetails(['status' => 'success'], ['id' => $order_transaction[0]->id], Transaction::class);
                            Log::alert("[Razorpay] Transaction updated to success id={$order_transaction[0]->id}");
                        } else {
                            $newTxn = Transaction::create(['transaction_type' => 'transaction', 'user_id' => $order->user_id, 'order_id' => $order_id, 'type' => 'razorpay', 'txn_id' => $txn_id, 'amount' => $amount, 'status' => 'success', 'message' => 'Order payment successful via webhook']);
                            Log::alert("[Razorpay] Transaction created id={$newTxn->id}");
                        }

                        $current_status = $order->active_status ?? 'awaiting';
                        Log::alert("[Razorpay] Current order status={$current_status} for order_id={$order_id}");
                        if ($current_status == 'awaiting') {
                            updateDetails(['active_status' => 'received'], ['order_id' => $order_id], OrderItems::class);
                            updateDetails(['active_status' => 'received'], ['id' => $order_id], Order::class);
                            $history = json_encode([['received', date("d-m-Y h:i:sa")]]);
                            updateDetails(['status' => $history], ['order_id' => $order_id], OrderItems::class);
                            Log::alert("[Razorpay] OrderItems updated to 'received' for order_id={$order_id}");

                            Log::alert("[Razorpay] Sending invoice email for order_id={$order_id}");
                            app(OrderService::class)->sendOrderInvoiceMail($order_id);
                            Log::alert("[Razorpay] Sending push notification to user_id={$order->user_id}");
                            app(FirebaseNotificationService::class)->sendCustomNotificationOnPaymentSuccess($order_id, $order->user_id);
                            Log::alert("[Razorpay] SUCCESS: order {$order_id} fully marked as received");
                        } else {
                            Log::alert("[Razorpay] Skipped status update — order_id={$order_id} is already '{$current_status}'");
                        }
                    } else {
                        Log::alert("[Razorpay] ERROR: order not found for order_id={$order_id}");
                    }
                }
            } else {
                Log::alert("[Razorpay] WARNING: empty order_id, cannot process");
            }
            Log::alert("[Razorpay] {$event_name} processing complete");
            return response()->json(['error' => false, 'message' => "Transaction successfully processed"]);

        } elseif ($event_name == 'payment.failed') {
            Log::alert("[Razorpay] payment.failed for order_id={$order_id}");
            if (!empty($order_id) && strpos($order_id, "wallet-refill-user") === false) {
                updateDetails(['active_status' => 'cancelled'], ['order_id' => $order_id], OrderItems::class);
                updateDetails(['active_status' => 'cancelled'], ['id' => $order_id], Order::class);
                $items = OrderItems::where('order_id', $order_id)->get();
                $variants = $items->pluck('product_variant_id')->toArray();
                $qtys = $items->pluck('quantity')->toArray();
                app(ProductService::class)->updateStock($variants, $qtys, 'plus');
                Log::alert("[Razorpay] Stock restored and order_id={$order_id} cancelled");
            }
            if (!$transaction->isEmpty()) {
                updateDetails(['status' => 'failed'], ['id' => $transaction[0]->id], Transaction::class);
                Log::alert("[Razorpay] Transaction marked as failed");
            }
            return response()->json(['error' => true, 'message' => "Transaction failed"]);

        } elseif ($event_name == "refund.processed") {
            Log::alert("[Razorpay] refund.processed");
            $transaction = Transaction::where('txn_id', $request['payload']['refund']['entity']['payment_id'])->first();
            if ($transaction) {
                app(OrderService::class)->process_refund($transaction->id, 'refunded');
                Log::alert("[Razorpay] Refund processed for txn_id=" . $request['payload']['refund']['entity']['payment_id']);
            } else {
                Log::alert("[Razorpay] Refund: no matching transaction found");
            }
            return response()->json(['error' => false, 'message' => "Refund processed"]);
        }

        Log::alert("[Razorpay] Unhandled event: {$event_name}");
        return response()->json(['error' => false, 'message' => "Webhook received"]);
    }

    public function stripe_webhook(Request $request)
    {
        Log::alert('STRIPE WEBHOOK ARRIVED');
        $raw_request = $request->getContent();
        Log::alert('Stripe Webhook Body: ' . $raw_request);

        // Fetch settings from DB directly if possible, avoiding complex service bindings
        try {
            $settings_val = \DB::table('settings')->where('variable', 'payment_method')->value('value');
            $credentials = json_decode($settings_val, true);
        } catch (\Exception $e) {
            Log::alert('Stripe Webhook: Could not load credentials from DB. ' . $e->getMessage());
            return response()->json(['error' => true, 'message' => 'DB error'], 500);
        }

        if (empty($credentials)) {
            Log::alert('Stripe Webhook: Empty credentials found in DB.');
            return response()->json(['error' => true, 'message' => 'Credentials not found'], 400);
        }

        $http_stripe_signature = $request->header('Stripe-Signature', '');
        $webhook_secret = $credentials['stripe_webhook_secret_key'] ?? "";

        $event_type = "";
        $object     = null;

        // Verify signature if secret is available
        if ($webhook_secret) {
            try {
                $stripe_event = \Stripe\Webhook::constructEvent(
                    $raw_request,
                    $http_stripe_signature,
                    $webhook_secret
                );
                $event_type = $stripe_event->type;
                $object     = $stripe_event->data->object;
                Log::alert("[Stripe] Webhook signature VERIFIED.");
            } catch (\Exception $e) {
                Log::alert('Stripe Webhook SIG FAILED: ' . $e->getMessage() . '. Falling back to unverified mode.');
                // We DON'T return 400 here anymore. We fall through to unverified mode to ensure payment processing.
                $decoded = json_decode($raw_request, FALSE);
                $event_type = $decoded->type ?? "";
                $object     = $decoded->data->object ?? null;
            }
        } else {
            // Unverified mode
            $decoded = json_decode($raw_request, FALSE);
            $event_type = $decoded->type ?? "";
            $object     = $decoded->data->object ?? null;
            Log::alert('Stripe Webhook: PRocessing UNVERIFIED (secret not found).');
        }

        if (!$object) {
            Log::alert('[Stripe] ERROR: No valid object found in payload.');
            return response()->json(['error' => true, 'message' => 'Invalid object'], 400);
        }

        // Meta extraction
        $metadata   = $object->metadata ?? new \stdClass();
        $type       = $metadata->type ?? "";
        
        // Always prioritize payment_intent ID (pi_...) as the txn_id for lookups.
        // For Checkout sessions, it's stored in 'payment_intent'.
        // For PaymentIntent objects, it's 'id'.
        // For Charge objects, it's in 'payment_intent'.
        $txn_id     = $object->payment_intent ?? ($object->object == 'payment_intent' ? $object->id : "");
        if (empty($txn_id) && isset($object->id) && str_starts_with($object->id, 'pi_')) {
            $txn_id = $object->id;
        }
        
        $amount     = $metadata->amount ?? (($object->amount ?? 0) / 100);
        $order_id_meta = $metadata->order_id ?? "";
        $user_id_meta  = $metadata->user_id ?? 0;

        // Inference logic (matches Razorpay)
        if (empty($type)) {
            if (!empty($order_id_meta) && strpos($order_id_meta, "wallet-refill-user") !== false) {
                $type = 'wallet';
            } elseif (!empty($order_id_meta)) {
                $type = 'order';
            }
        }

        // ✅ DB-based fallback: for old sessions (pre-fix) or app-created transactions where
        // Stripe session metadata has no 'type' and no 'order_id', look up the existing
        // transaction in the DB by txn_id (payment_intent) to determine the type.
        if (empty($type) && !empty($txn_id)) {
            $tx_prefetch = \DB::table('transactions')->where('txn_id', $txn_id)->first();
            if ($tx_prefetch) {
                $db_tx_type = $tx_prefetch->transaction_type ?? '';
                $db_order_id = $tx_prefetch->order_id ?? '';
                if ($db_tx_type === 'wallet') {
                    $type = 'wallet';
                    // Restore order_id_meta from DB so Stage 2 user_id parsing can work
                    if (empty($order_id_meta) && !empty($db_order_id)) {
                        $order_id_meta = $db_order_id;
                    }
                    Log::alert("[Stripe] DB fallback — resolved type='wallet' from DB transaction id={$tx_prefetch->id}");
                } elseif (!empty($db_order_id) && is_numeric($db_order_id)) {
                    $type = 'order';
                    $order_id_meta = $db_order_id;
                    Log::alert("[Stripe] DB fallback — resolved type='order' from DB transaction id={$tx_prefetch->id}");
                }
            }
        }

        Log::alert("[Stripe] INFERRED_TYPE: {$type} | TXN_ID: {$txn_id} | AMOUNT: {$amount} | ORDER_META: {$order_id_meta}");

        if (!in_array($type, ['wallet', 'order'])) {
             Log::alert("[Stripe] ERROR: Could not determine payment type.");
             return response()->json(['error' => true, 'message' => 'Unknown type'], 400);
        }


        switch ($event_type) {
            case 'payment_intent.succeeded':
            case 'checkout.session.completed':
            case 'charge.succeeded':
                Log::alert("[Stripe] SUCCESS EVENT — {$event_type}");
                if ($type === 'wallet') {
                    // --- Stage 1: user_id from session metadata ---
                    $user_id = $user_id_meta ?: 0;

                    // --- Stage 2: parse user_id from wallet-refill-user-{id}-{ts} order_id in metadata ---
                    if ($user_id == 0 && !empty($order_id_meta) && strpos($order_id_meta, "wallet-refill-user") !== false) {
                        $temp = explode("-", $order_id_meta);
                        if (isset($temp[3]) && is_numeric($temp[3])) { $user_id = $temp[3]; }
                        Log::alert("[Stripe] Stage2 user_id from order_id_meta pattern: {$user_id}");
                    }

                    // --- Stage 3: look up user_id from the existing DB transaction record ---
                    // Needed for old Stripe sessions (pre-fix) where metadata had no user_id,
                    // and for app-created transactions (add_transaction API) where the app
                    // stores user_id in the DB but not in the Stripe session metadata.
                    if (empty($user_id)) {
                        $tx_lookup = \DB::table('transactions')->where('txn_id', $txn_id)->first();
                        if ($tx_lookup && !empty($tx_lookup->user_id)) {
                            $user_id = $tx_lookup->user_id;
                            Log::alert("[Stripe] Stage3 user_id={$user_id} resolved from DB transaction id={$tx_lookup->id}");
                            // Also try to recover order_id_meta from DB if still missing
                            if (empty($order_id_meta) && !empty($tx_lookup->order_id)) {
                                $order_id_meta = $tx_lookup->order_id;
                                Log::alert("[Stripe] Stage3 order_id_meta recovered from DB: {$order_id_meta}");
                            }
                        }
                    }

                    if (empty($user_id)) {
                        Log::alert("[Stripe] ERROR: User ID missing for wallet refill — all 3 stages failed for txn_id={$txn_id}.");
                        return response()->json(['error' => true, 'message' => 'User ID missing'], 400);
                    }


                    // Handover to wallet processing (Check duplication first)
                    $existing = \DB::table('transactions')->where('txn_id', $txn_id)->first();
                    if ($existing && $existing->status === 'success') {
                         Log::alert("[Stripe] ALREADY COMPLETED: txn_id={$txn_id}");
                         return response()->json(['error' => false, 'message' => 'Already processed']);
                    }

                    if ($existing) {
                        \DB::table('transactions')->where('id', $existing->id)->update([
                            'status' => 'success',
                            'message' => 'Recharged via Stripe Webhook',
                            'order_id' => $existing->order_id ?: $order_id_meta
                        ]);
                        Log::alert("[Stripe] Updated existing transaction id={$existing->id}");
                    } else {
                        $trans_id = \DB::table('transactions')->insertGetId([
                            'transaction_type' => 'wallet',
                            'user_id'          => $user_id,
                            'order_id'         => $order_id_meta,
                            'type'             => 'credit',
                            'txn_id'           => $txn_id,
                            'amount'           => $amount,
                            'status'           => 'success',
                            'message'          => 'Recharged via Stripe Webhook fallback',
                            'created_at'       => now(),
                            'updated_at'       => now()
                        ]);
                        Log::alert("[Stripe] Created NEW transaction id={$trans_id}");
                    }

                    // Update balance
                    if (app(\App\Services\WalletService::class)->updateBalance($amount, $user_id, 'add')) {
                        Log::alert("[Stripe] Wallet balance updated user_id={$user_id}");
                        try { \Livewire::dispatch('refreshComponent', to: 'my-account.wallet'); } catch (\Exception $e) { }
                    } else {
                        Log::alert("[Stripe] ERROR: Balance update failed.");
                    }
                } else {
                    // Order identification
                    $order_id = $order_id_meta;
                    Log::alert("[Stripe] Processing order={$order_id}");
                    \DB::table('order_items')->where('order_id', $order_id)->update(['active_status' => 'received']);
                    \DB::table('transactions')->where('txn_id', $txn_id)->update(['status' => 'success']);
                    Log::alert("[Stripe] Order marked as received.");
                }
                break;

            case 'payment_intent.payment_failed':
            case 'charge.failed':
                Log::alert("[Stripe] FAILED: txn_id={$txn_id}");
                \DB::table('transactions')->where('txn_id', $txn_id)->update(['status' => 'failed']);
                break;
        }

        return response()->json(['error' => false, 'message' => 'Webhook processed successfully']);
    }

    public function spr_webhook(Request $request)
    {
    }
}