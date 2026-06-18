@extends('seller/layout')
@section('title')
    {{ labels('admin_labels.order_manage', 'Order Manage') }}
@endsection
@section('content')
    <section class="main-content">
        <x-seller.breadcrumb :title="labels('admin_labels.order_details', 'Order Details')" :subtitle="labels('admin_labels.every_detail_at_your_fingertips', 'Every detail at your fingertips')" :breadcrumbs="[
            ['label' => labels('admin_labels.order_manage', 'Order Manage')],
            ['label' => labels('admin_labels.order', 'Order')],
            ['label' => labels('admin_labels.order_details', 'Order Details')],
        ]" />
        @php
            use App\Models\OrderItems;
            use App\Services\MediaService;
            use App\Services\ShiprocketService;
            use App\Services\OrderService;
            use App\Services\SettingService;

            // Get admin shipping settings
            $shipping_settings = app(SettingService::class)->getSettings('shipping_method', true);
            $shipping_settings = json_decode($shipping_settings, true);
            $shiprocket_enabled =
                isset($shipping_settings['shiprocket_shipping_method']) &&
                $shipping_settings['shiprocket_shipping_method'] == 1;
            $local_shipping_enabled =
                isset($shipping_settings['local_shipping_method']) && $shipping_settings['local_shipping_method'] == 1;
        @endphp
        <section>
            <div class="card content-area p-3">
                <div class="align-items-center d-flex justify-content-between">
                    <div>
                        <span
                            class="body-default text-muted">{{ labels('admin_labels.order_number', 'Order Number') }}</span>
                        <p class="lead">#{{ $order_detls[0]->id }}</p>
                    </div>
                    <div class="d-flex flex-column">
                        <div class="d-flex align-items-center mb-2">
                            <span class="body-default text-muted">
                                {{ labels('admin_labels.order_date', 'Order Date') }} :
                            </span>
                            <span class="body-default ms-2">
                                {{ date('d M, Y', strtotime($order_detls[0]->created_at)) }}
                            </span>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="body-default text-muted">
                                {{ labels('admin_labels.order_note', 'Order Note') }} :
                            </span>
                            <span class="body-default ms-2">
                                {{ $order_detls[0]->notes ?? '' }}
                            </span>
                        </div>
                        @if (!empty($order_detls[0]->shipping_option_name))
                            <div class="d-flex align-items-center mt-2">
                                <span class="body-default text-muted">
                                    {{ labels('admin_labels.shipping_method', 'Shipping Method') }} :
                                </span>
                                <span class="body-default ms-2">
                                    {{ $order_detls[0]->shipping_option_name ?? '-' }}
                                    @if (!empty($order_detls[0]->shipping_carrier))
                                        ({{ $order_detls[0]->shipping_carrier }})
                                    @endif
                                </span>
                            </div>
                        @endif
                        @if (!empty($order_detls[0]->shipping_estimated_days))
                            <div class="d-flex align-items-center mt-2">
                                <span class="body-default text-muted">
                                    {{ labels('admin_labels.estimated_delivery', 'Estimated Delivery') }} :
                                </span>
                                <span class="body-default ms-2">
                                    {{ $order_detls[0]->shipping_estimated_days }}
                                </span>
                            </div>
                        @endif
                    </div>

                </div>
            </div>

            <div class="row mt-5 order-info">
                @if ($is_customer_privacy_permission == 1)
                    <div class="col-md-4">
                        <div class="card">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6>{{ labels('admin_labels.customer_info', 'Customer Info') }}</h6>
                                    <div class="d-flex mt-3 align-items-center">
                                        <span class="body-default me-1">{{ labels('admin_labels.name', 'Name') }}:</span>
                                        <span class="caption text-muted">{{ $order_detls[0]->user_name }}</span>
                                    </div>

                                    <div class="d-flex mt-2 align-items-center">

                                        <span
                                            class="body-default me-1">{{ labels('admin_labels.mobile', 'Contact') }}:</span>
                                        @if (!empty($order_detls[0]->mobile_number))
                                            <span class="caption text-muted">{{ $order_detls[0]->mobile_number }}</span>
                                        @elseif (!empty($order_detls[0]->mobile))
                                            <span class="caption text-muted">{{ $order_detls[0]->mobile }}</span>
                                        @else
                                            <span
                                                class="caption text-muted">{{ !empty($mobile_data[0]->mobile) ? $mobile_data[0]->mobile : '' }}</span>
                                        @endif
                                    </div>

                                    <div class="d-flex mt-2 align-items-center">
                                        <span class="body-default me-1">{{ labels('admin_labels.email', 'Email') }}:</span>
                                        <span class="caption text-muted">{{ $order_detls[0]->email }}</span>
                                    </div>
                                </div>
                                <div>
                                    <img src="{{ $items[0]['user_profile'] }}" class="customer-img-box">
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="col-md-4">
                    <div class="card">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6>{{ labels('admin_labels.shipping_info', 'Shipping Info') }}</h6>
                                <div class="d-flex mt-3 align-items-center">
                                    <span class="body-default me-1">{{ labels('admin_labels.name', 'Name') }}:</span>
                                    <span class="caption text-muted">{{ $order_detls[0]->user_name }}</span>
                                </div>

                                <div class="d-flex mt-2 align-items-center">
                                    <span class="body-default me-1">{{ labels('admin_labels.mobile', 'Contact') }}:</span>
                                    @if (!empty($order_detls[0]->mobile_number))
                                        <span class="caption text-muted">{{ $order_detls[0]->mobile_number }}</span>
                                    @elseif ($order_detls[0]->mobile != '' && isset($order_detls[0]->mobile))
                                        <span class="caption text-muted">{{ $order_detls[0]->mobile }}</span>
                                    @else
                                        <span
                                            class="caption text-muted">{{ $mobile_data->isNotEmpty() ? $mobile_data[0]->mobile : '' }}</span>
                                    @endif
                                </div>
                                <div class="d-flex mt-2 align-items-center">
                                    <span class="body-default me-1">{{ labels('admin_labels.address', 'Address') }}:</span>
                                    <span class="caption text-muted">{{ $order_detls[0]->address }}</span>
                                </div>
                            </div>
                            <div>
                                <img src="{{ $items[0]['user_profile'] }}" class="customer-img-box">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6>{{ labels('admin_labels.seller_info', 'Seller Info') }}</h6>
                                <div class="d-flex mt-3 align-items-center">
                                    <span
                                        class="body-default me-1">{{ labels('admin_labels.seller_name', 'Seller Name') }}:</span>
                                    <span class="caption text-muted">{{ $sellers[0]['seller_name'] }}</span>
                                </div>

                                <div class="d-flex mt-2 align-items-center">
                                    <span class="body-default me-1">{{ labels('admin_labels.mobile', 'Contact') }}:</span>
                                    <span class="caption text-muted">{{ $sellers[0]['seller_mobile'] }}</span>
                                </div>
                                <div class="d-flex mt-2 align-items-center">
                                    <span class="body-default me-1">{{ labels('admin_labels.email', 'Email') }}:</span>
                                    <span class="caption text-muted">{{ $sellers[0]['seller_email'] }}</span>
                                </div>
                            </div>
                            <div>
                                <img src="{{ !empty($sellers[0]['shop_logo']) ? app(MediaService::class)->getMediaImageUrl($sellers[0]['shop_logo'], 'SELLER_IMG_PATH') : '' }}"
                                    class="customer-img-box">
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <div class="row mt-5 order-detail col-md-12">
                <div class="col-md-12">
                    <div class="card ">
                        <div class="nav nav-tabs" id="product-tab" role="tablist">
                            <a class="nav-item nav-link active" id="order-items-tab" data-bs-toggle="tab"
                                href="#order-items" role="tab" aria-controls="order-items"
                                aria-selected="true">{{ labels('admin_labels.order_items', 'Order Items') }}</a>
                            @if ($items[0]['product_type'] != 'digital_product' && empty($order_tracking_data[0]['shipment_id']))
                                <a class="nav-item nav-link" id="shipments-tab" data-bs-toggle="tab" href="#shipments"
                                    role="tab" aria-controls="shipments"
                                    aria-selected="false">{{ labels('admin_labels.shipments', 'Shipments') }}</a>
                            @endif
                        </div>
                        <div class="card-body">
                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="order-items" role="tabpanel"
                                    aria-labelledby="order-items-tab">
                                    <table
                                        class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100 edit-order-table">
                                        <thead>

                                            <tr>
                                                @if ($items[0]['product_type'] == 'digital_product')
                                                    <th></th>
                                                @endif
                                                <th>
                                                    {{ labels('admin_labels.id', 'Id') }}
                                                </th>
                                                <th>{{ labels('admin_labels.name', 'Name') }}</th>
                                                <th>{{ labels('admin_labels.image', 'Image') }}</th>
                                                <th>{{ labels('admin_labels.attachment', 'Attachment') }}</th>
                                                <th>{{ labels('admin_labels.quantity', 'Quantity') }}</th>
                                                <th>{{ labels('admin_labels.product_type', 'Product Type') }}</th>
                                                <th>{{ labels('admin_labels.variations', 'Variant') }}</th>
                                                <th>{{ labels('admin_labels.variations_image', 'Variant Images') }}</th>
                                                <th>{{ labels('admin_labels.discount', 'Discounted Price') }}</th>
                                                <th>{{ labels('admin_labels.sub_total', 'Sub Total') }}</th>
                                                <th>{{ labels('admin_labels.active_status', 'Active Status') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $total = 0;
                                                $tax_amount = 0;
                                                $item_subtotal = 0;
                                            @endphp
                                            @foreach ($items as $index => $item)
                                                @php
                                                    $is_allow_to_ship_order = true;
                                                @endphp
                                                @if ($item['active_status'] == 'draft' || $item['active_status'] == 'awaiting')
                                                    @php
                                                        $is_allow_to_ship_order = false;
                                                    @endphp
                                                @endif
                                                @php
                                                    $selected = '';
                                                    $item['discounted_price'] =
                                                        $item['discounted_price'] == '' ? 0 : $item['discounted_price'];
                                                    $total += $subtotal =
                                                        $item['quantity'] != 0 &&
                                                        ($item['discounted_price'] != '' &&
                                                            $item['discounted_price'] > 0) &&
                                                        $item['price'] > $item['discounted_price']
                                                            ? $item['price'] - $item['discounted_price']
                                                            : $item['price'] * $item['quantity'];
                                                    $tax_amount += $item['tax_amount'];
                                                    $total += $subtotal = $tax_amount;
                                                    $item_subtotal += (float) $item['item_subtotal'];
                                                @endphp
                                                <tr>
                                                    @if ($items[0]['product_type'] == 'digital_product')
                                                        <td><input type="checkbox" name="order_item_ids[]"
                                                                value="{{ $item['id'] }}"
                                                                class="checked_order_items form-check-input selected_order_item_ids">
                                                        </td>
                                                    @endif
                                                    <td class="align-items-center d-flex">{{ $index + 1 }}</td>
                                                    @php
                                                        $product_name = json_decode($item['pname'], true);
                                                        $product_name = $product_name['en'] ?? '';
                                                        $product_id = $item['product_id'];

                                                    @endphp
                                                    <td>
                                                        <h6 class="title-color">
                                                            <a href="{{ isset($items[0]['order_type']) && $items[0]['order_type'] === 'combo_order'
                                                                ? route('seller.combo_products.show', ['id' => $product_id])
                                                                : route('seller.product.show', ['id' => $product_id]) }}"
                                                                title="Click To View Product" target="_blank">
                                                                {{ $product_name }}
                                                            </a>
                                                        </h6>


                                                    </td>
                                                    <td>
                                                        <div class="order-image-box">
                                                            <a href={{ app(MediaService::class)->getMediaImageUrl($item['product_image']) }}
                                                                target=""
                                                                data-lightbox="image-'{{ $item['product_id'] }}'">
                                                                <img class="rounded"
                                                                    src="{{ app(MediaService::class)->getMediaImageUrl($item['product_image']) }}"
                                                                    alt="{{ $product_name }}">
                                                            </a>
                                                        </div>
                                                    </td>
                                                    <td class="d-flex justify-content-center">
                                                        @if (!empty($item['attachment']))
                                                            <a href="{{ app(MediaService::class)->getMediaImageUrl($item['attachment']) }}"
                                                                target="_blank" class="image-link">
                                                                <i class='attachment_icon bx bx-link fs-3'></i>
                                                            </a>
                                                        @endif
                                                    </td>

                                                    <td>{{ $item['quantity'] }}</td>
                                                    <td>{{ str_replace('_', ' ', ucfirst($item['product_type'])) }}</td>
                                                    <td>{{ isset($item['product_variants']) && !empty($item['product_variants'][0]['variant_values'])
                                                        ? str_replace(',', ' | ', $item['product_variants'][0]['variant_values'])
                                                        : '-' }}
                                                    </td>
                                                    <td>
                                                        @if (!empty($item['product_variants'][0]['images']))
                                                            @foreach ($item['product_variants'][0]['images'] as $img)
                                                                <a href={{ app(MediaService::class)->getMediaImageUrl($img) }}
                                                                    target=""
                                                                    data-lightbox="image-'{{ $item['product_id'] }}'">
                                                                    <img src="{{ $img }}" alt="variant image"
                                                                        width="60" class="me-2 mb-2">
                                                            @endforeach
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td>{{ $item['discounted_price'] > 0 ? $item['discounted_price'] : $item['price'] }}
                                                    </td>
                                                    <td>{{ $item['item_subtotal'] }}</td>
                                                    @php
                                                        $badges = [
                                                            'awaiting' => 'secondary',
                                                            'received' => 'primary',
                                                            'processed' => 'info',
                                                            'shipped' => 'warning',
                                                            'delivered' => 'success',
                                                            'returned' => 'danger',
                                                            'cancelled' => 'danger',
                                                            'return_request_approved' => 'success',
                                                            'return_request_decline' => 'danger',
                                                            'return_request_pending' => 'warning',
                                                            'return_pickedup' => 'success',
                                                        ];

                                                        if ($item['active_status'] == 'return_request_pending') {
                                                            $status = 'Return Requested';
                                                        } elseif ($item['active_status'] == 'return_request_approved') {
                                                            $status = 'Return Approved';
                                                        } elseif ($item['active_status'] == 'return_request_decline') {
                                                            $status = 'Return Declined';
                                                        } else {
                                                            $status = $item['active_status'];
                                                        }
                                                    @endphp
                                                    <td>
                                                        <small>
                                                            <span
                                                                class="mt-1 badge badge-sm bg-{{ $badges[$item['active_status']] }}">
                                                                {{ $status }}
                                                            </span>
                                                        </small>
                                                    </td>
                                                </tr>
                                                <span class="d-none"
                                                    id="product_variant_id_{{ $item['product_variant_id'] }}">
                                                    {!! json_encode([
                                                        'id' => $item['id'],
                                                        'unit_price' => $item['price'],
                                                        'quantity' => $item['quantity'],
                                                        'delivered_quantity' => $item['delivered_quantity'],
                                                        'active_status' => $item['active_status'],
                                                        'pickup_location' => $item['pickup_location'],
                                                    ]) !!}
                                                </span>

                                                <input type="hidden" class="product_variant_id"
                                                    name="product_variant_id" value="{{ $item['product_variant_id'] }}">
                                                <input type="hidden" class="product_name" name="product_name"
                                                    value="{{ $product_name }}">
                                                <input type="hidden" class="order_item_id" name="order_item_id"
                                                    value="{{ $item['id'] }}">
                                            @endforeach
                                        </tbody>
                                    </table>
                                    @if ($items[0]['product_type'] == 'digital_product')
                                        <select name="status" class="form-control digital_order_status mb-3">
                                            <option value=''>Select Status</option>
                                            <option value="received"
                                                <?= $item['active_status'] == 'received' ? 'selected' : '' ?>>Received
                                            </option>
                                            <option value="delivered"
                                                <?= $item['active_status'] == 'delivered' ? 'selected' : '' ?>>Delivered
                                            </option>
                                        </select>
                                        <div class="d-flex justify-content-end">
                                            <button class="btn btn-primary digital_order_status_update">{{ labels('admin_labels.submit', 'Submit') }}</button>
                                        </div>
                                    @endif
                                </div>
                                @if ($items[0]['product_type'] != 'digital_product' && empty($order_tracking_data[0]['shipment_id']))
                                    <div class="tab-content">
                                        <div class="tab-pane fade" id="shipments" role="tabpanel"
                                            aria-labelledby="shipments-tab">
                                            <button type="button" class="btn btn-primary mb-2" data-bs-toggle="modal"
                                                data-bs-target="#create_parcel_modal" onclick="parcelModal()">Create A
                                                Parcel</button>
                                            <div class="col-md-12">
                                                <div class="row">
                                                    <div class="col-sm-12 d-flex justify-content-end mt-md-0 mt-sm-2">
                                                        <div class="input-group me-2 search-input-grp ">
                                                            <span class="search-icon"><i
                                                                    class='bx bx-search-alt'></i></span>
                                                            <input type="text" data-table="seller_parcel_table"
                                                                class="form-control searchInput"
                                                                placeholder="{{ labels('admin_labels.search', 'Search') }}">
                                                            <span
                                                                class="input-group-text">{{ labels('admin_labels.search', 'Search') }}</span>
                                                        </div>
                                                        <a class="btn me-2" id="tableFilter" data-bs-toggle="offcanvas"
                                                            data-bs-target="#columnFilterOffcanvas"
                                                            data-table="seller_parcel_table" StatusFilter='true'><i
                                                                class='bx bx-filter-alt'></i></a>
                                                        <a class="btn me-2" id="tableRefresh"
                                                            data-table="seller_parcel_table"><i
                                                                class='bx bx-refresh'></i></a>
                                                        <div class="dropdown">
                                                            <a class="btn dropdown-toggle export-btn" type="button"
                                                                id="exportOptionsDropdown" data-bs-toggle="dropdown"
                                                                aria-expanded="false">
                                                                <i class='bx bx-download'></i>
                                                            </a>
                                                            <ul class="dropdown-menu"
                                                                aria-labelledby="exportOptionsDropdown">
                                                                <li><button class="dropdown-item" type="button"
                                                                        onclick="exportTableData('seller_parcel_table','csv')">CSV</button>
                                                                </li>
                                                                <li><button class="dropdown-item" type="button"
                                                                        onclick="exportTableData('seller_parcel_table','json')">JSON</button>
                                                                </li>
                                                                <li><button class="dropdown-item" type="button"
                                                                        onclick="exportTableData('seller_parcel_table','sql')">SQL</button>
                                                                </li>
                                                                <li><button class="dropdown-item" type="button"
                                                                        onclick="exportTableData('seller_parcel_table','excel')">Excel</button>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>
                                            <table class='table edit-order-table' id="seller_parcel_table"
                                                data-toggle="table" data-loading-template="loadingTemplate"
                                                data-url="{{ route('seller.parcels.list') }}" data-click-to-select="true"
                                                data-side-pagination="server" data-pagination="true"
                                                data-page-list="[5, 10, 20, 50, 100, 200]" data-search="false"
                                                data-show-columns="false" data-show-refresh="false"
                                                data-trim-on-search="false" data-sort-name="id" data-sort-order="desc"
                                                data-mobile-responsive="true" data-toolbar="" data-show-export="false"
                                                data-maintain-selected="true" data-export-types='["txt","excel"]'
                                                data-query-params="parcel_query_params" id="parcel_table">

                                                <thead>
                                                    <tr>
                                                        <th data-field="id" data-sortable='true'>
                                                            {{ labels('admin_labels.id', 'Id') }}
                                                        </th>
                                                        <th data-field="order_id" data-sortable='true'>
                                                            {{ labels('admin_labels.order_id', 'Order Id') }}</th>
                                                        <th data-field="name" data-sortable='false'>
                                                            {{ labels('admin_labels.name', 'Name') }}</th>
                                                        <th data-field="status" data-sortable='false'>
                                                            {{ labels('admin_labels.status', 'Status') }}</th>
                                                        <th class="d-none" data-field="otp" data-sortable='false'>
                                                            {{ labels('admin_labels.otp', 'OTP') }}</th>
                                                        <th data-field="created_at" data-sortable='false'>
                                                            {{ labels('admin_labels.date_created', 'Date Created') }}</th>
                                                        <th data-field="operate" data-sortable="false">
                                                            {{ labels('admin_labels.action', 'Action') }}</th>
                                                    </tr>
                                                </thead>
                                            </table>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        {{-- modal for create parcel --}}
        @if ($is_allow_to_ship_order == true)
            <div class="modal fade" tabindex="-1" role="dialog" aria-hidden="true" id="create_parcel_modal"
                aria-labelledby="editModalLabel" aria-hidden="true">
                <input type="hidden" id="order_id" name="order_id" value="{{ $order_detls[0]->id }}" />
                <!-- In the modal -->
                <input type="hidden" id="modal_order_id" name="order_id" value="">
                <input type="hidden" class="seller_id" value="{{ $sellers[0]['id'] }}" />
                <input type="hidden" id="parcel_order_type" name="parcel_order_type"
                    value="{{ $order_detls[0]->order_type }}" />
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="myModalLabel">{{ labels('admin_labels.create_shiprocket_order_parcel', 'Create a Parcel') }}</h5>
                            <div class="d-flex justify-content-end"><button type="button" class="btn-close"
                                    data-bs-dismiss="modal" aria-label="Close"></button></div>
                        </div>
                        <div class="modal-body" id="empty_box_body"></div>
                        <div class="modal-body" id="modal-body">
                            <div class="input-group flex-nowrap mb-3">
                                <span class="input-group-text bg-gradient-light">{{ labels('admin_labels.parcel_title', 'Parcel Title') }}</span>
                                <input type="text" class="form-control" placeholder="{{ labels('admin_labels.parcel_title', 'Parcel Title') }}"
                                    aria-label="Username" aria-describedby="addon-wrapping" id="parcel_title" required>
                            </div>



                            <table class="table mt-2">
                                <thead>
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">{{ labels('admin_labels.product_name', 'Product Name') }}</th>
                                        <th scope="col">{{ labels('admin_labels.product_variant_id', 'Product Varient ID') }}</th>
                                        <th scope="col">{{ labels('admin_labels.quantity', 'Order Quantity') }}</th>
                                        <th scope="col">{{ labels('admin_labels.price', 'Unit Price') }}</th>
                                        <th scope="col">{{ labels('admin_labels.select_products', 'Select Items') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="product_details">
                                </tbody>
                            </table>
                            <div class="d-flex justify-content-end px-2">
                                <button type="button" class="btn btn-primary" id="ship_parcel_btn">{{ labels('admin_labels.shipped', 'Ship') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        <!-- modal for order tracking -->
        <div class="modal fade" tabindex="-1" role="dialog" aria-hidden="true" id="order_tracking_modal"
            aria-labelledby="editModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="user_name">
                            {{ labels('admin_labels.order_tracking', 'Order Tracking') }}
                        </h5>
                        <div class="d-flex justify-content-end"><button type="button" class="btn-close"
                                data-bs-dismiss="modal" aria-label="Close"></button></div>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <form class="form-horizontal " id="order_tracking_form"
                                    action="{{ route('seller.orders.update_order_tracking') }}" method="POST"
                                    enctype="multipart/form-data">
                                    @method('POST')
                                    @csrf
                                    <input type="hidden" name="parcel_id">
                                    <div class="card-body pad">
                                        <div class="form-group ">
                                            <label
                                                for="courier_agency">{{ labels('admin_labels.courier_agency', 'Courier Agency') }}</label>
                                            <input type="text" class="form-control" name="courier_agency"
                                                id="courier_agency" placeholder="{{ labels('admin_labels.courier_agency', 'Courier Agency') }}" />
                                        </div>
                                        <div class="form-group ">
                                            <label
                                                for="tracking_id">{{ labels('admin_labels.tracking_id', 'Tracking Id') }}</label>
                                            <input type="text" class="form-control" name="tracking_id"
                                                id="tracking_id" placeholder="{{ labels('admin_labels.tracking_id', 'Tracking ID') }}" />
                                        </div>
                                        <div class="form-group ">
                                            <label for="url">{{ labels('admin_labels.url', 'Url') }}</label>
                                            <input type="text" class="form-control" name="url" id="url"
                                                placeholder="URL" />
                                        </div>
                                        <div class="d-flex justify-content-end">
                                            <button type="reset"
                                                class="btn mx-2 reset_button">{{ labels('admin_labels.reset', 'Reset') }}</button>
                                            <button type="submit" class="btn btn-primary"
                                                id="submit_btn">{{ labels('admin_labels.save', 'Save') }}</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- modal for send digital product -->
        <div id="sendMailModal" class="modal fade editSendMail" tabindex="-1" role="dialog"
            aria-labelledby="myLargeModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-focus="false">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            {{ labels('admin_labels.manage_digital_product', 'Manage Digital Product') }}
                        </h5>
                        <div class="d-flex justify-content-end"><button type="button" class="btn-close"
                                data-bs-dismiss="modal" aria-label="Close"></button></div>
                    </div>

                    <div class="modal-body ">
                        <form class="form-horizontal form-submit-event submit_form"
                            action="{{ route('seller.orders.send_digital_product') }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            <div class="card-body">
                                <input type="hidden" name="order_id" value="{{ $order_detls[0]->order_id }}">
                                <input type="hidden" name="order_item_id" value="">
                                <input type="hidden" name="username" value="{{ $order_detls[0]->uname }}">
                                <div class="row form-group">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label class="form-label"
                                                for="product_name">{{ labels('admin_labels.email', 'Customer Email') }}</label>
                                            <input type="text" class="form-control" id="email" name="email"
                                                value="{{ $order_detls[0]->user_email }}" readonly>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label class="form-label"
                                                for="product_name">{{ labels('admin_labels.subject', 'Subject') }}
                                            </label>
                                            <input type="text" class="form-control" id="subject"
                                                placeholder="{{ labels('admin_labels.subject', 'Enter Subject for email') }}" name="subject" value="">
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="form-group">
                                            <label class="form-label"
                                                for="product_name">{{ labels('admin_labels.message', 'Message') }}</label>
                                            <textarea class="textarea" id="mail_msg" placeholder="{{ labels('admin_labels.message', 'Message for Email') }}" name="message"></textarea>
                                        </div>
                                    </div>

                                    <div class="col-12 mt-2" id="digital_media_container">
                                        <label class="form-label" for="image"
                                            class="ml-2">{{ labels('admin_labels.file', 'File') }}
                                            <span class='text-asterisks text-sm'>*</span></label>
                                        <div class='col-md-12'><a class="uploadFile img btn btn-primary text-white btn-sm"
                                                data-input='pro_input_file' data-isremovable='0'
                                                data-media_type="archive,document" data-is-multiple-uploads-allowed='0'
                                                data-bs-toggle="modal" data-bs-target="#media-upload-modal"
                                                value="Upload Photo"><i class='fa fa-upload'></i> Upload</a></div>
                                        <div class="container-fluid row image-upload-section">
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-success mt-3" id="submit_btn"
                                    value="Save">{{ labels('admin_labels.send_mail', 'Send Mail') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- modal for show parcel product details --}}

        <div class="modal fade" id="view_parcel_items_modal" tabindex="-1" role="dialog"
            aria-labelledby="myLargeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header mb-1">
                        <h5 class="modal-title" id="myModalLabel">
                            {{ labels('admin_labels.parcel_items', 'Parcel Items') }}
                        </h5>
                        <div class="d-flex justify-content-end"><button type="button" class="btn-close"
                                data-bs-dismiss="modal" aria-label="Close"></button></div>
                    </div>
                    <div class="modal-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">{{ labels('admin_labels.name', 'Name') }}</th>
                                    <th scope="col">{{ labels('admin_labels.image', 'Image') }}</th>
                                    <th scope="col">{{ labels('admin_labels.quantity', 'Quantity') }}</th>
                                    <th scope="col">{{ labels('admin_labels.status', 'Status') }}</th>

                                </tr>
                            </thead>
                            <tbody id="parcel_product_details">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- modal for update parcel items status --}}

    <div class="modal fade" id="parcel_status_modal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header mb-1">
                    <h5 class="modal-title" id="myModalLabel">{{ labels('admin_labels.update', 'Update') }} {{ labels('admin_labels.parcel_status', 'Parcel Status') }}</h5>
                    <div class="d-flex justify-content-end"><button type="button" class="btn-close"
                            data-bs-dismiss="modal" aria-label="Close"></button></div>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="parcel_id" id="parcel_id">
                    @if (isset($items[0]['product_type']) && $items[0]['product_type'] != 'digital_product')
                        <div class="col-md-12 mb-2">
                            <label class="badge badge-success">{{ labels('admin_labels.select_status', 'Select status which you want to update') }}
                            </label>
                        </div>
                        <div id="parcel-items-container"></div>
                    @endif

                    {{-- Shipping Method Info --}}
                    <div class="alert {{ $shiprocket_enabled ? 'alert-warning' : 'alert-info' }} mb-3" role="alert">
                        <h6 class="alert-heading">
                            <i class="bx {{ $shiprocket_enabled ? 'bx-rocket' : 'bx-car' }}"></i>
                            {{ labels('admin_labels.shipping_option', 'Active Shipping Method') }}:
                            {{ $shiprocket_enabled ? labels('admin_labels.standard_shipping_shiprocket', 'Standard Shipping (Shiprocket)') : labels('admin_labels.local_shipping', 'Local Shipping') }}
                        </h6>
                        <p class="mb-0 small">
                            @if ($shiprocket_enabled)
                                {{ labels('admin_labels.use_shiprocket_courier_partners', 'This order will be shipped using') }} <strong>{{ labels('admin_labels.standard_shipping_shiprocket', 'Shiprocket courier partners') }}</strong>.
                                {{ labels('admin_labels.create_shiprocket_order_parcel', 'You can create parcels, generate AWB, and track shipments below.') }}
                            @else
                                {{ labels('admin_labels.use_your_own_delivery_team', 'This order will be shipped using') }} <strong>{{ labels('admin_labels.local_shipping', 'your local delivery team') }}</strong>.
                                {{ labels('admin_labels.select_delivery_boy', 'Assign a delivery boy to complete the order.') }}
                            @endif
                        </p>
                    </div>

                    {{-- Parcel items container - populated by JavaScript --}}
                    <div id="parcel-items-container"></div>

                    {{-- Parcel-specific tracking boxes - populated by JavaScript --}}
                    <div id="tracking_box"></div>
                    <div id="tracking_box_old"></div>

                    {{-- Shiprocket Order Creation Form - shown when no existing order --}}
                    @if ($shiprocket_enabled)
                        <div class="shiprocket_order_box">
                            <div class="card card-info mb-4">
                                <div class="card-header bg-dark text-white">
                                    <h6 class="mb-0 text-white">{{ labels('admin_labels.create_shiprocket_order_parcel', 'Create Shiprocket Order') }}</h6>
                                </div>
                                <div class="card-body">
                                    <form class="form-horizontal shiprocket_order_parcel_form" action=""
                                        method="POST">
                                        @csrf
                                        <input type="hidden" id="order_id" name="order_id"
                                            value="{{ $order_detls[0]->id }}" />
                                        <input type="hidden" name="user_id" id="user_id"
                                            value="{{ $order_detls[0]->user_id }}" />
                                        <input type="hidden" name="shiprocket_seller_id" class="shiprocket_seller_id"
                                            value="{{ $sellers[0]['id'] ?? '' }}" />
                                        <input type="hidden" name="fromseller" value="1" class="fromseller" />
                                        <textarea name="order_items[]" hidden class="parcel_data"></textarea>
                                        <textarea name="parcel_data[]" hidden class="parcel_data"></textarea>
                                        <input type="hidden" name="pickup_location" id="pickup_location"
                                            value="" />

                                        <div class="form-group row mt-4">
                                            <div class="col-3">
                                                <label class="control-label col-md-12">Weight <small>(kg)</small> <span
                                                        class="text-danger">*</span></label>
                                                <input type="number" class="form-control" name="parcel_weight"
                                                    placeholder="Weight" value="" step=".01" required>
                                            </div>
                                            <div class="col-3">
                                                <label class="control-label col-md-12">Height <small>(cms)</small> <span
                                                        class="text-danger">*</span></label>
                                                <input type="number" class="form-control" name="parcel_height"
                                                    placeholder="Height" value="" min="1" required>
                                            </div>
                                            <div class="col-3">
                                                <label class="control-label col-md-12">Breadth <small>(cms)</small> <span
                                                        class="text-danger">*</span></label>
                                                <input type="number" class="form-control" name="parcel_breadth"
                                                    placeholder="Breadth" value="" min="1" required>
                                            </div>
                                            <div class="col-3">
                                                <label class="control-label col-md-12">Length <small>(cms)</small> <span
                                                        class="text-danger">*</span></label>
                                                <input type="number" class="form-control" name="parcel_length"
                                                    placeholder="Length" value="" min="1" required>
                                            </div>
                                        </div>

                                        <div class="card-footer d-flex justify-content-end">
                                            <button type="submit" class="btn btn-primary create_shiprocket_parcel">{{ labels('admin_labels.create_shiprocket_order_parcel', 'Create Shiprocket Order') }}</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        {{-- Manage Shiprocket Order Section - shown when order exists --}}
                        <div class="manage_shiprocket_box">
                            {{-- This will be populated by JavaScript with existing order details and action buttons --}}
                        </div>
                    @endif

                    {{-- Status Dropdown - Only show for Local Shipping --}}
                    @if (!$shiprocket_enabled)
                        <select name="status" class="form-control parcel_status mb-3">
                            <option value=''>{{ labels('admin_labels.select_status', 'Select Status') }}</option>
                            <option value="received">{{ labels('admin_labels.received', 'Received') }}</option>
                            <option value="processed">{{ labels('admin_labels.processed', 'Processed') }}</option>
                            <option value="shipped">{{ labels('admin_labels.shipped', 'Shipped') }}</option>
                            <option value="delivered">{{ labels('admin_labels.delivered', 'Delivered') }}</option>
                        </select>

                        {{-- Delivery Boy Selection --}}
                        <select id="deliver_by" name="deliver_by" class="form-control mb-2">
                            <option value="">{{ labels('admin_labels.select_delivery_boy', 'Select Delivery Boy') }}</option>
                            @foreach ($delivery_res as $row)
                                <option value="{{ $row->id }}"
                                    {{ $order_detls[0]->delivery_boy_id == $row->id ? 'selected' : '' }}>
                                    {{ $row->username }}
                                </option>
                            @endforeach
                        </select>
                    @endif

                    @if ($order_detls[0]->is_shiprocket_order == 0)
                        <div class="d-flex justify-content-end p-2">
                            <button type="button"
                                class="btn btn-primary btn-sm me-1 parcel_order_status_update">Update</button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
