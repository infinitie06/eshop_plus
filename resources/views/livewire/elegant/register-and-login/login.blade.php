@php
    $bread_crumb['page_main_bread_crumb'] = labels('front_messages.sign_in', 'Sign In');
    $is_rtl = session('is_rtl') ?? 0;
@endphp

@if ($is_rtl)
    <style>
        /* * This CSS forces the flag/country code to the right
     * when the page is rendered in RTL mode.
     */
        .rtl-layout .iti {
            /* Enable Flexbox on the container wrapper by the library */
            display: flex !important;
            /* Reverse the order: flag (order: -1) goes to the right */
            flex-direction: row-reverse !important;
            width: 100% !important;
        }

        /* Target the actual input field */
        .rtl-layout .iti input[type="tel"] {
            /* Ensure the input text aligns right and is read RTL */
            direction: rtl;
            text-align: right;
            /* Reset any conflicting LTR padding and ensure full width */
            padding-left: 12px !important;
            padding-right: 12px !important;
            flex-grow: 1;
        }

        /* Target the flag/dropdown container */
        .rtl-layout .iti .iti__flag-container {
            /* Explicitly place it first in the visual order (right side) */
            order: -1;
            /* Adjust border radius/margins for right alignment */
            border-radius: 0 4px 4px 0;
            margin-left: 0 !important;
            margin-right: 0 !important;
        }

        /* Adjust the border radius of the flag dropdown element for the right side */
        .rtl-layout .iti .iti__selected-flag {
            border-radius: 0 4px 4px 0 !important;
        }

        /* Ensure the input's placeholder is correct in RTL */
        .rtl-layout .iti input[type="tel"]::placeholder {
            text-align: right;
        }
    </style>
