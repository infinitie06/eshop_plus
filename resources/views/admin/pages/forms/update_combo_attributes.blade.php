@extends('admin/layout')
@section('title')
    {{ labels('admin_labels.update_Attributes', 'Update Attributes') }}
@endsection
@section('content')
    <x-admin.breadcrumb :title="labels('admin_labels.attributes', 'Attributes')" :subtitle="labels(
        'admin_labels.efficiently_manage_product_attributes_with_precision',
        'Efficiently Manage Product Attributes with Precision',
    )" :breadcrumbs="[
        ['label' => labels('admin_labels.combo_products', 'Combo Products')],
        ['label' => labels('admin_labels.update_Attributes', 'Update Attributes')],
    ]" />
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="mb-3">
                    {{ labels('admin_labels.update_Attributes', 'Update Attributes') }}
                </h5>
                <div class="form-group">
                    <form action="{{ url('/admin/combo_product_attributes/update/' . $attribute_data->id) }}"
                        enctype="multipart/form-data" method="POST" class="submit_form">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label"
                                for="basic-default-fullname">{{ labels('admin_labels.name', 'Name') }}
                                <i class="fa fa-info-circle text-secondary ms-1"
                                   data-bs-toggle="popover"
                                   data-bs-placement="right"
                                   data-bs-content="{{ labels('admin_labels.popover_attribute_name', 'Enter the attribute name, e.g. Size, Color.') }}"></i>
                            </label>
                            <input type="text" class="form-control" id="basic-default-fullname" placeholder="pizza"
                                name="name" value="{{ $attribute_data->name }}">

                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="attribute_values">
                                {{ labels('admin_labels.value_label', 'Value') }}
                                <i class="fa fa-info-circle text-secondary ms-1"
                                   data-bs-toggle="popover"
                                   data-bs-placement="right"
                                   data-bs-content="{{ labels('admin_labels.popover_attribute_value', 'Comma separated values for this attribute.') }}"></i>
                            </label>
                            <input type="text" class="form-control" readonly id="attribute_values"
                                placeholder="{{ labels('admin_labels.regular_small_medium_placeholder', 'Regular,Small,Medium') }}" name="value" value="{{ $attribute_values }}">

                        </div>
                        <div class="mb-3 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary submit_button">{{ labels('admin_labels.update_attribute_button', 'Update Attribute') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
