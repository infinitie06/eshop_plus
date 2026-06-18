<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\Admin\HomeController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\Webhook;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\InstallerController;
use App\Http\Controllers\Seller\MediaController as SellerMediaController;
use App\Http\Controllers\Seller\AreaController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\StoreController;
use App\Http\Controllers\Seller\CategoryController;
use App\Http\Controllers\Admin\AffiliateController;
use App\Http\Controllers\DeepLinkRedirectController;

use App\Jobs\SendOrderNotificationJob;
use Imagine\Filter\Basic\Rotate;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

// ---------------------------------------------------------------------------------------------------------------------------
Route::get('/sitemap', function () {
    Artisan::call('sitemap:generate');
    return redirect()->back()->with('message', 'Sitemap generated successfully!');
});
Route::get('/clear-cache', function () {
    Artisan::call('optimize:clear');
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    return redirect()->back()->with('message', 'Cache cleared successfully.');
});
Route::get('/optimize-prod', function () {
    Artisan::call('optimize:clear');
    Artisan::call('config:cache');
    Artisan::call('view:cache');

    // Ensure on-disk dynamic image cache directory exists so the resizer
    // can write to it without the @mkdir silently failing on first request.
    $cacheDir = public_path('storage/cache/dynamic_image');
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0775, true);
    }

    return response()->json([
        'message' => 'Production caches rebuilt: config + views cached, image cache dir ready.',
        'cache_dir_exists' => is_dir($cacheDir),
        'cache_dir_writable' => is_writable($cacheDir),
        'next_step' => url('/warmup-images') . ' (run this once to pre-generate all product image thumbnails)',
    ]);
});

