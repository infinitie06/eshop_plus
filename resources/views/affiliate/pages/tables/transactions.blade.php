@extends('affiliate/layout')
@section('title')
    {{ labels('admin_labels.transactions', 'Transactions') }}
@endsection

@section('content')
    <section class="main-content">
        <div class="row">
            <x-affiliate.breadcrumb :title="labels('admin_labels.withdrawal_requests', 'Transactions')" :subtitle="labels(
                'admin_labels.effortlessly_process_and_track_withdrawel_requests',
                'Effortlessly Process and Track Transactions',
            )" :breadcrumbs="[
                ['label' => labels('admin_labels.withdrawal_requests', 'Transactions')],
            ]" />
        </div>
        <section class="overview-data">
            <div class="card content-area p-4 ">
                <div class="row align-items-center d-flex heading mb-5">
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-12 col-xxl-6">
                                <h4>{{ labels('admin_labels.manage_withdrawal_requests', 'Manage Transactions') }}
                                </h4>
                            </div>
                            <div class="col-md-12 col-xxl-6 d-flex justify-content-end ">
                                <div class="input-group me-3 search-input-grp ">
                                    <span class="search-icon"><i class='bx bx-search-alt'></i></span>
                                    <input type="text" data-table="affiliate_transactions_table"
                                        class="form-control searchInput" placeholder="{{ labels('admin_labels.search', 'Search') }}">
                                    <span class="input-group-text">{{ labels('admin_labels.search', 'Search') }}</span>
                                </div>
                                <a class="btn me-2" id="tableFilter" data-bs-toggle="offcanvas"
                                    data-bs-target="#columnFilterOffcanvas" data-table="affiliate_transactions_table"
                                    dateFilter='true' paymentRequestStatusFilter='true'><i class='bx bx-filter-alt'></i></a>
                                <a class="btn me-2" id="tableRefresh" data-table="affiliate_transactions_table"><i
                                        class='bx bx-refresh'></i></a>
                                <div class="dropdown">
                                    <a class="btn dropdown-toggle export-btn" type="button" id="exportOptionsDropdown"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class='bx bx-download'></i>
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="exportOptionsDropdown">
                                        <li><button class="dropdown-item" type="button"
                                                onclick="exportTableData('affiliate_transactions_table','csv')">CSV</button>
                                        </li>
                                        <li><button class="dropdown-item" type="button"
                                                onclick="exportTableData('affiliate_transactions_table','json')">JSON</button>
                                        </li>
                                        <li><button class="dropdown-item" type="button"
                                                onclick="exportTableData('affiliate_transactions_table','sql')">SQL</button>
                                        </li>
                                        <li><button class="dropdown-item" type="button"
                                                onclick="exportTableData('affiliate_transactions_table','excel')">Excel</button>
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
                            <div class="table-responsive">
                                <table class='table-striped' id='affiliate_transactions_table' data-toggle="table"
                                    data-loading-template="loadingTemplate"
                                    data-url="{{ route('affiliate.get_transactions') }}"
                                    data-click-to-select="true" data-side-pagination="server" data-pagination="true"
                                    data-page-list="[5, 10, 20, 50, 100, 200]"   data-search="false" data-show-columns="false"
                                    data-show-refresh="false" data-trim-on-search="true" data-sort-name="id"
                                    data-sort-order="desc" data-mobile-responsive="true" data-toolbar=""
                                    data-show-export="false" data-maintain-selected="true"
                                    data-export-types='["txt","excel","csv"]'
                                    data-export-options='{
                                        "fileName": "products-list",
                                        "ignoreColumn": ["state"]
                                        }'
                                    data-query-params="queryParams">
                                    <thead>
                                        <tr>
                                            <th data-field="id" data-sortable="true">
                                                {{ labels('admin_labels.id', 'ID') }}
                                            </th>
                                            <th data-field="type" data-sortable="false">
                                                {{ labels('admin_labels.type', 'Type') }}
                                            </th>
                                            <th data-field="transaction_type" data-sortable="false">
                                                {{ labels('admin_labels.transaction_type', 'Transaction Type') }}
                                            </th>
                                            <th data-field="amount" data-sortable="false">
                                                {{ labels('admin_labels.amount', 'Amount') }}
                                            </th>
                                            <th data-field="message" data-sortable="false">
                                                {{ labels('admin_labels.message', 'Message') }}
                                            </th>
                                            <th data-field="date_created" data-sortable="false">
                                                {{ labels('admin_labels.date', 'Date') }}
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
    </section>
@endsection
