<?php

namespace App\Http\Controllers\Admin;

use App\Models\Setting;
use App\Models\Category;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use App\Traits\HandlesValidation;
use App\Services\SettingService;
use App\Http\Controllers\Admin\CategoryController;
use Illuminate\Support\Facades\Validator;

class AffiliateSettingController extends Controller
{
    use HandlesValidation;
    public function index()
    {
        $categoryController = new CategoryController();
        $categoriesResponse = $categoryController->get_all_categories();
        $categories = $categoriesResponse->original['categories']->isEmpty() ? [] : $categoriesResponse->original['categories'];

        $affiliateSettings = app(SettingService::class)->getSettings('affiliate_settings', true);
        $affiliateSettings = json_decode($affiliateSettings, true);

        // Convert to array if it's a collection
        $stack = $categories instanceof \Illuminate\Support\Collection ? $categories->all() : $categories;

        $affiliateCommissions = [];
        $processed = []; // track visited categories

        while (!empty($stack)) {
            $cat = array_shift($stack);

            // Skip if already processed
            if (in_array($cat->id, $processed)) {
                continue;
            }
            $processed[] = $cat->id;

            // Store commission if exists
            if (!empty($cat->affiliate_commission)) {
                $affiliateCommissions[] = [
                    'category_id' => $cat->id,
                    'commission' => $cat->affiliate_commission,
                ];
            }

            // Add children to stack if available
            if (!empty($cat->children)) {
                $children = $cat->children instanceof \Illuminate\Support\Collection
                    ? $cat->children->all()
                    : $cat->children;

                foreach ($children as $child) {
                    if (!in_array($child->id, $processed)) {
                        $stack[] = $child;
                    }
                }
            }
        }

        // Extract unique category IDs used
        $usedValues = array_unique(array_column($affiliateCommissions, 'category_id'));

        return view('admin.pages.forms.affiliate_settings', compact(
            'categories',
            'affiliateSettings',
            'affiliateCommissions',
            'usedValues'
        ));
    }

    public function store(Request $request)
    {
        $rules = [
            'max_amount_for_withdrawal_request' => 'required|numeric|min:1|gte:min_amount_for_withdrawal_request',
            'min_amount_for_withdrawal_request' => 'required|numeric|min:1',
        ];

        if ($response = $this->HandlesValidation($request, $rules)) {

            return $response;
        }

        $data = [
            'variable' => 'affiliate_settings',
            'value' => json_encode([
                'max_amount_for_withdrawal_request' => $request->max_amount_for_withdrawal_request,
                'min_amount_for_withdrawal_request' => $request->min_amount_for_withdrawal_request,
            ], JSON_UNESCAPED_SLASHES),
        ];

        $setting_data = Setting::where('variable', 'affiliate_settings')->first();

        if ($setting_data === null) {
            Setting::create($data);
            $message = labels('admin_labels.settings_inserted_successfully', 'Settings inserted successfully');
        } else {
            $setting_data->update($data);
            $message = labels('admin_labels.settings_updated_successfully', 'Settings updated successfully');
        }

        if ($request->ajax()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->back()->with('success', $message);
    }



    public function updateCommission(Request $request)
{
    $rules = [
        'category_id' => 'required|array',
        'category_id.*' => 'required|exists:categories,id',
        'commission' => 'required|array',
        'commission.*' => 'nullable|numeric|min:0|max:100',
    ];

    if ($response = $this->HandlesValidation($request, $rules)) {
        return $response; // This will trigger only if real validation fails
    }

    $categoryIds = $request->input('category_id', []);
    $commissions = $request->input('commission', []);

    // Reset commission for non-selected categories
    Category::whereNotIn('id', $categoryIds)
        ->where('affiliate_commission', '>', 0)
        ->update(['affiliate_commission' => 0, 'is_in_affiliate' => 0]);

    // Update commissions
    foreach ($categoryIds as $index => $catId) {
        $commission = isset($commissions[$index]) && $commissions[$index] !== ''
            ? $commissions[$index]
            : 0;

        Category::where('id', $catId)->update([
            'affiliate_commission' => $commission,
            'is_in_affiliate' => $commission > 0 ? 1 : 0,
        ]);
    }

    return response()->json([
        'success' => true,
        'message' => 'Commission updated successfully!',
    ]);
}


    public function policies()
    {
        $termsAndConditions = app(SettingService::class)->getSettings('affiliate_terms_and_conditions', true);
        $termsAndConditions = json_decode($termsAndConditions, true);
        $privacyPolicy = app(SettingService::class)->getSettings('affiliate_privacy_policy', true);
        $privacyPolicy = json_decode($privacyPolicy, true);
        return view('admin.pages.forms.affiliate_policies', compact('termsAndConditions', 'privacyPolicy'));
    }

    public function storeAffiliatePrivacyPolicy(Request $request)
    {
        $rules = [
            'affiliate_privacy_policy' => 'required|string',
        ];
        $messages = [
            'affiliate_privacy_policy.required' => 'Affiliate privacy policy is required.',
        ];

        if ($response = $this->HandlesValidation($request, $rules, $messages)) {
            return $response;
        }

        Setting::updateOrCreate(
            ['variable' => 'affiliate_privacy_policy'],
            ['value' => json_encode(['affiliate_privacy_policy' => $request->affiliate_privacy_policy], JSON_UNESCAPED_SLASHES)]
        );

        return response()->json([
            'message' => labels('admin_labels.settings_inserted_successfully', 'Settings updated successfully')
        ]);
    }
    public function storeAffiliateTermsAndConditions(Request $request)
    {
        $rules = [
            'affiliate_terms_and_conditions' => 'required|string',
        ];
        $messages = [
            'affiliate_terms_and_conditions.required' => 'Affiliate terms and conditions are required.',
        ];

        if ($response = $this->HandlesValidation($request, $rules, $messages)) {
            return $response;
        }

        Setting::updateOrCreate(
            ['variable' => 'affiliate_terms_and_conditions'],
            ['value' => json_encode(['affiliate_terms_and_conditions' => $request->affiliate_terms_and_conditions], JSON_UNESCAPED_SLASHES)]
        );

        return response()->json([
            'message' => labels('admin_labels.settings_inserted_successfully', 'Settings updated successfully')
        ]);
    }
}
