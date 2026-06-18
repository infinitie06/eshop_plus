<?php

namespace App\Livewire\Payments;

use App\Libraries\Phonepe;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Status extends Component
{
    public $id;
    public $response;
    public $payment_response;

    public function mount()
    {
        // Fetch from query params manually
        $this->id = request()->query('id') ?? request()->query('TransactionID');
        $this->response = request()->query('response');

        \Log::info('Mount Params', ['id' => $this->id, 'response' => $this->response, 'query' => request()->query()]);

        $this->payment_response = $this->handlePaymentResponse();

        // Backup trigger: if success landing, ensure status is received and mail is sent
        if ($this->payment_response === 'order_success') {
            if (empty($this->id) || $this->id == 'null' || $this->id == '') {
                // Try to find the latest order for this user as a last resort
                if (Auth::check()) {
                    $latestOrder = \App\Models\Order::where('user_id', Auth::id())
                        ->orderBy('id', 'desc')
                        ->first();
                    // If the order was created in the last 10 minutes, use it
                    if ($latestOrder && \Carbon\Carbon::parse($latestOrder->created_at)->diffInMinutes(now()) < 10) {
                        $this->id = $latestOrder->id;
                        \Log::info("Mail Debug: Found latest order #{$this->id} as fallback for null ID.");
                    }
                }
            }

            if (!empty($this->id) && $this->id != 'null' && $this->id != '') {
                $this->triggerOrderSuccessBackup($this->id);
            }
        }
    }

    protected function triggerOrderSuccessBackup($id)
    {
        try {
            // Find order_id from transaction or use $id if numeric
            $transaction = Transaction::where('txn_id', $id)->orWhere('order_id', $id)->first();
            $order_id = $transaction ? $transaction->order_id : (is_numeric($id) ? $id : null);

            if ($order_id) {
                \Log::info("Mail Debug: Backup trigger checking Order #$order_id from Status landing.");
                
                // Update OrderItems to 'received' as backup to Webhook
                \App\Models\OrderItems::where('order_id', $order_id)
                    ->whereIn('active_status', ['awaiting', 'awaiting_payment']) // common initial statuses
                    ->update([
                        'active_status' => 'received',
                        'status' => json_encode([['received', date("d-m-Y h:i:sa")]])
                    ]);

                // Also update order table if needed (though order_items is what's usually tracked)
                // \App\Models\Order::where('id', $order_id)->update(['active_status' => 'received']);

                // Trigger invoice mail
                app(\App\Services\OrderService::class)->sendOrderInvoiceMail($order_id);
            }
        } catch (\Exception $e) {
            \Log::error("Mail Debug: Status backup trigger error: " . $e->getMessage());
        }
    }

    public function handlePaymentResponse()
    {
        if (in_array($this->response, ['wallet_success', 'wallet_failed', 'order_success', 'order_failed'])) {
            return $this->response;
        }

        if (!$this->id || $this->id == 'null') {
            \Log::warning('Payment response called without ID.');
            return 'order_failed';
        }

        $phonepe = new Phonepe();
        $check_status = $phonepe->check_status_v2($this->id);

        $transaction = fetchDetails(Transaction::class, ['txn_id' => $this->id]);

        if (!empty($transaction)) {
            if ($transaction[0]->type == "phonepe") {
                if (Auth::check() == false) {
                    Auth::loginUsingId($transaction[0]->user_id);
                }
            }

            $status = $check_status['state'] ?? 'PAYMENT_FAILED';

            if (in_array($status, ['PAYMENT_SUCCESS', 'INTERNAL_SERVER_ERROR', 'COMPLETED'])) {
                return $transaction[0]->transaction_type === 'wallet' ? 'wallet_success' : 'order_success';
            } else {
                return $transaction[0]->transaction_type === 'wallet' ? 'wallet_failed' : 'order_failed';
            }
        }

        // If no transaction found but we have 'order_success' response, trust the response param
        if ($this->response == 'order_success') {
            return 'order_success';
        }

        return 'order_failed';
    }

    public function render()
    {
        return view('livewire.' . config('constants.theme') . '.payments.status', [
            'breadcrumb' => 'payment_status',
            'payment_response' => $this->payment_response,
        ])->title("Payment Status |");
    }
}
