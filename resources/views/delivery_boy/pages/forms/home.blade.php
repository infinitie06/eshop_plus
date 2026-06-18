@extends('delivery_boy/layout')
@section('title')
    {{ labels('admin_labels.home', 'Dashboard') }}
@endsection
@php
    use App\Services\OrderService;
@endphp
@section('content')
    <section class="main-content">
        <x-delivery_boy.breadcrumb :title="labels('admin_labels.dashboard', 'Dashboard')" :subtitle="labels('admin_labels.all_information_about_your_store', 'All Information About your Store')" :breadcrumbs="[]" />

        <section class="dashboard overview-data">

            <div class="container-fluid">
                <!-- ============================================ Info cards ======================================== -->

                <div class="row">
                    <div class="col-xxl-12 p-0">
                        <div class="row cols-5 d-flex">
                            <div class="col-md-6 col-xl-4">
                                <div class="info-box align-items-center">
                                    <div class="success-icon">
                                        <img src="{{ asset('storage/dashboard_icon/total_order.svg') }}"
                                            class="dashboard-icon" alt="">
                                    </div>
                                    <div class="content">
                                        <p class="body-default">
                                            {{ labels('admin_labels.total_orders', 'Total Orders') }}
                                        </p>
                                        <h5>{{ app(OrderService::class)->ordersCount('', '', '', '', $deliveryBoyId) }}</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-xl-4">
                                <div class="info-box align-items-center">
                                    <div class="danger-icon">
                                        <img src="{{ asset('storage/dashboard_icon/total_earning.svg') }}"
                                            class="dashboard-icon" alt="">
                                    </div>
                                    <div class="content">
                                        <p class="body-default">
                                            {{ labels('admin_labels.total_bonus', 'Total Bonus') }}
                                        </p>
                                        <h5>{{ $currency . number_format($bonus, 2) }}</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-xl-4">
                                <div class="info-box align-items-center">
                                    <div class="info-icon">
                                        <img src="{{ asset('storage/dashboard_icon/total_earning.svg') }}"
                                            class="dashboard-icon" alt="">
                                    </div>
                                    <div class="content">
                                        <p class="body-default">
                                            {{ labels('admin_labels.total_balance', 'Total Balance') }}
                                        </p>
                                        <h5>{{ $currency . number_format($balance, 2) }}</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-xl-4">
                                <div class="info-box align-items-center">
                                    <div class="{{ $is_available == 1 ? 'success-icon' : 'danger-icon' }}">
                                        <i class="bx {{ $is_available == 1 ? 'bx-check-circle' : 'bx-x-circle' }} fs-1"></i>
                                    </div>
                                    <div class="content">
                                        <p class="body-default">
                                            {{ labels('admin_labels.availability_status', 'Availability Status') }}
                                        </p>
                                        <div class="d-flex align-items-center gap-2">
                                            <h5 class="mb-0">
                                                <span class="badge bg-{{ $is_available == 1 ? 'success' : 'danger' }}">
                                                    {{ $is_available == 1 ? labels('admin_labels.available', 'Available') : labels('admin_labels.not_available', 'Not Available') }}
                                                </span>
                                            </h5>
                                            <button type="button" class="btn btn-sm btn-outline-primary toggle-availability-btn"
                                                data-available="{{ $is_available == 1 ? 0 : 1 }}">
                                                {{ $is_available == 1 ? labels('admin_labels.mark_unavailable', 'Mark Unavailable') : labels('admin_labels.mark_available', 'Mark Available') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>



                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 mt-4 p-0">
                        <section class="overview-data">
                            <div class="card content-area p-4 ">
                                <div class="row align-items-center d-flex heading mb-5">
                                    <div class="col-md-6">
                                        <h4> {{ labels('admin_labels.manage_orders', 'Manage Orders') }}
                                        </h4>
                                    </div>

                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="pt-0">
                                            <div class="table-responsive">
                                                <table class='table' id="delivery_boy_order_table"
                                                    data-loading-template="loadingTemplate" data-toggle="table"
                                                    data-url="{{ route('delivery_boy.view_parcels') }}"
                                                    data-click-to-select="true" data-side-pagination="server"
                                                    data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"
                                                    data-search="false" data-show-columns="false" data-show-refresh="false"
                                                    data-trim-on-search="false" data-sort-name="id" data-sort-order="desc"
                                                    data-mobile-responsive="true" data-toolbar="" data-show-export="false"
                                                    data-maintain-selected="true" data-export-types='["txt","excel"]'
                                                    data-query-params="queryParams">
                                                    <thead>
                                                        <tr>
                                                            <th data-field="order_id" data-sortable='true'>
                                                                {{ labels('admin_labels.order_id', 'Order ID') }}
                                                            </th>
                                                            <th data-field="user_id" data-sortable='true'
                                                                data-visible="false">
                                                                {{ labels('admin_labels.user_id', 'User ID') }}
                                                            </th>
                                                            <th data-field="quantity" data-sortable='false'
                                                                data-visible="false">
                                                                {{ labels('admin_labels.quantity', 'Quantity') }}
                                                            </th>
                                                            <th data-field="username" data-sortable='false'>
                                                                {{ labels('admin_labels.user_name', 'User Name') }}
                                                            </th>
                                                            <th data-field="product_name" data-sortable='false'>
                                                                {{ labels('admin_labels.product_name', 'Product Name') }}
                                                            </th>
                                                            <th data-field="mobile" data-sortable='false'
                                                                data-visible='false'>
                                                                {{ labels('admin_labels.mobile', 'Mobile') }}
                                                            </th>
                                                            <th data-field="payment_method" data-sortable='false'
                                                                data-visible="true">
                                                                {{ labels('admin_labels.payment_method', 'Payment Method') }}
                                                            </th>
                                                            <th data-field="status" data-sortable='false'
                                                                data-visible='true'>
                                                                {{ labels('admin_labels.active_status', 'Active Status') }}
                                                            </th>

                                                            <th data-field="created_at" data-sortable='true'>
                                                                {{ labels('admin_labels.created_at', 'Order Date') }}
                                                            </th>

                                                            <th data-field="operate" data-sortable='false'>
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
        </section>

    </section>
@endsection
