<?php

namespace App\Http\Controllers;

use App\Libraries\Stripe;
use App\Libraries\Phonepe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WalletController;
use App\Libraries\Razorpay;
use App\Models\Order;
use App\Services\CurrencyService;
use App\Models\User;

class PaymentsController extends Controller
{

    public $phonepe;
    public $stripe;
    public $razorpay;

    public function __construct()
    {
        $this->phonepe = new Phonepe();
        $this->stripe = new Stripe();
        $this->razorpay = new Razorpay();
    }

    public function phonepe(Request $request)
    {
        $user_id = $request['user_id'];
        $final_total = app(CurrencyService::class)->currentCurrencyPrice(
            $request['final_total'],
        );
        $mobile = $request['mobile'];
        $type = $request->input('type', 'order');
        $prefix = ($type == 'wallet') ? 'wallet-phonepe-' : '';
        $transaction_id = $prefix . time() . "" . rand("100", "999");
        $data = array(
            'merchantTransactionId' => $transaction_id,
            'merchantUserId' => $user_id,
            'amount' => $final_total * 100,
            'redirectUrl' => customUrl(url('payments/response')),
            'redirectMode' => 'POST',
            'callbackUrl' => url("webhook/phonepe_webhook"),
            'mobileNumber' => $mobile,
        );
        $res = $this->phonepe->pay_v2($data);
        if ($res) {
            if ($type == 'wallet') {
                \App\Models\Transaction::create([
                    'status' => 'awaiting',
                    'txn_id' => $transaction_id,
                    'message' => 'Payment Pending',
                    'user_id' => $user_id,
                    'transaction_type' => 'wallet',
                    'type' => 'credit',
                    'amount' => $request['final_total'],
                ]);
            }
            $response = [
                'error' => false,
                'message' => $res['message'] ?? "Success",
                'transaction_id' => $transaction_id ?? "",
                'payment_url' => $res['redirectUrl'] ?? "",
                'data' => $res,
            ];
            return response()->json($response);
        }
    }

    public function stripe(Request $request)
    {
        $user_id = Auth::user()->id ?? $request->input('user_id') ?? 0;
        $fetchUser = User::find($user_id);
        // dd($fetchUser->email);
        // Auto-detect wallet
        if (!isset($request['type']) && !empty($request['amount']) && empty($request['selected_address_id'])) {
            $request['type'] = 'wallet';
            Log::alert("[Stripe] Auto-detected wallet refill for user_id: {$user_id}");
        }

        if (isset($request['type']) && $request['type'] == "wallet") {

            $order_id = 'wallet-refill-user-' . $user_id . '-' . time();
            
            $data = [
                'amount' => $request['amount'],
                'product_name' => $request['product_name'] ?? "Wallet Refill",
                'type' => "wallet",
                'email' => $fetchUser->email ?? "test@gmail.com",
                'user_id' => $user_id,
                'order_id' => $order_id,

                // ✅ CRITICAL: SEND METADATA
                'metadata' => [
                    'amount' => $request['amount'],
                    'user_id' => $user_id,
                    'type' => 'wallet',
                    'order_id' => $order_id,
                ],
            ];
        } else {

            $data = [
                'amount' => $request['amount'],
                'product_name' => $request['product_name'],
                'selected_address_id' => $request['selected_address_id'],
                'email' => $request['user-email'] ?? "test@gmail.com",
                'type' => "order",

                // optional metadata for order
                'metadata' => [
                    'amount' => $request['amount'],
                    'type' => 'order',
                    'user_id' => $user_id,
                ],
            ];
        }

        $checkout_session = $this->stripe->createPaymentIntent($data);

        // ✅ IMMEDIATE TRANSACTION CREATION (At any cost!)
        // Create the record now so we don't lose track of this payment attempt.
        if (isset($checkout_session['id'])) {
            $txn_id = $checkout_session['payment_intent'] ?? $checkout_session['id'];

            // Check for existing to be safe
            if (!\App\Models\Transaction::where('txn_id', $txn_id)->exists()) {
                \App\Models\Transaction::create([
                    'transaction_type' => $data['type'] ?? 'wallet',
                    'user_id' => $user_id,
                    'order_id' => $data['order_id'] ?? '',
                    'type' => 'credit',
                    'txn_id' => $txn_id,
                    'amount' => $data['amount'],
                    'status' => 'received',
                    'message' => 'Payment From Stripe',
                ]);
                Log::alert("[Stripe] IMMEDIATE Transaction created for txn_id: {$txn_id}");
            }
        }

        return $checkout_session;
    }

    public function stripe_response(Request $request)
    {
        if (!$request->query("session_id")) {
            return json_encode([
                'error' => true,
                'message' => "request not allowed",
            ]);
        }

        $session_id = $request->query("session_id");
        $res = json_decode($this->stripe->stripe_response($session_id), true);

        if ($res['status'] !== "complete") {
            return redirect(url('payments?response=order_failed'));
        }

        $metadata = $res['data']['metadata'] ?? [];
        $payment_intent = $res['data']['payment_intent'] ?? '';

        // inject required fields
        $metadata['stripe_payment_id'] = $payment_intent;
        $metadata['payment_method'] = 'stripe';

        Log::alert("[Stripe Response] Metadata: " . json_encode($metadata));

        $transactionController = app(TransactionController::class);

        // ✅ WALLET FLOW
        if (($metadata['type'] ?? '') === "wallet") {

            $walletRequest = new Request();
            $walletRequest->replace([
                'res' => $metadata,
            ]);

            $walletController = app(WalletController::class);
            $walletController->refill($walletRequest, $transactionController);

            return redirect(url('payments?response=wallet_success'));
        }

        // ORDER FLOW
        $newRequest = new Request();
        $newRequest->replace([
            'res' => $metadata,
        ]);

        $cartController = app(CartController::class);
        $result = json_decode($cartController->place_order($newRequest, $transactionController)->getContent(), true);

        if ($result['error'] == false) {
            return redirect(url('payments?response=order_success&id=' . ($result['order_id'] ?? '')));
        }

        return redirect(url('payments?response=order_failed'));
    }
    public function razorpay(Request $request)
    {

        $amount = intval($request['amount'] * 100);
        $order_id = $request['order_id'];

        $res = $this->razorpay->create_order($amount, $order_id);
        return $res;
    }

}