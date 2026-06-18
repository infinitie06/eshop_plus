@extends('admin/layout')
@section('title')
    {{ labels('admin_labels.notifications', 'Seller Notifications') }}
@endsection
@section('content')
    <x-admin.breadcrumb :title="labels('admin_labels.notifications', 'Seller Notifications')" :subtitle="labels(
        'admin_labels.effortlessly_reach_users_with_swift_notification_delivery',
        'Effortlessly Reach Users with Swift Notification Delivery',
    )" :breadcrumbs="[['label' => labels('admin_labels.notifications', 'Seller Notifications')]]" />

    <div class="col-md-12">
        <div class="row">
            <div class="col-md-12 col-lg-4">
                <div class="card card-info">
                    <form class="form-horizontal submit_form" action="{{ route('notifications.store') }}" method="POST"
                        id="" enctype="multipart/form-data">
                        @csrf
                        <div class="card-body">
                            <h5 class="mb-3">
                                {{ labels('admin_labels.send_notification', 'Send Notifications') }}
                            </h5>
                            <div class="form-group">
                                <label for=""
                                    class="control-label mb-2 mt-2">{{ labels('admin_labels.send_to', 'Send to') }}<span
                                        class='text-asterisks text-sm'>*</span>
                                    <i class="fa fa-info-circle text-secondary ms-1"
                                       data-bs-toggle="popover"
                                       data-bs-placement="right"
                                       data-bs-content="{{ labels('admin_labels.send_notification_all_or_specific_sellers', 'Choose whether to send notification to all sellers or specific sellers.') }}"></i>
                                </label>
                                <select name="send_to" id="send_seller_notification"
                                    class="form-control form-select type_event_trigger" required>
                                    <option value="all_sellers">{{ labels('admin_labels.all_sellers_option', 'All Sellers') }}</option>
                                    <option value="specific_seller">{{ labels('admin_labels.specific_seller_option', 'Specific Seller') }}</option>
                                </select>
                            </div>
                            {{-- <div class="form-group row notification-sellers d-none"> --}}
                            <div class="form-group row notification-sellers d-none">
                                <label for="user_id"
                                    class="col-md-12 control-label">{{ labels('admin_labels.sellers', 'Sellers') }}
                                    <span class='text-asterisks text-sm'>*</span>
                                    <i class="fa fa-info-circle text-secondary ms-1"
                                       data-bs-toggle="popover"
                                       data-bs-placement="right"
                                       data-bs-content="{{ labels('admin_labels.select_sellers_for_notification', 'Select sellers to receive the notification.') }}"></i>
                                </label>
                                <div class="col-md-12">
                                    <input type="hidden" name="user_id" id="noti_user_id" value="">
                                    <select name="select_user_id[]" class="search_seller w-100" multiple
                                        {{-- <select name="select_user_id[]" class="search_user w-100" multiple --}} data-placeholder="Type to search and select sellers"
                                        onload="multiselect()">
                                        <!-- Users options here -->
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="type"
                                    class="control-label mb-2 mt-2">{{ labels('admin_labels.type', 'Type') }}
                                    <span class='text-asterisks text-sm'>*</span>
                                    <i class="fa fa-info-circle text-secondary ms-1"
                                       data-bs-toggle="popover"
                                       data-bs-placement="right"
                                       data-bs-content="{{ labels('admin_labels.select_seller_notification_type_help', 'Select the type of notification (Default, Notification URL).') }}"></i>
                                </label>
                                <select name="type" id="type" class="form-control form-select type_event_trigger"
                                    required>
                                    <option value=" ">
                                        {{ labels('admin_labels.select_type', 'Select Type') }}
                                    </option>
                                    <option value="default">{{ labels('admin_labels.default_theme', 'Default') }}</option>
                                    </option>
                                    <option value="notification_url">
                                        Notification URL</option>
                                </select>
                            </div>

                            <div id="type_add_html">

                                <div class="form-group notification-url d-none">
                                    <label for="notification_url">{{ labels('admin_labels.link', 'Link') }}
                                        <span class='text-asterisks text-sm'>*</span>
                                        <i class="fa fa-info-circle text-secondary ms-1"
                                           data-bs-toggle="popover"
                                           data-bs-placement="right"
                                           data-bs-content="{{ labels('admin_labels.enter_url_for_notification_help', 'Enter a URL to send with the notification.') }}"></i>
                                    </label>
                                    <input type="text" class="form-control" placeholder="{{ labels('admin_labels.example_url_placeholder', 'https://example.com') }}"
                                        name="link" value="">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="title"
                                    class="control-label mb-2 mt-2">{{ labels('admin_labels.title', 'Title') }}
                                    <span class='text-asterisks text-sm'>*</span>
                                    <i class="fa fa-info-circle text-secondary ms-1"
                                       data-bs-toggle="popover"
                                       data-bs-placement="right"
                                       data-bs-content="{{ labels('admin_labels.enter_notification_title_help', 'Enter the notification title.') }}"></i>
                                </label>
                                <input type="text" class="form-control" name="title" id="title"
                                    value="<?= isset($fetched_data[0]['title']) ? $fetched_data[0]['title'] : '' ?>">
                            </div>
                            <div class="form-group">
                                <label for="message"
                                    class="control-label mb-2 mt-2">{{ labels('admin_labels.message', 'Message') }}
                                    <span class='text-asterisks text-sm'>*</span>
                                    <i class="fa fa-info-circle text-secondary ms-1"
                                       data-bs-toggle="popover"
                                       data-bs-placement="right"
                                       data-bs-content="{{ labels('admin_labels.enter_notification_message_help', 'Enter the notification message.') }}"></i>
                                </label>
                                <textarea name='message' class="form-control"></textarea>
                            </div>
                            <div class="col-md-12 mt-3">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <label for="image_checkbox"
                                            class="form-label">{{ labels('admin_labels.include_image', 'Include Image') }}?
                                            <i class="fa fa-info-circle text-secondary ms-1"
                                               data-bs-toggle="popover"
                                               data-bs-placement="right"
                                               data-bs-content="{{ labels('admin_labels.enable_image_with_notification_help', 'Enable to include an image with the notification.') }}"></i>
                                        </label>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-switch notification-switch">
                                            <input class="form-check-input" type="checkbox" id="image_checkbox"
                                                name="image_checkbox">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group d-none include_image col-md-8 mt-4">
                                <label for="image" class="mb-2">{{ labels('admin_labels.image', 'Image') }}
                                    <span class='text-asterisks text-sm'>*</span>
                                    <i class="fa fa-info-circle text-secondary ms-1"
                                       data-bs-toggle="popover"
                                       data-bs-placement="right"
                                       data-bs-content="{{ labels('admin_labels.upload_notification_image_size_help', 'Upload an image for the notification. Recommended size: 80x80 pixels.') }}"></i>
                                </label>
                                <div class="col-md-12">
                                    <div class="row form-group">
                                        <div class="col-md-6 file_upload_box border file_upload_border mt-2">
                                            <div class="mt-2">
                                                <div class="col-md-12  text-center">
                                                    <div>
                                                        <a class="media_link" data-input="image" data-isremovable="0"
                                                            data-is-multiple-uploads-allowed="0" data-bs-toggle="modal"
                                                            data-bs-target="#media-upload-modal" value="Upload Photo">
                                                            <h4><i class='bx bx-upload'></i> Upload
                                                        </a></h4>
                                                        <p class="image_recommendation">Recommended Size: 80 x 80 pixels
                                                        </p>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 container-fluid row mt-3 image-upload-section">
                                            <div
                                                class="col-md-12 col-sm-12 p-3 mb-5 bg-white rounded m-4 text-center grow image d-none">

                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="reset"
                                    class="btn mx-2 reset_button">{{ labels('admin_labels.reset', 'Reset') }}</button>
                                <button type="submit"
                                    class="btn btn-primary submit_button">{{ labels('admin_labels.send_notification', 'Send Notification') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div
                class="col-lg-8 col-md-12 mt-md-2 mt-sm-2 {{ $user_role == 'super_admin' || $logged_in_user->hasPermissionTo('view send_notification') ? '' : 'd-none' }}">
                <section class="overview-data">
                    <div class="card content-area p-4 ">
                        <div class="row align-items-center d-flex heading mb-5">
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-sm-12">
                                        <h4>{{ labels('admin_labels.notifications', 'Notifications') }}
                                        </h4>
                                    </div>
                                    <div class="col-sm-12 d-flex justify-content-end mt-md-0 mt-sm-2">
                                        <div class="input-group me-2 search-input-grp ">
                                            <span class="search-icon"><i class='bx bx-search-alt'></i></span>
                                            <input type="text" data-table="admin_seller_notification_table"
                                                class="form-control searchInput" placeholder="{{ labels('admin_labels.search', 'Search') }}">
                                            <span
                                                class="input-group-text">{{ labels('admin_labels.search', 'Search') }}</span>
                                        </div>
                                        <a class="btn me-2" id="tableFilter" data-bs-toggle="offcanvas"
                                            data-bs-target="#columnFilterOffcanvas"
                                            data-table="admin_seller_notification_table" dateFilter='false'
                                            orderStatusFilter='false' paymentMethodFilter='false'
                                            orderTypeFilter='false'><i class='bx bx-filter-alt'></i></a>
                                        <a class="btn me-2"
                                            id="tableRefresh"data-table="admin_seller_notification_table"><i
                                                class='bx bx-refresh'></i></a>
                                        <div class="dropdown">
                                            <a class="btn dropdown-toggle export-btn" type="button"
                                                id="exportOptionsDropdown" data-bs-toggle="dropdown"
                                                aria-expanded="false">
                                                <i class='bx bx-download'></i>
                                            </a>
                                            <ul class="dropdown-menu" aria-labelledby="exportOptionsDropdown">
                                                <li><button class="dropdown-item" type="button"
                                                        onclick="exportTableData('admin_seller_notification_table','csv')">{{ labels('admin_labels.csv', 'CSV') }}</button>
                                                </li>
                                                <li><button class="dropdown-item" type="button"
                                                        onclick="exportTableData('admin_seller_notification_table','json')">{{ labels('admin_labels.json', 'JSON') }}</button>
                                                </li>
                                                <li><button class="dropdown-item" type="button"
                                                        onclick="exportTableData('admin_seller_notification_table','sql')">{{ labels('admin_labels.sql', 'SQL') }}</button>
                                                </li>
                                                <li><button class="dropdown-item" type="button"
                                                        onclick="exportTableData('admin_seller_notification_table','excel')">{{ labels('admin_labels.excel', 'Excel') }}</button>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <button type="button" class="btn btn-outline-primary btn-sm delete_selected_data"
                                    data-table-id="admin_seller_notification_table"
                                    data-delete-url="{{ route('notifications.delete') }}">{{ labels('admin_labels.delete_selected', 'Delete Selected') }}</button>
                            </div>
                            <div class="col-md-12">
                                <div class="pt-0">
                                    <div class="table-responsive">
                                        <table class='table' id="admin_seller_notification_table" data-toggle="table"
                                            data-loading-template="loadingTemplate"
                                            data-url="{{ route('admin.seller_notifications.list') }}"
                                            data-click-to-select="true" data-side-pagination="server"
                                            data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"
                                            data-search="false" data-show-columns="false" data-show-refresh="false"
                                            data-trim-on-search="false" data-sort-name="id" data-sort-order="desc"
                                            data-mobile-responsive="true" data-toolbar="" data-show-export="false"
                                            data-maintain-selected="true" data-export-types='["txt","excel"]'
                                            data-query-params="queryParams">
                                            <thead>
                                                <tr>
                                                    <th data-checkbox="true" data-field="delete-checkbox">
                                                        <input name="select_all" type="checkbox">
                                                    </th>
                                                    <th data-field="id" data-sortable="true">
                                                        {{ labels('admin_labels.id', 'ID') }}
                                                    </th>
                                                    <th data-field="title" data-disabled="1" data-sortable="false">
                                                        {{ labels('admin_labels.title', 'Title') }}
                                                    </th>
                                                    <th data-field="type" data-sortable="false">
                                                        {{ labels('admin_labels.type', 'Type') }}
                                                    </th>
                                                    <th class="d-flex justify-content-center" data-field="image"
                                                        data-sortable="false" class="col-md-5">
                                                        {{ labels('admin_labels.image', 'Image') }}
                                                    </th>
                                                    <th data-field="link" data-sortable="false" class="col-md-5">
                                                        {{ labels('admin_labels.link', 'Link') }}
                                                    </th>
                                                    <th data-field="message" data-sortable="false">
                                                        {{ labels('admin_labels.message', 'Message') }}
                                                    </th>
                                                    <th data-field="send_to" data-sortable="false">
                                                        {{ labels('admin_labels.title', 'Title') }}
                                                    </th>

                                                    <th data-field="users" data-sortable="false">
                                                        {{ labels('admin_labels.users', 'Users') }}
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
@endsection
