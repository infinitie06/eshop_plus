@extends('admin/layout')
@section('title')
{{ labels('admin_labels.affiliate_users', 'Affiliate Users') }}
@endsection
@section('content')
<x-admin.breadcrumb :title="labels('admin_labels.affiliate_users', 'Affiliate Users')" :subtitle="labels(
        'admin_labels.efficiently_organize_and_control_affiliate_users',
        'Efficiently Organize and Control Affiliate Users',
    )" :breadcrumbs="[
        ['label' => labels('admin_labels.affiliate_user', 'Affiliate Users'), 'url' => route('admin.affiliate.manage_user')],
        ['label' => labels('admin_labels.add_user', 'Add User')]]" />

<section class="content">
    <div class="row">
        <div class="col-12">
            <div class="card card-info">

                {{-- Stepper --}}
                <div class="px-3 py-3">
                    <div class="stepper-container d-flex flex-nowrap overflow-auto">
                        <div class="step-wrapper text-center flex-shrink-0 px-2">
                            <div class="affiliate_step" id="step1" onclick="goToStep(1)">
                                <div class="circle"></div>
                                <h6 class="mt-2">{{ labels('admin_labels.account_information', 'Account Information') }}</h6>
                            </div>
                            <div class="bar d-none d-md-block"></div>
                        </div>
                        <div class="step-wrapper text-center flex-shrink-0 px-2">
                            <div class="affiliate_step" id="step2" onclick="goToStep(2)">
                                <div class="circle"></div>
                                <h6 class="mt-2">{{ labels('admin_labels.website_and_mobile_app_list', 'Website and Mobile App List') }}</h6>
                            </div>
                            <div class="bar d-none d-md-block"></div>
                        </div>
                        <div class="step-wrapper text-center flex-shrink-0 px-2">
                            <div class="affiliate_step" id="step4" onclick="goToStep(3)">
                                <div class="circle"></div>
                                <h6 class="mt-2">{{ labels('admin_labels.start_using_associates_central', 'Start Using Associates Central') }}</h6>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Form --}}
                <form id="add_affiliate_user_form" method="POST" action="{{ route('admin.affiliate_users.store') }}" novalidate>
                    @csrf
                    <input type="hidden" name="form_mode" value="add">
                    <div class="card shadow-sm">
                        <div class="card-body">

                            {{-- Step 1 --}}
                            <div class="step-page" id="page1">
                                <h5 class="mb-4">{{ labels('admin_labels.account_information', 'Account Information') }}</h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="full_name" class="form-label">{{ labels('admin_labels.full_name', 'Full Name') }} <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="full_name" name="full_name" placeholder="{{ labels('admin_labels.enter_full_name', 'Enter Full Name') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">{{ labels('admin_labels.email_address', 'Email Address') }} <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" placeholder="{{ labels('admin_labels.enter_email', 'Enter Email') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="mobile" class="form-label">{{ labels('admin_labels.mobile', 'Mobile') }} <span class="text-danger">*</span></label>
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

                                            <input type="text" class="form-control" id="mobile" name="mobile" maxlength="16" placeholder="{{ labels('admin_labels.enter_mobile', 'Enter Mobile') }}" oninput="validateNumberInput(this)">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="password" class="form-label">{{ labels('admin_labels.password', 'Password') }} <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="password" name="password" placeholder="{{ labels('admin_labels.enter_password', 'Enter Password') }}">
                                            <button type="button" class="btn btn-outline-secondary toggleAffiliatePassword"><i class="fa fa-eye"></i></button>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="confirm_password" class="form-label">{{ labels('admin_labels.confirm_password', 'Confirm Password') }} <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="{{ labels('admin_labels.enter_confirm_password', 'Enter Confirm Password') }}">
                                            <button type="button" class="btn btn-outline-secondary toggleAffiliatePassword"><i class="fa fa-eye"></i></button>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="address" class="form-label">{{ labels('admin_labels.address', 'Address') }} <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="address" name="address" rows="3" placeholder="{{ labels('admin_labels.enter_address', 'Enter Address') }}"></textarea>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end mt-3">
                                    <button type="button" class="btn btn-primary" onclick="nextStep(2)">{{ labels('admin_labels.next_step', 'Next') }}</button>
                                </div>
                            </div>

                            {{-- Step 2 --}}
                            <div class="step-page d-none" id="page2">
                                <h5 class="mb-4">{{ labels('admin_labels.your_websites_and_mobile_apps', 'Your Websites and Mobile Apps') }}</h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="website_url" class="form-label">{{ labels('admin_labels.website', 'Website') }} <span class="text-danger">*</span></label>
                                        <input type="url" class="form-control" id="website_url" name="website_url" placeholder="{{ labels('admin_labels.website_url_example', 'https://www.example.com/myblog') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="application_url" class="form-label">{{ labels('admin_labels.mobile_app', 'Mobile App') }} <span class="text-danger">*</span></label>
                                        <input type="url" class="form-control" id="application_url" name="application_url" placeholder="{{ labels('admin_labels.application_url_example', 'https://xxxx/dp/xxxx') }}">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between mt-3">
                                    <button type="button" class="btn btn-secondary" onclick="prevStep(1)">{{ labels('admin_labels.previous', 'Previous') }}</button>
                                    <button type="button" class="btn btn-primary" onclick="nextStep(3)">{{ labels('admin_labels.next', 'Next') }}</button>
                                </div>
                            </div>

                            {{-- Step 3 --}}
                            <div class="step-page d-none" id="page3">
                                <h5 class="mb-4">{{ labels('admin_labels.start_using_associates_central', 'Start Using Associates Central') }}</h5>
                                <div class="mb-3">
                                    <label class="form-label">{{ labels('admin_labels.status', 'Status') }} <span class="text-danger">*</span></label>
                                    <div class="d-flex flex-wrap gap-3">
                                        @foreach (['0' => 'Deactive', '1' => 'Approved', '2' => 'Not-Approved'] as $value => $label)
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="status" id="status_{{ $value }}" value="{{ $value }}">
                                            <label class="form-check-label" for="status_{{ $value }}">{{ $label }}</label>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <button type="button" class="btn btn-secondary" onclick="prevStep(2)">{{ labels('admin_labels.previous', 'Previous') }}</button>
                                    <button type="submit" class="btn btn-primary submit_button">{{ labels('admin_labels.add_user', 'Add User') }}</button>
                                </div>
                            </div>

                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

{{-- Responsive CSS --}}
<style>
    .stepper-container {
        gap: 1rem;
    }

    .step-wrapper .circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #17a2b8;
        margin: auto;
    }

    @media (max-width: 576px) {
        .step-wrapper h6 {
            font-size: 0.8rem;
            white-space: nowrap;
        }

        .step-wrapper .circle {
            width: 30px;
            height: 30px;
        }
    }
</style>
@endsection