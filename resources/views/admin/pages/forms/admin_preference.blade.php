@extends('admin/layout')
@section('title')
    {{ labels('admin_labels.admin_preference', 'Admin Preference') }}
@endsection
@section('content')
    <x-admin.breadcrumb :title="labels('admin_labels.admin_preference', 'Admin Preference')"
                        :subtitle="labels('admin_labels.efficiently_organize_and_control_admin_setting', 'Efficiently Organize and Control admin Setting')"
                        :breadcrumbs="[
                            ['label' => labels('admin_labels.settings', 'Settings'), 'url' => route('settings.index')],
                            ['label' => labels('admin_labels.admin_preference', 'Admin Preference')],
                        ]" />

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="mb-4">{{ labels('admin_labels.admin_preference', 'Admin Preference') }}</h5>

                    <form class="submit_form" action="{{ route('admin_preference.store') }}" method="post">
                        @csrf

                        <div class="row mb-4 align-items-center">
                            {{-- Store Mode --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    {{ labels('admin_labels.store_mode', 'Store Mode') }}
                                    <i class="fa fa-info-circle text-muted ms-1"
                                       data-bs-toggle="popover"
                                       data-bs-placement="right"
                                       data-bs-content="{{ labels('admin_labels.select_single_or_multi_store_mode', 'Select whether the system operates as a single store or supports multiple stores.') }}"></i>
                                </label>
                                <div class="d-flex gap-3 mt-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="store_mode" id="single_store"
                                            value="single" {{ old('store_mode', $settings->store_mode ?? '') == 'single' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="single_store">{{ labels('admin_labels.single_store', 'Single Store') }}</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="store_mode" id="multi_store"
                                            value="multi" {{ old('store_mode', $settings->store_mode ?? '') == 'multi' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="multi_store">{{ labels('admin_labels.multi_store', 'Multi Store') }}</label>
                                    </div>
                                </div>
                            </div>

                            {{-- Order Notification --}}
                            {{-- <div class="col-md-6 ">
                                <label class="form-check-label ms-2" for="order_notification">
                                        {{ labels('admin_labels.order_notification', 'Order Notification') }}
                                            <i class="fa fa-info-circle text-muted ms-2"
                                       data-bs-toggle="popover"
                                       data-bs-placement="right"
                                       data-bs-content="Toggle to enable or disable order notifications for admins."></i>
                                    </label>

                                     <div class="d-flex gap-3 mt-2 ms-1">

                                        <div class="form-check form-switch mx-5">

                                     <input class="form-check-input" type="checkbox" id="order_notification" name="order_notification" value="1"
                                        {{ old('order_notification', $settings->order_notification ?? 0) ? 'checked' : '' }}>

                                        </div>
                                </div>
                            </div> --}}
                        </div>

                        {{-- Submit Button aligned right --}}
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                {{ labels('admin_labels.save_changes', 'Save Changes') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


@endsection
