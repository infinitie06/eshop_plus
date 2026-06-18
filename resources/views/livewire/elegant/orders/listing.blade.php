@php
    $bread_crumb['page_main_bread_crumb'] = labels('front_messages.orders', 'Orders');
    use App\Models\Currency;
    use App\Models\Product;
    use App\Models\ComboProduct;
    use App\Services\TranslationService;
    use App\Services\MediaService;
    use App\Services\CurrencyService;
@endphp

<div id="page-content">
    <x-utility.breadcrumbs.breadcrumbTwo :$bread_crumb />
    <div class="container-fluid">
        <div class="row">
            <x-utility.my_account_slider.account_slider :$user_info />
            <div class="col-12 col-sm-12 col-md-12 col-lg-9">
                <div class="dashboard-content h-100">
                    <div class="h-100" id="orders">
                        <div class="orders-card mt-0 h-100">
                            <div class="top-sec d-flex-justify-center justify-content-between mb-4">
                                <h2 class="mb-0">{{ labels('front_messages.my_orders', 'My Orders') }}</h2>
                            </div>
                            <div class="d-flex gap-3 mb-3">
                                <button wire:click="filterOrders('')"
                                    class="badge rounded-pill bg-info custom-badge {{ $orderStatus == '' ? 'border-2 border-dark' : '' }}">{{ labels('front_messages.all', 'All') }}</button>
                                <button wire:click="filterOrders('awaiting')"
                                    class="badge rounded-pill bg-secondary custom-badge {{ $orderStatus == 'awaiting' ? 'border-2 border-dark' : '' }}">{{ labels('front_messages.awaiting', 'Awaiting') }}</button>
                                <button wire:click.prevent="filterOrders('received')"
                                    class="badge rounded-pill bg-primary custom-badge {{ $orderStatus == 'received' ? 'border-2 border-dark' : '' }}">{{ labels('front_messages.received', 'Received') }}</button>
                                <button wire:click.prevent="filterOrders('processed')"
                                    class="badge rounded-pill bg-info custom-badge {{ $orderStatus == 'processed' ? 'border-2 border-dark' : '' }}">{{ labels('front_messages.processing', 'Processing') }}</button>
                                <button wire:click.prevent="filterOrders('shipped')"
                                    class="badge rounded-pill bg-warning custom-badge {{ $orderStatus == 'shipped' ? 'border-2 border-dark' : '' }}">{{ labels('front_messages.shipped', 'Shipped') }}</button>
                                <button wire:click.prevent="filterOrders('delivered')"
                                    class="badge rounded-pill bg-success custom-badge {{ $orderStatus == 'delivered' ? 'border-2 border-dark' : '' }}">{{ labels('front_messages.delivered', 'Delivered') }}</button>
                                <button wire:click.prevent="filterOrders('cancelled')"
                                    class="badge rounded-pill bg-danger custom-badge {{ $orderStatus == 'cancelled' ? 'border-2 border-dark' : '' }}">{{ labels('front_messages.canceled', 'Canceled') }}</button>
                            </div>
                            @if (count($user_orders['order_data']) >= 1)
                                <div class="table-bottom-brd table-responsive">

                                    <table class="table align-middle text-center order-table">
                                        <thead>
                                            <tr class="table-head text-nowrap">
                                                <th scope="col">{{ labels('front_messages.image', 'Image') }}</th>
                                                <th scope="col">{{ labels('front_messages.order_id', 'Order Id') }}
                                                </th>
                                                <th scope="col">
                                                    {{ labels('front_messages.product_details', 'Product Details') }}
                                                </th>
                                                <th scope="col">{{ labels('front_messages.price', 'Price') }}</th>
                                                <th scope="col">{{ labels('front_messages.status', 'Status') }}</th>
                                                <th scope="col">{{ labels('front_messages.view', 'View') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($user_orders['order_data'] as $user_order)
                                                @php
                                                    $user_order = json_decode(json_encode($user_order), true);
                                                    $order_image = app(MediaService::class)->dynamic_image(
                                                        $user_order['order_items'][0]['image_sm'],
                                                        50,
                                                    );
                                                @endphp
                                                <tr>
                                                    <td><img class="blur-up lazyload" data-src="{{ $order_image }}"
                                                            src="{{ $order_image }}" width="50" alt="product"
                                                            title="product" />
                                                    </td>
                                                    <td><span class="id">#{{ $user_order['id'] }}</span>
                                                    </td>
                                                    {{-- @dd($user_order['order_items']); --}}
                                                    @php
                                                        $product_name = '';
                                                        $language_code = app(
                                                            TranslationService::class,
                                                        )->getLanguageCode();
                                                        if (
                                                            $user_order['order_items'][0]['order_type'] ==
                                                            'regular_order'
                                                        ) {
                                                            $product_name = app(
                                                                TranslationService::class,
                                                            )->getDynamicTranslation(
                                                                Product::class,
                                                                'name',
                                                                $user_order['order_items'][0]['product_id'],
                                                                $language_code,
                                                            );
                                                        } else {
                                                            $product_name = app(
                                                                TranslationService::class,
                                                            )->getDynamicTranslation(
                                                                ComboProduct::class,
                                                                'title',
                                                                $user_order['order_items'][0]['product_id'],
                                                                $language_code,
                                                            );
                                                        }

                                                    @endphp
                                                    <td><span
                                                            class="name">{{ $product_name . (count($user_order['order_items']) > 1 ? ' & ' . count($user_order['order_items']) - 1 . ' more items' : '') }}</span>
                                                    </td>
                                                    @php
                                                        $currency_details = app(
                                                            CurrencyService::class,
                                                        )->getCurrencyCodeSettings(
                                                            $user_order['order_payment_currency_code'],
                                                            false,
                                                        );
                                                        
                                                        // Check if Shiprocket is the shipment method
                                                        $is_shiprocket = false;
                                                        $tracking_data = \App\Models\OrderTracking::where('order_id', $user_order['id'])->first();
                                                        if ($tracking_data) {
                                                            $shiprocket_order_id = $tracking_data->shiprocket_order_id ?? '';
                                                            if (!empty($shiprocket_order_id)) {
                                                                $is_shiprocket = true;
                                                            } else {
                                                                $courier_agency = $tracking_data->courier_agency ?? '';
                                                                if (stripos($courier_agency, 'shiprocket') !== false) {
                                                                    $is_shiprocket = true;
                                                                }
                                                            }
                                                        }
                                                        
                                                        // Calculate amount with or without currency conversion
                                                        if ($is_shiprocket) {
                                                            // Shiprocket - no currency conversion
                                                            $amount = isset($currency_details) && !empty($currency_details)
                                                                ? (float) $user_order['final_total']
                                                                : '';
                                                        } else {
                                                            // Standard shipping - apply currency conversion
                                                            $amount = isset($currency_details) && !empty($currency_details)
                                                                ? (float) $user_order['final_total'] *
                                                                    number_format(
                                                                        (float) $currency_details[0]['exchange_rate'],
                                                                        2,
                                                                    )
                                                                : '';
                                                        }
                                                    @endphp
                                                    <td>
                                                        {{-- @dd($user_order); --}}
                                                        @php

                                                            $currency_symbol = fetchDetails(
                                                                Currency::class,
                                                                ['code' => $user_order['order_payment_currency_code']],
                                                                'symbol',
                                                            );
                                                            // dd($currency_symbol);
                                                            $currency_symbol =
                                                                isset($currency_symbol) && !empty($currency_symbol)
                                                                    ? $currency_symbol[0]->symbol
                                                                    : '$';
                                                        @endphp
                                                        <span
                                                            class="price fw-500">{{ $currency_symbol . number_format($amount, 2) }}</span>

                                                    </td>
                                                    <td><span
                                                            class="badge rounded-pill custom-badge">{{ str_replace('_', ' ', $user_order['order_items'][0]['active_status']) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="{{ customUrl('orders/' . $user_order['id']) }}"
                                                            wire:navigate class="view">
                                                            <ion-icon name="eye-outline"
                                                                class="fs-5 hydrated"></ion-icon>
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="d-flex flex-column justify-content-center align-items-center py-5">
                                    <div class="opacity-50"><ion-icon name="cart-outline"
                                            class="address-location-icon text-muted"></ion-icon></div>
                                    <div class="fs-6 fw-500">
                                        {{ labels('front_messages.no_orders_have_been_placed', 'No orders have been placed at this Store.') }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                   <div class="d-flex justify-content-between align-items-center">
    {{-- Result summary --}}
    <div class="text-muted small">
        {{ labels('panel_labels.showing', 'Showing') }} {{ ($currentPage - 1) * $perPage + 1 }}
        {{ labels('panel_labels.pagination_to', 'to') }} {{ min($currentPage * $perPage, $user_orders['total']) }}
        {{ labels('panel_labels.pagination_of', 'of') }} {{ $user_orders['total'] }} {{ labels('panel_labels.pagination_results', 'results') }}
    </div>

    {{-- Pagination --}}
    <ul class="pagination mb-0">

        {{-- Previous --}}
        <li class="page-item {{ $currentPage == 1 ? 'disabled' : '' }}" aria-disabled="{{ $currentPage == 1 ? 'true' : 'false' }}" aria-label="« Previous">
            <button
                class="page-link"
                wire:click="goToPage({{ $currentPage - 1 }})"
                @if ($currentPage == 1) disabled @endif
            >
                ‹
            </button>
        </li>

        {{-- Page Numbers --}}
        @php
            $totalPages = ceil($user_orders['total'] / $perPage);
        @endphp

        @for ($i = 1; $i <= $totalPages; $i++)
            @if ($i >= $currentPage - 2 && $i <= $currentPage + 2)
                <li class="page-item {{ $i == $currentPage ? 'active' : '' }}" aria-current="{{ $i == $currentPage ? 'page' : '' }}">
                    <button class="page-link" wire:click="goToPage({{ $i }})">{{ $i }}</button>
                </li>
            @elseif ($i == 1 || $i == $totalPages)
                <li class="page-item">
                    <button class="page-link" wire:click="goToPage({{ $i }})">{{ $i }}</button>
                </li>
                @if ($i < $currentPage - 3 || $i > $currentPage + 3)
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                @endif
            @endif
        @endfor

        {{-- Next --}}
        <li class="page-item {{ $currentPage == $totalPages ? 'disabled' : '' }}" aria-disabled="{{ $currentPage == $totalPages ? 'true' : 'false' }}" aria-label="Next »">
            <button
                class="page-link"
                wire:click="goToPage({{ $currentPage + 1 }})"
                @if ($currentPage == $totalPages) disabled @endif
            >
                ›
            </button>
        </li>
    </ul>
</div>



                </div>
            </div>
        </div>
    </div>
</div>
