@extends('admin/layout')
@section('title')
    {{ labels('admin_labels.affiliate_users', 'Affiliate Users') }}
@endsection

@section('content')
    <x-admin.breadcrumb
        :title="labels('admin_labels.affiliate_users', 'Affiliate Users')"
        :subtitle="labels('admin_labels.efficiently_organize_and_control_affiliate_users', 'Efficiently Organize and Control Affiliate Users')"
        :breadcrumbs="[['label' => labels('admin_labels.affiliate_users', 'Affiliate Users')]]"
    />

    <section class="overview-data">
        <div class="card content-area p-4">

            {{-- Header & Toolbar --}}
            <div class="row align-items-center heading mb-4">
                <div class="col-md-12">
                    <div class="row g-3">
                        <div class="col-12 col-xl-4">
                            <h4>{{ labels('admin_labels.manage_user', 'Manage User') }}</h4>
                        </div>

                        <div class="col-12 col-xl-8 d-flex flex-wrap justify-content-xl-end gap-2">
                            <a href="{{ route('admin.affiliate.add_user') }}" class="btn btn-dark">
                                <i class='bx bx-plus-circle me-1'></i>
                                {{ labels('admin_labels.add_user', 'Add User') }}
                            </a>

                            <a href="#" class="settle_affiliate_commission text-white btn btn-primary">
                                <i class='bx bx-plus-circle me-1'></i>
                                {{ labels('admin_labels.settle_commission', 'Settle Commission') }}
                            </a>

                            <div class="input-group search-input-grp" style="max-width: 240px;">
                                <span class="search-icon"><i class='bx bx-search-alt'></i></span>
                                <input type="text" data-table="admin_affiliate_user_table"
                                    class="form-control searchInput" placeholder="{{ labels('admin_labels.search', 'Search') }}">
                                <span class="input-group-text">{{ labels('admin_labels.search', 'Search') }}</span>
                            </div>

                            <a class="btn" id="tableFilter" data-bs-toggle="offcanvas"
                                data-bs-target="#columnFilterOffcanvas" data-table="admin_affiliate_user_table"
                                dateFilter="false" orderStatusFilter="false" paymentMethodFilter="false"
                                orderTypeFilter="false">
                                <i class='bx bx-filter-alt'></i>
                            </a>

                            <a class="btn" id="tableRefresh" data-table="admin_affiliate_user_table">
                                <i class='bx bx-refresh'></i>
                            </a>

                            <div class="dropdown">
                                <a class="btn dropdown-toggle export-btn" type="button" id="exportOptionsDropdown"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class='bx bx-download'></i>
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="exportOptionsDropdown">
                                    <li><button class="dropdown-item" type="button"
                                            onclick="exportTableData('admin_affiliate_user_table','csv')">{{ labels('admin_labels.csv', 'CSV') }}</button>
                                    </li>
                                    <li><button class="dropdown-item" type="button"
                                            onclick="exportTableData('admin_affiliate_user_table','json')">{{ labels('admin_labels.json', 'JSON') }}</button>
                                    </li>
                                    <li><button class="dropdown-item" type="button"
                                            onclick="exportTableData('admin_affiliate_user_table','sql')">{{ labels('admin_labels.sql', 'SQL') }}</button>
                                    </li>
                                    <li><button class="dropdown-item" type="button"
                                            onclick="exportTableData('admin_affiliate_user_table','excel')">{{ labels('admin_labels.excel', 'Excel') }}</button>
                                    </li>
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
                        <table class="table table-striped"
                            id="admin_affiliate_user_table"
                            data-toggle="table"
                            data-loading-template="loadingTemplate"
                            data-url="{{ route('admin.affiliate_users.list') }}"
                            data-click-to-select="true"
                            data-side-pagination="server"
                            data-pagination="true"
                            data-page-list="[5, 10, 20, 50, 100, 200]"
                            data-search="false"
                            data-show-columns="false"
                            data-show-refresh="false"
                            data-trim-on-search="false"
                            data-sort-name="id"
                            data-sort-order="DESC"
                            data-mobile-responsive="true"
                            data-card-view="false"
                            data-toolbar=""
                            data-show-export="false"
                            data-maintain-selected="true"
                            data-export-types='["txt","excel"]'
                            data-query-params="queryParams">

                            <thead>
                                <th data-field="id" data-sortable="true">{{ labels('admin_labels.id', 'ID') }}</th>
                                <th data-field="affiliate_code">{{ labels('admin_labels.affiliate_code', 'Affiliate Code') }}</th>
                                <th data-field="username">{{ labels('admin_labels.username', 'Username') }}</th>
                                <th data-field="email" class="d-none d-md-table-cell">{{ labels('admin_labels.email', 'Email') }}</th>
                                <th data-field="mobile" class="d-none d-md-table-cell">{{ labels('admin_labels.mobile', 'Mobile') }}</th>
                                <th data-field="website_url" class="d-none d-lg-table-cell">{{ labels('admin_labels.website', 'Website') }}</th>
                                <th data-field="application_url" class="d-none d-lg-table-cell">{{ labels('admin_labels.app', 'App') }}</th>
                                <th data-field="status">{{ labels('admin_labels.status', 'Status') }}</th>
                                <th data-field="operate">{{ labels('admin_labels.operate', 'Operate') }}</th>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </section>

    {{-- Optional: Small CSS tweak for better spacing on mobile --}}
    <style>
        @media (max-width: 576px) {
            .search-input-grp {
                flex: 1 1 100%;
            }
        }
    </style>
@endsection