// Pre-generates the resized+compressed disk cache for every site image at the
// common widths used in the views, so visitors are served already-cached files
// straight from Apache instead of triggering Laravel + Intervention on first hit.
// Walks through stages: sliders -> categories -> brands -> category_sliders ->
// sections -> combo products -> products. Auto-redirects between stages/batches.
Route::get('/warmup-images', function (\Illuminate\Http\Request $request) {
    @set_time_limit(0);
    @ini_set('memory_limit', '512M');

    $stage = $request->input('stage', 'sliders');
    $batch = max(1, (int) $request->input('batch', 1));
    $batchSize = 20;

    $service = app(\App\Services\MediaService::class);
    $client = new \GuzzleHttp\Client([
        'verify' => false,
        'timeout' => 30,
        'http_errors' => false,
    ]);

    $cached = 0; $skipped = 0; $errors = 0;

    $warm = function ($rawImage, array $widths) use ($service, $client, &$cached, &$skipped, &$errors) {
        if (empty($rawImage)) { return; }
        foreach ($widths as $w) {
            $url = $service->dynamic_image($rawImage, $w);
            if (!str_contains($url, '/media/image')) { $skipped++; continue; }
            try { $client->get($url); $cached++; }
            catch (\Exception) { $errors++; }
        }
    };

    $totalForStage = 0;
    $processedSoFar = 0;
    $hasMoreInStage = false;

    switch ($stage) {
        case 'sliders':
            // Slider banner images appear at the top of the homepage at width ~620.
            $rows = \App\Models\Slider::all(['id', 'image']);
            $totalForStage = $rows->count();
            foreach ($rows as $r) { $warm($r->image, [620]); }
            $processedSoFar = $totalForStage;
            break;

        case 'categories':
            // Category cards use 400; banners on listing pages use 400.
            $rows = \App\Models\Category::all(['id', 'image', 'banner']);
            $totalForStage = $rows->count();
            foreach ($rows as $r) {
                $warm($r->image, [200, 400]);
                $warm($r->banner, [400, 800]);
            }
            $processedSoFar = $totalForStage;
            break;

        case 'brands':
            $rows = \App\Models\Brand::all(['id', 'image']);
            $totalForStage = $rows->count();
            foreach ($rows as $r) { $warm($r->image, [200, 400]); }
            $processedSoFar = $totalForStage;
            break;

        case 'category_sliders':
            $rows = \App\Models\CategorySliders::all(['id', 'banner_image']);
            $totalForStage = $rows->count();
            foreach ($rows as $r) { $warm($r->banner_image, [620]); }
            $processedSoFar = $totalForStage;
            break;

        case 'sections':
            $rows = \App\Models\Section::all(['id', 'banner_image']);
            $totalForStage = $rows->count();
            foreach ($rows as $r) { $warm($r->banner_image, [800]); }
            $processedSoFar = $totalForStage;
            break;

        case 'combo_products':
            $totalForStage = \App\Models\ComboProduct::count();
            $rows = \App\Models\ComboProduct::orderBy('id')
                ->skip(($batch - 1) * $batchSize)->take($batchSize)
                ->get(['id', 'image', 'other_images']);
            foreach ($rows as $r) {
                $imgs = collect([$r->image]);
                if (!empty($r->other_images)) {
                    $more = is_array($r->other_images) ? $r->other_images : json_decode($r->other_images, true);
                    if (is_array($more)) { $imgs = $imgs->merge($more); }
                }
                foreach ($imgs->filter()->unique() as $img) {
                    $warm($img, [200, 400, 600, 800]);
                }
            }
            $processedSoFar = min($batch * $batchSize, $totalForStage);
            $hasMoreInStage = ($batch * $batchSize) < $totalForStage;
            break;

        case 'products':
            $totalForStage = \App\Models\Product::count();
            $rows = \App\Models\Product::orderBy('id')
                ->skip(($batch - 1) * $batchSize)->take($batchSize)
                ->get(['id', 'image', 'other_images']);
            foreach ($rows as $r) {
                $imgs = collect([$r->image]);
                if (!empty($r->other_images)) {
                    $more = is_array($r->other_images) ? $r->other_images : json_decode($r->other_images, true);
                    if (is_array($more)) { $imgs = $imgs->merge($more); }
                }
                foreach ($imgs->filter()->unique() as $img) {
                    // Card thumbs (450, 650), detail page (600, 800), small variants (200, 400)
                    $warm($img, [200, 400, 450, 600, 650, 800]);
                }
            }
            $processedSoFar = min($batch * $batchSize, $totalForStage);
            $hasMoreInStage = ($batch * $batchSize) < $totalForStage;
            break;

        default:
            return response()->json(['status' => 'done', 'message' => 'Warmup complete.']);
    }

    // Decide what's next: continue current stage, advance to next stage, or finish.
    $stageOrder = ['sliders', 'categories', 'brands', 'category_sliders', 'sections', 'combo_products', 'products'];
    $nextStage = null;
    $nextBatch = 1;

    if ($hasMoreInStage) {
        $nextStage = $stage;
        $nextBatch = $batch + 1;
    } else {
        $idx = array_search($stage, $stageOrder, true);
        if ($idx !== false && isset($stageOrder[$idx + 1])) {
            $nextStage = $stageOrder[$idx + 1];
        }
    }

    if ($nextStage !== null) {
        $nextUrl = url('/warmup-images?stage=' . $nextStage . '&batch=' . $nextBatch);
        $progress = $totalForStage > 0 ? (int) round(($processedSoFar / $totalForStage) * 100) : 100;
        $html = '<!doctype html><html><head><meta charset="utf-8">'
            . '<meta http-equiv="refresh" content="1;url=' . htmlspecialchars($nextUrl, ENT_QUOTES) . '">'
            . '<title>Warming image cache: ' . htmlspecialchars($stage) . ' ' . $progress . '%</title>'
            . '<style>body{font:14px/1.5 system-ui,sans-serif;max-width:600px;margin:60px auto;padding:0 20px}'
            . 'h2{margin-bottom:4px}.muted{color:#777;font-size:13px}'
            . '.bar{height:14px;background:#eee;border-radius:7px;overflow:hidden;margin:12px 0}'
            . '.bar>span{display:block;height:100%;background:#22a06b;transition:width .3s}'
            . 'ul{padding-left:18px}li{margin:2px 0}</style></head><body>'
            . '<h2>Warming image cache</h2>'
            . '<div class="muted">Stage: <b>' . htmlspecialchars($stage) . '</b> &middot; batch ' . $batch . '</div>'
            . '<div class="bar"><span style="width:' . $progress . '%"></span></div>'
            . '<p>' . $processedSoFar . ' / ' . $totalForStage . ' rows in this stage</p>'
            . '<ul><li>New images cached this batch: <b>' . $cached . '</b></li>'
            . '<li>Already cached (skipped): ' . $skipped . '</li>'
            . '<li>Errors: ' . $errors . '</li></ul>'
            . '<p class="muted">Auto-continuing in 1s. <a href="' . htmlspecialchars($nextUrl, ENT_QUOTES) . '">Click if it stalls.</a></p>'
            . '</body></html>';
        return response($html);
    }

    return response()->json([
        'status' => 'done',
        'message' => 'All site images pre-cached across sliders, categories, brands, category sliders, sections, combo products, and products. Visitors are now served compressed cached images directly from Apache.',
        'last_stage_cached' => $cached,
        'last_stage_skipped' => $skipped,
        'last_stage_errors' => $errors,
    ]);
});
Route::get('/clear-logs', function () {
    $logPath = storage_path('logs/laravel.log');
    if (file_exists($logPath)) {
        file_put_contents($logPath, '');
    }
    return redirect()->back()->with('message', 'Log file cleared successfully.');
});
Route::get('/version', function () {
    return app()->version();
});

