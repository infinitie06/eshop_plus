<?php

use App\Http\Controllers\Admin\LanguageController;
use App\Http\Controllers\Affiliate\HomeController;
use App\Http\Controllers\Affiliate\TransactionController;
use App\Http\Controllers\Affiliate\UserController;
use App\Http\Controllers\Affiliate\ProductController;
 Route::get('/affiliate/top_categories', [HomeController::class, 'getTopCategories'])->name('affiliate.topCategories');
Route::group(
    ['middleware' => ['auth', 'role:affiliate']],
    function () {
        // Route::get('affiliate/home', [HomeController::class, 'index'])->name('affiliate.home');

        Route::get('affiliate/home', [HomeController::class, 'index'])->name('affiliate.home');

        Route::get('affiliate/account/{user}', [UserController::class, 'edit'])->name('affiliate.edit');
   Route::get('affiliate/settings/languages/change', [LanguageController::class, 'change'])->name('changeLang');

        Route::put('affiliate/users/update/{user}', [UserController::class, 'update'])->middleware(['demo_restriction']);

        Route::get('/affiliate/category_earnings', [HomeController::class, 'categoryEarnings'])->name('affiliate.category_earnings');
        Route::get('/affiliate/monthly_earnings', [HomeController::class, 'monthlyEarnings'])->name('affiliate.monthly_earnings');
        Route::get('/affiliate_products/category/{id}', [HomeController::class, 'showByCategory'])
        ->name('affiliate.products.category');
        Route::post('/affiliate/generate_token', [ProductController::class, 'generateToken'])
        ->name('affiliate.generate_token');
        Route::get('/affiliate/promoted_products', [ProductController::class, 'PromotedProducts'])->name('affiliate.promoted_products');
        Route::get('/affiliate/promoted_products/list', [ProductController::class, 'PromotedProductlist'])->name('affiliate.promoted_products.list');

        Route::get('/affiliate/categories', [HomeController::class, 'getCategories'])->name('affiliate.categories');

        Route::get('/affiliate/policies', [HomeController::class, 'getPolicies'])->name('affiliate.policies');



        Route::get('/affiliate/request_payment', [TransactionController::class, 'index'])->name('affiliate.request_payment');

        Route::get('affiliate/payment_request/get_payment_request_list', [TransactionController::class, 'getWithdrawalRequests'])->name('affiliate.payment_request.get_payment_request_list');
        Route::put('affiliate/payment_request/add_withdrawal_request', [TransactionController::class, 'addWithdrawalRequest'])->name('affiliate.payment_request.add_withdrawal_request')->middleware(['demo_restriction']);

        Route::get('affiliate/transactions', [TransactionController::class, 'transactions'])->name('affiliate.transactions');
        Route::get('affiliate/get_transactions', [TransactionController::class, 'getTransactions'])->name('affiliate.get_transactions');
    }

);
