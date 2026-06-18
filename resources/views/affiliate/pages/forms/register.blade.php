@include('admin.include_css')
@php
    use App\Services\TranslationService;
    $language_code = app(TranslationService::class)->getLanguageCode();
@endphp

@include('affiliate.include_css')
<div id="app_url" data-app-url="{{ config('app.url') }}"></div>
<div class="page-header min-vh-100">
    <div class="col-md-12">
        <div class="d-flex flex-column justify-content-center align-items-center">
            <div class="card w-75">
                <div class="card-body">
                    <h2>Affiliate Registration</h2>
                    <section class="content">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card card-info">
                                    <form class="" id="add_affiliate_user_form" method="POST"
                                        action="{{ route('admin.affiliate_users.register') }}" novalidate>
                                        @csrf
                                        <input type="hidden" name="form_mode" value="add">
                                        <input type="hidden" class="self_register" value="self_register">
                                        <input type="hidden" name="status" value="2">
                                        <div class="card shadow-sm">
                                            <div class="card-body">
                                                {{-- Step 1 --}}
                                                <h5 class="mb-4">
                                                    {{ labels('admin_labels.account_information', 'Account Information') }}
                                                </h5>

                                                <div class="row">
                                                    {{-- Full Name --}}
                                                    <div class="col-md-6 mb-3">
                                                        <label for="full_name"
                                                            class="form-label">{{ labels('admin_labels.full_name', 'Full Name') }}
                                                            <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" id="full_name"
                                                            name="full_name" placeholder="Enter Full Name">
                                                    </div>

                                                    {{-- Email --}}
                                                    <div class="col-md-6 mb-3">
                                                        <label for="email"
                                                            class="form-label">{{ labels('admin_labels.email_address', 'Email Address') }}
                                                            <span class="text-danger">*</span></label>
                                                        <input type="email" class="form-control" id="email"
                                                            name="email" placeholder="Enter Email">
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    {{-- Mobile --}}
                                                    <div class="col-md-6 mb-3">
                                                        <label for="mobile"
                                                            class="form-label">{{ labels('admin_labels.mobile', 'Mobile') }}
                                                            <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" id="mobile"
                                                            name="mobile" maxlength="16" placeholder="Enter Mobile"
                                                            oninput="validateNumberInput(this)">
                                                    </div>

                                                    {{-- Password --}}
                                                    <div class="col-md-6 mb-3">
                                                        <label for="password"
                                                            class="form-label">{{ labels('admin_labels.password', 'Password') }}
                                                            <span class="text-danger">*</span></label>
                                                        <div class="input-group">
                                                            <input type="password" class="form-control" id="password"
                                                                name="password" placeholder="Enter Password">
                                                            <button type="button"
                                                                class="btn btn-outline-secondary toggleAffiliatePassword"><i
                                                                    class="fa fa-eye"></i></button>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    {{-- Confirm Password --}}
                                                    <div class="col-md-6 mb-3">
                                                        <label for="confirm_password"
                                                            class="form-label">{{ labels('admin_labels.confirm_password', 'Confirm Password') }}
                                                            <span class="text-danger">*</span></label>
                                                        <div class="input-group">
                                                            <input type="password" class="form-control"
                                                                id="password_confirmation" name="password_confirmation"
                                                                placeholder="Enter Confirm Password">
                                                            <button type="button"
                                                                class="btn btn-outline-secondary toggleAffiliatePassword"><i
                                                                    class="fa fa-eye"></i></button>
                                                        </div>
                                                    </div>

                                                    {{-- Address --}}
                                                    <div class="col-md-6 mb-3">
                                                        <label for="address"
                                                            class="form-label">{{ labels('admin_labels.address', 'Address') }}
                                                            <span class="text-danger">*</span></label>
                                                        <textarea class="form-control" id="address" name="address" rows="3" placeholder="Enter Address"></textarea>
                                                    </div>
                                                </div>
                                                {{-- Step 2 --}}

                                                <h5 class="mb-4">
                                                    {{ labels('admin_labels.your_websites_and_mobile_apps', 'Your Websites and Mobile Apps') }}
                                                </h5>

                                                <div class="row">
                                                    {{-- Website --}}
                                                    <div class="col-md-6 mb-3">
                                                        <label for="website_url"
                                                            class="form-label">{{ labels('admin_labels.website', 'Website') }}
                                                            <span class="text-danger">*</span></label>
                                                        <input type="url" class="form-control" id="website_url"
                                                            name="website_url"
                                                            placeholder="https://www.example.com/myblog">
                                                    </div>

                                                    {{-- Mobile App --}}
                                                    <div class="col-md-6 mb-3">
                                                        <label for="application_url"
                                                            class="form-label">{{ labels('admin_labels.mobile_app', 'Mobile App') }}
                                                            <span class="text-danger">*</span></label>
                                                        <input type="url" class="form-control"
                                                            id="application_url" name="application_url"
                                                            placeholder="https://xxxx/dp/xxxx">
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-end">
                                                    <button type="submit"
                                                        class="btn btn-primary seller_register_button submit_button">{{ labels('admin_labels.add_user', 'Add User') }}</button>
                                                </div>
                                            </div>
                                        </div>
                                </div>
                                </form>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</div>
@include('affiliate.include_script')
