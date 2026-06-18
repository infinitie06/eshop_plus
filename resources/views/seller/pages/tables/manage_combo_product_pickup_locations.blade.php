@extends('seller/layout')
@section('title')
    {{ labels('admin_labels.manage_combo_product_pickup_locations', 'Manage Combo Product Pickup Locations') }}
@endsection
@section('content')
    <x-seller.breadcrumb :title="labels('admin_labels.manage_combo_product_pickup_locations', 'Manage Combo Product Pickup Locations')" :subtitle="labels(
        'admin_labels.bulk_update_pickup_locations_for_combo_products',
        'Bulk Update Pickup Locations for Your Combo Products',
    )" :breadcrumbs="[['label' => labels('admin_labels.manage_combo_product_pickup_locations', 'Manage Combo Product Pickup Locations')]]" />
    <div class="col-md-12">
        <div class="row">
            <div class="col-md-12 col-xl-12 mt-xl-0 mt-md-2">
                <section class="overview-data">
                    <div class="card content-area p-4 ">
                        <div class="row align-items-center d-flex heading mb-5">
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-sm-12">
                                        <h4>{{ labels('admin_labels.manage_combo_product_pickup_locations', 'Manage Combo Product Pickup Locations') }}
                                        </h4>
                                    </div>

                                    <div class="col-sm-12 d-flex justify-content-end mt-md-0 mt-sm-2">
                                        <div class="input-group me-2 search-input-grp ">
                                            <span class="search-icon"><i class='bx bx-search-alt'></i></span>
                                            <input type="text" data-table="seller_pickup_location_combo_products_table"
                                                class="form-control searchInput" placeholder="{{ labels('admin_labels.search', 'Search') }}">
                                            <span
                                                class="input-group-text">{{ labels('admin_labels.search', 'Search') }}</span>
                                        </div>
                                        <a class="btn me-2" id="tableFilter" data-bs-toggle="offcanvas"
                                            data-bs-target="#columnFilterOffcanvas" data-table="seller_pickup_location_combo_products_table"
                                            StatusFilter='true'><i class='bx bx-filter-alt'></i></a>
                                        <a class="btn me-2" id="tableRefresh" data-table="seller_pickup_location_combo_products_table"><i
                                                class='bx bx-refresh'></i></a>
                                        <div class="dropdown">
                                            <a class="btn dropdown-toggle export-btn" type="button"
                                                id="exportOptionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class='bx bx-download'></i>
                                            </a>
                                            <ul class="dropdown-menu" aria-labelledby="exportOptionsDropdown">
                                                <li><button class="dropdown-item" type="button"
                                                        onclick="exportTableData('seller_pickup_location_combo_products_table','csv')">CSV</button>
                                                </li>
                                                <li><button class="dropdown-item" type="button"
                                                        onclick="exportTableData('seller_pickup_location_combo_products_table','json')">JSON</button>
                                                </li>
                                                <li><button class="dropdown-item" type="button"
                                                        onclick="exportTableData('seller_pickup_location_combo_products_table','sql')">SQL</button>
                                                </li>
                                                <li><button class="dropdown-item" type="button"
                                                        onclick="exportTableData('seller_pickup_location_combo_products_table','excel')">Excel</button>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <button type="button"
                                    class="btn btn-outline-primary btn-sm bulk_update_pickup_location"
                                    data-table-id="seller_pickup_location_combo_products_table"
                                    data-url="{{ route('seller.combo.pickup_locations.bulk.update') }}">{{ labels('admin_labels.bulk_update', 'Bulk Update') }}</button>
                            </div>
                            <div class="col-md-12">
                                <div class="pt-0">
                                    <div class="table-responsive">
                                        <table class='table' id="seller_pickup_location_combo_products_table" data-toggle="table"
                                            data-loading-template="loadingTemplate"
                                            data-url="{{ route('seller.combo_product.pickup_locations.list') }}"
                                            data-click-to-select="true" data-side-pagination="server" data-pagination="true"
                                            data-page-list="[5, 10, 20, 50, 100, 200]" data-search="false"
                                            data-show-columns="false" data-show-refresh="false" data-trim-on-search="false"
                                            data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true"
                                            data-toolbar="" data-show-export="false" data-maintain-selected="true"
                                            data-export-types='["txt","excel"]' data-query-params="brand_query_params">
                                            <thead>
                                                <tr>
                                                    <th data-checkbox="true" data-field="delete-checkbox">
                                                        <input name="select_all" type="checkbox">
                                                    </th>
                                                    <th data-field="id" data-sortable="true" data-visible="true">
                                                        {{ labels('admin_labels.id', 'ID') }}
                                                    <th class="d-flex justify-content-center" data-field="image"
                                                        data-sortable="false">
                                                        {{ labels('admin_labels.image', 'Image') }}
                                                    </th>
                                                    <th data-field="name" data-disabled="1" data-sortable="false">
                                                        {{ labels('admin_labels.name', 'Name') }}
                                                    </th>
                                                    <th data-field="pickup_location_name" data-sortable="false">
                                                        {{ labels('admin_labels.pickup_location', 'Pickup Location') }}
                                                    </th>
                                                    <th data-field="operate" data-sortable="false">
                                                        {{ labels('admin_labels.action', 'Action') }}
                                                    </th>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
    <!-- Pickup Location Modal -->
    <div class="modal fade" id="pickupLocationModal" tabindex="-1" aria-labelledby="pickupLocationModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pickupLocationModalLabel">{{ labels('admin_labels.update_pickup_location', 'Update Pickup Location') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="pickupLocationForm">
                    <div class="modal-body">
                        <input type="hidden" id="product_id" name="product_id">

                        <div class="mb-3">
                            <label for="pickup_location_id" class="form-label">{{ labels('admin_labels.pickup_location', 'Pickup Location') }}</label>
                            <select class="form-select" name="pickup_location_id" id="pickup_location_id" required>
                                <option value="">{{ labels('admin_labels.select_pickup_location', 'Select Pickup Location') }}</option>
                            </select>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ labels('admin_labels.close', 'Close') }}</button>
                        <button type="submit" class="btn btn-primary">{{ labels('admin_labels.save_changes', 'Save Changes') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
