@extends('admin/layout')
@section('title')
    {{ labels('admin_labels.system_settings', 'System Settings') }}
@endsection
@section('content')
    @php
        $user = auth()->user();
        $role = auth()->user()->role->name;

    @endphp

    <x-admin.breadcrumb :title="labels('admin_labels.system_registration', 'System Registration')" :subtitle="labels('admin_labels.register_system_from_here', 'Register System From here')" :breadcrumbs="[
        ['label' => labels('admin_labels.home', 'Home'), 'url' => route('admin.home')],
        ['label' => labels('admin_labels.settings', 'Settings')],
        ['label' => labels('admin_labels.system_registration', 'System Registration')],
    ]" />

    <div class="col-md-12">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="mb-3">
                            {{ labels('admin_labels.system_registration', 'System Registration') }}
                        </h5>
                    </div>
                    <form id="" action="{{ route('admin.system_register') }}" class="submit_form"
                        enctype="multipart/form-data" method="POST">
                        @csrf
                        <div class="card-body pt-0">
                            <div class="mb-3">
                                <label class="form-label">
                                    {{ labels('admin_labels.purchase_code', 'eShop Plus Purchase Code for app') }}
                                    <span class="text-asterisks text-sm">*</span>
                                    <i class="fa fa-info-circle text-secondary ms-1"
                                       data-bs-toggle="popover"
                                       data-bs-placement="right"
                                       data-bs-content="{{ labels('admin_labels.popover_purchase_code_app', 'Enter the purchase code you received after buying eShop Plus for the app.') }}"></i>
                                </label>
                                <input type="text" class="form-control" id="purchase_code"
                                    placeholder="{{ labels('admin_labels.enter_purchase_code_here', 'Enter your purchase code here') }}" name="app_purchase_code" value="">
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="reset"
                                    class="btn mx-2 reset_button">{{ labels('admin_labels.reset', 'Reset') }}</button>
                                <button type="submit"
                                    class="btn btn-primary submit_button">{{ labels('admin_labels.register', 'Register') }}</button>
                            </div>
                        </div>
                    </form>
                    <form id="" action="{{ route('admin.web_system_register') }}" class="submit_form"
                        enctype="multipart/form-data" method="POST">
                        @csrf
                        <div class="card-body pt-0">
                            <div class="mb-3">
                                <label class="form-label">
                                    {{ labels('admin_labels.purchase_code', 'eShop Plus Purchase Code for web') }}
                                    <span class="text-asterisks text-sm">*</span>
                                    <i class="fa fa-info-circle text-secondary ms-1"
                                       data-bs-toggle="popover"
                                       data-bs-placement="right"
                                       data-bs-content="{{ labels('admin_labels.popover_purchase_code_web', 'Enter the purchase code you received after buying eShop Plus for the web.') }}"></i>
                                </label>
                                <input type="text" class="form-control" id="purchase_code"
                                    placeholder="Enter your purchase code here" name="web_purchase_code" value="">
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="reset"
                                    class="btn mx-2 reset_button">{{ labels('admin_labels.reset', 'Reset') }}</button>
                                <button type="submit"
                                    class="btn btn-primary submit_button">{{ labels('admin_labels.register', 'Register') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
