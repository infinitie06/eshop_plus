<?php

namespace App\Http\Controllers;

use App\Libraries\Paystack;
use Illuminate\Http\Request;
use App\Http\Controllers\PaymentsController;
use App\Models\PaymentRequest;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Services\SettingService;
use App\Services\WalletService;
use App\Services\OrderService;

class WalletController extends Controller
{
    public function refill(Request $request, TransactionController $transactionController)
    {
        if ($request->has('res')) {

            $res = $request->input('res');

            Log::alert("[WalletController] Incoming RES: " . json_encode($res));

            $request = new Request($res);

            // ✅ SAFE fallback handling
            $request['add_amount'] = $res['amount'] ?? 0;
            $request['p_user_id'] = $res['user_id'] ?? 0;
        }

        $user_id = Auth::user()->id
            ?? $request->input('user_id')
            ?? $request->input('p_user_id')
            ?? 0;

        if (empty($user_id)) {
            return response()->json([
                'error' => true,
                'message' => 'Please Login first.',
            ]);
        }

        Log::alert("[WalletController] user_id={$user_id}, amount={$request['add_amount']}");

        // ✅ STRICT validation
        $validated = Validator::make($request->all(), [
            'add_amount' => 'required|numeric|min:1',
            'payment_method' => 'required',
        ]);

        if ($validated->fails()) {
            Log::error("[WalletController] Validation failed: " . json_encode($validated->errors()->all()));

            return response()->json([
                'error' => true,
                'message' => $validated->errors()->all(),
            ]);
        }

        /*
         |--------------------------------------------------------------------------
         | STRIPE HANDLING
         |--------------------------------------------------------------------------
         */

        if ($request['payment_method'] == 'stripe') {

            $stripe_payment_id = $request['stripe_payment_id'] ?? null;

            if (empty($stripe_payment_id)) {
                Log::error("[Stripe] Missing payment ID");

                return response()->json([
                    'error' => true,
                    'message' => 'Stripe Payment ID missing',
                ]);
            }

            // prevent duplicate
            if (Transaction::where('txn_id', $stripe_payment_id)->exists()) {
                return response()->json([
                    'error' => true,
                    'message' => 'Transaction already exists',
                ]);
            }

            $order_id = $request['order_id']
                ?? 'wallet-refill-user-' . $user_id . '-' . time();

            // ✅ CREATE TRANSACTION
            Transaction::create([
                'transaction_type' => 'wallet',
                'user_id' => $user_id,
                'order_id' => $order_id,
                'type' => 'credit',
                'txn_id' => $stripe_payment_id,
                'amount' => $request['add_amount'],
                'status' => 'awaiting',
                'message' => 'Payment Pending',
            ]);

            Log::alert("[Stripe] Transaction created: {$stripe_payment_id}");

            return response()->json([
                'error' => false,
                'message' => 'Wallet refill initiated',
            ]);
        }

        /*
         |--------------------------------------------------------------------------
         | FALLBACK (OTHER GATEWAYS)
         |--------------------------------------------------------------------------
         */

        $transactionController->store(new Request([
            'status' => 'awaiting',
            'txn_id' => $request['transaction_id'] ?? null,
            'message' => 'Payment Pending',
            'user_id' => $user_id,
            'transaction_type' => 'wallet',
            'type' => 'credit',
            'amount' => $request['add_amount'],
        ]));

        return response()->json([
            'error' => false,
            'message' => 'Wallet Refill Successfully',
        ]);
    }

    function withdrawal(Request $request)
    {
        $user = Auth::user() ?? "";
        $balance = $user['balance'];
        if ($user->id == "") {
            $response = [
                'error' => true,
                'message' => 'Please Login first.',
                'code' => 102,
            ];
            return response()->json($response);
        }
        $validated = Validator::make($request->all(), [
            'amount_requested' => 'required|numeric',
            'payment_address' => 'required',
        ]);

        if ($validated->fails()) {
            $response = [
                'error' => true,
                'message' => $validated->errors()->all(),
                'code' => 102,
            ];
            return response()->json($response);
        }

        $system_settings = app(SettingService::class)->getSettings('system_settings', true);
        $system_settings = json_decode($system_settings, true);

        if ($balance < $request['amount_requested']) {
            $response = [
                'error' => true,
                'message' => 'unfortunately you don\'t have enough funds to Withdraw',
            ];
            return response()->json($response);
        }
        if ($request['amount_requested'] <= 0) {
            $response = [
                'error' => true,
                'message' => 'Please Enter Correct Amount',
            ];
            return response()->json($response);
        }
        $data = [
            'payment_type' => 'customer',
            'payment_address' => $request['payment_address'],
            'amount_requested' => $request['amount_requested'],
            'user_id' => $user->id,
        ];

        if (PaymentRequest::create($data)) {
            app(WalletService::class)->updateBalance($request['amount_requested'], $user->id, 'deduct');
            $balance = $user['balance'] - $request['amount_requested'];
            $response = [
                'error' => false,
                'message' => 'Withdrawal Request Sent Successfully.',
                'balance' => $balance,
            ];
            return response()->json($response);
        }
        $response = [
            'error' => true,
            'message' => 'Something Went Wrong Please Try Again later.',
        ];
        return response()->json($response);
    }
}