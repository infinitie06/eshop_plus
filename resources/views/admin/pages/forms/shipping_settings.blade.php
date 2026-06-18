@extends('admin/layout')
@section('title')
    {{ labels('admin_labels.shipping_methods', 'Shipping Methods') }}
@endsection
@section('content')
    @php
        $user = auth()->user();
        $role = auth()->user()->role->name;
    @endphp
    <x-admin.breadcrumb :title="labels('admin_labels.shipping_methods', 'Shipping Methods')" :subtitle="labels(
        'admin_labels.optimize_and_manage_your_shipping_channels_with_ease',
        'Optimize and Manage Your Shipping Channels with Ease',
    )" :breadcrumbs="[
        ['label' => labels('admin_labels.settings', 'Settings'), 'url' => route('settings.index')],
        ['label' => labels('admin_labels.shipping_methods', 'Shipping Methods')],
    ]" />

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <form id="" action="{{ route('shipping_settings.store') }}" class="submit_form"
                    enctype="multipart/form-data" method="POST">
                    @csrf
                    <div class="card-body">
                        <h5 class="mb-3">
                            {{ labels('admin_labels.shipping_methods', 'Shipping Methods') }}
                        </h5>

                        <!-- Guide Section -->
                        <div class="alert alert-info mb-4" role="alert">
                            <h6 class="alert-heading"><i class="bx bx-info-circle"></i> {{ labels('admin_labels.how_shipping_methods_work', 'How Shipping Methods Work') }}</h6>
                            <ul class="mb-2 ps-3">
                                <li><strong>{{ labels('admin_labels.local_shipping', 'Local Shipping') }}:</strong> {{ labels('admin_labels.use_your_own_delivery_team', 'Use your own delivery team to fulfill orders') }}</li>
                                <li><strong>{{ labels('admin_labels.standard_shipping_shiprocket', 'Standard Shipping (Shiprocket)') }}:</strong> {{ labels('admin_labels.use_shiprocket_courier_partners', 'Use Shiprocket\'s courier partners for delivery') }}</li>
                                <li><strong>{{ labels('admin_labels.important', 'Important') }}:</strong> {{ labels('admin_labels.only_one_shipping_method', 'You can only enable ONE shipping method at a time') }}</li>
                            </ul>
                            <hr>
                            <p class="mb-0"><small><i class="bx bx-bulb"></i> <strong>{{ labels('admin_labels.tip', 'Tip') }}:</strong> {{ labels('admin_labels.toggle_shipping_method_tip', 'When you toggle one method ON, the other will automatically turn OFF.') }}</small></p>
                        </div>

                        <!-- Shiprocket Setup Guide (Initially Hidden) -->
                        <div id="shiprocket_setup_guide" class="alert alert-warning mb-4 d-none" role="alert">
                            <h6 class="alert-heading"><i class="bx bx-rocket"></i> {{ labels('admin_labels.shiprocket_setup_steps', 'Shiprocket Setup Steps') }}</h6>
                            
                            <!-- Currency Restriction Notice -->
                            <div class="alert alert-danger mb-3" role="alert">
                                <strong><i class="bx bx-error-circle"></i> {{ labels('admin_labels.important_currency_restriction', 'Important Currency Restriction') }}:</strong>
                                <p class="mb-0">{{ labels('admin_labels.shiprocket_currency_restriction_desc', 'Shiprocket only works in India and supports INR (₹) currency only. When Shiprocket is enabled, the system will automatically:') }}</p>
                                <ul class="mb-0 mt-2">
                                    <li>{{ labels('admin_labels.force_prices_inr', 'Force all prices to display in INR') }}</li>
                                    <li>{{ labels('admin_labels.disable_currency_conversion', 'Disable currency conversion') }}</li>
                                    <li>{{ labels('admin_labels.hide_currency_selector', 'Hide currency selector from frontend') }}</li>
                                </ul>
                            </div>
                            
                            <ol class="mb-2 ps-3">
                                <li>{{ labels('admin_labels.create_account_at', 'Create account at') }} <a href="https://www.shiprocket.in/" target="_blank"
                                        class="alert-link">Shiprocket.in</a></li>
                                <li>{{ labels('admin_labels.get_api_credentials_from', 'Get your API credentials from') }} <a href="https://app.shiprocket.in/api-user"
                                        target="_blank" class="alert-link">Shiprocket Dashboard</a></li>
                                <li>{{ labels('admin_labels.enter_email_password_below', 'Enter your Email and Password below') }}</li>
                                <li>{{ labels('admin_labels.add_pickup_locations', 'Add pickup locations in Shiprocket Dashboard') }}</li>
                                <li>{{ labels('admin_labels.verify_pickup_locations', 'Verify pickup locations (required for AWB generation)') }}</li>
                                <li>{{ labels('admin_labels.configure_products_weight_dimensions', 'Configure products with weight and dimensions') }}</li>
                            </ol>
                            <p class="mb-0"><small><i class="bx bx-error-circle"></i> <strong>{{ labels('admin_labels.note', 'Note') }}:</strong> {{ labels('admin_labels.webhook_token_optional', 'Webhook Token is optional and can be added later.') }}</small></p>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="form-group">
                                        <label class="mb-3"
                                            for="local_shipping_method">{{ labels('admin_labels.enable_local_shipping', 'Enable Local Shipping') }}
                                            <small>({{ labels('admin_labels.use_local_delivery_boy', 'Use Local Delivery Boy For Shipping') }})</small></label>
                                    </div>
                                    <div class="form-group card-body d-flex justify-content-end">
                                        <a class="toggle form-switch me-1 mb-1" title="{{ labels('admin_labels.title_deactivate', 'Deactivate') }}"
                                            href="javascript:void(0)">
                                            <input type="checkbox" class="form-check-input shipping-method-toggle"
                                                role="switch" name="local_shipping_method" id="local_shipping_method"
                                                <?= @$settings['local_shipping_method'] == '1' ? 'checked' : '' ?>>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="d-flex align-items-center justify-content-between">
                                    <label class="mb-3"
                                        for="shiprocket_shipping_method">{{ labels('admin_labels.standard_delivery_method', 'Standard Delivery Method') }}
                                        (Shiprocket)
                                        <small><a href="https://app.shiprocket.in/api-user" target="_blank">{{ labels('admin_labels.click_here', 'Click here') }}</a> {{ labels('admin_labels.to_get_credentials', 'to get credentials') }}. <a href="https://www.shiprocket.in/"
                                                target="_blank">{{ labels('admin_labels.what_is_shiprocket', 'What is shiprocket?') }}</a></small></label>
                                    <br>
                                    <div class="card-body d-flex justify-content-end">
                                        <a class="toggle form-switch me-1 mb-1" title="{{ labels('admin_labels.title_deactivate', 'Deactivate') }}"
                                            href="javascript:void(0)">
                                            <input type="checkbox" class="form-check-input shipping-method-toggle"
                                                role="switch" name="shiprocket_shipping_method"
                                                id="shiprocket_shipping_method"
                                                <?= @$settings['shiprocket_shipping_method'] == '1' ? 'checked' : '' ?>>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="shiprocket_credentials_section"
                            class="<?= @$settings['shiprocket_shipping_method'] == '1' ? '' : 'd-none' ?>">
                            <div class="row">
                                <div class="form-group">
                                    <label class="mb-3" for="email">{{ labels('admin_labels.email', 'Email') }}<span
                                            class="text-asterisks text-sm">*</span></label>
                                    <input type="email" class="form-control" name="email" id="email"
                                        value="<?= isKeySetAndNotEmpty($settings, 'email') ? $settings['email'] : '' ?>"
                                        placeholder="{{ labels('admin_labels.shiprocket_account_email', 'Shiprocket account email') }}" />
                                </div>
                                <div class="form-group">
                                    <label class="mb-3"
                                        for="password">{{ labels('admin_labels.password', 'Password') }}<span
                                            class="text-asterisks text-sm">*</span></label>
                                    <input type="password" class="form-control" name="password" id="password"
                                        value="<?= isKeySetAndNotEmpty($settings, 'password') ? $settings['password'] : '' ?>"
                                        placeholder="{{ labels('admin_labels.shiprocket_account_password', 'Shiprocket account Password') }}" />
                                </div>
                                <div class="form-group">
                                    <label class="mb-3"
                                        for="webhook_url">{{ labels('admin_labels.webhook_url', 'Webhook URL') }}</label>
                                    <input type="text" class="form-control" name="webhook_url" id="webhook_url"
                                        value="<?= url('admin/webhook/spr_webhook') ?>" disabled />
                                </div>
                                <div class="form-group">
                                    <label class="mb-3"
                                        for="webhook_token">{{ labels('admin_labels.shiprocket_webhook_token', 'Shiprocket Webhook Token') }}</label>
                                    <input type="text" class="form-control" name="webhook_token" id="webhook_token"
                                        value="<?= isKeySetAndNotEmpty($settings, 'webhook_token') ? $settings['webhook_token'] : '' ?>" />
                                </div>
                            </div>

                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="reset"
                                class="btn mx-2 reset_button">{{ labels('admin_labels.reset', 'Reset') }}</button>
                            <button type="submit"
                                class="btn btn-primary submit_button">{{ labels('admin_labels.update_settings', 'Update Settings') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
