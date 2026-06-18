@extends('affiliate/layout')
@section('title')
    {{ labels('admin_labels.policies', 'Policies') }}
@endsection
@section('content')
    <x-affiliate.breadcrumb :title="labels('admin_labels.policies', 'Manage Policies')" :subtitle="labels('admin_labels.track_and_manage_policies', 'Track and manage policies with power and simplicity')" :breadcrumbs="[
        [
            'label' => labels('admin_labels.policies', 'Policies'),
        ],
    ]" />

    <div class="row g-4">
        <!-- Privacy Policy Card -->
        <div class="col-md-6">
            <div class="card shadow-sm rounded">
                <div class="card-header">
                    <h5 class="mb-0">
                        {{ labels('admin_labels.privacy_policy', 'Privacy Policy') }}
                    </h5>
                </div>
                <div class="card-body">
                    <textarea class="form-control addr_editor" name="affiliate_privacy_policy" placeholder="Enter your Privacy Policy here..."
                        rows="8" style="resize: vertical;">{{ old('affiliate_privacy_policy', $privacyPolicy['affiliate_privacy_policy'] ?? '') }}</textarea>
                </div>
            </div>
        </div>

        <!-- Terms and Conditions Card -->
        <div class="col-md-6">
            <div class="card shadow-sm rounded">
                <div class="card-header">
                    <h5 class="mb-0">
                        {{ labels('admin_labels.terms_and_conditions', 'Terms and Conditions') }}
                    </h5>
                </div>
                <div class="card-body">
                    <textarea class="form-control addr_editor" name="affiliate_terms_and_conditions"
                        placeholder="Enter your Terms and Conditions here..." rows="8" style="resize: vertical;">{{ old('affiliate_terms_and_conditions', $termsAndConditions['affiliate_terms_and_conditions'] ?? '') }}</textarea>
                </div>
            </div>
        </div>
    </div>
@endsection