Route::get('storage-link', function () {
    try {
        $target = storage_path('app/public');
        $link = public_path('storage');

        // Always copy the directory, even if the link/folder exists
        if (file_exists($link) && is_link($link)) {
            // Remove the existing symlink if it exists
            unlink($link);
            print_r('Symlink removed, now copying files to public/storage.<br>');
        } elseif (file_exists($link) && is_dir($link)) {
            // Remove the existing directory if it's not a symlink
            File::deleteDirectory($link);
            print_r('Directory removed, now copying files to public/storage.<br>');
        }

        // Attempt to create symbolic link first
        if (function_exists('symlink')) {
            symlink($target, $link);
            return 'Symlink created successfully.';
        } else {
            // If symlink creation fails, fallback to copying files
            File::copyDirectory($target, $link);
            return 'Files copied to public/storage (symlink not available).';
        }
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});

Route::get('/install', [InstallerController::class, 'index'])->middleware('guest');

Route::post('/installer/config-db', [InstallerController::class, 'config_db'])->middleware('guest');

Route::post('/installer/install', [InstallerController::class, 'install'])->middleware('guest');

Route::get('admin/web_product_card_style', [StoreController::class, 'webProductCardStyle']);
Route::get('admin/web_categories_style', [StoreController::class, 'webCategoriesStyle']);
Route::get('admin/web_brands_style', [StoreController::class, 'webBrandsStyle']);
Route::get('admin/web_wishlist_style', [StoreController::class, 'webWishlistStyle']);
Route::get('admin/web_home_page_theme', [StoreController::class, 'webHomePageTheme']);
Route::get('/media/list', [MediaController::class, 'list'])->name('admin.media.list');
Route::get('/manifest', function () {
    return response()->json(config('manifest'));
})->name('manifest');



Route::get('/publish-livewire-assets', function () {
    Artisan::call('vendor:publish', ['--tag' => 'livewire:assets']);
    return 'Livewire assets published successfully.';
});
Route::middleware(['CheckInstallation'])->group(function () {
    // Check if the frontend product details component physically exists
    // This is safer than class_exists because it doesn't trigger the autoloader
    $frontEndExists = file_exists(app_path('Livewire/Products/Details.php'));

    Route::get('/', function () {
        return redirect()->route('admin.home');
    });
    Route::get('admin/register', [UserController::class, 'create']);

    Route::post('admin/users', [UserController::class, 'store']);

    Route::get('admin/logout', [UserController::class, 'logout'])->name('admin.logout');

    Route::post('/admin/users/authenticate', [UserController::class, 'authenticate'])->name('admin.authenticate');
    Route::get('admin/home', [HomeController::class, 'index'])->name('admin.home');
    Route::get('admin/login', function () {
        if (Auth::check()) {
            return redirect()->route('admin.home');
        }
        return app(UserController::class)->login();
    })->name('admin.login');



    // Routs for forgot password and reset password

    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password-mail', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [ForgotPasswordController::class, 'showResetPasswordForm'])->name('password.reset');
    Route::post('/reset-password', [ForgotPasswordController::class, 'ResetPassword'])->name('admin.password.update');

    Route::get('/admin', function () {
        if (Auth::check()) {
            //dd('here');
            // User is logged in, redirect to the admin home page
            return redirect()->route('admin.home');
            // return redirect()->route('admin.login');
        } else {
            // User is not logged in, redirect to the admin login page
            return redirect()->route('admin.login');
        }
    });

    // Deep Link Catch-all for App-Only Redirect
    // The 'where' constraint ensures reserved/list-type slugs (e.g. "list", "search", "all", etc.)
    // are NOT captured by the deep link handler.
    if (!$frontEndExists) {
        $excludedSlugs = 'list|search|all|index|page|filter|category|tag';
        Route::get('/combo-products/{slug}', [DeepLinkRedirectController::class, 'handle'])
            ->defaults('type', 'combo-products')
            ->where('slug', '^(?!(' . $excludedSlugs . ')$).+');
        Route::get('/products/{slug}', [DeepLinkRedirectController::class, 'handle'])
            ->defaults('type', 'products')
            ->where('slug', '^(?!(' . $excludedSlugs . ')$).+');
        Route::get('/sellers/{slug}', [DeepLinkRedirectController::class, 'handle'])
            ->defaults('type', 'sellers')
            ->where('slug', '^(?!(' . $excludedSlugs . ')$).+');
        Route::get('/blogs/{slug}', [DeepLinkRedirectController::class, 'handle'])
            ->defaults('type', 'blogs')
            ->where('slug', '^(?!(' . $excludedSlugs . ')$).+');
    }

    // seller routes
    Route::get('/seller', function () {
        return redirect()->route('seller.login');
    });

    Route::get('seller/login', [UserController::class, 'seller_login'])->name('seller.login');
    Route::get('seller/register', [UserController::class, 'seller_register'])->name('seller.register');
    Route::get('seller/get_zones', [AreaController::class, 'get_zones'])->name('seller.get_zones');
    Route::get('seller/logout', [UserController::class, 'seller_logout'])->name('seller.logout');
    Route::get('seller/categories/get_category_details', [CategoryController::class, 'getCategoryDetails']);
    Route::post('seller/store', [UserController::class, 'sellerStore'])->name('seller.register.store')->middleware(['demo_restriction']);

    // delivery boy routes
    Route::get('/delivery_boy', function () {
        return redirect()->route('delivery_boy.login');
    });

    Route::get('delivery_boy/login', [UserController::class, 'delivery_boy_login'])->name('delivery_boy.login');
    Route::get('delivery_boy/logout', [UserController::class, 'delivery_boy_logout'])->name('delivery_boy.logout');

    // affiliate routes
    Route::get('affiliate/login', [UserController::class, 'affiliate_login'])->name('affiliate.login');
    Route::get('affiliate/logout', [UserController::class, 'affiliate_logout'])->name('affiliate.logout');
    Route::get('affiliate/register', [UserController::class, 'affiliate_register'])->name('affiliate.register');

    // system policies pages
    Route::get("settings/seller_privacy_policy", [SettingController::class, 'sellerPrivacyPolicy'])->name('seller_privacy_policy.view');

    Route::get("admin/privacy_policy/privacy_policy_page", [SettingController::class, 'privacy_policy'])->name('privacy_policy.view');

    Route::get("admin/terms_and_conditions/terms_and_condition_page", [SettingController::class, 'terms_and_conditions'])->name('terms_and_conditions.view');

    Route::get("admin/shipping_policy/shipping_policy_page", [SettingController::class, 'shipping_policy'])->name('shipping_policy.view');

    Route::get("admin/return_policy/return_policy_page", [SettingController::class, 'return_policy'])->name('return_policy.view');

    //admin & seller policies page

    Route::get("admin/privacy_policy/seller_privacy_policy_page", [SettingController::class, 'sellerPrivacyPolicy']);









    Route::get("admin/terms_and_condition/seller_terms_and_condition_page", [SettingController::class, 'seller_terms_and_condition'])->name('seller_terms_and_conditions.view');

    // delivery boy policies page

    Route::get("admin/privacy_policy/delivery_boy_privacy_page", [SettingController::class, 'delivery_boy_privacy_policy'])->name('delivery_boy_privacy_policy.view');

    Route::get("admin/terms_and_conditions/delivery_boy_terms_and_condition_page", [SettingController::class, 'delivery_boy_terms_and_conditions'])->name('delivery_boy_terms_and_conditions.view');

    // admin routes file

    Route::group(['middleware' => ['auth']], function () {
        // Routes that only admins can access
        include_once("admin_routes.php");
        include_once("seller_routes.php");
        include_once("delivery_boy_routes.php");
        include_once("affiliate_routes.php");
    });

    Route::get('admin/media/image', [MediaController::class, 'dynamic_image'])->name('admin.dynamic_image');
    Route::get('/media/image', [MediaController::class, 'dynamic_image'])->name('front_end.dynamic_image');

    // media route

    Route::get('/admin/media/list', [MediaController::class, 'list'])->name('admin.media.list');

    Route::get('/seller/media/list', [SellerMediaController::class, 'list'])->name('seller.media.list');

    if ($frontEndExists) {
        include_once("front_end_routes.php");
    }

    //webhook route

    Route::post('admin/webhook/razorpay_webhook', [Webhook::class, 'razorpay_webhook'])->name('admin.razorpay_webhook');
    Route::post('admin/webhook/paystack_webhook', [Webhook::class, 'paystack_webhook'])->name('admin.paystack_webhook');
    Route::post('admin/webhook/stripe_webhook', [Webhook::class, 'stripe_webhook'])->name('admin.stripe_webhook');
    Route::post('/admin/webhook/phonepe_webhook', [Webhook::class, 'phonepe_webhook']);

    Route::get('admin/webhook/spr_webhook', [Webhook::class, 'spr_webhook'])->name('admin.spr_webhook');
});
Route::get('admin/orders/generat_invoice_PDF/{id}/{user_id}', [OrderController::class, 'generatInvoicePDF'])
    ->name('admin.orders.generatInvoicePDF');
Route::get('admin/orders/generat_app_invoice_PDF/{id}/{user_id}/{path}', [OrderController::class, 'generatInvoicePDF'])
    ->name('admin.orders.generatAPPInvoicePDF');
Route::get('/admin/stores', [StoreController::class, 'index'])->name('admin.stores.index');
Route::post('admin/store', [StoreController::class, 'store'])->middleware(['demo_restriction'])->middleware('permissions:create store')->name('admin.stores.store');
Route::get("settings/registration", [SettingController::class, 'registration'])->name('admin.system_registration');
Route::post("settings/system_registration", [SettingController::class, 'systemRegister'])->name('admin.system_register')->middleware(['demo_restriction']);
Route::post("settings/web_system_registration", [SettingController::class, 'WebsystemRegister'])->name('admin.web_system_register')->middleware(['demo_restriction']);
Route::post('/affiliate_users/store', [AffiliateController::class, 'store'])->name('admin.affiliate_users.register');

Route::post('add_to_favorites', [ProductController::class, 'add_to_favorites'])
    ->name('add_to_favorites');

Route::post('remove_from_favorite', [ProductController::class, 'remove_from_favorite'])
    ->name('remove_from_favorite');
Route::get('/test-mail', function () {
    try {
        \Mail::raw('SMTP test successful.', function ($message) {
            $message->to('infinitie.raj@gmail.com')
                ->subject('SMTP Test');
        });
        return 'Mail sent successfully';
    } catch (\Exception $e) {
        return 'Mail failed: ' . $e->getMessage();
    }
});

// Route::get('sellers', [HomeController::class, 'getSellersDebug'])->name('get.sellers');
Route::get('/run-queue', function () {
    Artisan::call('queue:work', ['--once' => true]);

});
Route::get('/test-job', function () {
    SendOrderNotificationJob::dispatch(1); // pass test order ID
    return 'Job dispatched successfully';
});

Route::get('/queue-work', function () {
    Artisan::call('queue:work', ['--stop-when-empty' => true]);
});

// Deep Link Testing Page
Route::get('/test-deep-links', function () {
    $products = \App\Models\Product::limit(5)->get(['id', 'slug', 'name', 'store_id']);
    $sellers = \App\Models\Store::limit(5)->get(['id', 'slug', 'name']);
    $blogs = \App\Models\Blog::where('status', 1)->limit(5)->get(['id', 'slug', 'title']);

    return view('test-deep', compact('products', 'sellers', 'blogs'));
})->name('test.deep.links');

// =======================================

