<?php

namespace App\Services;
use App\Models\AffiliateUser;
use App\Models\AffiliateTracking;
use App\Models\AffiliateTransaction;
use App\Models\User;
use App\Models\Transaction;
use App\Http\Controllers\Admin\TransactionController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\log;
class WalletService
{
    public function getUserBalance($user_id)
    {
        $user = User::where('id', $user_id)->select('balance')->first();

        return $user ? $user->balance : 0;
    }

    public function updateBalance($amount, $deliveryBoyId, $action)
    {
        /**
         * action = add / deduct
         */
        AffiliateUser::where('user_id', $deliveryBoyId)->update(['affiliate_wallet_balance' => DB::raw("affiliate_wallet_balance + $amount")]);
        $user = User::find($deliveryBoyId);

        if (!$user) {
            return false; // User not found
        }

        if ($action == "add") {
            $user->balance += $amount;
        } else {
            $user->balance -= $amount;
        }
        return $user->save();
    }

    public function updateCashReceived($amount, $deliveryBoyId, $action)
    {
        /**
         * action = add / deduct
         */

        $user = User::find($deliveryBoyId);
        if (!$user) {
            return false; // User not found
        }

        if ($action == "add") {
            $user->cash_received += $amount;
        } elseif ($action == "deduct") {
            $user->cash_received -= $amount;
        }
        return $user->save();
    }
    public function updateWalletBalance($operation, $user_id, $amount, $message = "Balance Debited", $order_item_id = "", $is_refund = 0, $transaction_type = 'wallet')
    {
        $user = User::find($user_id);

        if (!$user) {
            $response['error'] = true;
            $response['error_message'] = "User does not exist";
            $response['data'] = [];
            return $response;
        }

        if ($operation == 'debit' && $amount > $user->balance) {
            $response['error'] = true;
            $response['error_message'] = "Debited amount can't exceed the user balance!";
            $response['data'] = [];
            return $response;
        }

        if ($amount == 0) {
            $response['error'] = true;
            $response['error_message'] = "Amount can't be zero!";
            $response['data'] = [];
            return $response;
        }

        if ($user->balance >= 0) {
            $data = [
                'transaction_type' => $transaction_type,
                'user_id' => $user_id,
                'type' => $operation,
                'amount' => $amount,
                'message' => $message,
                'order_item_id' => $order_item_id,
                'is_refund' => $is_refund,
            ];

            $payment_data = Transaction::where('order_item_id', $order_item_id)->pluck('type')->first();

            if ($operation == 'debit') {
                $data['message'] = $message ?: 'Balance Debited';
                $data['type'] = 'debit';
                $data['status'] = 'success';
                $user->balance -= $amount;
            } else if ($operation == 'credit') {
                $data['message'] = $message ?? 'Balance Credited';
                $data['type'] = 'credit';
                $data['status'] = 'success';
                $data['order_id'] = $order_item_id;
                if ($payment_data != 'razorpay') {
                    $user->balance += $amount;
                }
            } else {
                $data['message'] = $message ?: 'Balance refunded';
                $data['type'] = 'refund';
                $data['status'] = 'success';
                $data['order_id'] = $order_item_id;
                if ($payment_data != 'razorpay') {
                    $user->balance += $amount;
                }
            }

            $user->save();

            $request = new \Illuminate\Http\Request($data);
            $transactionController = app(TransactionController::class);

            $transactionController->store($request);
            $response['error'] = false;
            $response['message'] = "Balance Update Successfully";
            $response['data'] = [];
        } else {
            $response['error'] = true;
            $response['error_message'] = ($user->balance != 0) ? "User's Wallet balance less than {$user->balance} can be used only" : "Doesn't have sufficient wallet balance to proceed further.";
            $response['data'] = [];
        }

        return $response;
    }

    public function updateAffiliateWalletBalance($type, $userId, $amount, $productId = null, $message = '', $referenceType = 'order', $subTotal = null, $token = null)
    {
        // Step 1: Basic input validation
        if (!in_array($type, ['credit', 'debit']) || $userId <= 0 || $amount <= 0) {
            return ['error' => true, 'message' => 'Invalid input'];
        }

        if ($referenceType !== 'order') {
            return ['error' => true, 'message' => 'Unsupported reference type'];
        }

        // Step 2: Fetch affiliate user
        $affiliate = AffiliateUser::where('user_id', $userId)->first();

        if (!$affiliate) {
            return ['error' => true, 'message' => 'Affiliate not found'];
        }

        // Step 3: Calculate new balance
        $currentBalance = floatval($affiliate->affiliate_wallet_balance);
        $newBalance = ($type === 'credit') ? $currentBalance + $amount : $currentBalance - $amount;

        if ($newBalance < 0) {
            return ['error' => true, 'message' => 'Insufficient balance'];
        }

        // Optional: Check active DB connection
        $dbName = DB::connection()->getDatabaseName();

        DB::beginTransaction();

        try {
            // Step 4: Update wallet balance directly
            $updated = AffiliateUser::where('user_id', $userId)->update([
                'affiliate_wallet_balance' => $newBalance
            ]);

            if (!$updated) {
                DB::rollBack();
                return ['error' => true, 'message' => 'Update failed: Affiliate user not found or no change'];
            }

            // Step 5: Optional tracking update
            if (!empty($productId)) {
                $tracking = AffiliateTracking::where([
                    'affiliate_id' => $userId,
                    'product_id' => $productId,
                    'token' => $token,
                ])->first();

                if ($tracking) {
                    $tracking->commission_earned += $amount;

                    if (!empty($token)) {
                        $tracking->usage_count += 1;
                    }

                    if (!is_null($subTotal)) {
                        $tracking->total_order_value += $subTotal;
                    }

                    $tracking->save();
                }
            }

            // Step 6: Log transaction
            AffiliateTransaction::create([
                'user_id' => $userId,
                'amount' => $amount,
                'type' => $type,
                'reference_type' => $referenceType,
                'message' => $message,
            ]);

            DB::commit();

            // Step 7: Fetch updated value to confirm it's saved
            $confirmed = AffiliateUser::where('user_id', $userId)->first();

            return [
                'error' => false,
                'message' => 'Affiliate Wallet updated successfully',
                'debug' => [
                    'database' => $dbName,
                    'new_balance' => $newBalance,
                    'confirmed_balance_from_db' => $confirmed->affiliate_wallet_balance,
                ],
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Affiliate wallet update failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'amount' => $amount,
            ]);

            return ['error' => true, 'message' => 'Transaction failed: ' . $e->getMessage()];
        }
    }
}
