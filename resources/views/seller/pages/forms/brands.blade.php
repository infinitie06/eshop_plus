@extends('seller/layout')
@section('title')
    {{ labels('admin_labels.brands', 'Brands') }}
@endsection

@section('content')
    <x-seller.breadcrumb
        :title="labels('admin_labels.brands', 'Brands')"
        :subtitle="labels('admin_labels.elevate_your_store_with_seamless_brand_management','Elevate Your Store with Seamless Brand Management')"
        :breadcrumbs="[['label' => labels('admin_labels.brands', 'Brands')]]"
    />

    <div class="row">

        {{-- ADD BRAND FORM --}}
        <div class="col-md-12 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">
                        {{ labels('admin_labels.add_brand', 'Add Brand') }}
                        <i class="mx-2 fa fa-info-circle text-secondary" data-bs-toggle="popover"
                            data-bs-placement="right"
                            data-bs-content="You can request a brand to the admin, and the admin can approve it. You cannot add your own brand.">
                        </i>
                    </h5>
                </div>

                <form action="{{ route('seller.brands.store') }}" class="submit_form"
                    enctype="multipart/form-data" method="POST">
                    @csrf

                    <div class="card-body pt-0">

                        {{-- Language Tabs --}}
                        <ul class="nav nav-tabs" id="brandTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="language-nav-link nav-link active" id="tab-en" data-bs-toggle="tab"
                                    data-bs-target="#content-en" type="button" role="tab"
                                    aria-controls="content-en" aria-selected="true">
                                    {{ labels('admin_labels.default', 'Default') }}
                                </button>
                            </li>
                            <x-language.multi_language_tabs :languages="$languages" />
                        </ul>

                        {{-- Tab Content --}}
                        <div class="tab-content mt-3" id="brandTabsContent">

                            {{-- English Name --}}
                            <div class="tab-pane fade show active" id="content-en" role="tabpanel">
                                <div class="mb-3">
                                    <label class="form-label">
                                        {{ labels('admin_labels.name', 'Name') }}
                                        <span class="text-asterisks">*</span>
                                    </label>
                                    <input type="text" name="brand_name" class="form-control"
                                        placeholder="{{ labels('admin_labels.brand_name', 'Brand Name') }}">
                                </div>
                            </div>

                            {{-- Other Languages --}}
                            <x-language.multi_language_inputs
                                :languages="$languages"
                                nameKey="admin_labels.name"
                                nameValue="Name"
                                inputName="translated_brand_name"
                            />

                        </div>

                        {{-- Image upload --}}
                        <label class="form-label">{{ labels('admin_labels.image', 'Image') }}
                            <span class="text-asterisks">*</span>
                        </label>

                        <div class="row form-group">
                            <div class="col-md-6 file_upload_box border file_upload_border mt-4">
                                <div class="mt-2 text-center p-3">
                                    <a class="media_link" data-input="image"
                                        data-isremovable="0" data-is-multiple-uploads-allowed="0"
                                        data-bs-toggle="modal" data-bs-target="#media-upload-modal">
                                        <h4><i class='bx bx-upload'></i> Upload</h4>
                                    </a>
                                    <p class="image_recommendation">
                                        Recommended Size: 180 x 180 pixels
                                    </p>
                                </div>
                            </div>

                            <div class="col-md-6 mt-3 image-upload-section">
                                <div class="p-3 bg-white rounded text-center image d-none"></div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="reset" class="btn mx-2 reset_button">
                                {{ labels('admin_labels.reset', 'Reset') }}
                            </button>
                            <button type="submit" class="btn btn-primary submit_button">
                                {{ labels('admin_labels.add_brand', 'Add Brand') }}
                            </button>
                        </div>

                    </div>
                </form>
            </div>
        </div>

        {{-- REQUESTED BRAND LIST (RIGHT SIDE TABLE) --}}
        <div class="col-md-12 col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ labels('admin_labels.requested_brands', 'Requested Brands') }}</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive mt-2">

                        <table id="seller_requested_brand_table"
                            data-toggle="table"
                            data-loading-template="loadingTemplate"
                            data-url="{{ route('seller.brands.list') }}"
                            data-click-to-select="true"
                            data-side-pagination="server"
                            data-pagination="true"
                            data-page-list="[5, 10, 20, 50, 100, 200]"
                            data-search="true"
                            data-show-columns="false"
                            data-show-refresh="true"
                            data-trim-on-search="false"
                            data-sort-name="id"
                            data-sort-order="desc"
                            data-mobile-responsive="true"
                            data-maintain-selected="true"
                            data-show-export="false"
                            data-query-params="requested_brand_query_params">

                            <thead class="table-light">
                                <tr>
                                    <th data-field="id" data-sortable="true">{{ labels('admin_labels.id', 'ID') }}</th>
                                    <th data-field="name">{{ labels('admin_labels.name', 'Name') }}</th>
                                    <th data-field="image">{{ labels('admin_labels.image', 'Image') }}</th>

                                </tr>
                            </thead>
                        </table>

                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        $.extend($.fn.bootstrapTable.defaults, {
            formatSearch: function () {
                return '{{ labels("seller_labels.search", "Search") }}';
            }
        });
    </script>
@endsection
