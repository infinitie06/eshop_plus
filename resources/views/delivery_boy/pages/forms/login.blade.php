<html lang="en">
    @php
        use App\Services\MediaService;
    @endphp

    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" type="image/png"
            href="{{ app(MediaService::class)->getMediaImageUrl($system_settings['favicon']) }}">
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
    </head>

    <body class="">
        <div class="page-header min-vh-100">
            <div class="col-md-12">
                <div class="d-flex flex-column justify-content-center align-items-center">
                    <div class="card login-card">
                        <div class="card-body">
                            <div class="d-flex flex-column align-items-center text-center">
                                <div class="login-img-box mb-3">
                                    <img src="{{ config('app.url') }}storage/{{ $system_settings['logo'] }}"
                                        alt="logo" class="img-fluid">
                                </div>
                                <h1 class="font-weight-bolder">
                                    {{ labels('panel_labels.delivery_boy_login', 'Delivery Boy Login') }}
                                </h1>
                                <p class="mb-4 order_page_title">
                                    {{ labels('panel_labels.enter_details_to_sign_in', 'Hey, Enter your details to get sign in to your account') }}
                                </p>
                            </div>

                            <form class="form_authentication" action="{{ route('admin.authenticate') }}"
                                method="POST">
                                @csrf
                                <div class="form-group">
                                    <label class="form-label"
                                        for="">{{ labels('panel_labels.mobile', 'Mobile') }}<span
                                            class='text-asterisks text-sm'>*</span></label>
                                    <div class="input-group">

                                        @php
                                            use App\Services\SettingService;
                                            $settingsJson = app(SettingService::class)->getSettings('system_settings');
                                            $systemSettings = json_decode($settingsJson, true);
                                            $defaultCountryCode = $systemSettings['country_code'] ?? '91';
                                            $countryCodes = [
                                                '93',
                                                '355',
                                                '213',
                                                '1684',
                                                '376',
                                                '244',
                                                '1264',
                                                '1268',
                                                '54',
                                                '374',
                                                '297',
                                                '61',
                                                '43',
                                                '994',
                                                '1242',
                                                '973',
                                                '880',
                                                '1246',
                                                '375',
                                                '32',
                                                '501',
                                                '229',
                                                '1441',
                                                '975',
                                                '591',
                                                '387',
                                                '267',
                                                '55',
                                                '246',
                                                '673',
                                                '359',
                                                '226',
                                                '257',
                                                '855',
                                                '237',
                                                '1',
                                                '238',
                                                '1345',
                                                '236',
                                                '235',
                                                '56',
                                                '86',
                                                '61',
                                                '57',
                                                '269',
                                                '682',
                                                '506',
                                                '385',
                                                '53',
                                                '599',
                                                '357',
                                                '420',
                                                '45',
                                                '253',
                                                '1767',
                                                '1809',
                                                '593',
                                                '20',
                                                '503',
                                                '240',
                                                '291',
                                                '372',
                                                '251',
                                                '500',
                                                '298',
                                                '679',
                                                '358',
                                                '33',
                                                '594',
                                                '689',
                                                '241',
                                                '220',
                                                '995',
                                                '49',
                                                '233',
                                                '350',
                                                '30',
                                                '299',
                                                '1473',
                                                '590',
                                                '1671',
                                                '502',
                                                '224',
                                                '245',
                                                '592',
                                                '509',
                                                '504',
                                                '852',
                                                '36',
                                                '354',
                                                '91',
                                                '62',
                                                '98',
                                                '964',
                                                '353',
                                                '972',
                                                '39',
                                                '1876',
                                                '81',
                                                '962',
                                                '7',
                                                '254',
                                                '686',
                                                '82',
                                                '965',
                                                '996',
                                                '856',
                                                '371',
                                                '961',
                                                '266',
                                                '231',
                                                '218',
                                                '423',
                                                '370',
                                                '352',
                                                '853',
                                                '389',
                                                '261',
                                                '265',
                                                '60',
                                                '960',
                                                '223',
                                                '356',
                                                '692',
                                                '596',
                                                '222',
                                                '230',
                                                '262',
                                                '52',
                                                '691',
                                                '373',
                                                '377',
                                                '976',
                                                '382',
                                                '1664',
                                                '212',
                                                '258',
                                                '95',
                                                '264',
                                                '674',
                                                '977',
                                                '31',
                                                '687',
                                                '64',
                                                '505',
                                                '227',
                                                '234',
                                                '683',
                                                '672',
                                                '850',
                                                '47',
                                                '968',
                                                '92',
                                                '680',
                                                '970',
                                                '507',
                                                '675',
                                                '595',
                                                '51',
                                                '63',
                                                '48',
                                                '351',
                                                '1787',
                                                '974',
                                                '40',
                                                '250',
                                                '685',
                                                '378',
                                                '239',
                                                '966',
                                                '221',
                                                '381',
                                                '248',
                                                '232',
                                                '65',
                                                '421',
                                                '386',
                                                '677',
                                                '252',
                                                '27',
                                                '34',
                                                '94',
                                                '249',
                                                '597',
                                                '268',
                                                '46',
                                                '41',
                                                '963',
                                                '886',
                                                '992',
                                                '255',
                                                '66',
                                                '228',
                                                '690',
                                                '676',
                                                '1868',
                                                '216',
                                                '90',
                                                '993',
                                                '688',
                                                '256',
                                                '380',
                                                '971',
                                                '44',
                                                '598',
                                                '998',
                                                '678',
                                                '379',
                                                '58',
                                                '84',
                                                '1284',
                                                '1340',
                                                '681',
                                                '967',
                                                '260',
                                                '263',
                                            ];
                                        @endphp
                                        <span class="input-group-text">
                                            <i class='bx bx-mobile-alt fs-4'></i>

                                        </span>
                                        <select class="form-select select-country-code" id="country_code"
                                            name="country_code">
                                            @foreach ($countryCodes as $code)
                                                <option value="{{ $code }}"
                                                    {{ (string) $code === (string) $defaultCountryCode ? 'selected' : '' }}>
                                                    +{{ $code }}
                                                </option>
                                            @endforeach
                                        </select>

                                        <input type="text" maxlength="16" oninput="validateNumberInput(this)"
                                            class="form-control copied_mobile" name="mobile"
                                            placeholder="{{ labels('panel_labels.enter_your_mobile_number', 'Enter Your Mobile Number') }}"
                                            value={{ config('constants.ALLOW_MODIFICATION') === 0 ? '7852347893' : '' }}>
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
                                                <p id="mobileInfo" class="mb-1 data_total_font">8200354908</p>
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
                            href="{{ config('app.url') . 'admin/home' }}">{{ $system_settings['app_name'] }}.</a>
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
    </body>

</html>
