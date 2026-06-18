<div id="page-content">
    <x-utility.breadcrumbs.breadcrumbTwo :$bread_crumb />

    <!--Main Content-->
    @php
        use App\Models\City;
        use App\Models\Promocode;
        use App\Services\MediaService;
        use App\Services\TranslationService;
        use App\Services\CurrencyService;
        use App\Services\SettingService;
        $language_code = app(TranslationService::class)->getLanguageCode();
    @endphp
    <style>
        .delivery-options-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .delivery-option-card {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            background: #fff;
            text-align: center;
        }

        .delivery-option-card:hover {
            border-color: #3b82f6;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .delivery-option-card.active {
            border-color: #3b82f6;
            background-color: #eff6ff;
        }

        .delivery-option-card input[type="radio"] {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }

        .delivery-option-card .option-label {
            display: block;
            font-weight: 700;
            color: #1f2937;
            text-transform: capitalize;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .delivery-option-card .option-price {
            display: block;
            font-size: 16px;
            font-weight: 800;
            color: #3b82f6;
            margin-bottom: 5px;
        }

        .delivery-option-card .option-speed {
            display: block;
            font-size: 12px;
            color: #6b7280;
        }

        .delivery-option-card .badge-type {
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            background: #3b82f6;
            color: white;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .delivery-option-card.cheapest .badge-type {
            background: #10b981;
        }

        .delivery-option-card.fastest .badge-type {
            background: #f59e0b;
        }

        .delivery-option-card.recommended .badge-type {
            background: #3b82f6;
        }

        /* Place Order Button Disabled State */
        #place_order_btn:disabled,
        #place_order_btn.disabled {
            opacity: 0.6 !important;
            cursor: not-allowed !important;
            pointer-events: none !important;
        }

        #place_order_btn:disabled:hover,
        #place_order_btn.disabled:hover {
            opacity: 0.6 !important;
        }

        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
            border-width: 0.2em;
        }
    </style>
    <div class="container-fluid">
        <!--Checkout Content-->
        @if (count($cart_data['cart_items'] ?? []) > 0)
            <form action="{{ Route('cart.place_order') }}" method="POST" id="place_order_form">
                @csrf
                <div class="row">
                    <div class="col-lg-6 col-md-6 col-sm-12">
                        @php
                            $item = $cart_data['cart_items'][0];
                            $product = $item['product'] ?? ($item['comboproduct'] ?? null);
                            $productType = $product['type'] ?? '';
                            $cartProductType = $item['product_type'] ?? '';
                        @endphp
                        @if ($productType != 'digital_product' && ($productType != 'combo' || $cartProductType != 'digital_product'))

                            <div class="block mb-3 shipping-address mb-4">
                                <div class="address-book-section dashboard-content">
                                    <div class="address-select-box active">
                                        <div class="address-box bg-block">
                                            @if (isset($default_address[0]) && !empty($default_address[0]))
                                                <div class="top d-flex-justify-center justify-content-between mb-3">
                                                    <input type="hidden" name="selected_address_id"
                                                        id="selected_address_id"
                                                        value="{{ (int) @$default_address[0]->id }}">
                                                    <input type="hidden" name="address-mobile" id="address-mobile"
                                                        value="{{ @$default_address[0]->mobile }}">
                                                    <h5 class="m-0" id="address-name">
                                                        {{ @$default_address[0]->name }}
                                                    </h5>
                                                    <span class="product-labels start-auto end-0"><span
                                                            class="lbl pr-label1"
                                                            id="address-type">{{ @$default_address[0]->type }}</span>
                                                </div>

                                                <div class="middle">
                                                    <div class="address mb-2 text-muted">
                                                        <address class="m-0" id="address">
                                                            {{ @$default_address[0]->address }},
                                                            <br />{{ isset($default_address) && !empty($default_address[0]->landmark) ? "{$default_address[0]->landmark} " . app(TranslationService::class)->getDynamicTranslation(City::class, 'name', $default_address[0]->city_id, $language_code) : '' }}

                                                            <br />{{ @$default_address[0]->state . ' ' . @$default_address[0]->country . ' ' . @$default_address[0]->pincode }}.
                                                        </address>


                                                    </div>
                                                    <div class="number">
                                                        <p>{{ labels('front_messages.mobile', 'Mobile') }}: <span
                                                                id="">{{ @$default_address[0]->mobile }}</span>
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="bottom d-flex-justify-center justify-content-between">
                                                    @php
                                                        $allDeliverable = true; // Assume all are deliverable initially
                                                        foreach ($product_availability as $product) {
                                                            if (!$product['is_deliverable']) {
                                                                $allDeliverable = false;
                                                                break;
                                                            }
                                                        }
                                                    @endphp
                                                    @if ($allDeliverable)
                                                        <p class="m-0 fw-500 text-capitalize text-success">
                                                            {{ labels('front_messages.all_products_deliverable_on_selected_address', 'All the products are deliverable on the selected address') }}
                                                        </p>
                                                    @else
                                                        <div class="w-100">
                                                            <p class="m-0 fw-500 text-capitalize text-danger mb-2">
                                                                {{ labels('front_messages.some_products_not_deliverable', 'Some Products Are Not Deliverable') }}
                                                            </p>
                                                            <div class="alert alert-warning p-2"
                                                                style="font-size: 12px;">
                                                                @foreach ($product_availability as $product)
                                                                    @if (!$product['is_deliverable'])
                                                                        <div class="mb-1">
                                                                            <strong>{{ $product['name'] ?? labels('front_messages.product', 'Product') }}:</strong>
                                                                            <span
                                                                                class="text-danger">{{ $product['message'] ?? labels('front_messages.not_deliverable', 'Not deliverable') }}</span>
                                                                        </div>
                                                                    @endif
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endif
                                                    <button type="button" class="bottom-btn btn btn-gray btn-sm"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#address-modal">{{ labels('front_messages.change', 'Change') }}</button>
                                                </div>
                                            @else
                                                <div class="d-flex justify-content-center align-content-center">
                                                    <p class="m-0 fw-600">
                                                        {{ labels('front_messages.address_is_not_added', 'Address is Not Added !!') }}
                                                    </p>
                                                </div>
                                                <div class="bottom d-flex-justify-center justify-content-end">
                                                    <a target="_blank" href="{{ customUrl('my-account/addresses') }}"
                                                        class="bottom-btn btn btn-gray btn-sm">{{ labels('front_messages.add_address', 'Add Address') }}</a>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if (!empty($shipping_options))
                                <div class="block mb-3 delivery-options mb-4">
                                    <div class="block-content">
                                        <h3 class="title mb-3 text-uppercase">
                                            {{ labels('front_messages.delivery_options', 'Delivery Options') }}
                                        </h3>
                                        <div class="delivery-options-container">
                                            @foreach ($shipping_options as $key => $option)
                                                @if ($option['rate'] >= 0)
                                                    <label
                                                        class="delivery-option-card {{ $key }} {{ $selected_shipping_method == $key ? 'active' : '' }}">
                                                        <span class="badge-type">{{ labels('front_messages.' . $key, ucfirst($key)) }}</span>
                                                        <input type="radio" wire:model.live="selected_shipping_method"
                                                            value="{{ $key }}">
                                                        <span class="option-label">{{ $option['courier'] }}</span>
                                                        <span class="option-price">
                                                            {{ is_array($option['format_rate']) ? $option['rate'] ?? 'N/A' : $option['format_rate'] }}
                                                        </span>
                                                        <span class="option-speed">
                                                            @if ($option['days'] > 0)
                                                                {{ $option['days'] }}
                                                                {{ labels('front_messages.days', 'Days') }}
                                                            @else
                                                                {{ $option['etd'] }}
                                                            @endif
                                                        </span>
                                                    </label>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if ($time_slot_config->is_time_slots_enabled == 10)
                                <div class="d-none block mb-3 order-comments mb-4">
                                    <div class="block-content">
                                        <h3 class="title mb-3 text-uppercase">
                                            {{ labels('front_messages.time_slot', 'Time Slot') }}</h3>
                                        <div class="form-group col-md-12 col-lg-12 col-xl-12 mb-0">
                                            <div wire:ignore
                                                class="date-time-picker input-group position-relative rounded-1">
                                                <div class="align-self-center input-group-prepend ps-1">
                                                    <ion-icon name="calendar-outline" class="fs-4"></ion-icon>
                                                </div>
                                                <input type="text" class="ms-1 form-control" id="datepicker">
                                                <input type="hidden" id="start_date" class="form-control float-right">
                                                <input type="hidden" name="delivery_date" id="delivery_date"
                                                    class="form-control float-right">
                                            </div>
                                            @foreach ($time_slots as $key => $time_slot)
                                                <div class="form-check ps-1">
                                                    <input class="form-check-input time-slot-inputs" type="radio"
                                                        name="delivery_time" id="flexRadioDefault-{{ $time_slot->id }}"
                                                        data-last_order_time="{{ $time_slot->last_order_time }}"
                                                        value="{{ $time_slot->last_order_time }}">
                                                    <label class="form-check-label"
                                                        for="flexRadioDefault-{{ $time_slot->id }}">
                                                        {{ $time_slot->title }}
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif
                            <input type="hidden" id="is_time_slots_enabled" value="0">
                            <input type="hidden" id="delivery_starts_from" value="0">
                            <input type="hidden" id="delivery_ends_in" value="0">
                            <div class="block mb-3 order-comments mb-4">
                                <div class="block-content">
                                    <h3 class="title mb-3 text-uppercase">
                                        {{ labels('front_messages.order_comment', 'Order Comment') }}</h3>
                                    <fieldset>
                                        <div class="row">
                                            <div class="form-group col-md-12 col-lg-12 col-xl-12 mb-0">
                                                <textarea name="order_note" id="order_note" class="resize-both form-control" rows="3"
                                                    placeholder="{{ labels('front_messages.place_your_comment_here', 'Place your comment here') }}"></textarea>
                                            </div>
                                        </div>
                                    </fieldset>
                                </div>
                            </div>
                        @else
                            <div class="block mb-3 order-comments mb-4">
                                <div class="block-content">
                                    <h3 class="title mb-3 text-uppercase">
                                        {{ labels('front_messages.email', 'Email') }}</h3>
                                    <input type="email" name="email" id="email"
                                        placeholder="{{ labels('front_messages.write_your_email_here', 'Write Your Email Here') }}">
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12">
                        <!--Pay with wallet-->
                        <div class="block mb-3 payment-methods mb-4">
                            <div class="block-content">
                                <h3 class="title mb-3 text-uppercase">
                                    {{ labels('front_messages.pay_with_wallet', 'Pay With Wallet') }}</h3>
                                <div class="payment-accordion">
                                    <div class="widget-content filter-size filterDD">
                                        <label
                                            class="d-flex align-items-center justify-content-start swatchLbl py-2 gap-3"
                                            for="wallet-pay" data-bs-toggle="tooltip" data-bs-placement="top"
                                            title="{{ labels('front_messages.pay_with_wallet', 'Pay With Wallet') }}">
                                            <input type="checkbox" value="" id="wallet-pay"
                                                data-wallet-balance="{{ $wallet_balance }}"
                                                {{ $wallet_balance <= 0 ? 'disabled' : '' }}>
                                            <div class="image payment-image">
                                                <img class="blur-up lazyload"
                                                    data-src="{{ asset('frontend/elegant/svgs/wallet.svg') }}"
                                                    src="{{ asset('frontend/elegant/svgs/wallet.svg') }}"
                                                    alt="quotes" width="80" height="70" />
                                            </div>
                                            <div>
                                                <p class="fw-600 fs-6 m-0">
                                                    {{ labels('front_messages.wallet', 'Wallet') }}
                                                </p>
                                                <p class="fw-400 m-0">
                                                    {{ labels('front_messages.balance', 'Balance') }}:
                                                    {{ app(CurrencyService::class)->currentCurrencyPrice($wallet_balance, true) }}
                                                </p>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!--Payment Methods-->
                        <div wire:ignore class="block mb-3 payment-methods mb-4 payment-type">
                            <div class="block-content">
                                <h3 class="title mb-3 text-uppercase">
                                    {{ labels('front_messages.payment_methods', 'Payment Methods') }}</h3>
                                @php
                                    $codAllowedForAll = collect($cart_data['cart_items'])->every(function ($item) {
                                        $product = $item['product'] ?? ($item['comboproduct'] ?? null);

                                        return $product &&
                                            isset($product['cod_allowed']) &&
                                            $product['cod_allowed'] == 1;
                                    });
                                @endphp
                                <div class="payment-accordion">
                                    @if (isset($payment_method->cod_method) && $payment_method->cod_method == 1 && $codAllowedForAll)
                                        @if ($cart_data['cart_items'][0]['product']['type'] != 'digital_product')
                                            <div class="form-check mb-2 d-flex align-items-center">
                                                <input class="form-check-input" type="radio" name="payment_method"
                                                    id="cod" value="cod"
                                                    wire:model.live="payment_method_type">
                                                <label class="form-check-label d-flex align-items-center ps-2"
                                                    for="cod" title="{{ labels('front_messages.cod', 'COD') }}">
                                                    <div class="image payment-image">
                                                        <img class="blur-up lazyload"
                                                            data-src="{{ asset('frontend/elegant/images/logo/cod.png') }}"
                                                            src="{{ asset('frontend/elegant/images/logo/cod.png') }}"
                                                            alt="{{ labels('front_messages.cash_on_delivery', 'Cash On Delivery') }}" />
                                                    </div>
                                                </label>
                                            </div>
                                        @endif
                                    @endif
                                    @if (isset($payment_method->phonepe_method) && $payment_method->phonepe_method == 1)
                                        <div class="form-check mb-2 d-flex align-items-center">
                                            <input class="form-check-input" type="radio" name="payment_method"
                                                id="phonepe" value="phonepe" wire:model.live="payment_method_type">
                                            <label class="form-check-label d-flex align-items-center ps-2"
                                                for="phonepe" value="phonepe" title="{{ labels('front_messages.phonepe', 'PhonePe') }}">
                                                <div class="image payment-image">
                                                    <img class="blur-up lazyload"
                                                        data-src="{{ asset('frontend/elegant/images/logo/PhonePe_Logo.png') }}"
                                                        src="{{ asset('frontend/elegant/images/logo/PhonePe_Logo.png') }}"
                                                        alt="PhonePe" />
                                                </div>
                                            </label>
                                        </div>
                                    @endif
                                    @if (isset($payment_method->paypal_method) && $payment_method->paypal_method == 1)
                                        <div class="form-check mb-2 d-flex align-items-center">
                                            <input class="form-check-input" type="radio" name="payment_method"
                                                id="paypal-payment" value="paypal"
                                                wire:model.live="payment_method_type">
                                            <label class="form-check-label d-flex align-items-center ps-2"
                                                for="paypal-payment" value="paypal" title="{{ labels('front_messages.paypal', 'PayPal') }}">
                                                <div class="image payment-image">
                                                    <img class="blur-up lazyload"
                                                        data-src="{{ asset('frontend/elegant/images/logo/paypal-Logo.png') }}"
                                                        src="{{ asset('frontend/elegant/images/logo/paypal-Logo.png') }}"
                                                        alt="Paypal" />
                                                </div>
                                            </label>
                                        </div>
                                    @endif
                                    @if (isset($payment_method->paystack_method) && $payment_method->paystack_method == 1)
                                        <div class="form-check mb-2 d-flex align-items-center">
                                            <input class="form-check-input" type="radio" name="payment_method"
                                                id="paystack-payment" value="paystack"
                                                wire:model.live="payment_method_type">
                                            <label class="form-check-label d-flex align-items-center ps-2"
                                                for="paystack-payment" value="paystack" title="{{ labels('front_messages.paystack', 'Paystack') }}">
                                                <div class="image payment-image">
                                                    <img class="blur-up lazyload"
                                                        data-src="{{ asset('frontend/elegant/images/logo/Paystack_Logo.png') }}"
                                                        src="{{ asset('frontend/elegant/images/logo/Paystack_Logo.png') }}"
                                                        alt="Paystack" />
                                                </div>
                                            </label>
                                        </div>
                                        <input type="hidden" name="paystack_public_key" id="paystack_public_key"
                                            value="{{ $payment_method->paystack_key_id ?? '' }}" />
                                    @endif
                                    @if (isset($payment_method->stripe_method) && $payment_method->stripe_method == 1)
                                        <div class="form-check mb-2 d-flex align-items-center">
                                            <input class="form-check-input" type="radio" name="payment_method"
                                                id="stripe-payment" value="stripe"
                                                wire:model.live="payment_method_type">
                                            <label class="form-check-label d-flex align-items-center ps-2"
                                                for="stripe-payment" value="stripe" title="{{ labels('front_messages.stripe', 'Stripe') }}">
                                                <div class="image payment-image">
                                                    <img class="blur-up lazyload"
                                                        data-src="{{ asset('frontend/elegant/images/logo/stripe_logo.png') }}"
                                                        src="{{ asset('frontend/elegant/images/logo/stripe_logo.png') }}"
                                                        alt="stripe" />
                                                </div>
                                            </label>
                                        </div>
                                    @endif
                                    <!-- dd($payment_method->razorpay_method); -->
                                    @if (isset($payment_method->razorpay_method) && $payment_method->razorpay_method == 1)
                                        <div class="form-check mb-2 d-flex align-items-center">
                                            <input class="form-check-input" type="radio" name="payment_method"
                                                id="razorpay-payment" value="razorpay"
                                                wire:model.live="payment_method_type">
                                            <label class="form-check-label d-flex align-items-center ps-2"
                                                for="razorpay-payment" value="razorpay" title="{{ labels('front_messages.razorpay', 'Razorpay') }}">
                                                <div class="image payment-image">
                                                    <img class="blur-up lazyload"
                                                        data-src="{{ asset('frontend/elegant/images/logo/razorpay_logo.png') }}"
                                                        src="{{ asset('frontend/elegant/images/logo/razorpay_logo.png') }}"
                                                        alt="razorpay" />
                                                </div>
                                            </label>
                                        </div>
                                    @endif
                                    @if (isset($payment_method->direct_bank_transfer_method) && $payment_method->direct_bank_transfer_method == 1)
                                        <div class="form-check mb-2 d-flex align-items-center">
                                            <input class="form-check-input" type="radio" name="payment_method"
                                                id="bank_transfer" value="bank_transfer"
                                                wire:model.live="payment_method_type">
                                            <label class="form-check-label d-flex align-items-center ps-2"
                                                for="bank_transfer" value="direct_bank_transfer"
                                                title="{{ labels('front_messages.direct_bank_transfer', 'Direct Bank Transfer') }}">
                                                <div class="image payment-image">
                                                    <img class="blur-up lazyload"
                                                        data-src="{{ asset('frontend/elegant/images/logo/bank_transfer.png') }}"
                                                        src="{{ asset('frontend/elegant/images/logo/bank_transfer.png') }}"
                                                        alt="bank_transfer" />
                                                </div>
                                            </label>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div id="bank_transfer_slide">
                            <?php if (isset($payment_method->direct_bank_transfer_method) && $payment_method->direct_bank_transfer_method == 1) { ?>
                            <div class="row">
                                <div class="alert alert-warning">
                                    <strong>{{ labels('front_messages.bank_payment_instructions_title', 'Instructions!') }}</strong>
                                    {{ labels('front_messages.bank_payment_instructions_part1', 'Make your payment directly into our account. Your order will not further proceed until the funds have cleared in our account.') }}
                                    <br>
                                    {{ labels('front_messages.bank_payment_instructions_part2', 'You have to send your payment receipt from order details page then admin will verify that.') }}
                                </div>
                                <div class="alert alert-info col-md-12">
                                    <strong>{{ labels('front_messages.account_details', 'Account Details') }}!</strong> <br><br>
                                    <ul>
                                        <li>{{ labels('front_messages.account_name', 'Account Name') }} :
                                            {{ isset($payment_method->account_name) ? $payment_method->account_name : '' }}
                                        </li>
                                        <li>{{ labels('front_messages.account_number', 'Account Number') }} :
                                            {{ isset($payment_method->account_number) ? $payment_method->account_number : '' }}
                                        </li>
                                        <li>{{ labels('front_messages.bank_name', 'Bank Name') }} :
                                            {{ isset($payment_method->bank_name) ? $payment_method->bank_name : '' }}
                                        </li>
                                        <li>{{ labels('front_messages.bank_code', 'Bank Code') }} :
                                            {{ isset($payment_method->bank_code) ? $payment_method->bank_code : '' }}
                                        </li>
                                    </ul>
                                </div>
                                <div class="alert alert-info col-md-12">
                                    <strong>{{ labels('front_messages.extra_details', 'Extra Details') }}!</strong> <br><br>
                                    {!! isset($payment_method->notes) ? $payment_method->notes : '' !!}
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                        <div class="block mb-3 apply-code mb-4">
                            <div class="block-content">
                                <h3 class="title mb-3 text-uppercase">
                                    {{ labels('front_messages.apply_promocode', 'Apply Promocode') }}</h3>
                                <div wire:ignore id="coupon" class="coupon-dec">
                                    <div class="input-group mb-0 d-flex">
                                        <input id="coupon-code" type="text"
                                            class="form-control text-uppercase mx-2"
                                            placeholder="{{ labels('front_messages.promotion_discount_code', 'Promotion/Discount Code') }}" data-promocode-id="">
                                        <button class="apply-coupon-btn btn btn-primary"
                                            type="submit">{{ labels('front_messages.apply', 'Apply') }}</button>
                                        <button class="remove-coupon-btn btn btn-secondary d-none"
                                            type="submit">{{ labels('front_messages.remove', 'Remove') }}</button>
                                    </div>
                                    <div class="d-flex justify-content-end mt-1"><button type="button"
                                            data-bs-toggle="modal" data-bs-target="#promo-modal"
                                            class="m-0 fw-500 border-0 text-decoration-underline">{{ labels('front_messages.add_promo', 'Add Promo') }}</button>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <!--End Apply Promocode-->
                    </div>
                </div>
                @php
                    $item = $cart_data['cart_items'][0];
                    $product = $item['product'] ?? ($item['comboproduct'] ?? null);
                    $productType = $product['type'] ?? '';
                @endphp

                <input type="hidden" name="product_type" id="product_type" value="{{ $productType }}">
                <input wire:ignore type="hidden" name="promo_set" id="promo_set" value="0" />
                <input wire:ignore type="hidden" name="promo-code-id" class="promo-code-id" value="" />
                <input type="hidden" name="user-email" id="user-email"
                    value="{{ $user_details['email'] ?? '' }}" />
                <input type="hidden" name="phonepe_transaction_id" id="phonepe_transaction_id" value="" />
                <input type="hidden" name="paypal_transaction_id" id="paypal_transaction_id" value="" />
                <input type="hidden" name="paystack_reference" id="paystack_reference" value="" />
                <input type="hidden" name="razorpay_signature" id="razorpay_signature">
                <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
                <input type="hidden" name="app_name" id="app_name" value="{{ $web_settings['site_title'] }}">
                <input type="hidden" name="logo" id="logo"
                    value="{{ app(MediaService::class)->getImageUrl($web_settings['logo']) }}">
                <input type="hidden" name="username" id="username" value="{{ $user_info['username'] ?? '' }}" />
                {{-- <input type="hidden" name="currency_symbol" id="currency_symbol" value="{{ $currency_symbol }}" /> --}}
                <input type="hidden" name="discount" id="discount" value="" />
                <input type="hidden" name="is_wallet_used" id="is_wallet_used"
                    value="{{ $is_wallet_use == true ? 1 : 0 }}" />
                <input type="hidden" name="is_delivery_charge_returnable" id="is_delivery_charge_returnable"
                    value="{{ $cart_data['delivery_charge'] != 0 ? 1 : 0 }}" />
                <input type="hidden" name="delivery_charge" id="delivery_charge"
                    value="{{ $cart_data['delivery_charge'] }}" />
                <input type="hidden" name="wallet_balance_used" id="wallet_balance_used"
                    value="{{ $is_wallet_use == true ? $wallet_used_balance : 0 }}" />
                @php
                    $system_settings = app(SettingService::class)->getSettings('system_settings', true);
                    $system_settings = json_decode($system_settings, true);
                    $currency_code = session('currency') ?? $system_settings['currency_setting']['code'];
                    $currency_details = app(CurrencyService::class)->getCurrencyCodeSettings($currency_code);
                @endphp
                <input type="hidden" name="currency_code" id="currency_code"
                    value="{{ $currency_details[0]['symbol'] }}">

                {{-- Shipping Option Data --}}
                @if (!empty($shipping_options) && isset($selected_shipping_method))
                    @php
                        $selected_option = $shipping_options[$selected_shipping_method] ?? null;
                    @endphp
                    @if ($selected_option)
                        <input type="hidden" name="shipping_option_id"
                            value="{{ $selected_option['courier_company_id'] ?? '' }}">
                        <input type="hidden" name="shipping_option_name"
                            value="{{ $selected_option['courier'] ?? '' }}">
                        <input type="hidden" name="shipping_carrier"
                            value="{{ $selected_option['courier'] ?? 'Shiprocket' }}">
                        <input type="hidden" name="shipping_estimated_days"
                            value="{{ $selected_option['days'] ?? ($selected_option['etd'] ?? '') }}">
                    @endif
                @endif

                <div class="row">
                    <div class="col-lg-8 col-md-7 col-sm-12 col-12 mb-4 mb-md-0">
                        <!--Order Summary-->
                        <div class="block order-summary">
                            <div class="block-content">
                                <h3 class="title mb-3 text-uppercase">
                                    {{ labels('front_messages.order_summary', 'Order Summary') }}</h3>
                                <div class="table-responsive-sm table-bottom-brd order-table cart-box">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="cart-row cart-header small-hide">
                                            <tr>
                                                <th class="action">&nbsp;</th>
                                                <th class="text-start">{{ labels('front_messages.image', 'Image') }}
                                                </th>
                                                <th class="text-start proName">
                                                    {{ labels('front_messages.product', 'Product') }}</th>
                                                <th class="text-center">{{ labels('front_messages.price', 'Price') }}
                                                </th>
                                                <th class="text-center">{{ labels('front_messages.qty', 'Qty') }}</th>
                                                <th class="text-center">
                                                    {{ labels('front_messages.subtotal', 'Subtotal') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $is_save_later_hide = 0;
                                                $is_remove_from_cart = 1;
                                                $for_checkout = 1;
                                            @endphp
                                            @foreach ($cart_data['cart_items'] as $key => $cartItem)
                                                <x-utility.cart.CardOne :$cartItem :$is_save_later_hide
                                                    :$is_remove_from_cart
                                                    :product_availability=$product_availability[$key] : $for_checkout />
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <!--End Order Summary-->
                    </div>
                    <div class="col-lg-4 col-md-5 col-sm-12 col-12">
                        <p class="cashback-text cart-shipping fs-6 fw-600 text-success m-0 d-none">
                            {{ labels('front_messages.cashback_message_part1', 'When your order is delivered, you will receive a cashback of') }}
                            <span class="cashback-amount"></span> 🎉
                            {{ labels('front_messages.cashback_message_part2', 'in your wallet') }}.
                        </p>
                        <!--Cart Summary-->
                        {{-- @dd($cart_data['sub_total']); --}}
                        <div class="cart-info">
                            <div class="cart-order-detail cart-col">

                                <div class="row g-0 border-bottom pb-2">
                                    <span
                                        class="col-6 col-sm-6 cart-subtotal-title"><strong>{{ labels('front_messages.subtotal', 'Subtotal') }}</strong></span>
                                    <span class="col-6 col-sm-6 cart-subtotal-title cart-subtotal text-end"><span
                                            class="money">
                                            @if (!empty($shipping_options))
                                                {{-- Shiprocket - subtotal is already converted, don't convert again --}}
                                                ₹{{ number_format($cart_data['sub_total'], 2) }}
                                            @else
                                                {{-- Local shipping - apply currency conversion --}}
                                                {{ app(CurrencyService::class)->currentCurrencyPrice($cart_data['sub_total'], true) }}
                                            @endif
                                        </span>
                                </div>
                                <div class="row g-0 border-bottom py-2">
                                    <span
                                        class="col-6 col-sm-6 cart-subtotal-title"><strong>{{ labels('front_messages.delivery_charge', 'Delivery Charge') }}</strong></span>
                                    <span class="col-6 col-sm-6 cart-subtotal-title cart-subtotal text-end"><span
                                            class="money">
                                            @if (!empty($shipping_options))
                                                {{-- Shiprocket - no currency conversion --}}
                                                ₹{{ number_format($cart_data['delivery_charge'], 2) }}
                                            @else
                                                {{-- Local shipping - apply currency conversion --}}
                                                {{ app(CurrencyService::class)->currentCurrencyPrice($cart_data['delivery_charge'], true) }}
                                            @endif
                                        </span>
                                </div>
                                <div class="row g-0 border-bottom py-2 d-none coupon-box">
                                    <span
                                        class="col-6 col-sm-6 cart-subtotal-title"><strong>{{ labels('front_messages.coupon_discount', 'Coupon Discount') }}</strong></span>
                                    <span class="col-6 col-sm-6 cart-subtotal-title cart-subtotal text-end">-<span
                                            class="money coupon-field" id="coupon-field"></span>
                                </div>
                                @if ($is_wallet_use == true)
                                    <div class="row g-0 border-bottom py-2">
                                        <span
                                            class="col-6 col-sm-6 cart-subtotal-title"><strong>{{ labels('front_messages.wallet_balance_used', 'Wallet Balance Used') }}</strong></span>
                                        <span class="col-6 col-sm-6 cart-subtotal-title cart-subtotal text-end"><span
                                                class="money">{{ app(CurrencyService::class)->currentCurrencyPrice($wallet_used_balance, true) }}</span>
                                    </div>
                                @endif
                                <div class="row g-0 pt-2">
                                    <span
                                        class="col-6 col-sm-6 cart-subtotal-title fs-6"><strong>{{ labels('front_messages.total', 'Total') }}</strong></span>
                                    <span
                                        class="col-6 col-sm-6 cart-subtotal-title fs-5 cart-subtotal text-end text-primary"><b
                                            class="money">
                                            @if (!empty($shipping_options))
                                                {{-- Shiprocket - total is already sum of converted subtotal + INR delivery charge --}}
                                                ₹{{ number_format($final_total, 2) }}
                                            @else
                                                {{-- Local shipping - apply currency conversion --}}
                                                {{ app(CurrencyService::class)->currentCurrencyPrice($final_total, true) }}
                                            @endif
                                        </b></span>
                                </div>
                                <input type="hidden" name="final_total" id="final_total"
                                    value="{{ $final_total }}">
                                <p class="cart-shipping m-0">{{ labels('front_messages.inclusive_of_all_taxes_and_shipping', 'Inclusive of all taxes & Shipping') }}</p>

                                <button type="submit" id="place_order_btn" class="btn btn-lg my-4 checkout w-100"
                                    wire:loading.attr="disabled" wire:loading.class="disabled">
                                    {{ labels('front_messages.place_order', 'Place order') }}
                                </button>
                            </div>
                        </div>
                        <div id="paypal-button-container" class="mt-3 d-none"></div>
                        <div id="stripe-checkout">
                        </div>
                    </div>
                </div>

                {{-- address model --}}
                <div class="modal fade" id="address-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
                    aria-labelledby="staticBackdropLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-scrollable modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h1 class="modal-title fs-5" id="staticBackdropLabel">
                                    {{ labels('front_messages.select_address', 'Select Address') }}</h1>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-2 form-check">
                                @foreach ($addresses as $address)
                                    <div class="address-div form-check-box">
                                        <input class="form-check-input p-2 ms-0 me-1 address-radio" type="radio"
                                            name="select-address{{ $address->id }}"
                                            id="select-address{{ $address->id }}"
                                            data-address-id="{{ $address->id }}"><label class="form-check-label"
                                            for="select-address{{ $address->id }}">
                                            <p class="m-0 fw-600 d-flex"><ion-icon name="navigate-circle-outline"
                                                    class="fs-5 me-1"></ion-icon> {{ $address->name }} -
                                                {{ $address->type }}
                                            </p>
                                            <p class="m-0 text-muted">
                                                {{ "{$address->address} {$address->landmark}," }}
                                                {{ app(TranslationService::class)->getDynamicTranslation(City::class, 'name', $address->city_id, $language_code) . '-' . $address->state }},
                                                {{ $address->pincode }}
                                            </p>
                                            <p class="m-0 text-muted">{{ $address->mobile }}</p>
                                    </div>
                                @endforeach
                            </div>
                            <div class="modal-footer justify-content-between">
                                <a target="_blank" href="{{ customUrl('my-account/addresses') }}"
                                    class="m-0 fw-600">{{ labels('front_messages.add_address', 'Add Address') }}</a>
                                <div>
                                    <button type="button" class="btn btn-secondary"
                                        data-bs-dismiss="modal">{{ labels('front_messages.close', 'Close') }}</button>
                                    <button type="button"
                                        class="btn btn-primary set-address">{{ labels('front_messages.select', 'Select') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- promo model --}}
                <div class="modal fade" id="promo-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
                    aria-labelledby="staticBackdropLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-scrollable modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h1 class="modal-title fs-5" id="staticBackdropLabel">
                                    {{ labels('front_messages.select_promo', 'Select Promo') }}</h1>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-2 form-check">


                                @if ($promo_codes['data']->isEmpty())
                                    <!-- Display a "No data" message if the collection is empty -->
                                    <div class="d-flex justify-content-center">

                                        <p>{{ labels('front_messages.no_promo_codes_available', 'No promo codes available') }}</p>
                                    </div>
                                @else
                                    @foreach ($promo_codes['data'] as $promocode)
                                        <div class="address-div form-check-box">
                                            <input class="form-check-input p-2 ms-0 me-1 promo-radio" type="radio"
                                                id="select-promocode{{ $promocode['id'] }}"
                                                data-promocode-id="{{ $promocode['id'] }}"
                                                data-promocode="{{ $promocode['promo_code'] }}">
                                            <label class="form-check-label"
                                                for="select-promocode{{ $promocode['id'] }}">
                                                <div class="d-flex align-items-center">
                                                    <!-- Display the image (adjust src path based on your data) -->
                                                    <img src="{{ app(MediaService::class)->getMediaImageUrl($promocode['image']) }}"
                                                        alt="Promo" class="promo-image me-2">

                                                    <div>
                                                        <p class="m-0 text-uppercase fw-700 fs-6 d-flex">
                                                            {{ $promocode['promo_code'] }}
                                                        </p>
                                                        <p class="m-0 text-muted">
                                                            {{ Str::limit(app(TranslationService::class)->getDynamicTranslation(Promocode::class, 'message', $promocode['id'], $language_code), 10, '...') }}
                                                        </p>
                                                        <p class="m-0 text-danger fw-500">
                                                            {{ labels('front_messages.valid_minimum_order_amount_of', 'Valid Minimum Order Amount Of') }}
                                                            <span
                                                                class="fw-600">{{ app(CurrencyService::class)->currentCurrencyPrice($promocode['min_order_amt'], true) }}</span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    @endforeach
                                @endif
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary"
                                    data-bs-dismiss="modal">{{ labels('front_messages.close', 'Close') }}</button>
                                @if (!$promo_codes['data']->isEmpty())
                                    <button type="button"
                                        class="btn btn-primary set-promo">{{ labels('front_messages.add_promo', 'Add Promo') }}</button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        @else
            @php
                $title =
                    '<strong>' .
                    labels('front_messages.sorry', 'SORRY') .
                    '</strong>' .
                    labels('front_messages.cart_is_currently_empty', 'Cart is currently empty');
            @endphp
            <x-utility.others.not-found :$title />
        @endif
    </div>
</div>
@if (!empty($payment_method->razorpay_method) && $payment_method->razorpay_method == 1)
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
@endif
@if (!empty($payment_method->paypal_method) && $payment_method->paypal_method == 1)
    <script
        src="https://www.paypal.com/sdk/js?client-id={{ $payment_method->paypal_client_id }}&currency={{ $payment_method->currency_code }}">
    </script>
@endif
@if (!empty($payment_method->stripe_method) && $payment_method->stripe_method == 1)

    <script src="https://js.stripe.com/v3/"></script>
@endif
@if (!empty($payment_method->paystack_method) && $payment_method->paystack_method == 1)

    <script src="https://js.paystack.co/v1/inline.js"></script>
@endif

<script>
    // Prevent multiple form submissions
    let formSubmitted = false;
    const placeOrderForm = document.getElementById('place_order_form');
    const placeOrderBtn = document.getElementById('place_order_btn');

    if (placeOrderForm && placeOrderBtn) {
        const originalText = placeOrderBtn.innerHTML;

        // Prevent form submission if already submitted
        placeOrderForm.addEventListener('submit', function (e) {
            if (formSubmitted) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }

            // Mark form as submitted
            formSubmitted = true;

            // Disable button and add loading state
            placeOrderBtn.disabled = true;
            placeOrderBtn.style.opacity = '0.6';
            placeOrderBtn.style.pointerEvents = 'none';
            placeOrderBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' + 
                                      '{{ labels("front_messages.processing", "Processing...") }}';

            // Set timeout to re-enable if form doesn't submit (network error, validation error, etc.)
            const reEnableTimeout = setTimeout(() => {
                formSubmitted = false;
                placeOrderBtn.disabled = false;
                placeOrderBtn.style.opacity = '1';
                placeOrderBtn.style.pointerEvents = 'auto';
                placeOrderBtn.innerHTML = originalText;
            }, 5000);

            // Clear timeout if form actually submits
            placeOrderForm.addEventListener('submit', function () {
                clearTimeout(reEnableTimeout);
            }, { once: true });
        });

        // Prevent button click if already submitted
        placeOrderBtn.addEventListener('click', function (e) {
            if (formSubmitted) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        });

        // Prevent form submission via Enter key on input fields if already submitted
        placeOrderForm.addEventListener('keypress', function (e) {
            if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA' && formSubmitted) {
                e.preventDefault();
            }
        });
    }
</script>

