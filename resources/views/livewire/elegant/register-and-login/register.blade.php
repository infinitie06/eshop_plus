@php
    $bread_crumb['page_main_bread_crumb'] = labels('front_messages.register', 'Register');
@endphp
<div id="page-content">
    <x-utility.breadcrumbs.breadcrumbTwo :$bread_crumb />
    <div class="container-fluid">
        <div class="login-register pt-2">
            <div class="row">
                <div class="col-12 col-sm-12 col-md-8 offset-md-2 col-lg-6 offset-lg-3">
                    <div class="inner h-100">
                        <h2 class="text-center fs-4 mb-4">
                            {{ labels('front_messages.register', 'Register') }}
                        </h2>
                        <div class="form-row send-otp-box">
                            <div class="form-group col-12 d-flex flex-column">
                                <label for="mobile" class="d-none">{{ labels('front_messages.mobile', 'mobile') }}
                                    <span class="required">*</span></label>
                                <input type="number" name="mobile" placeholder="{{ labels('front_messages.mobile', 'Mobile') }}" id="number"
                                    data-default-country="{{ $default_country ?? 'in' }}" value="" />
                                <div class="d-flex justify-content-center align-content-center my-2">
                                    <div id="recaptcha-container"></div>
                                </div>
                                <button class="btn btn-primary btn-lg w-100" id="send_otp">
                                    {{ labels('front_messages.send_otp', 'Send OTP') }}</button>
                            </div>
                        </div>
                        <div class="form-row verify-otp-box d-none">
                            <div class="form-group col-12">
                                <label for="verificationCode"
                                    class="d-none">{{ labels('front_messages.verification_code', 'verificationCode') }}
                                    <span class="required">*</span></label>
                                <input type="text" id="verificationCode" class="form-control"
                                    placeholder="{{ labels('front_messages.enter_verification_code', 'Enter Verification Code') }}"><br>
                                <button type="button" class="btn btn-primary btn-lg w-100"
                                    id="verify_otp">{{ labels('front_messages.verify_code', 'Verify Code') }}</button>
                            </div>
                        </div>
                        <form class="register-form d-none" method="POST" action="{{ url('register/submit') }}">
                            <div class="form-row">
                                <div class="form-group col-12">
                                    <label for="username"
                                        class="d-none">{{ labels('front_messages.username', 'Username') }}
                                        <span class="required">*</span></label>
                                    <input type="text" name="username" placeholder="{{ labels('front_messages.username', 'Username') }}" id="username"
                                        value="" />
                                </div>
                                <div class="form-group col-12">
                                    <label for="email" class="d-none">{{ labels('front_messages.email', 'Email') }}
                                        <span class="required">*</span></label>
                                    <input type="email" name="email" placeholder="{{ labels('front_messages.email', 'Email') }}" id="email"
                                        value="" />
                                </div>
                                <div class="form-group col-12">
                                    <label for="password"
                                        class="d-none">{{ labels('front_messages.password', 'Password') }}
                                        <span class="required">*</span></label>
                                    <input type="password" name="password" placeholder="{{ labels('front_messages.password', 'Password') }}" id="password"
                                        value="" />
                                    <ion-icon name="eye-off-outline" class="eye-icon"></ion-icon>
                                </div>
                                <div class="form-group col-12">
                                    <label for="password_confirmation"
                                        class="d-none">{{ labels('front_messages.confirm_password', 'Confirm Password') }}
                                        <span class="required">*</span></label>
                                    <input type="Password" id="password_confirmation" name="password_confirmation"
                                        placeholder="{{ labels('front_messages.confirm_password', 'Confirm Password') }}" />
                                    <ion-icon name="eye-off-outline" class="eye-icon"></ion-icon>
                                </div>
                                <div class="form-group col-12">
                                    <div
                                        class="login-remember-forgot d-flex justify-content-between align-items-center">
                                        <div class="agree-check customCheckbox">
                                            <input id="agree" name="agree" type="checkbox" value="agree" />
                                            <label
                                                for="agree">{{ labels('front_messages.i_agree_to_term_and_policy', 'I agree to terms & Policy.') }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group col-12 mb-0">
                                    <input type="submit" class="btn btn-primary btn-lg w-100" id="register-form-submit"
                                        value="{{ labels('front_messages.register', 'Register') }}" />
                                </div>
                            </div>
                        </form>
                        @if ($system_settings['google'] == 1 || $system_settings['facebook'] == 1)
                            <div class="login-divide"><span
                                    class="login-divide-text">{{ labels('front_messages.or', 'OR') }}</span>
                            </div>
                            <p class="text-center fs-6 text-muted mb-3">
                                {{ labels('front_messages.or_sign_up_with', 'Or Sign up with') }}</p>
                            <div class="login-social d-flex-justify-center">
                                @if ($system_settings['facebook'] == 1)
                                    <a class="social-link facebook rounded-5 d-flex-justify-center"
                                        href="{{ url('auth/facebook') }}">
                                        <i class="anm anm-facebook hdr-icon icon me-2"></i></ion-icon>
                                        {{ labels('front_messages.facebook', 'Facebook') }}</a>
                                @endif
                                @if ($system_settings['google'] == 1)
                                    <a class="social-link google rounded-5 d-flex-justify-center"
                                        href="{{ url('auth/google') }}"><i
                                            class="anm anm-google hdr-icon icon me-2"></i>
                                        {{ labels('front_messages.google', 'Google') }}</a>
                                @endif
                            </div>
                        @endif

                        <div class="login-signup-text mt-4 mb-2 fs-6 text-center text-muted">
                            {{ labels('front_messages.already_have_an_account', 'Already have an account? ') }}<a
                                href="{{ customUrl('login') }}" wire:navigate
                                class="btn-link">{{ labels('front_messages.login_now', 'Login now') }}</a>
                        </div>
                    </div>
                    <input type="hidden" name="authentication_method" id="authentication_method"
                        value="{{ $authentication_method }}">
                </div>
            </div>
        </div>
    </div>
</div>
