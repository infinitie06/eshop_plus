@extends('admin/layout')
@section('title')
{{ labels('panel_labels.account', 'Account') }}
@endsection
@section('content')
<x-admin.breadcrumb :title="labels('panel_labels.account_setting', 'Account Setting')" :subtitle="labels(
            'panel_labels.efficiently_manage_account_with_precision',
            'Efficiently Manage Account With Precision',
        )" :breadcrumbs="[['label' => labels('admin_labels.account_setting', 'Account Setting')]]" />

@php
use App\Services\MediaService;
$allowModification = config('constants.ALLOW_MODIFICATION') == 1;
@endphp

<div class="col-md-12 col-xxl-6">
    <div class="card">
        <div class="card-body">
            <form id="validationForm" method="POST" action="/admin/users/update/{{ auth()->user()->id }}"
                enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-12">
                        @php
                        $isPublicDisk = $user->disk == 'public' ? 1 : 0;
                        $imagePath = $isPublicDisk
                        ? app(MediaService::class)->getMediaImageUrl($user->image, 'USER_IMG_PATH')
                        : $user->image;
                        @endphp

                    </div>
                </div>

                <div class="row">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="edit_image" value="{{ $user->image }}">
                    <div class="col-md-12 text-center">
                        <img class="rounded-circle img-fluid" src="{{ route('admin.dynamic_image', [
        'url' => $imagePath,
        'width' => 120,
        'quality' => 90,
    ]) }}" alt="">
                    </div>
                </div>
                <div class="row mt-8">
                    <div class="col-md-12 text-center">
                        <input type="file" class="filepond" name="image" multiple data-max-file-size="30MB"
                            data-max-files="20" accept="image/*,.webp" / />
                    </div>
                </div>
                <div class="row">
                    @csrf
                    @method('PUT')
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="example-text-input"
                                class="form-control-label mb-2">{{ labels('panel_labels.user_name', 'User Name') }}
                                <i class="fa fa-info-circle text-secondary ms-1" data-bs-toggle="popover"
                                    data-bs-placement="right"
                                    data-bs-content="{{ labels('admin_labels.enter_username_for_account_login', 'Enter your username for account login.') }}"></i>
                            </label>
                            <input class="form-control" type="text" name="username"
                                value="{{ $user->username !== 'null' ? $user->username : '' }}" onfocus="focused(this)"
                                onfocusout="defocused(this)">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="example-text-input"
                                class="form-control-label mb-2">{{ labels('admin_labels.mobile', 'Mobile') }}
                                <i class="fa fa-info-circle text-secondary ms-1" data-bs-toggle="popover"
                                    data-bs-placement="right" data-bs-content="{{ labels('admin_labels.your_registered_mobile_number', 'Your registered mobile number.') }}"></i>
                            </label>
                            <div class="col-md-12">
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
                                        <option value="{{ $code }}"
                                            @selected($user->country_code == $code)>
                                            +{{ $code }}
                                        </option>
                                        @endforeach
                                    </select>

                                    <input class="form-control" readonly type="number" name="mobile"
                                        value="{{ $user->mobile !== 'null' ? ($allowModification ? $user->mobile : '************') : '' }}"
                                        onfocus="focused(this)" onfocusout="defocused(this)">
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- <div class="col-md-6">
                            <div class="form-group">
                                <label for="country_code"
                                    class="form-control-label mb-2">{{ labels('admin_labels.country_code', 'Country Code') }}
                                    <i class="fa fa-info-circle text-secondary ms-1" data-bs-toggle="popover"
                                        data-bs-placement="right" data-bs-content="Your registered country code."></i>
                                </label>
                                <input class="form-control" readonly type="text" id="country_code" name="country_code"
                                    value="{{ $user->country_code !== 'null' ? $user->country_code : '' }}"
                                    onfocus="focused(this)" onfocusout="defocused(this)">
                            </div>
                        </div> -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="example-text-input"
                                class="form-control-label mb-2">{{ labels('admin_labels.email', 'Email') }}
                                <i class="fa fa-info-circle text-secondary ms-1" data-bs-toggle="popover"
                                    data-bs-placement="right" data-bs-content="{{ labels('admin_labels.your_registered_email_address', 'Your registered email address.') }}"></i>
                            </label>
                            <input class="form-control" readonly type="email" name="email"
                                value="{{ $user->email !== 'null' ? ($allowModification ? $user->email : '************') : '' }}"
                                onfocus="focused(this)" onfocusout="defocused(this)">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="old_password"
                                class="form-control-label mb-2">{{ labels('admin_labels.old_password', 'Old Password') }}
                                <i class="fa fa-info-circle text-secondary ms-1" data-bs-toggle="popover"
                                    data-bs-placement="right"
                                    data-bs-content="{{ labels('admin_labels.enter_current_password_to_change_it', 'Enter your current password to change it.') }}"></i>
                            </label>
                            <div class="input-group">
                                <input type="password" class="form-control show_profile_password" name="old_password"
                                    placeholder="{{ labels('admin_labels.enter_your_password_placeholder', 'Enter Your Password') }}">
                                <span class="input-group-text cursor-pointer toggle_profile_password"><i
                                        class="bx bx-hide"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="new_password"
                                class="form-control-label mb-2">{{ labels('admin_labels.new_password', 'New Password') }}
                                <i class="fa fa-info-circle text-secondary ms-1" data-bs-toggle="popover"
                                    data-bs-placement="right"
                                    data-bs-content="{{ labels('admin_labels.enter_new_password_for_account', 'Enter a new password for your account.') }}"></i>
                            </label>
                            <div class="input-group">
                                <input type="password" class="form-control show_profile_password" name="new_password"
                                    placeholder="Enter Your Password">
                                <span class="input-group-text cursor-pointer toggle_profile_password"><i
                                        class="bx bx-hide"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="new_password_confirmation"
                                class="form-control-label mb-2">{{ labels('admin_labels.confirm_password', 'Confirm Password') }}
                                <i class="fa fa-info-circle text-secondary ms-1" data-bs-toggle="popover"
                                    data-bs-placement="right"
                                    data-bs-content="{{ labels('admin_labels.reenter_new_password_for_confirmation', 'Re-enter your new password for confirmation.') }}"></i>
                            </label>
                            <div class="input-group">
                                <input type="password" class="form-control show_profile_password"
                                    name="new_password_confirmation" placeholder="{{ labels('admin_labels.confirm_your_password_placeholder', 'Confirm Your Password') }}">
                                <span class="input-group-text cursor-pointer toggle_profile_password"><i
                                        class="bx bx-hide"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="example-text-input"
                                class="form-control-label mb-2">{{ labels('admin_labels.address', 'Address') }}
                                <i class="fa fa-info-circle text-secondary ms-1" data-bs-toggle="popover"
                                    data-bs-placement="right"
                                    data-bs-content="{{ labels('admin_labels.enter_address_for_account_records', 'Enter your address for account records.') }}"></i>
                            </label>
                            <textarea name="address" class="form-control"
                                placeholder="{{ labels('admin_labels.write_here_your_address', 'Write here your address') }}">{{ $user->address !== 'null' ? $user->address : '' }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="mt-2 d-flex justify-content-end">
                    <button type="submit"
                        class="btn btn-primary submit_button">{{ labels('admin_labels.save_changes', 'Save Changes') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection