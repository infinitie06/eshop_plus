<html lang="en">
    @php
        use App\Services\MediaService;
    @endphp

    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @if ($system_settings != null)
            <link rel="icon" type="image/png"
                href="{{ app(MediaService::class)->getMediaImageUrl($system_settings['favicon']) }}">
        @endif
        <title>{{ labels('panel_labels.login', 'Login') }} | {{ $system_settings['app_name'] }}</title>
        <link rel="apple-touch-icon" sizes="76x76" href="./assets/img/apple-icon.png">
        <!--     Fonts and icons     -->
        <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet" />

        <link rel="stylesheet" href="{{ asset('assets/admin/css/iziToast.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/admin/css/dropzone.css') }}">

        <link rel="stylesheet" href="{{ asset('assets/admin/css/bootstrap-table.min.css') }}">
        <!-- CSS Files -->

        <link id="pagestyle" href="{{ asset('/assets/css/argon-dashboard.css?v=2.0.4') }}" rel="stylesheet" />
        <link id="pagestyle" href="{{ asset('/assets/admin/css/select2.min.css') }}" rel="stylesheet" />
        <link id="pagestyle" href="{{ asset('/assets/admin/css/tagify.min.css') }}" rel="stylesheet" />
        <link id="pagestyle" href="{{ asset('/assets/admin/css/sweetalert2.min.css') }}" rel="stylesheet" />
        <link id="pagestyle" href="{{ asset('/assets/admin/css/style.min.css') }}" rel="stylesheet" />
        <link rel="stylesheet" href="{{ asset('assets/boxicons/css/boxicons.min.css') }}">

        <link rel="stylesheet" href="{{ asset('assets/admin/custom/custom.css') }}">
        <!-- intl-tel-input -->
        <link rel="stylesheet" href="{{ asset('frontend/elegant/css/intlTelInput.css') }}">
        <style>
            .intl-tel-input {
                display: block !important;
                width: 100%;
            }

            .intl-tel-input input {
                width: 100%;
            }

            .intl-tel-input .country-list {
                z-index: 9999;
            }

            .intl-tel-input.allow-dropdown.separate-dial-code .selected-flag {
                background-color: #f5f5f5;
                border-right: 1px solid #dee2e6;
            }
        </style>
    </head>


    <body class="">
        <div class="page-header min-vh-100">
            <div class="col-md-12">
                @if (config('constants.ALLOW_MODIFICATION') === 0)
                    <div class="alert alert-info d-flex justify-content-center">
                        {{ labels('panel_labels.demo_login_note', 'Note: If you cannot login here, please close the codecanyon frame by clicking on x Remove Frame button from top right corner on the page or') }}
                        <a href="https://eshop-pro.eshopweb.store/admin" target="_blank"
                            class="text-dark">{!! labels('panel_labels.click_here_arrow', '>> Click here <<') !!}</a>
                    </div>
                @endif
                <div class="d-flex flex-column justify-content-center align-items-center">
                    <div class="card login-card">
                        <div class="card-body">
                            <div class="d-flex flex-column align-items-center text-center">
                                <div class="login-img-box mb-3">
                                    @php
                                        $store_logo =
                                            !empty($system_settings['logo']) &&
                                            file_exists(
                                                public_path(config('constants.MEDIA_PATH') . $system_settings['logo']),
                                            )
                                                ? app(MediaService::class)->getMediaImageUrl($system_settings['logo'])
                                                : asset('assets/img/default_full_logo.png');
                                    @endphp
                                    <img src="{{ $store_logo }}" alt="logo" class="img-fluid">
                                </div>
                                <h1 class="font-weight-bolder">
                                    {{ labels('panel_labels.admin_login', 'Admin Login') }}</h1>
                                <p class="mb-4 order_page_title">
                                    {{ labels('panel_labels.enter_details_to_sign_in', 'Hey, Enter your details to get sign in to your account') }}
                                </p>
                            </div>

                            <form class="form_authentication" action="{{ route('admin.authenticate') }}"
                                method="POST">
                                @csrf
                                <div class="form-group">
                                    <label class="form-label"
                                        for="phone_input">{{ labels('panel_labels.mobile', 'Mobile') }}<span
                                            class='text-asterisks text-sm'>*</span></label>
                                    <div class="input-group">
                                        @php
                                            use App\Services\SettingService;
                                            $settingsJson = app(SettingService::class)->getSettings('system_settings');
                                            $systemSettings = json_decode($settingsJson, true);
                                            $defaultCountryCode = $systemSettings['country_code'] ?? '91';
                                            // Map numeric dial code to ISO2 for intl-tel-input
                                            $dialToIso = [
                                                '91' => 'in',
                                                '1' => 'us',
                                                '44' => 'gb',
                                                '20' => 'eg',
                                                '971' => 'ae',
                                                '966' => 'sa',
                                                '92' => 'pk',
                                                '880' => 'bd',
                                                '94' => 'lk',
                                                '977' => 'np',
                                                '81' => 'jp',
                                                '49' => 'de',
                                                '33' => 'fr',
                                                '86' => 'cn',
                                                '55' => 'br',
                                                '7' => 'ru',
                                                '234' => 'ng',
                                                '254' => 'ke',
                                                '27' => 'za',
                                                '62' => 'id',
                                                '63' => 'ph',
                                                '60' => 'my',
                                                '66' => 'th',
                                                '84' => 'vn',
                                                '82' => 'kr',
                                                '90' => 'tr',
                                                '98' => 'ir',
                                                '964' => 'iq',
                                                '961' => 'lb',
                                                '962' => 'jo',
                                                '965' => 'kw',
                                                '968' => 'om',
                                                '974' => 'qa',
                                                '973' => 'bh',
                                                '967' => 'ye',
                                                '212' => 'ma',
                                                '216' => 'tn',
                                                '213' => 'dz',
                                                '218' => 'ly',
                                                '249' => 'sd',
                                                '251' => 'et',
                                                '255' => 'tz',
                                                '256' => 'ug',
                                                '260' => 'zm',
                                                '263' => 'zw',
                                                '52' => 'mx',
                                                '54' => 'ar',
                                                '56' => 'cl',
                                                '57' => 'co',
                                                '51' => 'pe',
                                                '58' => 've',
                                                '593' => 'ec',
                                                '595' => 'py',
                                                '598' => 'uy',
                                                '507' => 'pa',
                                                '506' => 'cr',
                                                '502' => 'gt',
                                                '503' => 'sv',
                                                '504' => 'hn',
                                                '505' => 'ni',
                                                '1868' => 'tt',
                                                '1876' => 'jm',
                                                '1809' => 'do',
                                                '30' => 'gr',
                                                '31' => 'nl',
                                                '32' => 'be',
                                                '34' => 'es',
                                                '36' => 'hu',
                                                '39' => 'it',
                                                '40' => 'ro',
                                                '41' => 'ch',
                                                '43' => 'at',
                                                '45' => 'dk',
                                                '46' => 'se',
                                                '47' => 'no',
                                                '48' => 'pl',
                                                '351' => 'pt',
                                                '353' => 'ie',
                                                '354' => 'is',
                                                '358' => 'fi',
                                                '359' => 'bg',
                                                '370' => 'lt',
                                                '371' => 'lv',
                                                '372' => 'ee',
                                                '380' => 'ua',
                                                '381' => 'rs',
                                                '382' => 'me',
                                                '385' => 'hr',
                                                '386' => 'si',
                                                '420' => 'cz',
                                                '421' => 'sk',
                                            ];
                                            $defaultIso = $dialToIso[$defaultCountryCode] ?? 'in';
                                        @endphp
                                        {{-- Hidden field carries the dial code to the backend --}}
                                        <input type="hidden" name="country_code" id="admin_country_code_hidden"
                                            value="{{ $defaultCountryCode }}">
                                        {{-- intl-tel-input phone field (name="mobile" stays the same) --}}
                                        <input type="tel" id="admin_phone_input" class="form-control copied_mobile"
                                            name="mobile"
                                            placeholder="{{ labels('panel_labels.enter_your_mobile_number', 'Enter Your Mobile Number') }}"
                                            value="{{ config('constants.ALLOW_MODIFICATION') === 0 ? '9876543210' : '' }}"
                                            data-default-country="{{ $defaultIso }}" autocomplete="off">
                                    </div>
                                </div>
                                <label class="form-label"
                                    for="">{{ labels('panel_labels.password', 'Password') }}<span
                                        class='text-asterisks text-sm'>*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class='bx bx-lock fs-4'></i>
                                    </span>
                                    <input type="password" class="form-control copied_password" name="password"
                                        id="show_password"
                                        placeholder="{{ labels('panel_labels.enter_your_password', 'Enter Your Password') }}"
                                        value={{ config('constants.ALLOW_MODIFICATION') === 0 ? '12345678' : '' }}>
                                    <span class="input-group-text password_show" onclick="show_password()">
                                        <i class='bx bx-show fs-4'></i>
                                    </span>
                                    <span class="input-group-text low_vision" onclick="show_password()">
                                        <i class='bx bx-low-vision fs-4'></i>
                                    </span>
                                </div>

                                <div class="d-flex justify-content-between mt-4">

                                    <a class="view_all"
                                        href="{{ route('password.request') }}">{{ labels('panel_labels.forgot_password', 'Forgot Password') }}?</a>
                                </div>
                                <button type="submit"
                                    class="btn btn-lg btn-primary login_button w-100 mt-4 mb-0">{{ labels('panel_labels.sign_in', 'Sign In') }}</button>

                                {{-- show only in demo mode  --}}
                                @if (config('constants.ALLOW_MODIFICATION') === 0)
                                    <div class="credential_box mt-4 p-2 d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <div class="d-flex gap-2">
                                                <p class="data_total_font mb-1">{{ labels('panel_labels.mobile', 'Mobile') }} :</p>
                                                <p id="mobileInfo" class="mb-1 data_total_font">9876543210</p>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <p class="data_total_font mb-1">{{ labels('panel_labels.password', 'Password') }} :</p>
                                                <p id="passwordInfo" class="mb-1 data_total_font">12345678</p>
                                            </div>
                                        </div>
                                        <div class="credential_copy_box">
                                            <i class='bx bx-copy-alt' onclick="copyCombinedInfo()"></i>
                                        </div>
                                    </div>
                                @endif
                            </form>
                        </div>
                    </div>
                    <div class="copyright mt-4">
                        {{ labels('panel_labels.copyright', 'Copyright') }} © {{ date('Y') }} <a
                            href="{{ route('admin.home') }}">{{ $system_settings['app_name'] }}.</a>
                        {{ labels('panel_labels.all_rights_reserved', 'All rights reserved.') }}
                    </div>
                </div>
            </div>
        </div>

        <!--   Core JS Files   -->
        <script src="{{ asset('/assets/admin/js/jquery.min.js') }}"></script>
        <script src="{{ asset('/assets/admin/js/jquery.js') }}"></script>
        <script src="{{ asset('/assets/js/core/popper.min.js') }}"></script>
        <script src="{{ asset('/assets/js/core/bootstrap.min.js') }}"></script>
        <script src="{{ asset('/assets/js/plugins/perfect-scrollbar.min.js') }}"></script>
        <script src="{{ asset('/assets/js/plugins/smooth-scrollbar.min.js') }}"></script>
        <script src="{{ asset('/assets/js/plugins/chartjs.min.js') }}"></script>
        <script src="{{ asset('assets/admin/js/iziToast.min.js') }}"></script>
        <script src="{{ asset('assets/admin/js/dropzone.js') }}"></script>
        <script src="{{ asset('assets/admin/js/bootstrap-table.min.js') }}"></script>
        <script src="{{ asset('assets/admin/js/select2.min.js') }}"></script>
        <script src="{{ asset('assets/admin/js/tagify.min.js') }}"></script>
        <script src="{{ asset('assets/admin/js/jstree.min.js') }}"></script>
        <script src="{{ asset('assets/admin/js/jquery.blockUI.js') }}"></script>
        <script src="{{ asset('assets/admin/js/sweetalert2.min.js') }}"></script>
        <script src="{{ asset('assets/admin/js/tinymce.min.js') }}"></script>
        <script src="{{ asset('/assets/js/boxicons.js') }}">
            < script >
                var win = navigator.platform.indexOf('Win') > -1;
            if (win && document.querySelector('#sidenav-scrollbar')) {
                var options = {
                    damping: '0.5'
                }
                Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
            }
        </script>
        <!-- Github buttons -->
        <script async defer src="https://buttons.github.io/buttons.js"></script>
        <!-- Control Center for Soft Dashboard: parallax effects, scripts for the example pages etc -->
        <script src="{{ asset('/assets/js/argon-dashboard.min.js?v=2.0.4') }}"></script>

        <script src="{{ asset('assets/admin/custom/custom.js') }}"></script>
        <!-- intl-tel-input JS -->
        <script src="{{ asset('frontend/elegant/js/intlTelInput.js') }}"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var $input = $('#admin_phone_input');
                if (!$input.length) return;
                var defaultCountry = $input.data('default-country') || 'in';
                $input.intlTelInput({
                    initialCountry: defaultCountry,
                    separateDialCode: true,
                    preferredCountries: [defaultCountry, 'in', 'us', 'gb']
                });

                function syncDialCode() {
                    var data = $input.intlTelInput('getSelectedCountryData');
                    if (data && data.dialCode) {
                        $('#admin_country_code_hidden').val(data.dialCode);
                    }
                }
                $input.on('countrychange', syncDialCode);
                $input.on('input', syncDialCode);
                syncDialCode();
            });
        </script>
    </body>

</html>
