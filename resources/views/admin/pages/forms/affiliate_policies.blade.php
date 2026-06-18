@extends('admin/layout')
@section('title')
    {{ labels('admin_labels.policies', 'Affiliate Policies') }}
@endsection
@section('content')
    <x-admin.breadcrumb :title="labels('admin_labels.privacy_policy', 'Privacy Policy And Terms & Conditions')" :subtitle="labels(
        'admin_labels.efficiently_organize_and_control_affiliate_policies',
        'Efficiently Organize and Control Affiliate Policies',
    )" :breadcrumbs="[['label' => labels('admin_labels.policies', 'Policies')]]" />
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12 col-xxl-6">
                            <div class="form-group">
                                <form id="" action="{{ route('affiliate_privacy_policy.store') }}" class="submit_form"
                                    enctype="multipart/form-data" method="POST">
                                    @csrf
                                    <div class="form-group">
                                        <div class="card-body p-0">
                                            <div class="form-group">

                                                <h5 class="mb-3">
                                                    {{ labels('admin_labels.privacy_policy', 'Privacy Policy') }}
                                                </h5>
                                                <textarea class="form-control addr_editor"name="affiliate_privacy_policy" placeholder="{{ labels('admin_labels.privacy_policy_placeholder', 'Privacy Policy') }}" rows="5">{{ isset($privacyPolicy['affiliate_privacy_policy']) ? $privacyPolicy['affiliate_privacy_policy'] : '' }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-end mt-4">
                                        <button type="reset"
                                            class="btn mx-2 reset_button">{{ labels('admin_labels.reset', 'Reset') }}</button>
                                        <button type="submit"
                                            class="btn btn-primary submit_button">{{ labels('admin_labels.update_settings', 'Update Settings') }}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="col-md-12 col-xxl-6">
                            <div class="form-group">
                                <form id="" action="{{ route('affiliate_terms_and_conditions.store') }}"
                                    class="aff_submit_form" enctype="multipart/form-data" method="POST">
                                    @csrf
                                    <div class="form-group">
                                        <div class="card-body p-0">
                                            <div class="form-group">
                                                <h5 class="mb-3">
                                                    {{ labels('admin_labels.affiliate_terms_and_conditions', 'Terms & Conditions') }}
                                                </h5>
                                                <textarea class="form-control addr_editor"name="affiliate_terms_and_conditions" placeholder="{{ labels('admin_labels.terms_and_conditions_placeholder', 'Terms and Conditions') }}"
                                                    rows="5">{{ isset($termsAndConditions['affiliate_terms_and_conditions']) ? $termsAndConditions['affiliate_terms_and_conditions'] : '' }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-end mt-4">
                                        <button type="reset"
                                            class="btn mx-2 reset_button">{{ labels('admin_labels.reset', 'Reset') }}</button>
                                        <button type="submit"
                                            class="btn btn-primary submit_button">{{ labels('admin_labels.update_settings', 'Update Settings') }}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
