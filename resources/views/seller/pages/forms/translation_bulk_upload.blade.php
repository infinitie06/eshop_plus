@extends('seller/layout')
@section('title')
    {{ labels('admin_labels.multi_language_bulk_import', 'Multi Language Bulk Import') }}
@endsection
@section('content')
    <x-seller.breadcrumb :title="labels('admin_labels.multi_language_bulk_import', 'Multi Language Bulk Import')" :subtitle="labels(
        'admin_labels.simplify_tasks_with_powerful_multi_language_bulk_import_capabilities',
        'Simplify Tasks with Powerful Multi Language Bulk Import Capabilities.',
    )" :breadcrumbs="[
        ['label' => labels('admin_labels.language', 'Language')],
        ['label' => labels('admin_labels.multi_language_bulk_import', 'Multi Language Bulk Import')],
    ]" />

    <div class="row">
        <div class="col-md-12 col-lg-6">
            <div class="card">
                <form class="form-horizontal" action="{{ route('seller.translation_bulk_upload') }}" method="POST"
                    id="translation_bulk_upload_form">
                    @csrf
                    <div class="card-body">
                        <h5 class="mb-3">
                            {{ labels('admin_labels.multi_language_bulk_import', 'Multi Language Bulk Import') }}
                        </h5>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="type" class="form-label">{{ labels('admin_labels.type', 'Type') }}
                                        <span class='text-asterisks text-sm'>*</span></label>
                                    <select class="form-control form-select" name="type" id="type">
                                        <option value="">{{ labels('admin_labels.select_option', 'Select') }}</option>
                                        <option value="products">{{ labels('admin_labels.products_option', 'Products') }}</option>
                                        <option value="combo_products">{{ labels('admin_labels.combo_products_option', 'Combo Products') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <label for="upload_file">{{ labels('admin_labels.file', 'File') }}
                                    <span class='text-asterisks text-sm'>*</span></label>
                                <div class="input-group">
                                    <label for="upload_file" class="btn btn-outline-secondary mb-0">
                                        {{ labels('admin_labels.choose_file', 'Choose file') }}
                                    </label>
                                    <span class="form-control" id="upload_file_name"
                                        data-placeholder="{{ labels('admin_labels.no_file_chosen', 'No file chosen') }}">
                                        {{ labels('admin_labels.no_file_chosen', 'No file chosen') }}
                                    </span>
                                    <input type="file" name="upload_file" id="upload_file" class="d-none" accept=".csv" />
                                </div>
                            </div>
                        </div>
                        <div class="row mt-4">
                            <div class="d-flex justify-content-end">
                                <button type="reset"
                                    class="btn mx-2 reset_button">{{ labels('admin_labels.reset', 'Reset') }}</button>
                                <button type="submit"
                                    class="btn btn-primary submit_button">{{ labels('admin_labels.submit', 'Submit') }}</button>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="row">
                                <label for="file">{{ labels('admin_labels.sample_files', 'Sample Files') }}</label>
                                <div class="col-md-3">
                                    <div class="form-group mt-2">
                                        <a href="{{ asset('storage/bulk_translation.zip') }}"
                                            class="btn btn-primary btn-sm instructions_files"
                                            download="bulk_translation.zip">{{ labels('admin_labels.download', 'Download') }}
                                            <i class="fas fa-download mx-2"></i></a>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mt-2">
                                        <a href="{{ url('seller/export/translation_csv') }}"
                                            class="btn btn-primary btn-sm instructions_files">
                                            {{ labels('admin_labels.download_data_for_translation', 'Download Data for Translation') }}<i
                                                class="fas fa-download mx-2"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 d-flex justify-content-center form-group">
                                    <div id="upload_result" class="p-3"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-md-12 col-lg-6 mt-md-2 mt-lg-0">
            <div class="bulk_upload_instruction_card">
                <ul>
                    <li>{{ labels('admin_labels.read_follow_instructions_carefully', 'Read and follow instructions carefully while preparing data') }}</li>
                    <li>{{ labels('admin_labels.download_save_sample_file', 'Download and save the sample file to reduce errors') }}</li>
                    <li>{{ labels('admin_labels.bulk_translation_csv_format', 'For adding bulk translation, the file should be in .csv format') }}</li>
                    <li>{{ labels('admin_labels.zip_contains_all_data_not_store_wise', 'When you download data for translation using the "Download Data for Translation" button, the ZIP file contains all the data, not store-wise.') }}</li>
                    <li><b>{{ labels('admin_labels.enter_valid_data_per_instructions', 'Make sure you enter valid data as per instructions before proceeding') }}</b></li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var input = document.getElementById('upload_file');
            var display = document.getElementById('upload_file_name');
            if (!input || !display) return;
            var placeholder = display.getAttribute('data-placeholder') || '';
            input.addEventListener('change', function () {
                display.textContent = (input.files && input.files.length > 0) ? input.files[0].name : placeholder;
            });
            var form = document.getElementById('translation_bulk_upload_form');
            if (form) {
                form.addEventListener('reset', function () {
                    setTimeout(function () { display.textContent = placeholder; }, 0);
                });
            }
        })();
    </script>
@endsection
