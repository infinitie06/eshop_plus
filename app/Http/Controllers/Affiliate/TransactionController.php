<?php

namespace App\Http\Controllers\Affiliate;

use App\Models\AffiliateTransaction;
use App\Models\AffiliateUser;
use App\Models\Currency;
use Illuminate\Http\Request;
use App\Models\PaymentRequest;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\WalletService;
use App\Services\SettingService;
use App\Traits\HandlesValidation;
use Carbon\Carbon;

class TransactionController extends Controller
{
    use HandlesValidation;
    public function index()
    {
        $userId = Auth::id() ?? 0;
        return view('affiliate.pages.tables.payment_requests', compact('userId'));
    }

    public function addWithdrawalRequest(Request $request)
    {
        // dd($request);
        $rules = [
            'user_id' => 'required|numeric|exists:users,id',
            'payment_address' => 'required',
            'amount' => 'required|numeric|gt:0',
        ];

        if ($response = $this->HandlesValidation($request, $rules)) {
            return $response;
        }
        $affiliateSettings = app(SettingService::class)->getSettings('affiliate_settings', true);
        $affiliateSettings = json_decode($affiliateSettings, true) ?: [];
        $maxAmount = $affiliateSettings['max_amount_for_withdrawal_request'] ?? 0;
        $minAmount = $affiliateSettings['min_amount_for_withdrawal_request'] ?? 0;
        // dd($affiliateSettings);
        $user_id = $request->input('user_id');
        $payment_address = $request->input('payment_address');
        $amount = $request->input('amount');
        $userData = fetchDetails(AffiliateUser::class, ['user_id' => $user_id], 'affiliate_wallet_balance');
        // dd($userData);
        if (!empty($userData)) {
            if ($maxAmount > 0 && $amount > $maxAmount) {
                $response['error'] = true;
                $response['message'] = 'You can sent maximum ' . $maxAmount . ' for the withdraw request.';
                $response['error_message'] = $response['message'];
                $response['data'] = array();
                return response()->json($response);
            } elseif ($minAmount > 0 && $amount < $minAmount) {
                $response['error'] = true;
                $response['message'] = 'Minimum ' . $minAmount . ' amount is required in wallet.';
                $response['error_message'] = $response['message'];
                $response['data'] = array();
                return response()->json($response);
            } else {
                if ($amount <= $userData[0]->affiliate_wallet_balance) {
                    $payment_type = 'affiliate';
                    $data = [
                        'user_id' => $user_id,
                        'payment_address' => $payment_address,
                        'payment_type' => $payment_type,
                        'amount_requested' => $amount,
                    ];
                    if (PaymentRequest::create($data)) {
                        $lastAddedRequest = PaymentRequest::latest()->first();
                        if ($lastAddedRequest) {
                            $data = $lastAddedRequest->toArray();

                            // Change the key from 'status' to 'status_code'
                            $data['status_code'] = $data['status'];
                            $data['date_created'] = Carbon::parse($data['created_at'])->format('d-m-Y');
                            $data['updated_at'] = Carbon::parse($data['updated_at'])->format('d-m-Y');
                            unset($data['status']);
                            unset($data['created_at']);
                        }
                        $message = labels('admin_labels.withdrawal_request_sent_successfully', 'Affiliate withdrawal request sent successfully');
                        app(WalletService::class)->updateAffiliateWalletBalance('debit', $user_id, $amount, '', $message);
                        $userData = fetchDetails(AffiliateUser::class, ['user_id' => $user_id], 'affiliate_wallet_balance');
                        // dd($userData);
                        $response['error'] = false;
                        $response['message'] =
                            labels('admin_labels.withdrawal_request_sent_successfully', 'Withdrawal Request Sent Successfully');
                        $response['amount'] = $userData[0]->affiliate_wallet_balance;
                        $response['data'] = $data;
                    } else {
                        $response['error'] = true;
                        $response['message'] =
                            labels('admin_labels.cannot_send_withdrawal_request', "Cannot sent Withdrawal Request.Please Try again later.");
                        $response['data'] = array();
                        $response['amount'] = 0;
                    }
                } else {
                    $response['error'] = true;
                    $response['message'] =
                        labels('admin_labels.insufficient_balance_for_withdrawal', "You don't have enough balance to sent the withdraw request.");
                    $response['error_message'] = $response['message'];
                    $response['data'] = array();
                    $response['amount'] = 0;
                }
                return response()->json($response);
            }
        }
    }

