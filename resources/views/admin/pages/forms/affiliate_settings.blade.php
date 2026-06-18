@extends('admin/layout')
@section('title')
    {{ labels('admin_labels.affiliate_settings', 'Affiliate Settings') }}
@endsection
@section('content')
    <x-admin.breadcrumb :title="labels('admin_labels.settings', 'Settings')" :subtitle="labels(
        'admin_labels.efficiently_organize_and_control_affiliate_settings',
        'Efficiently Organize and Control Affiliate Settings',
    )" :breadcrumbs="[['label' => labels('admin_labels.settings', 'Settings')]]" />

    <div class="row">
        <div class="col-md-6">
            <div class="card card-primary card-outline h-100">

                <div class="card-body">
                    <h5 class="card-title">{{ labels('admin_labels.basic_information', 'Basic Information') }}</h5>
                    <form class="form-horizontal submit_form" action="{{ route('admin.affiliate.settings.store') }}"
                        method="POST" id="system_setting_form" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group mb-3">
                            <label for="max_amount_for_withdrawal_request" class="mb-2">
                                {{ labels('admin_labels.maximum_amount_for_withdrawal_request', 'Maximum Amount for Withdrawal Request') }}
                                <span class='text-danger text-xs'>*</span>
                            </label>
                            <input type="number" maxlength="16" min=1 class="form-control"
                                name="max_amount_for_withdrawal_request"
                                value="{{ isset($affiliateSettings['max_amount_for_withdrawal_request']) ? $affiliateSettings['max_amount_for_withdrawal_request'] : '' }}"
                                placeholder="{{ labels('admin_labels.max_amount_for_withdrawal_request_placeholder', 'Max Amount for Withdrawal Request') }}" />
                        </div>
                        <div class="form-group mb-3">
                            <label for="min_amount_for_withdrawal_request" class="mb-2">
                                {{ labels('admin_labels.minimum_amount_for_withdrawal_request', 'Minimum Amount for Withdrawal Request') }}
                                <span class='text-danger text-xs'>*</span>
                            </label>
                            <input type="number" maxlength="16" min=1 class="form-control"
                                name="min_amount_for_withdrawal_request"
                                value="{{ isset($affiliateSettings['min_amount_for_withdrawal_request']) ? $affiliateSettings['min_amount_for_withdrawal_request'] : '' }}"
                                placeholder="{{ labels('admin_labels.min_amount_for_withdrawal_request_placeholder', 'Min Amount for Withdrawal Request') }}" />
                        </div>
                        <div class="d-flex gap-2 justify-content-end">
                            <button type="reset" class="btn mt-4 reset-btn mx-2" id="">
                                {{ labels('admin_labels.reset', 'Reset') }}
                            </button>
                            <button type="submit" class="btn btn-primary mt-4 submit_button" id="">
                                {{ labels('admin_labels.update_settings', 'Update Settings') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card card-primary card-outline h-100">

                <div class="card-body">
                    <h5 class="card-title">{{ labels('admin_labels.affiliate_commission', 'Affiliate Commission') }} <small>(%)</small></h5>
                    <form action="{{ route('admin.affiliate.settings.update_commission') }}" method="post"
                        class="form-horizontal affiliate_commission_form" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-4">
                            <div class="categories-repeater">
                                @if (!empty($affiliateCommissions))
                                    @foreach ($affiliateCommissions as $item)
                                        <div class="row repeater-item mb-3">
                                            <div class="col-md-5 p-1">
                                                <select name="category_id[]" class="form-control select2">
                                                    <option value="">Select Category</option>
                                                    {!! getAffiliateCategoriesOptionHtml($categories, [$item['category_id']], 0, $usedValues) !!}
                                                </select>
                                            </div>
                                            <div class="col-md-5 p-1">
                                                <input type="number" min=1 max=100 class="form-control" name="commission[]"
                                                    placeholder="{{ labels('admin_labels.commission_placeholder', 'Commission') }}" value="{{ $item['commission'] }}">
                                            </div>
                                            <div class="col-md-2 d-flex align-items-center">
                                                <button type="button" class="btn btn-danger remove-btn"><i
                                                        class="fas fa-times"></i></button>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="row repeater-item mb-3">
                                        <div class="col-md-5 p-1">
                                            <select name="category_id[]" class="form-control select2" required>
                                                <option value="">Select Category</option>
                                                {!! getAffiliateCategoriesOptionHtml($categories) !!}
                                            </select>
                                        </div>
                                        <div class="col-md-5 p-1">
                                            <input type="number" min=1 max=100 class="form-control" name="commission[]"
                                                placeholder="{{ labels('admin_labels.commission_placeholder', 'Commission') }}" required>
                                        </div>
                                        <div class="col-md-2 d-flex align-items-center">
                                            <button type="button" class="btn btn-danger remove-btn"><i
                                                    class="fas fa-times"></i></button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <button type="button" class="btn btn-primary add_more_categories">{{ labels('admin_labels.add_more', 'Add More') }}</button>
                        </div>
                        <div class="d-flex gap-2 justify-content-end">
                            <button type="submit" class="btn btn-primary mt-4 commission_submit_button">
                                {{ labels('admin_labels.update_commission', 'Update Commission') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
