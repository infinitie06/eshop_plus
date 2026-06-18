@include('admin.include_css')
@php
    use App\Models\Store;
    use App\Services\TranslationService;
    use App\Services\SettingService;
    $language_code = app(TranslationService::class)->getLanguageCode();
    $settings = app(SettingService::class)->getSettings('admin_preference', true);

    // Decode JSON string if it's not already an array
if (is_string($settings)) {
    $settings = json_decode($settings, true);
}

// Now you can access
$storeMode = $settings['store_mode'] ?? null;

@endphp
<div id="app_url" data-app-url="{{ config('app.url') }}"></div>

<div class="page-header min-vh-100">
    <div class="col-md-12">
        <div class="d-flex flex-column justify-content-center align-items-center">
            <div class="card w-75">
                <div class="card-body">
                    <h2>Seller Registration</h2>
                    <form action="{{ route('seller.register.store') }}" enctype="multipart/form-data" class="submit_form"
                        method="POST">
                        @csrf
                        <input type="hidden" name="form_seller" value="1">
                        <div class="col-md-12">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="mb-3">
                                                {{ labels('admin_labels.seller_details', 'Seller Details') }}
                                            </h5>
                                            <div class="row">
                                                <div class="mb-3 col-md-12">
                                                    <label for="firstName"
                                                        class="form-label">{{ labels('admin_labels.name', 'Name') }}
                                                        <span class="text-asterisks text-sm">*</span></label>
                                                    <input class="form-control" type="text" id="name"
                                                        name="name" value="{{ old('name') }}" autofocus />
                                                </div>
                                                <div class="mb-3 col-md-12">
                                                    <label class="form-label"
                                                        for="phone">{{ labels('admin_labels.mobile', 'Mobile') }}
                                                        <span class="text-asterisks text-sm">*</span></label>
                                                    <div class="input-group input-group-merge">
                                                           @php
        $settingsJson = app(SettingService::class)->getSettings('system_settings');
        $systemSettings = json_decode($settingsJson, true);
        $defaultCountryCode = $systemSettings['country_code'] ?? '91';
        $countryCodes = [
    '93','355','213','1684','376','244','1264','1268','54','374','297','61','43','994','1242','973','880','1246','375',
    '32','501','229','1441','975','591','387','267','55','246','673','359','226','257','855','237','1','238','1345',
    '236','235','56','86','61','57','269','682','506','385','53','599','357','420','45','253','1767','1809','593','20',
    '503','240','291','372','251','500','298','679','358','33','594','689','241','220','995','49','233','350','30','299',
    '1473','590','1671','502','224','245','592','509','504','852','36','354','91','62','98','964','353','972','39','1876',
    '81','962','7','254','686','82','965','996','856','371','961','266','231','218','423','370','352','853','389','261',
    '265','60','960','223','356','692','596','222','230','262','52','691','373','377','976','382','1664','212','258','95',
    '264','674','977','31','687','64','505','227','234','683','672','850','47','968','92','680','970','507','675','595',
    '51','63','48','351','1787','974','40','250','685','378','239','966','221','381','248','232','65','421','386','677',
    '252','27','34','94','249','597','268','46','41','963','886','992','255','66','228','690','676','1868','216','90',
    '993','688','256','380','971','44','598','998','678','379','58','84','1284','1340','681','967','260','263'
];
    @endphp

    <select class="form-select select-country-code" id="country_code" name="country_code">
        @foreach($countryCodes as $code)
            <option value="{{ $code }}" {{ (string)$code === (string)$defaultCountryCode ? 'selected' : '' }}>
                +{{ $code }}
            </option>
        @endforeach
    </select>

                                                        <input type="number" id="phone" name="mobile"
                                                            min='1' class="form-control" placeholder=""
                                                            value="{{ old('phone') }}" maxlength="16"
                                                            oninput="validateNumberInput(this)" />
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="mb-3 col-md-12">
                                                    <label class="form-label"
                                                        for="email">{{ labels('admin_labels.email', 'Email') }}
                                                        <span class="text-asterisks text-sm">*</span></label>
                                                    <div class="input-group input-group-merge">
                                                        <input class="form-control" type="email" name="email"
                                                            value="{{ old('email') }}">
                                                    </div>
                                                </div>
                                                <div class="mb-3 col-md-12 form-password-toggle">
                                                    <label class="form-label"
                                                        for="password">{{ labels('admin_labels.password', 'Password') }}
                                                        <span class="text-asterisks text-sm">*</span></label>
                                                    <div class="input-group input-group-merge">

                                                        <input type="password"
                                                            class="form-control show_seller_password" name="password"
                                                            placeholder="Enter Your Password">
                                                        <span
                                                            class="input-group-text cursor-pointer toggle_password"><i
                                                                class="bx bx-hide"></i></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="mb-3 col-md-12 form-password-toggle">
                                                    <label class="form-label"
                                                        for="password">{{ labels('admin_labels.confirm_password', 'Confirm Password') }}
                                                        <span class="text-asterisks text-sm">*</span></label>
                                                    <div class="input-group input-group-merge">
                                                        <input type="password" class="form-control"
                                                            name="confirm_password" placeholder="Enter your password"
                                                            aria-describedby="password" />
                                                        <span
                                                            class="input-group-text cursor-pointer toggle_confirm_password"><i
                                                                class="bx bx-hide"></i></span>
                                                    </div>
                                                </div>
                                                <div class="mb-3 col-md-12 form-password-toggle">
                                                    <label class="form-label"
                                                        for="address">{{ labels('admin_labels.address', 'Address') }}
                                                        <span class="text-asterisks text-sm">*</span></label>
                                                    <textarea name="address" class="form-control" placeholder="Write here your address">{{ old('address') }}</textarea>

                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="form-group col-md-12">
                                                    <div class="mb-3">
                                                        <label class="form-label"
                                                            for="basic-default-phone">{{ labels('admin_labels.address_proof', 'Address Proof') }}
                                                            <span class="text-asterisks text-sm">*</span></label>

                                                        <input type="file" class="filepond" name="address_proof"
                                                            multiple data-max-file-size="30MB" data-max-files="20"
                                                            accept="image/*,.webp" />

                                                    </div>
                                                </div>
                                                <div class="form-group col-md-12">
                                                    <div class="mb-3">
                                                        <label class="form-label"
                                                            for="basic-default-phone">{{ labels('admin_labels.authorized_signature', 'Authorized Signature') }}
                                                            <span class="text-asterisks text-sm">*</span></label>

                                                        <input type="file" class="filepond"
                                                            name="authorized_signature" multiple
                                                            data-max-file-size="30MB" data-max-files="20"
                                                            accept="image/*,.webp" />

                                                    </div>
                                                </div>
                                                <div class="form-group col-md-12">
                                                    <div class="mb-3">
                                                        <label class="form-label"
                                                            for="basic-default-company">{{ labels('admin_labels.other_documents', 'Other Documents') }}</label>
                                                        <small>(Note for Necessary Documents)</small>
                                                        <input type="file" class="filepond"
                                                            name="other_documents[]" multiple
                                                            data-max-file-size="300MB" data-max-files="200" />
                                                    </div>
                                                </div>
                                                <div class="form-group col-md-12">
                                                    <div class="mb-3">
                                                        <label class="form-label"
                                                            for="basic-default-phone">{{ labels('admin_labels.categories', 'Categories') }}<span
                                                                class="text-asterisks text-sm">*</span>
                                                        </label>

                                                        <select class="form-select category-select w-100"
                                                            id="seller_categories" name="requested_categories[]"
                                                            multiple data-placeholder="Search for categories"
                                                            data-allow-clear="true">
                                                        </select>

                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card mt-4">
                                        <div class="card-body">
                                            <h5 class="mb-3">
                                                {{ labels('admin_labels.store_details', 'Store Details') }}
                                            </h5>

                                            <div class="row">
                                                <div class="mb-3 col-md-12">
                                                    <label class="form-label"
                                                        for="store_name">{{ labels('admin_labels.select_store', 'Select Store') }}
                                                        <span class="text-asterisks text-sm">*</span></label>

                                                    @if ($settings['store_mode'] === 'single')
                                                        <input type="hidden" name="store_id"
                                                            value="{{ $stores->first()->id }}">
                                                        <p class="form-select >
                                                            {{ app(TranslationService::class)->getDynamicTranslation(
                                                                Store::class,
                                                                'name',
                                                                $stores->first()->id,
                                                                $language_code,
                                                            ) }}
                                                        </p>