    public function getWithdrawalRequests(Request $request)
    {
        $user_id = $user_id ?? Auth::id();
        $currency = Currency::where('is_default', 1)->first();
        $currency = $currency->symbol;
        $search = trim($request->input('search', ''));
        $offset = $search || request('pagination_offset') ? request('pagination_offset') : 0;
        $limit = $request->input('limit', 10);
        $sort = $request->input('sort', 'id');
        $order = $request->input('order', 'DESC');
        $userFilter = $request->input('user_filter');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $status = $request->input('payment_request_status');

        // Base query with Eloquent and eager loading
        $query = PaymentRequest::with('user');

        // Filters
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('id', $search)
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('username', 'like', "%$search%")
                            ->orWhere('email', 'like', "%$search%");
                    });
            });
        }

        if ($startDate && $endDate) {
            $query->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate);
        }

        if (isset($status)) {
            $query->where('status', intval($status));
        }

        if (!empty($userFilter)) {
            $query->where('payment_type', $userFilter);
        }

        if (!empty($user_id)) {
            $query->where('user_id', $user_id);
        }

        $total = $query->count();

        $paymentRequests = $query->orderBy($sort, $order)
            ->offset($offset)
            ->limit($limit)
            ->get();

        // Format the response
        $rows = $paymentRequests->map(function ($row) use ($currency) {
            $statusMap = [
                0 => '<span class="badge bg-success">Pending</span>',
                1 => '<span class="badge bg-primary">Approved</span>',
                2 => '<span class="badge bg-danger">Rejected</span>',
            ];

            return [
                'id' => $row->id,
                'user_id' => $row->user_id,
                'user_name' => optional($row->user)->username,
                'payment_type' => $row->payment_type,
                'amount_requested' => $currency . $row->amount_requested,
                'remarks' => $row->remarks,
                'payment_address' => $row->payment_address,
                'date_created' => Carbon::parse($row->created_at)->format('d-m-Y'),
                'status_code' => $row->status,
                'status' => $statusMap[$row->status] ?? '',
            ];
        });

        return response()->json([
            'total' => $total,
            'rows' => $rows,
        ]);
    }

    public function transactions()
    {
        $userId = Auth::id() ?? 0;
        return view('affiliate.pages.tables.transactions', compact('userId'));
    }

 public function getTransactions(Request $request)
{
    $userId   = Auth::id();
    $currency = Currency::where('is_default', 1)->value('symbol') ?? '';

    $search    = trim($request->input('search', ''));
    $offset    = (int) $request->input('offset', 0);
    $limit     = (int) $request->input('limit', 10);
    $sort      = $request->input('sort', 'id');
    $order     = $request->input('order', 'DESC');
    $startDate = $request->input('start_date');
    $endDate   = $request->input('end_date');

    $query = AffiliateTransaction::with('user')
        ->when($search, function ($q) use ($search) {
            $q->where(function ($q2) use ($search) {
                $q2->where('message', 'LIKE', "%{$search}%")
                   ->orWhere('id', 'LIKE', "%{$search}%")
                   ->orWhere('amount', 'LIKE', "%{$search}%")
                   ->orWhereHas('user', function ($uq) use ($search) {
                       $uq
                          ->orWhere('email', 'LIKE', "%{$search}%");
                   });
            });
        })
        ->when($startDate && $endDate, fn ($q) =>
            $q->whereBetween('created_at', [$startDate, $endDate])
        )
        ->when($userId, fn ($q) => $q->where('user_id', $userId));

    $total = $query->count();

    $transactions = $query->orderBy($sort, $order)
        ->offset($offset)
        ->limit($limit)
        ->get();

    $rows = $transactions->map(function ($row) use ($currency) {
        $badge = match (strtolower($row->type ?? '')) {
            'credit' => '<span class="badge bg-success">Credit</span>',
            'debit'  => '<span class="badge bg-danger">Debit</span>',
            default  => '<span class="badge bg-secondary">' . ucfirst($row->type ?? 'N/A') . '</span>',
        };

        return [
            'id'               => $row->id,
            'type'             => $badge,
            'transaction_type' => $badge,
            'amount'           => $currency . $row->amount,
            'message'          => $row->message,
            'date_created'     => Carbon::parse($row->created_at)->format('d-m-Y'),
        ];
    });

    return response()->json([
        'total' => $total,
        'rows'  => $rows,
    ]);
}

}
