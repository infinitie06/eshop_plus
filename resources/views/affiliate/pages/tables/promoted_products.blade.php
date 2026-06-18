@extends('affiliate/layout')

@section('title')
    {{ labels('admin_labels.promoted_products', 'Promoted Products') }}
@endsection

@section('content')
    <x-affiliate.breadcrumb
        :title="labels('admin_labels.manage_products', 'Manage Promoted Products')"
        :subtitle="labels('admin_labels.track_and_manage_promoted_products', 'Track And Manage Promoted Products')"
        :breadcrumbs="[['label' => labels('admin_labels.manage_promoted_products', 'Manage Promoted Products')]]"
    />

    <section class="overview-data">
        <div class="card content-area p-4">

            {{-- Header & Toolbar --}}
            <div class="row align-items-center heading mb-4">
                <div class="col-md-12">
                    <div class="row g-3">
                        <div class="col-12 col-xl-4">
                            <h4>{{ labels('admin_labels.products', 'Products') }}</h4>
                        </div>

                        <div class="col-12 col-xl-8 d-flex flex-wrap justify-content-xl-end gap-2">
                            {{-- Search --}}
                            <div class="input-group search-input-grp" style="max-width: 240px;">
                                <span class="search-icon"><i class='bx bx-search-alt'></i></span>
                                <input type="text" data-table="affiliate_promoted_product_table"
                                    class="form-control searchInput" placeholder="{{ labels('admin_labels.search', 'Search') }}">
                                <span class="input-group-text">{{ labels('admin_labels.search', 'Search') }}</span>
                            </div>

                            {{-- Filter --}}
                           <a class="btn" id="tableFilter" data-bs-toggle="offcanvas"
                                data-bs-target="#columnFilterOffcanvas" data-table="affiliate_promoted_product_table"
                                dateFilter="false" orderStatusFilter="false" paymentMethodFilter="false"
                                orderTypeFilter="false">
                                <i class='bx bx-filter-alt'></i>
                            </a>

                            {{-- Refresh --}}
                            <a class="btn" id="tableRefresh" data-table="affiliate_promoted_product_table">
                                <i class='bx bx-refresh'></i>
                            </a>

                            {{-- Export --}}
                            <div class="dropdown">
                                <a class="btn dropdown-toggle export-btn" type="button" id="exportOptionsDropdown"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class='bx bx-download'></i>
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="exportOptionsDropdown">
                                    <li><button class="dropdown-item" type="button"
                                            onclick="exportTableData('affiliate_promoted_product_table','csv')">CSV</button></li>
                                    <li><button class="dropdown-item" type="button"
                                            onclick="exportTableData('affiliate_promoted_product_table','json')">JSON</button></li>
                                    <li><button class="dropdown-item" type="button"
                                            onclick="exportTableData('affiliate_promoted_product_table','sql')">SQL</button></li>
                                    <li><button class="dropdown-item" type="button"
                                            onclick="exportTableData('affiliate_promoted_product_table','excel')">Excel</button></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="row">
                <div class="col-12">
                    <div class="table-responsive table-responsive-sm">
                        <table id="affiliate_promoted_product_table" data-toggle="table"
                            data-loading-template="loadingTemplate"
                            data-url="{{ route('affiliate.promoted_products.list') }}"
                            data-click-to-select="true"
                            data-side-pagination="server"
                            data-pagination="true"
                            data-page-list="[5, 10, 20, 50, 100, 200]"
                            data-search="false"
                            data-show-columns="false"
                            data-show-refresh="false"
                            data-trim-on-search="false"
                            data-sort-name="id"
                            data-sort-order="desc"
                            data-mobile-responsive="true"
                            data-card-view="false"
                            data-toolbar=""
                            data-show-export="false"
                            data-maintain-selected="true"
                            data-export-types='["txt","excel"]'
                            data-query-params="promotedProductsQueryParams">
                            <thead>
                                <tr>
                                    <th data-field="id" data-sortable="true">{{ labels('admin_labels.id', 'ID') }}</th>
                                    <th data-field="product_id" data-sortable="true" class="d-none d-md-table-cell">
                                        {{ labels('admin_labels.product_id', 'Product ID') }}
                                    </th>
                                    <th data-field="image" class="text-center" data-sortable="false">
                                        {{ labels('admin_labels.image', 'Image') }}
                                    </th>
                                    <th data-field="product_name">{{ labels('admin_labels.product_name', 'Name') }}</th>
                                    <th data-field="category_commission" class="d-none d-md-table-cell">
                                        {{ labels('admin_labels.category_commission', 'Category Commission') }}
                                    </th>
                                    <th data-field="category_name" class="d-none d-md-table-cell">
                                        {{ labels('admin_labels.category', 'Category Name') }}
                                    </th>
                                    <th data-field="usage_count">{{ labels('admin_labels.usage_count', 'Usage Count') }}</th>
                                    <th data-field="commission_earned" class="d-none d-lg-table-cell">
                                        {{ labels('admin_labels.commission_earned', 'Commission Earned') }}
                                    </th>
                                    <th data-field="total_order_value" class="d-none d-md-table-cell">
                                        {{ labels('admin_labels.total_order_value', 'Total Order Value') }}
                                    </th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Offcanvas Filter --}}


        </div>
    </section>

    {{-- Optional: Small CSS tweak for better spacing on mobile --}}
    <style>
        @media (max-width: 576px) {
            .search-input-grp { flex: 1 1 100%; }
        }
        .table td img { max-width: 50px; height: auto; }
    </style>

    {{-- Query Params Script --}}
    @push('scripts')
        <script>
            function promotedProductsQueryParams(params) {
                params.date = $('#filterDate').val();
                params.category_id = $('#filterCategory').val();
                params.product_status = $('#filterProductStatus').val();
                params.seller_id = $('#filterSeller').val();
                return params;
            }
        </script>
    @endpush
@endsection
