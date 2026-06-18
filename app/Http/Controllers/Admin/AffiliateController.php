<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use App\Models\OrderItems;
use App\Models\AffiliateTracking;
use App\Models\Product;
use App\Models\Currency;
use App\Services\MediaService;
use App\Services\TranslationService;
use App\Services\WalletService;
use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use App\Traits\HandlesValidation;
use App\Models\User;
use App\Models\AffiliateUser;
use App\Services\SettingService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use Exception;
use Illuminate\Validation\Rule;

class AffiliateController extends Controller
{
    use HandlesValidation;

    public function index()
    {
        return view('admin.pages.forms.affiliate_users');
    }

    public function manage_users()
    {
        return view('admin.pages.tables.manage_affiliate_users');
    }

    public function store(Request $request)
    {
        $rules = [
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
             'country_code' => 'required',
            'mobile' => [
                'required',
                Rule::unique('users')->where(function ($query) use ($request) {
                    return $query->where('country_code', $request->country_code);
                })
            ],
            'password' => 'required|confirmed',
            'address' => 'required',
            'website_url' => 'required|url',
            'application_url' => 'required|url',
            'status' => 'required|in:0,1,2',
        ];

        if ($response = $this->HandlesValidation($request, $rules)) {

            return $response; // returns JSON with error/message/code
        }


            $user = User::create([
                'username' => $request->full_name,
                'email' => $request->email,
                'country_code'=>$request->country_code,
                'mobile' => $request->mobile,
                'password' => Hash::make($request->password),
                'address' => $request->address,
                'type' => 'phone',
                'is_affiliate_user' => 1,
                'role_id' => 7,
                'active' => 1,
            ]);

            AffiliateUser::create([
                'user_id' => $user->id,
                'uuid' => mt_rand(100000, 999999),
                'website_url' => $request->website_url,
                'application_url' => $request->application_url,
                'status' => $request->status,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Affiliate user created successfully',
                'location' => route('admin.affiliate.manage_user'),
            ]);


        }


