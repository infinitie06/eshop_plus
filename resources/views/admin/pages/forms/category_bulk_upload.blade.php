@extends('admin/layout')

@section('title')
    {{ labels('admin_labels.bulk_upload', 'Bulk Upload') }}
@endsection

@section('content')
    <x-admin.breadcrumb :title="labels('admin_labels.bulk_upload', 'Bulk Upload')" :subtitle="labels(
        'admin_labels.simplify_tasks_with_powerful_bulk_upload_capabilities',
        'Simplify Tasks with Powerful Bulk Upload Capabilities.',
    )" :breadcrumbs="[
        ['label' => labels('admin_labels.category', 'Category'), 'url' => route('categories.index')],
        ['label' => labels('admin_labels.bulk_upload', 'Bulk Upload')],
    ]" />

    <div class="row">
        <!-- Bulk Upload Form -->
        <div class="col-md-12 col-lg-6">
            <div class="card shadow-sm border-0">
                <form id="bulk_upload_form" method="POST" action="{{ route('categories.bulk_upload') }}"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="card-body">
                        <h5 class="card-title mb-3 fw-semibold text-dark">
                            {{ labels('admin_labels.bulk_upload', 'Bulk Upload') }}
                        </h5>

                        <!-- Type Selection -->
                        <div class="mb-3">
                            <label for="type" class="form-label fw-medium">
                                {{ labels('admin_labels.type', 'Type') }}
                                <small class="text-muted">{{ labels('admin_labels.upload_update_hint', '[upload/update]') }}</small>
                                <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" name="type" id="type" >
                                <option value="">{{ labels('admin_labels.select', 'Select') }}</option>
                                <option value="upload">{{ labels('admin_labels.upload', 'Upload') }}</option>
                                <option value="update">{{ labels('admin_labels.update', 'Update') }}</option>
                            </select>
                        </div>

                        <!-- File Upload -->
                        <div class="mb-3">
                            <label for="upload_file" class="form-label fw-medium">
                                {{ labels('admin_labels.file', 'File') }}
                                <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <label for="upload_file" class="btn btn-outline-secondary mb-0">
                                    {{ labels('admin_labels.choose_file', 'Choose file') }}
                                </label>
                                <span class="form-control" id="upload_file_name"
                                    data-placeholder="{{ labels('admin_labels.no_file_chosen', 'No file chosen') }}">
                                    {{ labels('admin_labels.no_file_chosen', 'No file chosen') }}
                                </span>
                                <input type="file" name="upload_file" id="upload_file" class="d-none" accept=".csv">
                            </div>
                            <small class="text-muted d-block mt-1">
                                {{ labels('admin_labels.only_csv_allowed', 'Only .csv files are allowed') }}
                            </small>
                        </div>

                        <!-- Buttons -->
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="reset" class="btn btn-light border reset_button">
                                {{ labels('admin_labels.reset', 'Reset') }}
                            </button>
                            <button type="submit" class="btn btn-primary submit_button">
                                {{ labels('admin_labels.submit', 'Submit') }}
                            </button>
                        </div>

                        <hr class="my-4">

                        <!-- Instruction Files -->
                        <label class="form-label fw-semibold">
                            {{ labels('admin_labels.instruction_files', 'Instruction Files') }}
                        </label>
                        <div class="row mt-2">
                            <div class="col-md-6 mb-2">
                                <a href="{{ asset('storage/categoey-bulk-upload.zip') }}"
                                    download="categoey-bulk-upload.zip"
                                    class="btn btn-outline-primary btn-sm w-100 instructions_files">
                                    <i class="fas fa-download me-2"></i>
                                    {{ labels('admin_labels.download_sample', 'Download Sample File') }}
                                </a>
                            </div>

                            <div class="col-md-6 mb-2">
                                <a href="{{ asset('storage/bulk_upload_category.pdf') }}" download="bulk_upload_category.pdf"
                                   class="btn btn-outline-primary btn-sm w-100 instructions_files">
                                    {{ labels('admin_labels.download', 'Download Guide') }}
                                    <i class="fas fa-download ms-2"></i>
                                </a>
                            </div>
                        </div>

                        <div id="upload_result" class="mt-4 text-center"></div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Instruction Card -->
        {{-- Uncomment if you want to display guidelines beside the form --}}
        {{--
        <div class="col-md-12 col-lg-6 mt-4 mt-lg-0">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-light fw-semibold">
                    {{ labels('admin_labels.instructions', 'Instructions') }}
                </div>
                <div class="card-body">
                    <ul class="list-unstyled ps-2 mb-0">
                        <li class="mb-2">1. Read and follow the instructions carefully before uploading data.</li>
                        <li class="mb-2">2. Always download and follow the provided sample structure.</li>
                        <li class="mb-2">3. Ensure the file format is strictly <b>.csv</b>.</li>
                        <li class="mb-2">4. Provide valid image paths from the Media section.</li>
                        <li><b>5. Invalid or missing data may cause the upload to fail.</b></li>
                    </ul>
                </div>
            </div>
        </div>
        --}}
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
            var form = document.getElementById('bulk_upload_form');
            if (form) {
                form.addEventListener('reset', function () {
                    setTimeout(function () { display.textContent = placeholder; }, 0);
                });
            }
        })();
    </script>
@endsection
