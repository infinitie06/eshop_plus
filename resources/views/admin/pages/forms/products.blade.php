@extends('admin/layout')
@php
    use App\Services\SettingService;
    $shipping_settings = app(SettingService::class)->getSettings('shipping_method', true);
    $shipping_settings = json_decode($shipping_settings, true);
    $shiprocketEnabled =
        isset($shipping_settings['shiprocket_shipping_method']) &&
        $shipping_settings['shiprocket_shipping_method'] == 1;
@endphp
@section('title')
    {{ labels('admin_labels.products', 'Products') }}
@endsection
@section('content')
    <x-admin.breadcrumb :title="labels('admin_labels.add_product', 'Add Product')" :subtitle="labels('admin_labels.add_products_with_power_and_simplicity', 'Add products with power and simplicity')" :breadcrumbs="[
        ['label' => labels('admin_labels.products', 'Products'), 'url' => route('admin.products.index')],
        ['label' => labels('admin_labels.manage_products', 'Manage Products')],
        ['label' => labels('admin_labels.add_product', 'Add Product')],
    ]" />

    <div class="">
        <form class="" action="{{ route('admin.products.store') }}" enctype="multipart/form-data" method="POST"
            id="save-product">
            @csrf
            <div class="card p-5">
                <h6>{{ labels('admin_labels.choose_seller', 'Select Seller And Product') }}
                </h6>
                <div class="col-md-12 mt-4">
                    <div class="row">
                        <div class="col-md-4">
                            <label for="seller_id" class="form-label">
                                {{ labels('admin_labels.choose_seller', 'Choose Seller') }}
                                <span class='text-asterisks text-sm'>*</span>
                            </label>
                            <select class='form-control mt-4' name='seller_id' id="seller_id">
                                <option value="">{{ labels('admin_labels.select_seller', 'Select Seller') }}</option>
                                @foreach ($sellers as $seller)
                                    @php
                                        $userId = $seller->pivot->user_id;
                                        $username = \App\Models\User::find($userId)?->username ?? '';
                                    @endphp
                                    <option value="{{ $seller->id }}">
                                        {{ $username }} - {{ $seller->pivot->store_name }} ({{ labels('admin_labels.store_label', 'store') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="product_type_menu" class="form-label">
                                {{ labels('admin_labels.choose_product_type', 'Choose Product Type') }}
                                <span class='text-asterisks text-sm'>*</span>
                            </label>
                            <select class='form-control mt-4' name='product_type_menu' id="product_type_menu">
                                <option value="">{{ labels('admin_labels.select_product_type', 'Select Product Type') }}</option>
                                <option value="physical_product">
                                    {{ labels('admin_labels.physical_product', 'Physical Product') }}
                                </option>
                                <option value="digital_product">
                                    {{ labels('admin_labels.digital_product', 'Digital Product') }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="category_select" class="form-label">
                                {{ labels('admin_labels.select_category', 'Select Product Category') }}
                                <span class='text-asterisks text-sm'>*</span>
                            </label>
                            <select class='form-control mt-4' name='category_id' id="category_select">
                                <option value="">{{ labels('admin_labels.select_category', 'Select Category') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card p-5 mt-4">
                <h6>{{ labels('admin_labels.product_information', 'Product Information') }}</h6>
                <ul class="nav nav-tabs mt-4" id="brandTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="language-nav-link nav-link active" id="tab-en" data-bs-toggle="tab"
                            data-bs-target="#content-en" type="button" role="tab" aria-controls="content-en"
                            aria-selected="true">
                            {{ labels('admin_labels.default', 'Default') }}
                        </button>
                    </li>
                    <x-language.multi_language_tabs :languages="$languages" />
                </ul>


                <div class="tab-content mt-3" id="brandTabsContent">
                    <div class="tab-pane fade show active" id="content-en" role="tabpanel" aria-labelledby="tab-en">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="pro_input_text" class="form-label">
                                    {{ labels('admin_labels.product_name', 'Product Name') }}
                                    <span class='text-asterisks text-sm'>*</span>
                                </label>
                                <div class="position-relative">
                                    <input type="text" class="form-control" id="pro_input_text"
                                        placeholder="{{ labels('admin_labels.product_name_placeholder', 'Product Name') }}" name="pro_input_name">
                                    {{-- <div id="prompt_suggestions" class="list-group position-absolute w-100 z-3 d-none">
                                    </div> --}}
                                </div>

                            </div>

                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center">
                                    <label for="short_description" class="form-label mb-0">
                                        {{ labels('admin_labels.short_description', 'Short Description') }}
                                        <span class='text-asterisks text-sm'>*</span>
                                    </label>

                                    <div class="d-flex align-items-center gap-2">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input custom_prompt_toggle" type="checkbox">
                                            <label class="form-check-label small" for="custom_prompt_toggle">{{ labels('admin_labels.custom_prompt_label', 'Custom Prompt') }}</label>
                                        </div>
                                        <button type="button"
                                            class="btn btn-sm btn-outline-primary generate_short_description">
                                            {{ labels('admin_labels.generate_with_ai', 'Generate with AI') }}
                                        </button>
                                        <div id="prompt_actions" class="d-none">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-secondary get_prompt_suggestions">
                                                {{ labels('admin_labels.get_prompt_suggestions', 'Get Prompt Suggestions') }}
                                            </button>
                                        </div>

                                        <button type="button" class="btn btn-secondary btn-sm" data-container="body"
                                            data-bs-toggle="popover" data-bs-placement="top"
                                            data-bs-content="{{ labels('admin_labels.popover_use_ai_assistance', 'Use AI Assistance :Enter the product name first to generate a description. To improve results, enable the custom prompt option—you can use suggested prompts or write your own.') }}">
                                            {{ labels('admin_labels.how_it_works', 'How It Works?') }}
                                        </button>

                                    </div>
                                </div>

                                <!-- Custom prompt input -->
                                <textarea class="form-control mt-2 d-none custom_prompt" name="custom_prompt"
                                    placeholder="{{ labels('admin_labels.placeholder_custom_ai_prompt', 'Enter your own AI prompt (optional)...') }}"></textarea>
                                <div class="position-relative w-100">
                                    <div id="prompt_suggestions" class="list-group position-absolute w-100 z-3 d-none">
                                    </div>
                                </div>
                                <textarea class="form-control mt-2" id="short_description" placeholder="{{ labels('admin_labels.product_short_description_placeholder', 'Product Short Description') }}"
                                    name="short_description"></textarea>
                            </div>

                        </div>
                    </div>
                    @foreach ($languages as $lang)
                        @if ($lang->code !== 'en')
                            <div class="tab-pane fade" id="content-{{ $lang->code }}" role="tabpanel"
                                aria-labelledby="tab-{{ $lang->code }}">
                                <div class="row">
                                    <div class="col-md-6 position-relative">
                                        <label class="form-label">
                                            {{ labels('admin_labels.product_name', 'Product Name') }}
                                            ({{ $lang->language }})
                                        </label>
                                        <div class="position-relative">
                                            <input type="text" class="form-control translated-name-input"
                                                id="translated_name_{{ $lang->code }}"
                                                name="translated_product_name[{{ $lang->code }}]"
                                                data-lang="{{ $lang->code }}">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <label class="form-label mb-0">
                                                {{ labels('admin_labels.short_description', 'Short Description') }}
                                                ({{ $lang->language }})
                                            </label>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input custom_translated_prompt_toggle"
                                                        type="checkbox" data-lang="{{ $lang->code }}"
                                                        id="custom_translated_prompt_toggle_{{ $lang->code }}">

                                                    <label class="form-check-label small"
                                                        for="custom_translated_prompt_toggle">{{ labels('admin_labels.custom_prompt_label', 'Custom Prompt') }}</label>
                                                </div>
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-primary generate_translated_short_description"
                                                    data-lang="{{ $lang->code }}"
                                                    data-lang-name="{{ $lang->language }}">
                                                    {{ labels('admin_labels.generate_with_ai', 'Generate with AI') }}
                                                </button>
                                                <div id="language_prompt_action_{{ $lang->code }}"
                                                    class="language_prompt_action d-none">
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-secondary get_language_prompt_suggestions">
                                                        {{ labels('admin_labels.get_prompt_suggestions', 'Get Prompt Suggestions') }}
                                                    </button>
                                                </div>
                                                <button type="button" class="btn btn-secondary btn-sm"
                                                    data-container="body" data-bs-toggle="popover"
                                                    data-bs-placement="top"
                                                    data-bs-content="{{ labels('admin_labels.popover_use_ai_assistance', 'Use AI Assistance :Enter the product name first to generate a description. To improve results, enable the custom prompt option—you can use suggested prompts or write your own.') }}">
                                                    {{ labels('admin_labels.how_it_works', 'How It Works?') }}
                                                </button>

                                            </div>

                                        </div>
                                        <textarea id="language_custom_prompt_{{ $lang->code }}" class="form-control mt-2 d-none custom_translated_prompt"
                                            name="custom_translated_prompt" data-lang="{{ $lang->code }}"
                                            placeholder="{{ labels('admin_labels.placeholder_custom_ai_prompt', 'Enter your own AI prompt (optional)...') }}"></textarea>
                                        <div class="position-relative w-100">
                                            <div
                                                class="language_prompt_suggestions list-group position-absolute w-100 z-3 d-none">
                                            </div>
                                        </div>
                                        <textarea class="form-control mt-1" id="translated_short_description_{{ $lang->code }}"
                                            placeholder="{{ labels('admin_labels.product_short_description_placeholder', 'Product Short Description') }}" name="translated_product_short_description[{{ $lang->code }}]"></textarea>

                                    </div>

                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="form-group">
                            <div class="d-flex align-items-center justify-content-between mb-2">

                                <label for="brand" class="form-label m-0">
                                    {{ labels('admin_labels.select_brand', 'Select Brand') }}
                                </label>
                                <a href="{{ route('brands.index') }}" target="_blank"
                                    class="btn btn-sm btn-outline-primary btn-sm me-2">
                                    <i class="fa fa-plus-circle"></i>
                                </a>
                            </div>

                            <select class="form-select admin_product_brand_list" id="admin_brand_list" name="brand">
                                <!-- Brand options go here -->
                            </select>
                        </div>

                    </div>
                    <div class="col-md-6">
                        <div class="form-group country_list_div">
                            <label for="made_in" class="form-label">{{ labels('admin_labels.made_in', 'Made IN') }}
                            </label>
                            <select class="col-md-12 form-control country_list" id="country_list" name="made_in">
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 d-none indicator">
                        <label for="indicator" class="form-label">{{ labels('admin_labels.indicator', 'Indicator') }}
                        </label>
                        <select class="form-select" name="indicator">
                            <option value="0">{{ labels('admin_labels.none_option', 'None') }}</option>
                            <option value="1">{{ labels('admin_labels.veg_option', 'Veg') }}</option>
                            <option value="2">{{ labels('admin_labels.non_veg_option', 'Non-Veg') }}</option>
                        </select>

                    </div>
                    <div class="col-md-6 hsn_code d-none">
                        <div class="form-group">
                            <label for="zipcodes" class="form-label">{{ labels('admin_labels.hsn_code', 'HSN Code') }}
                            </label>
                            <input type="text" class="col-md-12 form-control" name="hsn_code" value=""
                                placeholder="{{ labels('admin_labels.hsn_code_placeholder', 'HSN Code') }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="tags" class="form-label">
                                {{ labels('admin_labels.tags', 'Tags') }}
                            </label>
                            <input type="text" class="form-control" id="tags" placeholder="{{ labels('admin_labels.tags_placeholder_example', 'dress,milk,almond') }}"
                                name="tags">
                        </div>
                    </div>
                </div>
                {{-- custom fields  --}}
                @include('components.product.custom_fields', [
                    'customFields' => $customFields,
                ])
            </div>
            <div class="card p-5 mt-4">
                <div class="row">
                    <h6>{{ labels('admin_labels.product_tax', 'Product Tax') }}</h6>
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="d-flex align-items-center justify-content-between mb-2">

                                    <label for="pro_input_tax" class="form-label m-0">
                                        {{ labels('admin_labels.select_tax', 'Select Tax') }}
                                    </label>
                                    <a href="{{ route('taxes.index') }}" target="_blank"
                                        class="btn btn-outline-primary btn-sm me-2">
                                        <i class="fa fa-plus-circle"></i>
                                    </a>
                                </div>

                                <select name="pro_input_tax[]" class="tax_list form-select w-100" multiple>
                                    <option value=""></option>
                                    <!-- Add tax options here -->
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6 mt-7">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <label for="is_prices_inclusive_tax"
                                        class="form-label">{{ labels('admin_labels.tax_includes_in_price', 'Tax Includes In Price') }}
                                    </label>
                                </div>
                                <div class="d-flex">
                                    <label for="" class="me-6 text-muted">[{{ labels('admin_labels.enable_disable', 'Enable/Disable') }}]</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id=""
                                            name="is_prices_inclusive_tax">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card p-5 mt-4 product_quantity_and_others">
                <div class="row">
                    <div class="col col-xxl-12">
                        <h6>{{ labels('admin_labels.product_quantity_and_other', 'Product Quantity & Other') }}
                        </h6>
                        <div class="row mt-4">
                            <div class="col-md-6 total_allowed_quantity">
                                <div class="form-group">
                                    <label for="total_allowed_quantity"
                                        class="form-label">{{ labels('admin_labels.total_allowed_quantity', 'Total Allowed Quantity') }}
                                    </label>
                                    <input type="number" min=0 class="col-md-12 form-control"
                                        name="total_allowed_quantity" value=""
                                        placeholder="{{ labels('admin_labels.total_allowed_quantity', 'Total Allowed Quantity') }}">
                                </div>
                            </div>
                            <div class="col-md-6 minimum_order_quantity">
                                <div class="form-group">
                                    <label for="minimum_order_quantity"
                                        class="form-label">{{ labels('admin_labels.minimum_order_quantity', 'Minimum Order Quantity') }}
                                    </label>
                                    <input type="number" min=1 class="col-md-12 form-control"
                                        name="minimum_order_quantity" min="1" value="1"
                                        placeholder="{{ labels('admin_labels.minimum_order_quantity', 'Minimum Order Quantity') }}">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 quantity_step_size">
                                <div class="form-group">
                                    <label for="quantity_step_size"
                                        class="form-label">{{ labels('admin_labels.quantity_step_size', 'Quantity Step Size') }}
                                    </label>
                                    <input type="number" min=1 class="col-md-12 form-control" name="quantity_step_size"
                                        min="1" value="1" placeholder="{{ labels('admin_labels.quantity_step_size', 'Quantity Step Size') }}">
                                </div>
                            </div>
                            <div class="col-md-6 warranty_period d-none">
                                <div class="form-group">
                                    <label for="warranty_period"
                                        class="form-label">{{ labels('admin_labels.warrenty_period', 'Warrenty Period') }}
                                    </label>
                                    <input type="text" class="col-md-12 form-control" name="warranty_period"
                                        value="" placeholder="{{ labels('admin_labels.warranty_period_placeholder', 'Warranty Period if any') }}">
                                </div>
                            </div>
                        </div>
                        <div class="row d-none">
                            <div class="col-md-6 guarantee_period">
                                <div class="form-group">
                                    <label for="guarantee_period"
                                        class="form-label">{{ labels('admin_labels.gurantee_period', 'Guarantee Period') }}
                                    </label>
                                    <input type="text" class="col-md-12 form-control" name="guarantee_period"
                                        value="" placeholder="{{ labels('admin_labels.guarantee_period_placeholder', 'Guarantee Period if any') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card p-5 mt-4 delivery_and_shipping_settings ">
                <div class="row">
                    <div class="col col-xxl-12">

                        <h6>{{ labels('admin_labels.delivery_and_shipping_setting', 'Delivery And Shipping Setting') }}
                        </h6>
                        <div class="row mt-4">
                            <div class="col-md-6 ">
                                <div class="form-group">
                                    <label for="zipcode"
                                        class="form-label">{{ labels('admin_labels.deliverable_type', 'Deliverable Type') }}
                                    </label>
                                    <select class="form-select" name="deliverable_type" id="deliverable_type">
                                        <option value="0">{{ labels('admin_labels.none_option', 'None') }}</option>
                                        <option value="1" class="all_deliverable_type">{{ labels('admin_labels.all_option', 'All') }}</option>
                                        <option value="2">{{ labels('admin_labels.specific_lower', 'specific') }}</option>
                                        {{-- <option value="3">Excluded</option> --}}
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="cities"
                                        class="form-label">{{ labels('admin_labels.deliverable_zones', 'Deliverable Zones') }}
                                        <span class="text-asterisks text-sm">*</span>
                                    </label>
                                    <select name="deliverable_zones[]" class="search_seller_zone form-select w-100"
                                        multiple {{-- class="search_zone form-select w-100" multiple --}} id="deliverable_zones" disabled>
                                        <option value="">
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="shipping_type"
                                        class="form-label">{{ labels('admin_labels.for_standard_shipping', 'For Standard Shipping') }}
                                    </label>
                                    <select class='form-control shiprocket_type' name="pickup_location"
                                        id="pickup_location">
                                        <option value=" ">
                                            {{ labels('admin_labels.select_pickup_location', 'Select Pickup Location') }}
                                        </option>

                                    </select>
                                </div>
                            </div>

                            @if (!$shiprocketEnabled)
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="zipcodes"
                                            class="form-label">{{ labels('admin_labels.minimum_free_delivery_order_quantity', 'Minimum Free Delivery Order Quantity') }}
                                        </label>
                                        <input type="number" min=1 class="form-control" value=""
                                            name="minimum_free_delivery_order_qty">
                                    </div>
                                </div>
                            @endif

                        </div>
                        @if (!$shiprocketEnabled)
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="zipcodes"
                                            class="form-label">{{ labels('admin_labels.delivery_charges', 'Delivery Charges') }}
                                        </label>
                                        <input type="number" min=1 class="form-control" value=""
                                            name="delivery_charges">
                                    </div>
                                </div>
                            </div>
                        @endif
                        <div class="col-md-6 mt-7">
                            <div class="form-group">
                                <div class="d-flex justify-content-between">

                                    <div id="affiliate_info" style="display:none;">
                                        <label for="is_in_affiliate"
                                            class="form-label">{{ labels('admin_labels.is_in_affiliate', 'Is In Affiliate') }}?
                                        </label>

                                        <div class="d-flex">
                                            <label class="me-6 text-muted form-label">[{{ labels('admin_labels.enable_disable', 'Enable/Disable') }}]</label>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="is_in_affiliate"
                                                    name="is_in_affiliate">
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <label for="is_cod_allowed"
                                            class="form-label">{{ labels('admin_labels.is_cod_allowed', 'IS COD Allowed') }}?
                                        </label>
                                    </div>
                                    <div class="d-flex">
                                        <label for="" class="me-6 text-muted">[{{ labels('admin_labels.enable_disable', 'Enable/Disable') }}]</label>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id=""
                                                name="cod_allowed">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mt-7 ">
                            <div class="form-group">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <label for="is_returnable"
                                            class="form-label">{{ labels('admin_labels.is_returnable', 'IS Returnable') }}?
                                        </label>
                                    </div>
                                    <div class="d-flex">
                                        <label class="me-6 text-muted form-label">[{{ labels('admin_labels.enable_disable', 'Enable/Disable') }}]</label>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id=""
                                                name="is_returnable">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mt-7 ">
                            <div class="form-group">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <label for="is_cancelable"
                                            class="form-label">{{ labels('admin_labels.is_cancelable', 'IS Cancelable') }}?
                                        </label>
                                    </div>
                                    <div class="d-flex">
                                        <label class="me-6 text-muted form-label">[{{ labels('admin_labels.enable_disable', 'Enable/Disable') }}]</label>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="is_cancelable_checkbox"
                                                name="is_cancelable">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">

                        <div class="col-md-6 mt-7 collapse" id='cancelable_till'>
                            <div class="form-group">
                                <label for="cancelable_till"
                                    class="form-label">{{ labels('admin_labels.till_which_status', 'Cancelable Till Which Status') }}?
                                </label>
                                <select class='form-select' name="cancelable_till">
                                    <option value='received'>{{ labels('admin_labels.received_option', 'Received') }}</option>
                                    <option value='processed'>{{ labels('admin_labels.processed_option', 'Processed') }}</option>
                                    <option value='shipped'>{{ labels('admin_labels.shipped_option', 'Shipped') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 mt-7 ">
                            <div class="form-group">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <label for="is_attachment_required"
                                            class="form-label">{{ labels('admin_labels.is_attachment_required', 'IS Attachment Required') }}?
                                        </label>
                                    </div>
                                    <div class="d-flex">
                                        <label class="me-6 text-muted form-label">[{{ labels('admin_labels.enable_disable', 'Enable/Disable') }}]</label>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox"
                                                id="is_attachment_required_checkbox" name="is_attachment_required">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card p-5 mt-4">
                <div class="row">
                    <div class="col col-xxl-12">
                        <h6>{{ labels('admin_labels.products_additional_info', 'Product Additional Info') }}
                </h6>
                <div class="row">
                    <div class="col-md-12 additional-info existing-additional-settings">
                        <div class="mt-4 col-md-12 additional-info-nav-header d-flex">
                            <div class="col-md-6">
                                <nav class="w-100">
                                    <div class="nav nav-tabs" id="product-tab" role="tablist">
                                        <a class="nav-item nav-link active" id="tab-for-general-price"
                                            data-bs-toggle="tab" href="#general-settings" role="tab"
                                            aria-controls="general-price"
                                            aria-selected="true">{{ labels('admin_labels.general', 'General') }}</a>
                                        <a class="nav-item nav-link edit-product-attributes" id="tab-for-attributes"
                                            data-bs-toggle="tab" href="#product-attributes" role="tab"
                                            aria-controls="product-attributes"
                                            aria-selected="false">{{ labels('admin_labels.attributes', 'Attributes') }}</a>
                                        <a class="nav-item nav-link d-none" id="tab-for-variations" data-bs-toggle="tab"
                                            href="#product-variants" role="tab" aria-controls="product-variants"
                                            aria-selected="false">{{ labels('admin_labels.variantions', 'Variations') }}</a>
                                    </div>
                                </nav>
                            </div>
                            <div class="col-md-6">
                                <div class="align-items-center d-flex form-group justify-content-end">
                                    <label for="type"
                                        class="col-md-3">{{ labels('admin_labels.type_of_product', 'Type Of Product') }}:</label>
                                    <div class="col-md-6">
                                        <input type="hidden" name="product_type" value="">
                                        <input type="hidden" name="simple_product_stock_status">
                                        <input type="hidden" name="variant_stock_level_type">
                                        <input type="hidden" name="variant_stock_status">
                                        <select name="type" id="product-type" class="form-control"
                                            data-placeholder="{{ labels('admin_labels.type_to_search_and_select_type', 'Type to search and select type') }}"
                                            <?= isset($product_details[0]['id']) ? 'disabled' : '' ?>>
                                            <option value=" ">{{ labels('admin_labels.select_type', 'Select Type') }}</option>
                                            <option value="simple_product">{{ labels('admin_labels.simple_product_option', 'Simple Product') }}</option>
                                            <option value="variable_product">{{ labels('admin_labels.variable_product_option', 'Variable Product') }}</option>
                                        </select>
                                        {{-- <select name="type" id="product-type" class="form-control form-select"
                                                    data-placeholder=" Type to search and select type">
                                                    <option value="">
                                                        {{ labels('admin_labels.select_type', 'Select Type') }}
                                                    </option>
                                                    <option value="simple_product">Simple Product
                                                    </option>
                                                    <option value="variable_product">Variable Product
                                                    </option>

                                                </select> --}}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Guide for Variable Product Variations --}}
                        <div class="alert alert-info mt-3 d-none" id="variation-guide" role="alert">
                            <div class="d-flex align-items-start">
                                <i class="fa fa-info-circle fa-2x me-3 mt-1"></i>
                                <div>
                                    <h6 class="alert-heading mb-2">
                                        <strong>{{ labels('admin_labels.how_to_add_variations', 'How to Add Product Variations') }}</strong>
                                    </h6>
                                    <p class="mb-2">
                                        {{ labels('admin_labels.variation_guide_intro', 'Follow these steps to add variations to your variable product:') }}
                                    </p>
                                    <ol class="mb-0 ps-3">
                                        <li class="mb-1">
                                            <strong>{{ labels('admin_labels.step_1', 'Step 1') }}:</strong>
                                            {{ labels('admin_labels.variation_step_1', 'Configure stock management settings below (if needed) and click "Save Settings" button') }}
                                        </li>
                                        <li class="mb-1">
                                            <strong>{{ labels('admin_labels.step_2', 'Step 2') }}:</strong>
                                            {{ labels('admin_labels.variation_step_2', 'Go to "Attributes" tab and add product attributes (e.g., Size, Color)') }}
                                        </li>
                                        <li class="mb-1">
                                            <strong>{{ labels('admin_labels.step_3', 'Step 3') }}:</strong>
                                            {{ labels('admin_labels.variation_step_3', 'Check the checkbox next to attributes you want to use for variations') }}
                                        </li>
                                        <li class="mb-1">
                                            <strong>{{ labels('admin_labels.step_4', 'Step 4') }}:</strong>
                                            {{ labels('admin_labels.variation_step_4', 'Click "Save Attributes" button') }}
                                        </li>
                                        <li class="mb-0">
                                            <strong>{{ labels('admin_labels.step_5', 'Step 5') }}:</strong>
                                            {{ labels('admin_labels.variation_step_5', 'The "Variations" tab will appear - click it to manage your product variations') }}
                                        </li>
                                    </ol>
                                </div>
                            </div>
                        </div>

                        <div id="attributes_values_json_data" class="d-none">
                            <select class="select_single" data-placeholder="{{ labels('admin_labels.type_to_search_and_select_attributes', 'Type to search and select attributes') }}">
                                <option name='' value='' data-values=''> {{ labels('admin_labels.select_an_option', 'Select an option') }} </option>
                                @foreach ($attributes as $attribute)
                                    @php
                                        $data = json_encode($attribute->attribute_values, 1);
                                    @endphp
                                    <option name='{{ $attribute->name }}' value='{{ $attribute->name }}'
                                        data-values='{{ json_encode($attribute->attribute_values, 1) }}'>
                                        {{ $attribute->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        {{-- <div id="attributes_values_json_data" class="d-none">
                                    <select class="select_single" data-placeholder="Type to search and select attributes">
                                        <option value="">Select an option</option>
                                    </select>
                                </div> --}}
                        <div class="tab-content p-3 col-md-12" id="nav-tabContent">
                            <div class="tab-pane fade active show" id="general-settings" role="tabpanel"
                                aria-labelledby="general-settings-tab">
                                <div id="product-general-settings">
                                    <div id="general_price_section" class="collapse">
                                        <div class="row">
                                            <div class="col-md-6">

                                                <ul>
                                                    <li>
                                                        <h6>{{ labels('admin_labels.price_info', 'Price Info') }}
                                                        </h6>
                                                    </li>
                                                </ul>

                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <label for="simple_price"
                                                            class="col-md-6 form-label">{{ labels('admin_labels.price', 'Price') }}:
                                                            <span class="text-asterisks text-sm">*</span></label>
                                                        <input type="number" name="simple_price"
                                                            class="form-control stock-simple-mustfill-field price"
                                                            min="0.01" step="0.01">
                                                    </div>
                                                </div>
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <label for="type"
                                                            class="col-md-6 form-label">{{ labels('admin_labels.special_price', 'Special Price') }}
                                                            : <span class="text-asterisks text-sm">*</span></label>
                                                        <input type="number" name="simple_special_price"
                                                            class="form-control discounted_price" min="0"
                                                            step="0.01">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="dimensions " id="product-dimensions">

                                                    <ul>
                                                        <li>
                                                            <h6>{{ labels('admin_labels.standard_shipping_weightage', 'Standard shipping weightage') }}
                                                            </h6>
                                                        </li>
                                                    </ul>
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <div class="form-group">
                                                                <label for="weight"
                                                                    class="form-label col-md-12">{{ labels('admin_labels.weight', 'Weight') }}
                                                                    <small>(kg)</small></span></label>
                                                                <input type="number" class="form-control" name="weight"
                                                                    placeholder="{{ labels('admin_labels.weight_placeholder', 'Weight') }}" id="weight" min=0
                                                                    value="0" step="0.01">
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <div class="form-group">
                                                                <label for="height"
                                                                    class="form-label col-md-12">{{ labels('admin_labels.height', 'Height') }}
                                                                    <small>(cms)</small></label>
                                                                <input type="number" class="form-control" name="height"
                                                                    placeholder="{{ labels('admin_labels.height_placeholder', 'Height') }}" id="height" min=0
                                                                    value="0" step="0.01">

                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <div class="form-group">
                                                                <label for="breadth"
                                                                    class="form-label col-md-12">{{ labels('admin_labels.bredth', 'Bredth') }}
                                                                    <small>(cms)</small> </label>
                                                                <input type="number" class="form-control" name="breadth"
                                                                    placeholder="{{ labels('admin_labels.breadth_placeholder', 'Breadth') }}" id="breadth" min=0
                                                                    value="0" step="0.01">
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <div class="form-group">
                                                                <label for="length"
                                                                    class="form-label col-md-12">{{ labels('admin_labels.length', 'Length') }}
                                                                    <small>(cms)</small> </label>
                                                                <input type="number" class="form-control" name="length"
                                                                    placeholder="{{ labels('admin_labels.length_placeholder', 'Length') }}" id="length" value="0"
                                                                    min=0 step="0.01">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group  simple_stock_management">
                                            <div class="col">
                                                <input type="checkbox" name="simple_stock_management_status"
                                                    class="align-middle simple_stock_management_status form-check-input m-0">
                                                <span
                                                    class="align-middle">{{ labels('admin_labels.enable_stock_management', 'Enable Stock Management') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="simple-product-level-stock-management collapse">
                                        <div class="row d-flex">
                                            <div class="col col-xs-4 col-md-4">
                                                <div class="form-group">
                                                    <label class="form-label">{{ labels('admin_labels.sku', 'Sku') }}
                                                        :</label>
                                                    <input type="text" name="product_sku"
                                                        class="col form-control simple-pro-sku" value="">
                                                </div>
                                            </div>
                                            <div class="col col-xs-4 col-md-4">
                                                <div class="form-group">
                                                    <label
                                                        class="form-label">{{ labels('admin_labels.total_stock', 'Total Stock') }}
                                                        :</label>
                                                    <input type="number" min="0" name="product_total_stock"
                                                        class="col form-control stock-simple-mustfill-field">
                                                </div>
                                            </div>
                                            <div class="col col-xs-4 col-md-4">
                                                <div class="form-group">
                                                    <label
                                                        class="form-label">{{ labels('admin_labels.stock_status', 'Stock Status') }}
                                                        :</label>
                                                    <select type="text"
                                                        class="col form-control form-select stock-simple-mustfill-field"
                                                        id="simple_product_stock_status">
                                                        <option value="1">
                                                            {{ labels('admin_labels.in_stock', 'In Stock') }}</option>
                                                        <option value="0">
                                                            {{ labels('admin_labels.out_of_stock', 'Out Of Stock') }}</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group collapse simple-product-save">
                                        <div class="col-md-12">
                                            <a href="javascript:void(0);"
                                                class="btn btn-dark save-settings float-end">{{ labels('admin_labels.save_settings', 'Save Settings') }}</a>
                                        </div>
                                    </div>
                                </div>
                                <div id="variant_stock_level" class="collapse">
                                    <div class="form-group">
                                        <div class="col">
                                            <input type="checkbox" name="variant_stock_management_status"
                                                class="align-middle variant_stock_status form-check-input m-0">
                                            <span class="align-middle">
                                                {{ labels('admin_labels.enable_stock_management', 'Enable Stock Mamagement') }}</span>
                                        </div>
                                    </div>
                                    <div class="form-group collapse" id="stock_level">
                                        <label for="type"
                                            class="col-md-12 form-label">{{ labels('admin_labels.choose_stock_management_type', 'Choose Stock Management Type') }}:</label>
                                        <div class="col-md-12">
                                            <select id="stock_level_type" class="form-select variant-stock-level-type"
                                                data-placeholder="{{ labels('admin_labels.type_to_search_and_select_type', 'Type to search and select type') }}">
                                                <option value=" ">
                                                    {{ labels('admin_labels.select_stock_type', 'Select Stock Type') }}
                                                </option>
                                                <option value="product_level">{{ labels('admin_labels.product_level_option', 'Product Level ( Stock Will Be Managed Generally )') }}</option>
                                                <option value="variable_level">{{ labels('admin_labels.variable_level_option', 'Variable Level ( Stock Will Be Managed Variant Wise )') }}</option>
                                            </select>
                                            <div class="form-group variant-product-level-stock-management collapse">
                                                <div class="row d-flex mt-5">
                                                    <div class="col col-xs-4 col-md-4">
                                                        <div class="form-group">
                                                            <label
                                                                class="form-label">{{ labels('admin_labels.sku', 'Sku') }}
                                                                :</label>
                                                            <input type="text" name="sku_variant_type"
                                                                class="col form-control">
                                                        </div>
                                                    </div>
                                                    <div class="col col-xs-4 col-md-4">
                                                        <div class="form-group">
                                                            <label
                                                                class="form-label">{{ labels('admin_labels.total_stock', 'Total Stock') }}:</label>
                                                            <input type="number" min="1"
                                                                name="total_stock_variant_type"
                                                                class="col form-control variant-stock-mustfill-field">
                                                        </div>
                                                    </div>
                                                    <div class="col col-xs-4 col-md-4">
                                                        <div class="form-group">
                                                            <label
                                                                class="form-label">{{ labels('admin_labels.stock_status', 'Stock Status') }}:</label>
                                                            <select type="text" id="stock_status_variant_type"
                                                                name="variant_status"
                                                                class="col form-select form-control variant-stock-mustfill-field">
                                                                <option value="1">{{ labels('admin_labels.in_stock', 'In Stock') }}
                                                                </option>
                                                                <option value="0">{{ labels('admin_labels.out_of_stock', 'Out Of Stock') }}
                                                                </option>
                                                            </select>
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col float-end"> <a href="javascript:void(0);"
                                                class="btn btn-dark save-variant-general-settings">{{ labels('admin_labels.save_settings', 'Save Settings') }}</a>
                                        </div>
                                    </div>
                                </div>
                                <div id="digital_product_setting" class="collapse">
                                    <div class="row form-group">
                                        <div class="col-md-6 d-flex">
                                            <label for="download_allowed"
                                                class="col form-label">{{ labels('admin_labels.is_download_allowed', 'IS Download Allowed') }}?</label>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="download_allowed"
                                                    id="download_allowed">
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-xs-6 collapse" id='download_type'>
                                            <label for="download_link_type"
                                                class="col form-label">{{ labels('admin_labels.download_link_type', 'Download Link Type') }}
                                            </label>
                                            <select class='form-control form-select' name="download_link_type"
                                                id="download_link_type">
                                                <option value=''>{{ labels('admin_labels.none_option', 'None') }}</option>
                                                <option value='self_hosted'>{{ labels('admin_labels.self_hosted_option', 'Self Hosted') }}</option>
                                                <option value='add_link'>{{ labels('admin_labels.add_link_option', 'Add Link') }}</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 d-none" id="digital_link_container">
                                            <label for="video"
                                                class="col form-label ml-1">{{ labels('admin_labels.digital_product_link', 'Digital Product Link') }}
                                                <span class='text-asterisks text-sm'>*</span></label>
                                            <input type="url" class='form-control' name='download_link'
                                                id='download_link' value=""
                                                placeholder="{{ labels('admin_labels.paste_digital_product_link_placeholder', 'Paste digital product link or URL here') }}">
                                        </div>

                                        <div class="container-fluid row image-upload-section">
                                        </div>
                                        <div class="form-group d-none mt-5" id="digital_media_container">
                                            <a class="media_link" data-input="pro_input_zip" data-isremovable="0"
                                                data-is-multiple-uploads-allowed="0" data-media_type="archive,document"
                                                data-bs-toggle="modal" data-bs-target="#media-upload-modal"
                                                value="{{ labels('admin_labels.upload_photo', 'Upload Photo') }}">
                                                <div class="col-md-6 file_upload_box border file_upload_border">
                                                    <div class="mt-2">
                                                        <div class="col-md-12  text-center">
                                                            <div>
                                                                <p class="caption text-dark-secondary">
                                                                    {{ labels('admin_labels.choose_video_for_product', 'Choose video for product.') }}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </a>
                                            <div class="row mt-3 image-upload-section">
                                            </div>
                                        </div>

                                        <div class="form-group mt-4">
                                            <div class="col float-end">
                                                <a href="javascript:void(0);"
                                                    class="btn btn-dark save-digital-product-settings">{{ labels('admin_labels.save_settings', 'Save Settings') }}</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane" id="product-attributes" role="tabpanel"
                                aria-labelledby="product-attributes-tab">
                                <div class="d-flex">
                                    <div class="info col-md-6 p-3" id="note">
                                        <div class="col-12 d-flex align-center">
                                            <strong>{{ labels('admin_labels.note', 'Note') }}
                                                :</strong>
                                            <input type="checkbox" checked=""
                                                class="ml-3 my-auto custom-checkbox form-check-input ms-1 me-1" readonly>
                                            <span class="ml-3">{{ labels('admin_labels.check_if_attribute_used_for_variation', 'check if the attribute is to be used for variation') }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6 d-flex justify-content-end">
                                        <button type="button" id="add_attributes"
                                            class="btn  btn-primary m-2 btn-xs">{{ labels('admin_labels.add_attributes', 'Add Attributes') }}</button>
                                        <a href="javascript:void(0);" id=""
                                            class="save_attributes btn btn-dark m-2 btn-xs d-none">{{ labels('admin_labels.save_attributes', 'Save Attributes') }}</a>
                                    </div>
                                </div>
                                <div class="clearfix"></div>
                                <div id="attributes_process">
                                    <div
                                        class="form-group text-center row my-auto p-2 border rounded bg-gray-light col-md-12 no-attributes-added">
                                        <div class="col-md-12 text-center">
                                            {{ labels('admin_labels.no_product_attributes_are_added', 'No Product Attributes Are Added') }}!
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="product-variants" role="tabpanel"
                                aria-labelledby="product-variants-tab">
                                <div class="col-md-12">
                                    <a href="javascript:void(0);" id="reset_variants"
                                        class="btn btn-block btn-outline-primary col-md-2 float-right m-2 btn-sm collapse">{{ labels('admin_labels.reset_variants', 'Reset Variants') }}</a>
                                </div>

                                <div class="clearfix"></div>
                                <div
                                    class="form-group text-center row my-auto p-2 border rounded bg-gray-light col-md-12 no-variants-added">
                                    <div class="col-md-12 text-center">
                                        {{ labels('admin_labels.no_product_variations_added', 'No Product Variations Added') }}!
                                    </div>
                                </div>
                                <div id="variants_process" class="ui-sortable">
                                    <div class="form-group move p-2 pe-0 product-variant-selectbox ps-0 pt-3 rounded row">
                                        <div class="col-1 text-center my-auto">
                                            <i class="fas fa-sort"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card mt-4 p-5">
        <div class="row">
            <div class="col-6 col-xxl-12">
                <div class="card p-5">
                    <h6>{{ labels('admin_labels.product_media', 'Product Media') }}(
                        {{ labels('admin_labels.images', 'Images') }} )
                    </h6>
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <label class="form-label">{{ labels('admin_labels.main_image', 'Main Image') }}<span
                                    class="text-asterisks text-sm">*</span>
                            </label>
                            <div class="form-group">
                                <a class="media_link" data-input="pro_input_image" data-isremovable="0"
                                    data-is-multiple-uploads-allowed="0" data-bs-toggle="modal"
                                    data-bs-target="#media-upload-modal" value="{{ labels('admin_labels.upload_photo', 'Upload Photo') }}">

                                    <div class="col-md-12 file_upload_box border file_upload_border">
                                        <div class="mt-2">
                                            <div class="col-md-12  text-center">
                                                <div>
                                                    <p class="caption text-dark-secondary">{{ labels('admin_labels.choose_image_for_product', 'Choose image for product.') }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                                <p class="image_recommendation mt-2">({{ labels('admin_labels.recommended_size_180x180', 'Recommended Size : 180 x 180 pixels') }})</p>
                                <div class="col-md-6 container-fluid row mt-3 image-upload-section">
                                    <div
                                        class="col-md-12 col-sm-12 p-3 mb-5 bg-white rounded m-4 text-center grow image d-none">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ labels('admin_labels.other_images', 'Other Images') }}</label>
                            <div class="form-group">
                                <a class="media_link" data-input="other_images[]" data-isremovable="1"
                                    data-is-multiple-uploads-allowed="1" data-bs-toggle="modal"
                                    data-bs-target="#media-upload-modal" value="{{ labels('admin_labels.upload_photo', 'Upload Photo') }}">

                                    <div class="col-md-12 file_upload_box border file_upload_border">
                                        <div class="mt-2">
                                            <div class="col-md-12  text-center">
                                                <div>
                                                    <p class="caption text-dark-secondary">{{ labels('admin_labels.choose_images_for_product', 'Choose images for product.') }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                                <p class="image_recommendation mt-2">({{ labels('admin_labels.recommended_size_180x180', 'Recommended Size : 180 x 180 pixels') }})</p>
                                <div class="row mt-3 image-upload-section">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card mt-4 p-5">
        <div class="row">
            <div class="col-6 col-xxl-12">
                <div class="card p-5">
                    <h6>{{ labels('admin_labels.product_media', 'Product Media') }} (
                        {{ labels('admin_labels.videos', 'Videos') }} )
                    </h6>

                    <div class="row mt-4">
                        <div class="form-group col-md-6">
                            <label for="video_type"
                                class="form-label">{{ labels('admin_labels.video_type', 'Video Type') }}
                            </label>
                            <select class="form-select" name="video_type" id="video_type">
                                <option value="" selected>{{ labels('admin_labels.none_option', 'None') }}</option>
                                <option value="self_hosted">{{ labels('admin_labels.self_hosted_option', 'Self Hosted') }}</option>
                                <option value="youtube">{{ labels('admin_labels.youtube_option', 'Youtube') }}</option>
                                <option value="vimeo">{{ labels('admin_labels.vimeo_option', 'Vimeo') }}</option>
                            </select>
                        </div>

                        <div class="col-md-6 d-none" id="video_link_container">
                            <label for="video"
                                class="form-label">{{ labels('admin_labels.video_link', 'Video Link') }}
                                <span class="text-asterisks text-sm">*</span>
                            </label>
                            <input type="text" class="form-control" name="video" id="video"
                                placeholder="{{ labels('admin_labels.paste_video_link_placeholder', 'Paste Youtube / Vimeo Video link or URL here') }}">
                        </div>

                        <div class="col-md-6 d-none" id="video_media_container">
                            <label for="" class="form-label">{{ labels('admin_labels.video_label', 'Video') }}<span class="text-asterisks text-sm">*</span>
                            </label>
                            <div class="form-group">
                                <a class="media_link" data-input="pro_input_video" data-isremovable="1"
                                    data-is-multiple-uploads-allowed="0" data-media_type="video" data-bs-toggle="modal"
                                    data-bs-target="#media-upload-modal" value="{{ labels('admin_labels.upload_photo', 'Upload Photo') }}">

                                    <div class="col-md-12 file_upload_box border file_upload_border">
                                        <div class="mt-2">
                                            <div class="col-md-12  text-center">
                                                <div>
                                                    <p class="caption text-dark-secondary">{{ labels('admin_labels.choose_video_for_product', 'Choose video for product.') }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                                <div class="row mt-3 image-upload-section">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card p-5 mt-4">
        <div class="col col-xxl-12">
            <h6>{{ labels('admin_labels.product_description', 'Product Description') }}</h6>
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label mb-0">{{ labels('admin_labels.description', 'Description') }}</label>
                        <div class="d-flex align-items-center gap-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input custom_description_prompt_toggle" type="checkbox">
                                <label class="form-check-label small" for="custom_description_prompt_toggle">{{ labels('admin_labels.custom_prompt_label', 'Custom Prompt') }}</label>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary generate_description">
                                {{ labels('admin_labels.generate_with_ai', 'Generate with AI') }}
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" data-container="body"
                                data-bs-toggle="popover" data-bs-placement="top"
                                data-bs-content="{{ labels('admin_labels.description_ai_prompt_hint', 'Enter the product name first to generate a description. To improve results, enable the custom prompt option—you can write your own.') }}">
                                {{ labels('admin_labels.how_it_works', 'How It Works?') }}
                            </button>
                        </div>

                    </div>
                    <textarea class="form-control mt-2 d-none mb-2 custom_description_prompt" name="custom_description_prompt"
                        placeholder="{{ labels('admin_labels.placeholder_custom_ai_prompt', 'Enter your own AI prompt (optional)...') }}"></textarea>
                    <textarea id="pro_input_description" name="pro_input_description"
                        class="form-control addr_editor pro_input_description" placeholder="{{ labels('admin_labels.place_some_text_here', 'Place some text here') }}"></textarea>
                </div>


                <div class="col-md-6">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label
                            class="form-label">{{ labels('admin_labels.extra_description', 'Extra Description') }}</label>
                        <div class="d-flex align-items-center gap-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input custom_extra_description_prompt_toggle" type="checkbox">
                                <label class="form-check-label small" for="custom_extra_description_prompt_toggle">{{ labels('admin_labels.custom_prompt_label', 'Custom Prompt') }}</label>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary generate_extra_description">
                                {{ labels('admin_labels.generate_with_ai', 'Generate with AI') }}
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" data-container="body"
                                data-bs-toggle="popover" data-bs-placement="top"
                                data-bs-content="{{ labels('admin_labels.description_ai_prompt_hint', 'Enter the product name first to generate a description. To improve results, enable the custom prompt option—you can write your own.') }}">
                                {{ labels('admin_labels.how_it_works', 'How It Works?') }}
                            </button>
                        </div>

                    </div>
                    <textarea class="form-control mt-2 d-none mb-2 custom_extra_description_prompt" name="custom_extra_description_prompt"
                        placeholder="{{ labels('admin_labels.placeholder_custom_ai_prompt', 'Enter your own AI prompt (optional)...') }}"></textarea>
                    {{-- <textarea id="pro_input_description" name="pro_input_description"
                            class="form-control addr_editor pro_input_description" placeholder="Place some text here"></textarea> --}}
                    <textarea id="extra_input_description" name="extra_input_description"
                        class="form-control addr_editor extra_input_description" placeholder="{{ labels('admin_labels.place_some_text_here', 'Place some text here') }}"></textarea>
                </div>
            </div>
        </div>
    </div>
    <div class="float-end ml-2 mt-xxl-3 mt-7 text-center">
        <button type="submit" id="submit_btn"
            class="btn btn-primary submit_button">{{ labels('admin_labels.submit', 'Submit') }}</button>
    </div>
    </form>
    </div>
@endsection
