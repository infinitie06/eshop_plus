@extends('admin/layout')
@section('title')
{{ labels('admin_labels.add_seller', 'Add Seller') }}
@endsection
@section('content')
<x-admin.breadcrumb :title="labels('admin_labels.add_seller', 'Add Seller')" :subtitle="labels(
        'admin_labels.empower_your_marketplace_with_seamless_seller_integration',
        'Empower Your Marketplace with Seamless Seller Integration.',
    )" :breadcrumbs="[
        ['label' => labels('admin_labels.sellers', 'Sellers'), 'url' => route('sellers.index')],
        ['label' => labels('admin_labels.add_seller', 'Add Seller')],
    ]" />

<div>
    <form action="{{ route('sellers.store') }}" enctype="multipart/form-data" class="submit_form" method="POST">
        @csrf
        <textarea cols="20" rows="20" id="cat_data" name="commission_data" class="image-upload-btn"></textarea>
        <div class="col-md-12">
            <div class="row">
                <div class="col-md-12 col-xxl-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="mb-3">
                                {{ labels('admin_labels.seller_details', 'Seller Details') }}
                            </h5>
                            <div class="row">
                                <div class="mb-3 col-md-6">
                                    <label for="firstName" class="form-label">{{ labels('admin_labels.name', 'Name') }}
                                        <span class="text-asterisks text-sm">*</span>
                                    </label>
                                    <input class="form-control" type="text" placeholder="{{ labels('admin_labels.john_doe_placeholder', 'John Doe') }}" id="name"
                                        name="name" value="{{ old('name') }}" autofocus />
                                </div>
                                <div class="mb-3 col-md-6">
                                    <label class="form-label"
                                        for="phone">{{ labels('admin_labels.mobile', 'Mobile') }}
                                        <span class="text-asterisks text-sm">*</span>
                                    </label>
                                    <div class="input-group input-group-merge">
                                        @php
                                        $country_codes = [
                                        93, 355, 213, 1684, 376, 244, 1264, 1268, 54, 374,
                                        297, 61, 43, 994, 1242, 973, 880, 1246, 375, 32,
                                        501, 229, 1441, 975, 591, 387, 267, 55, 246, 673,
                                        359, 226, 257, 855, 237, 1, 238, 1345, 236, 235,
                                        56, 86, 57, 269, 682, 506, 385, 53, 599, 357,
                                        420, 45, 253, 1767, 1809, 593, 20, 503, 240, 291,
                                        372, 251, 500, 298, 679, 358, 33, 594, 689, 241,
                                        220, 995, 49, 233, 350, 30, 299, 1473, 590, 1671,
                                        502, 224, 245, 592, 509, 504, 852, 36, 354, 91,
                                        62, 98, 964, 353, 972, 39, 1876, 81, 962, 7,
                                        254, 686, 82, 965, 996, 856, 371, 961, 266, 231,
                                        218, 423, 370, 352, 853, 389, 261, 265, 60, 960,
                                        223, 356, 692, 596, 222, 230, 262, 52, 691, 373,
                                        377, 976, 382, 1664, 212, 258, 95, 264, 674, 977,
                                        31, 687, 64, 505, 227, 234, 683, 672, 850, 47,
                                        968, 92, 680, 970, 507, 675, 595, 51, 63, 48,
                                        351, 1787, 974, 40, 250, 685, 378, 239, 966, 221,
                                        381, 248, 232, 65, 421, 386, 677, 252, 27, 34,
                                        94, 249, 597, 268, 46, 41, 963, 886, 992, 255,
                                        66, 228, 690, 676, 1868, 216, 90, 993, 1649, 688,
                                        256, 380, 971, 44, 598, 998, 678, 379, 58, 84,
                                        1284, 1340, 681, 967, 260, 263
                                        ];
                                        @endphp

                                        <select name="country_code" class="form-select" id="country_code" style="max-width: 100px;">
                                            @foreach($country_codes as $code)
                                            <option value="{{ $code }}">
                                                +{{ $code }}
                                            </option>
                                            @endforeach
                                        </select>


                                        <input type="text" class="form-control" id="phone" name="mobile"
                                            maxlength="16" placeholder="{{ labels('admin_labels.mobile_number_example', '8787878787') }}"
                                            oninput="validateNumberInput(this)" value="{{ old('mobile') }}">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="mb-3 col-md-6">
                                    <label class="form-label"
                                        for="email">{{ labels('admin_labels.email', 'Email') }}
                                        <span class="text-asterisks text-sm">*</span>
                                    </label>
                                    <div class="input-group input-group-merge">
                                        <input class="form-control" placeholder="{{ labels('admin_labels.email_example_placeholder', 'johndoe@gmail.com') }}" type="email"
                                            name="email" value="{{ old('email') }}">
                                    </div>
                                </div>
                                <div class="mb-3 col-md-6 form-password-toggle">
                                    <label class="form-label"
                                        for="password">{{ labels('admin_labels.password', 'Password') }}
                                        <span class="text-asterisks text-sm">*</span>
                                    </label>
                                    <div class="input-group input-group-merge">

                                        <input type="password" class="form-control show_seller_password"
                                            name="password" placeholder="{{ labels('admin_labels.enter_your_password_placeholder', 'Enter Your Password') }}">
                                        <span class="input-group-text cursor-pointer toggle_password"><i
                                                class="bx bx-hide"></i></span>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="mb-3 col-md-6">
                                    <label class="form-label"
                                        for="password">{{ labels('admin_labels.confirm_password', 'Confirm Password') }}
                                        <span class="text-asterisks text-sm">*</span>
                                    </label>
                                    <div class="input-group input-group-merge">
                                        <input type="password" class="form-control" name="confirm_password"
                                            placeholder="{{ labels('admin_labels.enter_your_password_placeholder_alt', 'Enter your password') }}" aria-describedby="password" />
                                        <span class="input-group-text cursor-pointer toggle_confirm_password"><i
                                                class="bx bx-hide"></i></span>
                                    </div>
                                </div>
                                <div class="mb-3 col-md-6 form-password-toggle">
                                    <label class="form-label"
                                        for="address">{{ labels('admin_labels.address', 'Address') }}
                                        <span class="text-asterisks text-sm">*</span>
                                    </label>
                                    <textarea name="address" class="form-control" placeholder="{{ labels('admin_labels.write_here_your_address', 'Write here your address') }}">{{ old('address') }}</textarea>

                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label"
                                            for="basic-default-phone">{{ labels('admin_labels.profile_image', 'Profile image') }}
                                            <span class="text-asterisks text-sm">*</span>
                                            <i class="fa fa-info-circle text-secondary ms-1" data-bs-toggle="popover"
                                                data-bs-placement="right"
                                                data-bs-content="{{ labels('admin_labels.upload_seller_profile_image', "Upload the seller's profile image.") }}"></i>
                                        </label>

                                        <input type="file" class="filepond" name="profile_image"
                                            data-max-file-size="300MB" data-max-files="200" accept="image/*,.webp"
                                            data-label-idle='{{ labels('admin_labels.drag_drop_files_or', 'Drag & Drop your files or') }} <span class="filepond--label-action">{{ labels('admin_labels.browse', 'Browse') }}</span>' />

                                    </div>
                                </div>
                                <div class="form-group col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label"
                                            for="basic-default-phone">{{ labels('admin_labels.address_proof', 'Address Proof') }}
                                            <span class="text-asterisks text-sm">*</span>
                                            <i class="fa fa-info-circle text-secondary ms-1" data-bs-toggle="popover"
                                                data-bs-placement="right"
                                                data-bs-content="{{ labels('admin_labels.upload_address_proof_document', 'Upload a document as address proof.') }}"></i>
                                        </label>

                                        <input type="file" class="filepond" name="address_proof"
                                            data-max-file-size="300MB" data-max-files="200" accept="image/*,.webp"
                                            data-label-idle='{{ labels('admin_labels.drag_drop_files_or', 'Drag & Drop your files or') }} <span class="filepond--label-action">{{ labels('admin_labels.browse', 'Browse') }}</span>' />

                                    </div>
                                </div>
                                <div class="form-group col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label"
                                            for="basic-default-phone">{{ labels('admin_labels.authorized_signature', 'Authorized Signature') }}
                                            <span class="text-asterisks text-sm">*</span>
                                            <i class="fa fa-info-circle text-secondary ms-1" data-bs-toggle="popover"
                                                data-bs-placement="right"
                                                data-bs-content="{{ labels('admin_labels.upload_authorized_signature_document', 'Upload the authorized signature document.') }}"></i>
                                        </label>

                                        <input type="file" class="filepond" name="authorized_signature"
                                            data-max-file-size="300MB" data-max-files="200" accept="image/*,.webp"
                                            data-label-idle='{{ labels('admin_labels.drag_drop_files_or', 'Drag & Drop your files or') }} <span class="filepond--label-action">{{ labels('admin_labels.browse', 'Browse') }}</span>' />

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 col-xxl-6 mt-md-2 mt-xxl-0">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="mb-3">
                                {{ labels('admin_labels.comission', 'Commission') }}
                            </h5>
                            <div class="form-group col-md-12">
                                <label for="commission"
                                    class="col-sm-12 form-label">{{ labels('admin_labels.comission', 'Commission') }}(%)
                                    <small>({{ labels('admin_labels.commission_global_hint', 'Commission(%) to be given to the Super Admin on order item globally.') }})</small>
                                    <i class="fa fa-info-circle text-secondary ms-1" data-bs-toggle="popover"
                                        data-bs-placement="right"
                                        data-bs-content="{{ labels('admin_labels.set_global_commission_percentage_for_seller', 'Set the global commission percentage for this seller.') }}"></i>
                                </label>

                                <input type="number" class="form-control" min=0 max=100 id="global_commission"
                                    placeholder="{{ labels('admin_labels.enter_commission_to_be_given_to_super_admin', 'Enter Commission(%) to be given to the Super Admin on order item.') }}"
                                    name="global_commission" value="">
                            </div>
                            @php
                            $category_html = getCategoriesOptionHtml($categories);
                            @endphp
                            <div class="form-group row">
                                <label for="commission"
                                    class="col-sm-12 form-label">{{ labels('admin_labels.choose_categories_and_commission', 'Choose Categories & Commission') }}(%)
                                    <i class="fa fa-info-circle text-secondary ms-1" data-bs-toggle="popover"
                                        data-bs-placement="right"
                                        data-bs-content="{{ labels('admin_labels.set_commission_for_specific_categories', 'Set commission for specific categories.') }}"></i>
                                </label>
                                <div class="image-upload-btn" id="cat_html">
                                    <?= $category_html ?>
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <div class="">
                                    {{-- @dd($store_data[0]->seller_id, $store_data[0]->category_ids); --}}
                                    <a href="javascript:void(0)" id="seller_model"
                                        data-seller_id="<?= isset($store_data[0]->seller_id) && !empty($store_data[0]->seller_id) ? $store_data[0]->seller_id : '' ?>"
                                        data-cat_ids="<?= isset($store_data[0]->id) && !empty($store_data[0]->id) && isset($store_data[0]->category_ids) && !empty($store_data[0]->category_ids) ? $store_data[0]->category_ids : '' ?>"
                                        class="btn text-white btn-primary btn-sm"
                                        title="{{ labels('admin_labels.manage_categories_and_commission', 'Manage Categories & Commission') }}" data-bs-toggle="offcanvas"
                                        data-bs-target="#set_commission_offcanvas">
                                        {{ labels('admin_labels.add_category_comission', 'Add Category Commission') }}
                                    </a>
                                </div>
                            </div>
                            <div class="alert alert-info mt-3 mb-3" role="alert">
                                <h6 class="alert-heading fw-bold mb-1"><i class="fas fa-info-circle me-2"></i>{{ labels('admin_labels.how_to_add_and_save_commission', 'How to Add & Save Commission:') }}</h6>
                                <hr class="my-2 text-primary">
                                <ul class="mb-0 small text-dark">
                                    <li><strong>{{ labels('admin_labels.step_1', 'Step 1') }}:</strong> {{ labels('admin_labels.step_1_instruction', 'Click the "Add Category Commission" button.') }}</li>
                                    <li><strong>{{ labels('admin_labels.step_2', 'Step 2') }}:</strong> {{ labels('admin_labels.step_2_instruction', 'In the popup, select your categories and enter the commission percentage for each.') }}</li>
                                    <li><strong>{{ labels('admin_labels.step_3', 'Step 3') }}:</strong> {{ labels('admin_labels.step_3_instruction', 'Click "Save" inside the popup to confirm your selection.') }}</li>
                                    <li><strong>{{ labels('admin_labels.step_4', 'Step 4') }}:</strong> <span class="text-danger fw-bold">{{ labels('admin_labels.important_label', 'IMPORTANT') }}:</span> {{ labels('admin_labels.step_4_instruction', 'Finally, click the "Add Seller" button at the bottom of this page to permanently save all changes.') }}</li>
                                </ul>
                                <hr class="my-2 text-primary">
                                <h6 class="alert-heading fw-bold mb-1 mt-2">{{ labels('admin_labels.commission_logic', 'Commission Logic:') }}</h6>
                                <ul class="mb-0 small text-dark">
                                    <li><strong>{{ labels('admin_labels.global_commission_label', 'Global Commission') }}:</strong> {{ labels('admin_labels.global_commission_description', 'Applies to all products by default if no specific category commission is set.') }}</li>
                                    <li><strong>{{ labels('admin_labels.category_commission_label', 'Category Commission') }}:</strong> {{ labels('admin_labels.category_commission_description', 'Overrides Global Commission for specific categories.') }}</li>
                                </ul>
                            </div>
                            <small class="text-muted d-block mt-2">
                                <strong>{{ labels('admin_labels.note_label', 'Note') }}:</strong> {{ labels('admin_labels.commission_note_text', 'The system first checks for a Category Commission. If one exists, it is used. If not, the Global Commission is applied.') }}
                            </small>

                        </div>
                    </div>
                    <div class="card mt-4">
                        <div class="card-body">
                            <h5 class="mb-3">
                                {{ labels('admin_labels.bank_details', 'Bank Details') }}
                            </h5>
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <div class="mb-3">
                                        <label for="tax_name"
                                            class="col-sm-12 form-label">{{ labels('admin_labels.account_number', 'Account Number') }}
                                            <span class='text-asterisks text-sm'>*</span>
                                        </label>

                                        <input type="text" class="form-control" id="account_number"
                                            placeholder="{{ labels('admin_labels.account_number_placeholder', 'Account Number') }}" name="account_number"
                                            value="{{ old('account_number') }}">

                                    </div>
                                </div>
                                <div class="form-group col-md-6">
                                    <div class="mb-3">
                                        <label for="tax_name"
                                            class="col-sm-4 form-label">{{ labels('admin_labels.account_name', 'Account Name') }}
                                            <span class='text-asterisks text-sm'>*</span>
                                        </label>

                                        <input type="text" class="form-control" id="account_name"
                                            placeholder="{{ labels('admin_labels.account_name_placeholder', 'Account Name') }}" name="account_name"
                                            value="{{ old('account_name') }}">

                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <div class="mb-3">
                                        <label for="tax_name"
                                            class="col-sm-4 form-label">{{ labels('admin_labels.bank_name', 'Bank Name') }}
                                            <span class='text-asterisks text-sm'>*</span>
                                        </label>

                                        <input type="text" class="form-control" id="bank_name"
                                            placeholder="{{ labels('admin_labels.bank_name_placeholder', 'Bank Name') }}" name="bank_name" value="{{ old('bank_name') }}">

                                    </div>
                                </div>
                                <div class="form-group col-md-6">
                                    <div class="mb-3">
                                        <label for="tax_name"
                                            class="col-sm-4 form-label">{{ labels('admin_labels.bank_code', 'Bank Code') }}
                                            <span class='text-asterisks text-sm'>*</span>
                                        </label>

                                        <input type="text" class="form-control" id="bank_code"
                                            placeholder="{{ labels('admin_labels.bank_code_placeholder', 'Bank Code') }}" name="bank_code" value="{{ old('bank_code') }}">

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 col-xxl-6 mt-md-2 mt-xxl-0">
                    <div class="card mt-4">
                        <div class="card-body">
                            <h5 class="mb-3">
                                {{ labels('admin_labels.store_details', 'Store Details') }}
                            </h5>
                            <div class="row">
                                <div class="mb-3 col-md-6">
                                    <label class="form-label"
                                        for="store_name">{{ labels('admin_labels.store_name', 'Store Name') }}
                                        <span class="text-asterisks text-sm">*</span>
                                    </label>
                                    <div class="input-group input-group-merge">
                                        <input type="text" name="store_name" class="form-control"
                                            placeholder="{{ labels('admin_labels.starbucks_placeholder', 'starbucks') }}" value="{{ old('store_name') }}" />
                                    </div>

                                </div>
                                <div class="mb-3 col-md-6">
                                    <label class="form-label"
                                        for="store_url">{{ labels('admin_labels.store_url', 'Store URL') }}
                                    </label>
                                    <div class="input-group input-group-merge">
                                        <input type="text" name="store_url" class="form-control"
                                            placeholder="{{ labels('admin_labels.starbucks_placeholder', 'starbucks') }}" value="{{ old('store_url') }}" />
                                    </div>

                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label"
                                            for="basic-default-phone">{{ labels('admin_labels.logo', 'Logo') }}
                                            <span class="text-asterisks text-sm">*</span>
                                        </label>
                                        <input type="file" class="filepond" name="store_logo"
                                            data-max-file-size="300MB" data-max-files="200" accept="image/*,.webp"
                                            data-label-idle='{{ labels('admin_labels.drag_drop_files_or', 'Drag & Drop your files or') }} <span class="filepond--label-action">{{ labels('admin_labels.browse', 'Browse') }}</span>' />


                                    </div>
                                </div>
                                <div class="form-group col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label"
                                            for="basic-default-phone">{{ labels('admin_labels.store_thumbnail', 'Store Thumbnail') }}
                                            <span class="text-asterisks text-sm">*</span>
                                        </label>
                                        <input type="file" class="filepond" name="store_thumbnail"
                                            data-max-file-size="300MB" data-max-files="200" accept="image/*,.webp"
                                            data-label-idle='{{ labels('admin_labels.drag_drop_files_or', 'Drag & Drop your files or') }} <span class="filepond--label-action">{{ labels('admin_labels.browse', 'Browse') }}</span>' />

                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label"
                                            for="basic-default-company">{{ labels('admin_labels.other_documents', 'Other Documents') }}
                                        </label>
                                        <small>({{ $note_for_necessary_documents }})</small>
                                        <input type="file" class="filepond" name="other_documents[]" multiple
                                            data-max-file-size="300MB" data-max-files="200"
                                            data-label-idle='{{ labels('admin_labels.drag_drop_files_or', 'Drag & Drop your files or') }} <span class="filepond--label-action">{{ labels('admin_labels.browse', 'Browse') }}</span>' />
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label"
                                            for="basic-default-company">{{ labels('admin_labels.description', 'Description') }}
                                            <span class="text-asterisks text-sm">*</span>
                                        </label>
                                        <textarea id="basic-default-message" value="" name="description" class="form-control"
                                            placeholder="{{ labels('admin_labels.write_some_description_here', 'Write some description here') }}">{{ old('description') }}</textarea>

                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group city_list_parent">
                                        <label for="city"
                                            class="control-label mb-2 mt-2">{{ labels('admin_labels.city', 'City') }}
                                            <span class='text-asterisks text-xs'>*</span>
                                        </label>
                                        <select class="form-select city_list" name="city" id="">
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
                                            <span class='text-asterisks text-xs'>*</span>
                                        </label>
                                        <select class="form-select zipcode_list" name="zipcode" id="">
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
                                        <select class="form-select" name="deliverable_type" id="deliverable_type">
                                            <option value="1" selected>{{ labels('admin_labels.all_option', 'All') }}</option>
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
                                        <select name="deliverable_zones[]" class="search_zone form-select w-100"
                                            multiple id="deliverable_zones" disabled>
                                            <option value="">
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for=""
                                    class="form-label">{{ labels('admin_labels.status', 'Status') }}
                                </label>
                                <div class="mt-2">
                                    <div id="stsatus" class="btn-group d-flex justify-content-center"
                                        role="group" aria-label="Status">
                                        <label class="btn status_button btn-outline-secondary flex-fill">
                                            <input type="radio" name="status" class="mx-1" value="0">
                                            {{ labels('admin_labels.deactive_users', 'Deactive') }}
                                        </label>
                                        <label class="btn status_button btn-outline-primary flex-fill">
                                            <input type="radio" name="status" class="mx-1" value="1">
                                            {{ labels('admin_labels.approved_status', 'Approved') }}
                                        </label>
                                        <label class="btn btn-outline-danger flex-fill">
                                            <input type="radio" name="status" class="mx-1" value="2">
                                            {{ labels('admin_labels.not_approved', 'Not-Approved') }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for=""
                                    class="form-label">{{ labels('admin_labels.store_status', 'Store Status') }}
                                </label>
                                <div class="mt-2">
                                    <div id="stsatus" class="btn-group" role="group" aria-label="Status">
                                        <label class="btn btn-outline-primary flex-fill">
                                            <input type="radio" name="store_status" class="mx-1" value="1">
                                            {{ labels('admin_labels.approved_status', 'Approved') }}
                                        </label>
                                        <label class="btn btn-outline-danger flex-fill">
                                            <input type="radio" name="store_status" class="mx-1" value="2">
                                            {{ labels('admin_labels.not_approved', 'Not-Approved') }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 col-xxl-6 mt-md-2 mt-xxl-0">
                    <div class="card mt-4">
                        <div class="card-body">
                            <h5 class="mb-3">
                                {{ labels('admin_labels.other_details', 'Other Details') }}
                            </h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="tax_name"
                                            class="form-label">{{ labels('admin_labels.tax_name', 'Tax Name') }}
                                        </label>
                                        <div>
                                            <input type="text" class="form-control" id="tax_name"
                                                placeholder="{{ labels('admin_labels.tax_name_placeholder', 'Tax Name') }}" name="tax_name">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="tax_number"
                                            class="form-label">{{ labels('admin_labels.tax_number', 'Tax Number') }}
                                        </label>
                                        <div>
                                            <input type="text" class="form-control" id="tax_number"
                                                placeholder="{{ labels('admin_labels.tax_number_placeholder', 'Tax Number') }}" name="tax_number">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="pan_number"
                                            class="form-label">{{ labels('admin_labels.pan_number', 'Pan Number') }}
                                        </label>
                                        <div>
                                            <input type="text" class="form-control" id="pan_number"
                                                placeholder="{{ labels('admin_labels.pan_number_placeholder', 'Pan Number') }}" name="pan_number">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="latitude"
                                            class="form-label">{{ labels('admin_labels.latitude', 'Latitude') }}
                                        </label>
                                        <div>
                                            <input type="text" class="form-control" id="latitude"
                                                placeholder="{{ labels('admin_labels.latitude_placeholder', 'Latitude') }}" name="latitude">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="longitude"
                                            class="form-label">{{ labels('admin_labels.longitude', 'Longitude') }}
                                        </label>
                                        <div>
                                            <input type="text" class="form-control" id="longitude"
                                                placeholder="{{ labels('admin_labels.longitude_placeholder', 'Longitude') }}" name="longitude">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="national_identity_card"
                                            class="form-label">{{ labels('admin_labels.national_identity_card', 'National Identity Card') }}
                                            <i class="fa fa-info-circle text-secondary ms-1" data-bs-toggle="popover"
                                                data-bs-placement="right"
                                                data-bs-content="{{ labels('admin_labels.upload_seller_national_identity_card', "Upload the seller's national identity card.") }}"></i>
                                        </label>
                                        <div>
                                            <input type="file" class="filepond" name="national_identity_card"
                                                data-max-file-size="300MB" data-max-files="200"
                                                accept="image/*,.webp"
                                                data-label-idle='{{ labels('admin_labels.drag_drop_files_or', 'Drag & Drop your files or') }} <span class="filepond--label-action">{{ labels('admin_labels.browse', 'Browse') }}</span>' />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card mt-4">
                        <div class="card-body">
                            <h5 class="mb-3">{{ labels('admin_labels.permissions', 'Permissions') }}</h5>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group row">
                                        <label for="require_products_approval"
                                            class="col-sm-6 col-form-label">{{ labels('admin_labels.require_product_approvel', 'Require Product Approvel') }}?
                                            <i class="fa fa-info-circle text-secondary ms-1" data-bs-toggle="popover"
                                                data-bs-placement="right"
                                                data-bs-content="{{ labels('admin_labels.enable_if_products_require_approval_before_listing', 'Enable if products require approval before being listed.') }}"></i>
                                        </label>
                                        <div class="col-sm-6 form-check form-switch">
                                            <input type="checkbox" class="form-check-input mx-2 float-end"
                                                id="require_products_approval" name="require_products_approval">
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group row">
                                        <label for="customer_privacy"
                                            class="col-sm-5 col-form-label">{{ labels('admin_labels.view_customer_details', 'View Customer Details') }}?
                                            <i class="fa fa-info-circle text-secondary ms-1" data-bs-toggle="popover"
                                                data-bs-placement="right"
                                                data-bs-content="{{ labels('admin_labels.enable_if_seller_can_view_customer_details', 'Enable if seller can view customer details.') }}"></i>
                                        </label>
                                        <div class="col-sm-7 form-check form-switch">
                                            <input type="checkbox" name="customer_privacy"
                                                class="form-check-input mx-2 float-end">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row d-none">
                                <div class="col-md-6">
                                    <div class="form-group row">
                                        <label for="view_order_otp"
                                            class="col-sm-8 col-form-label">{{ labels('admin_labels.view_order_otp_and_change_delivery_status', 'View Order OTP & Can Change Delivery Status') }}?
                                            <i class="fa fa-info-circle text-secondary ms-1" data-bs-toggle="popover"
                                                data-bs-placement="right"
                                                data-bs-content="{{ labels('admin_labels.enable_if_seller_can_view_order_otp_and_change_delivery_status', 'Enable if seller can view order OTP and change delivery status.') }}"></i>
                                        </label>
                                        <div class="col-sm-4 form-check form-switch">
                                            <input type="checkbox" name="view_order_otp"
                                                class="form-check-input mx-2 float-end">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end mt-4">
            <button type="reset" class="btn mx-2 reset_button">{{ labels('admin_labels.reset', 'Reset') }}</button>
            <button type="submit"
                class="btn btn-primary submit_button">{{ labels('admin_labels.add_seller', 'Add Seller') }}</button>
        </div>
    </form>
</div>


{{-- commission modal --}}

<div class="offcanvas offcanvas-end" tabindex="-1" id="set_commission_offcanvas"
    aria-labelledby="set_commission_offcanvasLabel" role="dialog">
    <div class="offcanvas-header">
        <h5 id="set_commission_offcanvasLabel">
            {{ labels('admin_labels.categories_and_commission', 'Categories & Commission (%)') }}
        </h5> <button
            type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <form class="form-horizontal overflow-auto" id="add-seller-commission-form" action="" method="POST"
        enctype="multipart/form-data"> @csrf <div class="offcanvas-body"> <label for="Categories"
                class="col-sm-12 form-label">{{ labels('admin_labels.categories', 'Categories') }}</label>
            <div id="category_section"></div>
            <div class="form-group col-md-12"> <button type="button" id="add_category"
                    class="btn btn-primary btn-xs"> <i class="fa fa-plus"></i>
                    {{ labels('admin_labels.add_more_category', 'Add More Category') }} </button> </div> <a
                href="{{ route('categories.store') }}" class="btn btn-outline-primary btn-xs" target="_blank"> <i
                    class="fa fa-plus-circle"></i> {{ labels('admin_labels.add_new_category', 'Add New Category') }}
            </a>
        </div>
        <div class="offcanvas-footer d-flex justify-content-end"> <button type="reset"
                class="btn mx-2 reset_button">{{ labels('admin_labels.reset', 'Reset') }}</button> <button
                type="submit" class="btn btn-primary mx-2"
                id="save_btn">{{ labels('admin_labels.save', 'Save') }}</button> </div>
    </form>
</div>
@endsection