    public function list(Request $request)
    {
        $search = trim($request->input('search', ''));
        $sort = $request->input('sort', 'id');
        $order = $request->input('order', 'DESC');
        $offset = $search || $request->has('pagination_offset') ? $request->input('pagination_offset', 0) : 0;
        $limit = $request->input('limit', 10);
        $status = $request->input('status', '');

        $userQuery = User::with('affiliateUser')->where('is_affiliate_user', 1);

        if ($search) {
            $userQuery->where(function ($q) use ($search) {
                $q->where('username', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%")
                    ->orWhere('mobile', 'like', "%$search%");
            });
        }

        if ($status !== '' && $status !== null) {
            $userQuery->whereHas('affiliateUser', function ($q) use ($status) {
                $q->where('status', $status);
            });
        }

        $total = $userQuery->count();

        $users = $userQuery->orderBy($sort, $order)
            ->offset($offset)
            ->limit($limit)
            ->get();

        $data = $users->map(function ($u) {
            $editUrl = route('admin.affiliate_users.edit', $u->id);
            $status = $u->affiliateUser->status ?? null;
            $statusLabel = 'Unknown';
            $badgeClass = 'secondary';

            if ($status == 1) {
                $statusLabel = 'Approved';
                $badgeClass = 'success';
            } elseif ($status == 2) {
                $statusLabel = 'Not Approved';
                $badgeClass = 'warning';
            } elseif ($status == 0) {
                $statusLabel = 'Deactive';
                $badgeClass = 'danger';
            }

            $action = '<div class="dropdown bootstrap-table-dropdown">
                <a href="#" class="text-dark" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="bx bx-dots-horizontal-rounded"></i>
                </a>
                <div class="dropdown-menu table_dropdown brand_action_dropdown">
                    <a class="dropdown-item dropdown_menu_items" href="' . $editUrl . '">
                        <i class="bx bx-pencil mx-2"></i> ' . labels('admin_labels.edit', 'Edit') . '
                    </a>
                </div>
            </div>';

            return [
                'id' => $u->id,
                'username' => $u->username,
                'email' => $u->email,
                'mobile' => $u->mobile,
                'affiliate_code' => $u->affiliateUser->uuid ?? '-',
                'website_url' => $u->affiliateUser->website_url ?? '-',
                'application_url' => $u->affiliateUser->application_url ?? '-',
                'status' => '<span class="badge bg-' . $badgeClass . '">' . $statusLabel . '</span>',
                'operate' => $action,
            ];
        });

        return response()->json([
            'rows' => $data,
            'total' => $total,
        ]);
    }

    public function edit($id)
    {
        $user = User::with('affiliateUser')->findOrFail($id);
        return view('admin.pages.forms.update_affiliate_user', compact('user'));
    }

    public function update(Request $request, $id)
    {
        $rules = [
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'mobile' => 'required|unique:users,mobile,' . $id,
            'address' => 'required',
            'password' => 'nullable|confirmed|min:6',
            'website_url' => 'required|url',
            'application_url' => 'required|url',
            'status' => 'required|in:0,1,2',
        ];

        if ($response = $this->HandlesValidation($request, $rules)) {
            return $response;
        }

        try {
            $user = User::findOrFail($id);
            $user->update([
                'username' => $request->full_name,
                'email' => $request->email,
                'mobile' => $request->mobile,
                'address' => $request->address,
            ]);

            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
                $user->save();
            }

            $affiliate = $user->affiliateUser;
            if ($affiliate) {
                $affiliate->update([
                    'website_url' => $request->website_url,
                    'application_url' => $request->application_url,
                    'status' => $request->status,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Affiliate user updated successfully',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
                'code' => 102,
            ], 422);
        } catch (QueryException $e) {
            return response()->json([
                'error' => true,
                'message' => 'Database error: ' . $e->getMessage(),
                'code' => 102,
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Unexpected error: ' . $e->getMessage(),
                'code' => 102,
            ], 422);
        }
    }

    public function settleCommission(Request $request)
    {
        $isDate = $request->input('is_date', false);
        $date = Carbon::today()->toDateString();
        $settings = json_decode(app(SettingService::class)->getSettings('system_settings', true), true);
        $maxReturnDays = $settings['max_days_to_return_item'] ?? 0;

        $query = OrderItems::query()
            ->where('active_status', 'delivered')
            ->where('is_affiliate_commission_settled', 0)
            ->whereNotNull('affiliate_token')
            ->where('affiliate_token', '!=', '')
            ->with('productVariant');

        $orderItems = $query->get();
        $walletUpdated = false;

        foreach ($orderItems as $item) {
            $productId = $item->productVariant->product_id ?? null;
            if (!$item->affiliate_id || !$item->affiliate_token || !$productId) continue;

            $affiliate = AffiliateTracking::where([
                'product_id' => $productId,
                'affiliate_id' => $item->affiliate_id,
                'token' => $item->affiliate_token,
            ])->first();

            if (!$affiliate) continue;

            $commissionPercent = floatval($affiliate->category_commission);
            $commissionAmount = ($item->sub_total * $commissionPercent) / 100;

            $msg = "Affiliate Commission for Order Item ID: {$item->id} and Product ID: {$productId}";
            $response = app(WalletService::class)->updateAffiliateWalletBalance(
                'credit',
                $item->affiliate_id,
                $commissionAmount,
                $productId,
                $msg,
                'order',
                $item->sub_total,
                $item->affiliate_token
            );

            if (!($response['error'] ?? true)) {
                $item->is_affiliate_commission_settled = 1;
                $item->save();
                $walletUpdated = true;
            }
        }

        return response()->json([
            'error' => !$walletUpdated,
            'message' => $walletUpdated
                ? 'Affiliate Commission Settled Successfully'
                : 'All affiliate commission settled',
        ]);
    }

    public function reports(Request $request)
    {
        $query = AffiliateUser::with('user')->orderBy('created_at', 'desc');

        if ($search = $request->input('search')) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $affiliates = $query->get();
        $languageCode = app(TranslationService::class)->getLanguageCode();

        $data = $affiliates->map(function ($affiliate) use ($languageCode) {
            $trackings = AffiliateTracking::with(['product.category'])
                ->where('affiliate_id', $affiliate->user_id)
                ->get();

            $products = $trackings->pluck('product')->filter()->unique('id');
            $categories = $products->pluck('category')->filter()->unique('id');
            $currencyDetails = fetchDetails(Currency::class, ['is_default' => 1], 'symbol');
            $currency = !$currencyDetails->isEmpty() ? $currencyDetails[0]->symbol : '';

            return [
                'id' => $affiliate->id,
                'user_name' => $affiliate->user->username ?? 'N/A',
                'profile_image' => app(MediaService::class)->getMediaImageUrl($affiliate->user->image ?? null, 'USER_IMG_PATH'),
                'email' => $affiliate->user->email ?? '',
                'clicks' => $trackings->sum('usage_count'),
                'commission' => $currency . number_format($trackings->sum('commission_earned'), 2),
                'status' => $affiliate->status,
                'products' => $products->map(function ($p) use ($languageCode) {
                    return [
                        'name' => app(TranslationService::class)->getDynamicTranslation(Product::class, 'name', $p->id, $languageCode),
                        'image' => app(MediaService::class)->getMediaImageUrl($p->image ?? null),
                    ];
                }),
                'categories' => $categories->map(function ($c) use ($languageCode) {
                    return [
                        'name' => app(TranslationService::class)->getDynamicTranslation(Category::class, 'name', $c->id, $languageCode),
                        'image' => app(MediaService::class)->getMediaImageUrl($c->image ?? null),
                    ];
                }),
                'created_at' => optional($affiliate->created_at)->format('Y-m-d'),
            ];
        });

        return view('admin.pages.tables.affiliate_reports', compact('data'));
    }
}