@else
<select class="form-select
                                                            seller_register_store_id" name="store_id" id="store_id">
                                                            @foreach ($stores as $store)
                                                                <option value="{{ $store->id }}"
                                                                    @if ($loop->first) selected @endif>
                                                                    {{ app(TranslationService::class)->getDynamicTranslation(Store::class, 'name', $store->id, $language_code) }}
                                                                </option>
                                                            @endforeach
                                                            </select>
                                                    @endif



                                                </div>
                                                <div class="mb-3 col-md-12">
                                                    <label class="form-label"
                                                        for="store_name">{{ labels('admin_labels.store_name', 'Store Name') }}
                                                        <span class="text-asterisks text-sm">*</span></label>
                                                    <div class="input-group input-group-merge">
                                                        <input type="text" name="store_name" class="form-control"
                                                            placeholder="starbucks"
                                                            value="{{ old('store_name') }}" />
                                                    </div>
                                                </div>

                                                <div class="mb-3 col-md-12">
                                                    <label class="form-label"
                                                        for="store_url">{{ labels('admin_labels.store_url', 'Store URL') }}
                                                    </label>
                                                    <div class="input-group input-group-merge">
                                                        <input type="text" name="store_url" class="form-control"
                                                            placeholder="starbucks" value="{{ old('store_url') }}" />
                                                    </div>

                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="form-group col-md-12">
                                                    <div class="mb-3">
                                                        <label class="form-label"
                                                            for="basic-default-phone">{{ labels('admin_labels.logo', 'Logo') }}
                                                            <span class="text-asterisks text-sm">*</span></label>
                                                        <input type="file" class="filepond" name="store_logo"
                                                            multiple data-max-file-size="30MB" accept="image/*,.webp"
                                                            data-max-files="20" />


                                                    </div>
                                                </div>
                                                <div class="form-group col-md-12">
                                                    <div class="mb-3">
                                                        <label class="form-label"
                                                            for="basic-default-phone">{{ labels('admin_labels.store_thumbnail', 'Store Thumbnail') }}
                                                            <span class="text-asterisks text-sm">*</span></label>
                                                        <input type="file" class="filepond" name="store_thumbnail"
                                                            multiple data-max-file-size="30MB" data-max-files="20"
                                                            accept="image/*,.webp" />


                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="form-group col-md-12">
                                                    <div class="mb-3">
                                                        <label class="form-label"
                                                            for="basic-default-company">{{ labels('admin_labels.description', 'Description') }}
                                                            <span class="text-asterisks text-sm">*</span></label>
                                                        <textarea id="basic-default-message" value="" name="description" class="form-control"
                                                            placeholder="Write some description here">{{ old('description') }}</textarea>

                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group city_list_parent">
                                                        <label for="city"
                                                            class="control-label mb-2 mt-2">{{ labels('admin_labels.city', 'City') }}
                                                            <span class='text-asterisks text-xs'>*</span></label>
                                                        <select class="form-select city_list" name="city"
                                                            id="">
                                                            <option value=" ">
                                                                {{ labels('admin_labels.select_city', 'Select City') }}
                                                            </option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="city"
                                                            class="control-label mb-2 mt-2">{{ labels('admin_labels.zipcode', 'Zipcode') }}
                                                            <span class='text-asterisks text-xs'>*</span></label>
                                                        <select class="form-select zipcode_list" name="zipcode"
                                                            id="">
                                                            <option value=" ">
                                                                {{ labels('admin_labels.select_zipcode', 'Select Zipcode') }}
                                                            </option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6 ">
                                                    <div class="form-group">
                                                        <label for="zipcode"
                                                            class="form-label">{{ labels('admin_labels.deliverable_type', 'Deliverable Type') }}</label>
                                                        <select class="form-select" name="deliverable_type"
                                                            id="deliverable_type">
                                                            <option value="1" selected>All</option>
                                                            <option value="2">Specific</option>
                                                            {{-- <option value="3">Excluded</option> --}}
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="cities"
                                                            class="form-label">{{ labels('admin_labels.deliverable_zones', 'Deliverable Zones') }}
                                                            <span class="text-asterisks text-sm">*</span></label>
                                                        <select name="deliverable_zones[]"
                                                            class="search_all_zone form-select w-100" multiple
                                                            id="deliverable_zones" disabled>
                                                            <option value="">
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card mt-4">
                                        <div class="card-body">
                                            <h5 class="mb-3">
                                                {{ labels('admin_labels.other_details', 'Other Details') }}
                                            </h5>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <label for="tax_name"
                                                            class="form-label">{{ labels('admin_labels.tax_name', 'Tax Name') }}
                                                        </label>
                                                        <div>
                                                            <input type="text" class="form-control" id="tax_name"
                                                                placeholder="Tax Name" name="tax_name">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <label for="tax_number"
                                                            class="form-label">{{ labels('admin_labels.tax_number', 'Tax Number') }}
                                                        </label>
                                                        <div>
                                                            <input type="text" class="form-control"
                                                                id="tax_number" placeholder="Tax Number"
                                                                name="tax_number">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <label for="pan_number"
                                                            class="form-label">{{ labels('admin_labels.pan_number', 'Pan Number') }}</label>
                                                        <div>
                                                            <input type="text" class="form-control"
                                                                id="pan_number" placeholder="Pan Number"
                                                                name="pan_number">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <label for="latitude"
                                                            class="form-label">{{ labels('admin_labels.latitude', 'Latitude') }}</label>
                                                        <div>
                                                            <input type="text" class="form-control" id="latitude"
                                                                placeholder="Latitude" name="latitude">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <label for="longitude"
                                                            class="form-label">{{ labels('admin_labels.longitude', 'Longitude') }}</label>
                                                        <div>
                                                            <input type="text" class="form-control" id="longitude"
                                                                placeholder="Longitude" name="longitude">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <label for="national_identity_card"
                                                            class="form-label">{{ labels('admin_labels.national_identity_card', 'National Identity Card') }}
                                                        </label>
                                                        <div>

                                                            <input type="file" class="filepond"
                                                                name="national_identity_card" multiple
                                                                data-max-file-size="30MB" data-max-files="20"
                                                                accept="image/*,.webp" />
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                                <div class="col-md-6">
                                    <div class="card mt-4">
                                        <div class="card-body">
                                            <h5 class="mb-3">
                                                {{ labels('admin_labels.bank_details', 'Bank Details') }}
                                            </h5>
                                            <div class="row">
                                                <div class="form-group col-md-12">
                                                    <div class="mb-3">
                                                        <label for="tax_name"
                                                            class="col-sm-4 form-label">{{ labels('admin_labels.account_number', 'Account Number') }}
                                                            <span class='text-asterisks text-sm'>*</span></label>

                                                        <input type="text" class="form-control"
                                                            id="account_number" placeholder="Account Number"
                                                            name="account_number"
                                                            value="{{ old('account_number') }}">

                                                    </div>
                                                </div>
                                                <div class="form-group col-md-12">
                                                    <div class="mb-3">
                                                        <label for="tax_name"
                                                            class="col-sm-4 form-label">{{ labels('admin_labels.account_name', 'Account Name') }}
                                                            <span class='text-asterisks text-sm'>*</span></label>

                                                        <input type="text" class="form-control" id="account_name"
                                                            placeholder="Account Name" name="account_name"
                                                            value="{{ old('account_name') }}">

                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="form-group col-md-12">
                                                    <div class="mb-3">
                                                        <label for="tax_name"
                                                            class="col-sm-4 form-label">{{ labels('admin_labels.bank_name', 'Bank Name') }}
                                                            <span class='text-asterisks text-sm'>*</span></label>

                                                        <input type="text" class="form-control" id="bank_name"
                                                            placeholder="Bank Name" name="bank_name"
                                                            value="{{ old('bank_name') }}">

                                                    </div>
                                                </div>
                                                <div class="form-group col-md-12">
                                                    <div class="mb-3">
                                                        <label for="tax_name"
                                                            class="col-sm-4 form-label">{{ labels('admin_labels.bank_code', 'Bank Code') }}
                                                            <span class='text-asterisks text-sm'>*</span></label>

                                                        <input type="text" class="form-control" id="bank_code"
                                                            placeholder="Bank Code" name="bank_code"
                                                            value="{{ old('bank_code') }}">

                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-4">
                            <button type="reset"
                                class="btn mx-2 reset_button">{{ labels('admin_labels.reset', 'Reset') }}</button>
                            <button type="submit"
                                class="btn btn-primary seller_register_button submit_button">{{ labels('admin_labels.add_seller', 'Add Seller') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>


@include('admin.include_script')
