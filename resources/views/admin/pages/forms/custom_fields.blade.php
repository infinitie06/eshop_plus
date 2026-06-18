@extends('admin/layout')
@section('title')
    {{ labels('admin_labels.custom_field', 'Custom Fields') }}
@endsection
@section('content')
    <x-admin.breadcrumb :title="labels('admin_labels.custom_fields', 'Custom Fields')" :subtitle="labels(
        'admin_labels.effortless_custom_fields_management_for_an_organized_ecommerce_universe',
        'Effortless Custom Fields Management for an Organized E-commerce Universe',
    )" :breadcrumbs="[['label' => labels('admin_labels.custom_fields', 'Custom Fields')]]" />


    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.custom_fields.store') }}" class="submit_form">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">
                                {{ labels('admin_labels.field_name', 'Field Name') }}
                                <span class='text-asterisks text-sm'>*</span>
                                <i class="fa fa-info-circle text-secondary ms-1"
                                   data-bs-toggle="popover"
                                   data-bs-placement="right"
                                   data-bs-content="{{ labels('admin_labels.tooltip_unique_field_name', 'Enter a unique name for the custom field.') }}"></i>
                            </label>
                            <input type="text" name="name" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                {{ labels('admin_labels.field_type', 'Field Type') }}
                                <span class='text-asterisks text-sm'>*</span>
                                <i class="fa fa-info-circle text-secondary ms-1"
                                   data-bs-toggle="popover"
                                   data-bs-placement="right"
                                   data-bs-content="{{ labels('admin_labels.tooltip_select_input_type', 'Select the type of input for this field (e.g. text, number, dropdown, etc.).') }}"></i>
                            </label>
                            <select name="type" class="form-select custom_field_type">
                                <option value="">{{ labels('admin_labels.select', 'Select') }}</option>
                                <option value="text">{{ labels('admin_labels.text', 'Text') }}</option>
                                <option value="number">{{ labels('admin_labels.number', 'Number') }}</option>
                                <option value="textarea">{{ labels('admin_labels.textarea', 'Textarea') }}</option>
                                <option value="color">{{ labels('admin_labels.color', 'Color') }}</option>
                                <option value="date">{{ labels('admin_labels.date', 'Date') }}</option>
                                <option value="file">{{ labels('admin_labels.file', 'File') }}</option>
                                <option value="radio">{{ labels('admin_labels.radio', 'Radio') }}</option>
                                <option value="dropdown">{{ labels('admin_labels.dropdown', 'Dropdown') }}</option>
                                <option value="checkbox">{{ labels('admin_labels.checkbox', 'Checkbox') }}</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                {{ labels('admin_labels.field_length', 'Field Length') }}
                                <small>{{ labels('admin_labels.label_field_length_hint', '(used for input type text and textarea)') }}</small>
                                <i class="fa fa-info-circle text-secondary ms-1"
                                   data-bs-toggle="popover"
                                   data-bs-placement="right"
                                   data-bs-content="{{ labels('admin_labels.tooltip_max_chars_allowed', 'Set the maximum number of characters allowed.') }}"></i>
                            </label>
                            <input type="number" min="1" name="field_length" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                {{ labels('admin_labels.min', 'Min') }}
                                <small>{{ labels('admin_labels.label_min_max_hint', '(used for input type number only)') }}</small>
                                <i class="fa fa-info-circle text-secondary ms-1"
                                   data-bs-toggle="popover"
                                   data-bs-placement="right"
                                   data-bs-content="{{ labels('admin_labels.tooltip_min_value_number', 'Minimum value allowed for number fields.') }}"></i>
                            </label>
                            <input type="number" min="1" name="min" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                {{ labels('admin_labels.max', 'Max') }}
                                <small>{{ labels('admin_labels.label_min_max_hint', '(used for input type number only)') }}</small>
                                <i class="fa fa-info-circle text-secondary ms-1"
                                   data-bs-toggle="popover"
                                   data-bs-placement="right"
                                   data-bs-content="{{ labels('admin_labels.tooltip_max_value_number', 'Maximum value allowed for number fields.') }}"></i>
                            </label>
                            <input type="number" min="1" name="max" class="form-control">
                        </div>

                        <div class="mb-3 customOptionInput d-none">
                            <label class="form-label">
                                {{ labels('admin_labels.options', 'Options') }}
                                <i class="fa fa-info-circle text-secondary ms-1"
                                   data-bs-toggle="popover"
                                   data-bs-placement="right"
                                   data-bs-content="{{ labels('admin_labels.tooltip_options_csv_for_dropdown', 'Comma separated options for dropdown, radio, or checkbox fields.') }}"></i>
                            </label>
                            <input type="text" id="custom_options" name="options" class="form-control"
                                placeholder="{{ labels('admin_labels.placeholder_options_examples', 'e.g. Small, Medium, Large') }}">
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="required" value="1"
                                id="requiredCheck">
                            <label class="form-check-label" for="requiredCheck">
                                {{ labels('admin_labels.required', 'Required') }}
                                <i class="fa fa-info-circle text-secondary ms-1"
                                   data-bs-toggle="popover"
                                   data-bs-placement="right"
                                   data-bs-content="{{ labels('admin_labels.tooltip_field_required', 'Check if this field must be filled by the user.') }}"></i>
                            </label>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="active" value="1" id="activeCheck"
                                checked>
                            <label class="form-check-label" for="activeCheck">
                                {{ labels('admin_labels.active', 'Active') }}
                                <i class="fa fa-info-circle text-secondary ms-1"
                                   data-bs-toggle="popover"
                                   data-bs-placement="right"
                                   data-bs-content="{{ labels('admin_labels.tooltip_enable_disable_field', 'Enable or disable this custom field.') }}"></i>
                            </label>
                        </div>
                        <div class="d-flex justify-content-end mt-3">
                            <button type="reset" class="btn reset-btn mx-2">
                                {{ labels('admin_labels.reset', 'Reset') }}
                            </button>
                            <button type="submit" class="submit_button btn btn-primary">
                                {{ labels('admin_labels.add_custom_field', 'Add Custom Field') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <section class="overview-data">
                <div class="card content-area p-4 ">
                    <div class="row align-items-center d-flex heading mb-5">
                        <div class="col-md-12">
                            <div class="row">
                                <div class="col-sm-12">
                                    <h4>{{ labels('admin_labels.custom_fields', 'Custom Fields') }}</h4>
                                </div>


                                <div class="col-sm-12 d-flex justify-content-end mt-md-0 mt-sm-2">

                                    <div class="input-group me-3 search-input-grp">
                                        <span class="search-icon"><i class='bx bx-search-alt'></i></span>
                                        <input type="text" data-table="admin_custom_field_table"
                                            class="form-control searchInput" placeholder="{{ labels('admin_labels.search', 'Search') }}">
                                        <span class="input-group-text">{{ labels('admin_labels.search', 'Search') }}</span>
                                    </div>
                                    <a class="btn me-2" id="tableFilter" data-bs-toggle="offcanvas"
                                        data-bs-target="#columnFilterOffcanvas" data-table="admin_custom_field_table"
                                        StatusFilter='true'><i class='bx bx-filter-alt'></i></a>
                                    <a class="btn me-2" id="tableRefresh" data-table="admin_custom_field_table"><i
                                            class='bx bx-refresh'></i></a>
                                    <div class="dropdown">
                                        <button class="btn dropdown-toggle export-btn" type="button"
                                            id="exportOptionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class='bx bx-download'></i>
                                        </button>
                                        <ul class="dropdown-menu" aria-labelledby="exportOptionsDropdown">
                                            <li><button class="dropdown-item" type="button"
                                                    onclick="exportTableData('admin_custom_field_table','csv')">{{ labels('admin_labels.csv', 'CSV') }}</button>
                                            </li>
                                            <li><button class="dropdown-item" type="button"
                                                    onclick="exportTableData('admin_custom_field_table','json')">{{ labels('admin_labels.json', 'JSON') }}</button>
                                            </li>
                                            <li><button class="dropdown-item" type="button"
                                                    onclick="exportTableData('admin_custom_field_table','sql')">{{ labels('admin_labels.sql', 'SQL') }}</button>
                                            </li>
                                            <li><button class="dropdown-item" type="button"
                                                    onclick="exportTableData('admin_custom_field_table','excel')">{{ labels('admin_labels.excel', 'Excel') }}</button>
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="pt-0">
                                <div class="card-body p-0 list_view_html" id="">
                                    <div class="gaps-1-5x"></div>

                                    <div class="table-responsive">
                                        <table id='admin_custom_field_table' data-toggle="table"
                                            data-loading-template="loadingTemplate"
                                            data-url="{{ route('admin.custom_fields.list') }}"
                                            data-side-pagination="server" data-pagination="true"
                                            data-click-to-select="true" data-page-list="[5, 10, 20, 50, 100, 200]"
                                            data-search="false" data-show-columns="false" data-show-refresh="false"
                                            data-trim-on-search="false" data-sort-name="id" data-sort-order="desc"
                                            data-mobile-responsive="true" data-toolbar="" data-show-export="false"
                                            data-maintain-selected="true" data-export-types='["txt","excel","pdf","csv"]'
                                            data-export-options='{
                                                "fileName": "categories-list",
                                                "ignoreColumn": ["action"]
                                            }'
                                            data-query-params="category_query_params">
                                            <thead>
                                                <tr>
                                                    <th data-field="id" data-sortable="true">{{ labels('admin_labels.id', 'ID') }}</th>
                                                    <th data-field="name" data-sortable="true">{{ labels('admin_labels.name', 'Name') }}</th>
                                                    <th data-field="type" data-sortable="true">{{ labels('admin_labels.type', 'Type') }}</th>
                                                    <th data-field="field_length">{{ labels('admin_labels.field_length', 'Field Length') }}</th>
                                                    <th data-field="min">{{ labels('admin_labels.min', 'Min') }}</th>
                                                    <th data-field="max">{{ labels('admin_labels.max', 'Max') }}</th>
                                                    <th data-field="required">{{ labels('admin_labels.required', 'Required') }}</th>
                                                    <th data-field="active">{{ labels('admin_labels.active', 'Active') }}</th>
                                                    <th data-field="options">{{ labels('admin_labels.options', 'Options') }}</th>
                                                    <th data-field="operate">{{ labels('admin_labels.action', 'Action') }}</th>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
