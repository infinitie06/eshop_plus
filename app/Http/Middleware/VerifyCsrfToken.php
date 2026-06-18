<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        "admin/settings/*",
        "/payments/response*",
        "/cart*",
        "/installer*",
        "admin/webhook*",
        "webhook/*",
        "auth/google/callback*",
        "auth/facebook/callback*",
        "payments/stripe-response*",
        "admin/webhook*",
        "admin/webhook/razorpay_webhook",

        'admin/webhook/phonepe_webhook',
    ];
}