@endif
<div id="page-content">
    <x-utility.breadcrumbs.breadcrumbTwo :$bread_crumb />
    <div class="container-fluid">
        <div class="login-register pt-2">
            <div class="row">
                @if ($is_rtl)
                    <div class="col-12 col-sm-12 d-flex justify-content-center offset-lg-3 offset-md-2">
                    @else
                        <div class="col-12 col-sm-12 col-md-8 offset-md-2 col-lg-6 offset-lg-3">
                @endif
                <div class="inner h-100">
                    <form wire:submit="login" class="customer-form" wire:loading.attr="disabled">
                        <h2 class="text-center fs-4 mb-3">
                            {{ labels('front_messages.sign_in', 'Sign In') }}
                        </h2>
                        <p class="text-center mb-4">
                            {{ labels('front_messages.if_you_have_an_account_with_us_please_log_in', 'If you have an account with us, please log in.') }}
                        </p>
                        <div class="form-row justify-content-around">
                            @if ($errors->has('loginError'))
                                <p class="fw-400 text-danger mt-1">{{ $errors->first('loginError') }}</p>
                            @endif
                            <div class="form-group col-12" @if ($is_rtl) dir="rtl" @endif>
                                <input wire:model="mobile" type="tel" id="mobile" name="mobile"
                                    class="form-control col-12"
                                    placeholder="{{ labels('front_messages.mobile', 'Mobile') }}"
                                    data-default-country="{{ $default_country ?? 'in' }}"
                                    @if ($is_rtl) dir="rtl" @endif />
                                @error('mobile')
                                    <p class="fw-400 text-danger mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="form-group col-12">
                                <label for="password" class="d-none">{{ labels('front_messages.password', 'Password') }}
                                    <span class="required">*</span></label>
                                <input wire:model="password" type="password" name="password"
                                    placeholder="{{ labels('front_messages.password', 'Password') }}" id="password"
                                    value="" />
                                <ion-icon name="eye-off-outline" class="eye-icon"></ion-icon>
                                @error('password')
                                    <p class="fw-400 text-danger mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="form-group col-12">
                                <div class="login-remember-forgot d-flex justify-content-between align-items-center">
                                    <div class="remember-check customCheckbox">
                                        <input id="remember" name="remember" type="checkbox" value="remember" />
                                        <label for="remember">{{ labels('front_messages.remember_me', 'Remember me') }}
                                        </label>
                                    </div>
                                    <a href="{{ customUrl('password-recovery') }}">
                                        {{ labels('front_messages.forgot_password', 'Forgot your password?') }}
                                    </a>
                                </div>
                            </div>
                            <div class="form-group col-12 mb-0">
                                <input type="submit" class="btn btn-primary btn-lg w-100 sign-in"
                                    value="{{ labels('front_messages.sign_in', 'Sign In') }}" />
                            </div>
                        </div>
                    </form>
                    @if ($system_settings['google'] == 1 || $system_settings['facebook'] == 1)
                        <div class="login-divide"><span
                                class="login-divide-text">{{ labels('front_messages.or', 'OR') }}</span></div>

                        <p class="text-center fs-6 text-muted mb-3">
                            {{ labels('front_messages.sign_in_with_social_account', 'Sign in with social account') }}
                        </p>
                        <div class="login-social d-flex-justify-center">
                            @if ($system_settings['facebook'] == 1)
                                <a class="social-link facebook rounded-5 d-flex-justify-center"
                                    href="{{ url('auth/facebook') }}">
                                    <i class="anm anm-facebook hdr-icon icon me-2"></i></ion-icon>
                                    {{ labels('front_messages.facebook', 'Facebook') }}</a>
                            @endif
                            @if ($system_settings['google'] == 1)
                                <a class="social-link google rounded-5 d-flex-justify-center"
                                    href="{{ url('auth/google') }}"><i class="anm anm-google hdr-icon icon me-2"></i>
                                    {{ labels('front_messages.google', 'Google') }}</a>
                            @endif
                        </div>
                    @endif
                    <div class="login-signup-text mt-4 mb-2 fs-6 text-center text-muted">
                        {{ labels('front_messages.dont_have_an_account', 'Don\'t have an account?') }}?
                        <a href="{{ customUrl('register') }}"
                            class="btn-link">{{ labels('front_messages.sign_up_now', 'Sign up now') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
<script src="{{ asset('vendor/intl-tel-input/js/utils.js') }}"></script>
<script>
    function initPhoneInputs() {
        document.querySelectorAll("input[type=tel]#mobile").forEach(input => {
            // Check if already initialized by looking for the data attribute or parent wrapper
            const parentWrapper = input.parentElement;
            
            // Destroy existing instance if it exists
            if (window.intlTelInput && typeof window.intlTelInput.getInstance === 'function') {
                const existingInstance = window.intlTelInput.getInstance(input);
                if (existingInstance) {
                    existingInstance.destroy();
                }
            }

            // Remove the wrapper if it exists
            if (parentWrapper && parentWrapper.classList.contains("iti")) {
                const inputParent = input.closest(".form-group");
                if (inputParent && parentWrapper.parentElement === inputParent) {
                    parentWrapper.replaceWith(input);
                }
            }

            // Initialize intlTelInput
            const iti = window.intlTelInput(input, {
                initialCountry: "{{ $default_country ?? 'in' }}",
                separateDialCode: true,
                preferredCountries: ["{{ $default_country ?? 'in' }}", "in", "us", "gb"],
                utilsScript: "/vendor/intl-tel-input/js/utils.js",
            });

            // Force full width inline
            const container = input.closest(".iti");
            if (container) {
                container.style.width = "100%";
                input.style.width = "100%";
            }
        });
    }

    // Ensure intlTelInput is loaded before initializing
    function ensureIntlTelInputReady() {
        if (typeof window.intlTelInput === 'function') {
            initPhoneInputs();
        } else {
            // Retry if library not yet loaded
            setTimeout(ensureIntlTelInputReady, 100);
        }
    }

    document.addEventListener("DOMContentLoaded", ensureIntlTelInputReady);
    document.addEventListener("livewire:navigated", ensureIntlTelInputReady);
</script>



<!--End Main Content-->
</div>
