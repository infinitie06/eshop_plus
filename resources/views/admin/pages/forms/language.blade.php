@extends('admin/layout')
@section('title')
    {{ labels('admin_labels.language', 'Language') }}
@endsection
@section('content')
    <x-admin.breadcrumb :title="labels('admin_labels.language', 'Language')" :subtitle="labels('admin_labels.track_and_manage_language', 'Track and Manage Language')" :breadcrumbs="[['label' => labels('admin_labels.language', 'Language')]]" />


    <div class="alert alert-info d-flex align-items-start gap-2 mb-4">
        <i class="fas fa-lightbulb mt-1 text-primary"></i>
        <div>
            <div class="fw-bold">{{ labels('admin_labels.quick_guide', 'Quick guide') }}</div>
            <ul class="mb-0 ps-3">
                <li>{{ labels('admin_labels.create_new_language_requires_translation_file', 'Create a new language (requires translation file) or switch to an existing one using the action toggle.') }}
                </li>
                <li>{{ labels('admin_labels.when_creating_new_language_upload_file', 'When creating a new language, upload a JSON/PHP translation file. It will be saved as admin_labels.json for Panels type by default.') }}
                </li>
                <li>{{ labels('admin_labels.use_compare_labels_to_see_missing_keys', 'Use Compare Labels to see what keys are missing before uploading.') }}
                </li>
            </ul>
        </div>
    </div>

    <div class="row">
        <!-- Merged Card for Creating/Selecting Language and Uploading Files -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">
                        {{ labels('admin_labels.create_or_manage_language', 'Create or Manage Language') }}</h5>
                    <p class="text-muted small mb-3">
                        {{ labels('admin_labels.create_new_language_or_select_existing', 'Create a new language (requires translation file) or select an existing one to update.') }}
                    </p>
                    <input type="hidden" id="current-lang" value="{{ $language_code }}" />
                    <form class="submit_form" action="{{ route('language.store') }}" method="POST"
                        enctype="multipart/form-data" id="language_manage_form"
                        data-store-route="{{ route('language.store') }}"
                        data-savelabel-route="/admin/settings/languages/savelabel"
                        data-compare-route="/admin/compare-language-labels">
                        @csrf
                        <meta name="csrf-token" content="{{ csrf_token() }}">

                        <!-- Language Selection Toggle -->
                        <div class="mb-4">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <label
                                    class="form-label mb-0">{{ labels('admin_labels.language_action', 'Action') }}</label>
                                <i class="fas fa-info-circle text-secondary" data-bs-toggle="popover"
                                    data-bs-content="{{ labels('admin_labels.choose_how_you_want_to_work', 'Choose how you want to work: create a new language or update an existing one.') }}"></i>
                            </div>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="action_type" id="action_create" value="create"
                                    checked>
                                <label class="btn btn-outline-primary" for="action_create">
                                    <i
                                        class="fas fa-plus me-2"></i>{{ labels('admin_labels.create_new_language', 'Create New Language') }}
                                </label>
                                <input type="radio" class="btn-check" name="action_type" id="action_select"
                                    value="select">
                                <label class="btn btn-outline-primary" for="action_select">
                                    <i
                                        class="fas fa-list me-2"></i>{{ labels('admin_labels.select_existing_language', 'Select Existing Language') }}
                                </label>
                            </div>
                            <p class="text-muted small mt-2 mb-0">
                                {{ labels('admin_labels.use_create_option_for_new_languages', 'Use the Create option for brand new languages (translation file required). Select lets you optionally upload updated labels to an existing language.') }}
                            </p>
                        </div>

                        <!-- Create New Language Fields -->
                        <div id="create_language_fields" class="language-fields-section">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center gap-2">
                                        <label class="form-label mb-0" for="language">
                                            {{ labels('admin_labels.language', 'Language') }} <span
                                                class="text-danger">*</span>
                                        </label>
                                        <i class="fas fa-info-circle text-secondary" data-bs-toggle="popover"
                                            data-bs-content="{{ labels('admin_labels.display_name_shown_across_app', 'Display name shown across the app (e.g., English, Hindi).') }}"></i>
                                    </div>
                                    <input type="text" class="form-control" id="language" name="language"
                                        placeholder="{{ labels('admin_labels.english_placeholder', 'English') }}" value="{{ old('language') }}">
                                    <small
                                        class="text-muted">{{ labels('admin_labels.example_english_hindi', 'Example: English, Hindi') }}</small>
                                </div>
                                <div class="col-md-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <label class="form-label mb-0" for="code">
                                            {{ labels('admin_labels.code', 'Code') }} <span class="text-danger">*</span>
                                        </label>
                                        <i class="fas fa-info-circle text-secondary" data-bs-toggle="popover"
                                            data-bs-content="{{ labels('admin_labels.two_letter_iso_code', 'Two-letter ISO code used in file names and API calls (e.g., en, hi).') }}"></i>
                                    </div>
                                    <input type="text" class="form-control" id="code" name="code"
                                        placeholder="{{ labels('admin_labels.en_placeholder', 'en') }}" value="{{ old('code') }}">
                                    <small
                                        class="text-muted">{{ labels('admin_labels.use_iso_code', 'Use ISO code') }}</small>
                                </div>
                                <div class="col-md-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <label class="form-label mb-0" for="native_language">
                                            {{ labels('admin_labels.native_language', 'Native Language') }}
                                        </label>
                                        <i class="fas fa-info-circle text-secondary" data-bs-toggle="popover"
                                            data-bs-content="{{ labels('admin_labels.optional_native_script', 'Optional: native script to help translators (e.g., English / हिन्दी).') }}"></i>
                                    </div>
                                    <input type="text" class="form-control" id="native_language" name="native_language"
                                        placeholder="English / हिन्दी" value="{{ old('native_language') }}">
                                </div>
                                <div class="col-md-6 d-flex align-items-center">
                                    <div class="form-check form-switch ms-4 mt-2">
                                        <input type="checkbox" name="is_rtl" class="form-check-input" id="is_rtl_switch">
                                    </div>
                                    <label for="is_rtl_switch" class="mb-0 ms-2">
                                        {{ labels('admin_labels.is_rtl', 'Is RTL') }}?
                                    </label>
                                </div>
                            </div>

                            <hr class="my-4">
                            <h6 class="mb-3">
                                {{ labels('admin_labels.upload_translation_files_all_required', 'Upload Translation Files (All required)') }}
                            </h6>
                            <div class="row g-3 mb-3">
                                @php
                                    $labelFiles = [
                                        'panel' => labels('admin_labels.panels_labels_file', 'Panels Labels'),
                                        'web' => labels('admin_labels.web_labels_file', 'Web Labels'),
                                        'app' => labels('admin_labels.app_labels_file', 'App Labels'),
                                        'seller' => labels('admin_labels.seller_app_labels_file', 'Seller App Labels'),
                                        'delivery' => labels('admin_labels.delivery_app_labels_file', 'Delivery App Labels'),
                                    ];
                                @endphp
                                @foreach ($labelFiles as $type => $label)
                                    <div class="col-12">
                                        <label class="form-label">
                                            {{ $label }}
                                            <span class="text-danger">*</span>
                                        </label>
                                        <div class="custom-file-input-wrapper d-flex align-items-stretch form-control p-0 overflow-hidden">
                                            <label for="create_file_{{ $type }}"
                                                class="btn btn-light border-0 mb-0 px-3 d-flex align-items-center"
                                                style="cursor: pointer; border-radius: 0;">
                                                {{ labels('admin_labels.choose_file', 'Choose file') }}
                                            </label>
                                            <span id="create_file_{{ $type }}_name"
                                                class="flex-grow-1 d-flex align-items-center px-3 text-muted text-truncate">
                                                {{ labels('admin_labels.no_file_chosen', 'No file chosen') }}
                                            </span>
                                        </div>
                                        <input type="file" name="translation_files[{{ $type }}]"
                                            id="create_file_{{ $type }}" accept=".json,.php"
                                            class="d-none translation-file-input create-translation-file"
                                            data-name-target="#create_file_{{ $type }}_name"
                                            data-empty-text="{{ labels('admin_labels.no_file_chosen', 'No file chosen') }}">
                                        <small class="text-muted">{{ labels('admin_labels.json_or_php_file_for', 'JSON or PHP file for') }} {{ strtolower($label) }}.</small>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Select Existing Language Dropdown -->
                        <div id="select_language_fields" class="language-fields-section d-none">
                            <div class="row g-3 mb-3">
                                <div class="col-md-12">
                                    <div class="d-flex align-items-center gap-2">
                                        <label for="language_code" class="form-label mb-0">
                                            {{ labels('admin_labels.select_language', 'Select Language') }} <span
                                                class="text-danger">*</span>
                                        </label>
                                        <i class="fas fa-info-circle text-secondary" data-bs-toggle="popover"
                                            data-bs-content="{{ labels('admin_labels.pick_existing_language_to_replace', 'Pick an existing language to replace or add labels via upload.') }}"></i>
                                    </div>
                                    <select name="language_code" id="language_code" class="form-control">
                                        <option value="">{{ labels('admin_labels.select', 'Select') }}</option>
                                        @foreach ($languages as $language)
                                            <option value="{{ $language->code }}"
                                                {{ $language->code == $language_code ? 'selected' : '' }}>
                                                {{ $language->language }} ({{ $language->code }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <hr class="my-4">
                            <h6 class="mb-3">
                                {{ labels('admin_labels.upload_translation_files_optional', 'Upload Translation Files (optional)') }}
                            </h6>
                            <div class="row g-3 mb-3">
                                @php
                                    $labelFiles = [
                                        'panel' => labels('admin_labels.panels_labels_file', 'Panels Labels'),
                                        'web' => labels('admin_labels.web_labels_file', 'Web Labels'),
                                        'app' => labels('admin_labels.app_labels_file', 'App Labels'),
                                        'seller' => labels('admin_labels.seller_app_labels_file', 'Seller App Labels'),
                                        'delivery' => labels('admin_labels.delivery_app_labels_file', 'Delivery App Labels'),
                                    ];
                                @endphp
                                @foreach ($labelFiles as $type => $label)
                                    <div class="col-12">
                                        <label class="form-label">
                                            {{ $label }}
                                        </label>
                                        <div class="custom-file-input-wrapper d-flex align-items-stretch form-control p-0 overflow-hidden">
                                            <label for="select_file_{{ $type }}"
                                                class="btn btn-light border-0 mb-0 px-3 d-flex align-items-center"
                                                style="cursor: pointer; border-radius: 0;">
                                                {{ labels('admin_labels.choose_file', 'Choose file') }}
                                            </label>
                                            <span id="select_file_{{ $type }}_name"
                                                class="flex-grow-1 d-flex align-items-center px-3 text-muted text-truncate">
                                                {{ labels('admin_labels.no_file_chosen', 'No file chosen') }}
                                            </span>
                                        </div>
                                        <input type="file" name="translation_files_select[{{ $type }}]"
                                            id="select_file_{{ $type }}" accept=".json,.php"
                                            class="d-none translation-file-input select-translation-file"
                                            data-name-target="#select_file_{{ $type }}_name"
                                            data-empty-text="{{ labels('admin_labels.no_file_chosen', 'No file chosen') }}">
                                        <small class="text-muted">{{ labels('admin_labels.json_or_php_file_for', 'JSON or PHP file for') }} {{ strtolower($label) }}.</small>
                                    </div>
                                @endforeach
                            </div>
                        </div>



                        <!-- Action Buttons -->
                        <div class="mt-3 d-flex flex-wrap gap-2">
                            <button type="button" id="compare_labels_btn" class="btn btn-outline-info d-none">
                                <i
                                    class="fas fa-search me-2"></i>{{ labels('admin_labels.compare_labels', 'Compare Labels') }}
                            </button>
                            <button type="submit" class="btn btn-primary submit_button">
                                <i class="fas fa-save me-2"></i>{{ labels('admin_labels.save', 'Save') }}
                            </button>
                        </div>
                    </form>
                    <div id="comparison_result" class="mt-3 alert alert-secondary d-none"></div>
                </div>
            </div>
        </div>

        <!-- Card for Downloading Translation Files -->
        <div class="col-lg-4">

            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <h6 class="card-title mb-1">
                                {{ labels('admin_labels.download_translation_file', 'Download Translation File') }}</h6>
                            <p class="text-muted small mb-0">
                                {{ labels('admin_labels.select_language_and_type_to_download', 'Select language and type, then download existing labels.') }}
                            </p>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="download_language_code"
                                class="form-label">{{ labels('admin_labels.select_language', 'Select Language') }}</label>
                            <select name="download_language_code" id="download_language_code" class="form-control">
                                <option value="">{{ labels('admin_labels.select', 'Select') }}</option>
                                @foreach ($languages as $language)
                                    <option value="{{ $language->code }}"
                                        {{ $language->code == $language_code ? 'selected' : '' }}>
                                        {{ $language->language }} ({{ $language->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="download_file_type"
                                class="form-label">{{ labels('admin_labels.file_type', 'File Type') }}</label>
                            <select name="download_file_type" id="download_file_type" class="form-control form-select">
                                <option value="web">{{ labels('admin_labels.web_frontend_option', 'Web (Frontend)') }}</option>
                                <option value="panel">{{ labels('admin_labels.panels_option', 'Panels') }}</option>
                                <option value="app">{{ labels('admin_labels.app_customer_option', 'App (Customer)') }}</option>
                                <option value="seller">{{ labels('admin_labels.seller_option', 'Seller') }}</option>
                                <option value="delivery">{{ labels('admin_labels.delivery_boy_option', 'Delivery Boy') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <button type="button" id="download_file_btn" class="btn btn-primary"
                            data-route-base="{{ url('/download-language-json-file') }}">
                            <i
                                class="fas fa-download me-2"></i>{{ labels('admin_labels.download_file', 'Download File') }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <h6 class="card-title mb-1">{{ labels('admin_labels.get_file_info', 'Get File Information') }}
                            </h6>
                            <p class="text-muted small mb-0">
                                {{ labels('admin_labels.quickly_see_which_files_exist', 'Quickly see which files exist, counts, and last updates.') }}
                            </p>
                        </div>
                    </div>
                    <div class="d-flex justify-content-start">
                        <button type="button" id="get_file_info_btn" class="btn btn-outline-secondary btn-sm">
                            <i
                                class="fas fa-info-circle me-2"></i>{{ labels('admin_labels.get_file_info', 'Get File Information') }}
                        </button>
                    </div>
                    <div id="file_info_result" class="mt-3 d-none"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="/assets/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.translation-file-input').forEach(function(input) {
                input.addEventListener('change', function() {
                    var targetSel = this.getAttribute('data-name-target');
                    var emptyText = this.getAttribute('data-empty-text') || '';
                    if (!targetSel) return;
                    var target = document.querySelector(targetSel);
                    if (!target) return;
                    target.textContent = (this.files && this.files[0]) ? this.files[0].name : emptyText;
                });
            });
        });
    </script>
@endsection
