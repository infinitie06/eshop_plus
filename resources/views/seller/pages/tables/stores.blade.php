@extends('seller/layout')
@section('title')
    {{ labels('admin_labels.stores', 'Stores') }}
@endsection
@section('content')
    <section class="main-content">
        <div class="row">
            <x-seller.breadcrumb :title="labels('admin_labels.stores', 'Stores')" :subtitle="labels('admin_labels.manage_seller_stores', 'Manage your stores')" :breadcrumbs="[
                ['label' => labels('admin_labels.stores', 'Stores')],
            ]" />

            <section class="overview-data">
                <div class="card content-area p-4">
                    <div class="row align-items-center d-flex heading mb-4">
                        <div class="col-md-12">
                            <div class="row">
                                <div class="col-md-12 col-xl-6">
                                    <h4>{{ labels('admin_labels.stores', 'Stores') }}</h4>
                                </div>
                                @if ($stores->isEmpty())
                                    <div class="col-md-12 col-xl-6 text-end">
                                        <a href="{{ route('seller.stores.create') }}" class="btn btn-primary">
                                            <i class='bx bx-plus me-1'></i>
                                            {{ labels('admin_labels.create_store', 'Create Store') }}
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>{{ labels('admin_labels.id', 'ID') }}</th>
                                            <th>{{ labels('admin_labels.store_name', 'Store Name') }}</th>
                                            <th>{{ labels('admin_labels.store_url', 'Store URL') }}</th>
                                            <th>{{ labels('admin_labels.status', 'Status') }}</th>
                                            <th>{{ labels('admin_labels.action', 'Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($stores as $store)
                                            <tr>
                                                <td>{{ $store->id }}</td>
                                                <td>{{ $store->store_name }}</td>
                                                <td>{{ $store->store_url }}</td>
                                                <td>
                                                    @if ($store->status == 1)
                                                        <span class="badge bg-label-success">
                                                            {{ labels('admin_labels.active', 'Active') }}
                                                        </span>
                                                    @else
                                                        <span class="badge bg-label-secondary">
                                                            {{ labels('admin_labels.deactive', 'Deactive') }}
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($store->status == 0)
                                                        <button type="button"
                                                            class="btn btn-sm btn-success seller-activate-store me-1"
                                                            data-url="{{ route('seller.store.activate') }}"
                                                            data-store-id="{{ $store->store_id }}">
                                                            {{ labels('admin_labels.make_active', 'Make Active') }}
                                                        </button>
                                                    @endif

                                                    @if ($store->status == 1)
                                                        <button type="button"
                                                            class="btn btn-sm btn-warning seller-deactivate-store me-1"
                                                            data-url="{{ route('seller.store.deactivate') }}"
                                                            data-store-id="{{ $store->store_id }}">
                                                            {{ labels('admin_labels.deactivate', 'Deactivate') }}
                                                        </button>
                                                    @endif

                                                    <button type="button"
                                                        class="btn btn-sm btn-danger seller-delete-store"
                                                        data-url="{{ route('seller.store.delete') }}"
                                                        data-store-id="{{ $store->store_id }}">
                                                        {{ labels('admin_labels.delete', 'Delete') }}
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center py-5">
                                                    <div class="d-flex flex-column align-items-center">
                                                        <i class='bx bx-store' style="font-size: 64px; color: #ccc; margin-bottom: 20px;"></i>
                                                        <h5 class="mb-2">{{ labels('admin_labels.no_store_found_for_seller', 'No store found for this seller.') }}</h5>
                                                        <p class="text-muted mb-3">{{ labels('admin_labels.create_store_to_start_selling', 'Create a store to start selling your products.') }}</p>
                                                        <a href="{{ route('seller.stores.create') }}" class="btn btn-primary">
                                                            <i class='bx bx-plus me-1'></i>
                                                            {{ labels('admin_labels.create_store', 'Create Store') }}
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </section>
@endsection


