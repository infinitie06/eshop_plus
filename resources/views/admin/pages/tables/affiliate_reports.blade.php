@extends('admin/layout')
@section('title')
    {{ labels('admin_labels.affiliate_details', 'Affiliate Details') }}
@endsection
@section('content')
    <x-admin.breadcrumb :title="labels('admin_labels.affiliate_details', 'Affiliate Details')" :subtitle="labels('admin_labels.manage_affiliate_details', 'Manage Affiliate Details')" :breadcrumbs="[['label' => labels('admin_labels.affiliate_details', 'Affiliate Details')]]" />

    <section class="overview-data">
        <div class="card-body py-4">
            <div id="affiliateCardsAccordion" class="row g-4">
                @forelse($data as $index => $affiliate)
                    <div class="col-md-4 col-sm-6">
                        <div class="card shadow-lg border-0">
                            <div class="card-body d-flex flex-column justify-content-between">
                                @php
                                    $status = $affiliate['status'];
                                    $statusMap = [
                                        1 => ['label' => labels('admin_labels.active', 'Active'), 'class' => 'bg-success'],
                                        2 => ['label' => labels('admin_labels.rejected', 'Rejected'), 'class' => 'bg-danger'],
                                        0 => ['label' => labels('admin_labels.pending', 'Pending'), 'class' => 'bg-warning text-dark'],
                                    ];
                                    $statusInfo = $statusMap[$status] ?? [
                                        'label' => labels('admin_labels.unknown', 'Unknown'),
                                        'class' => 'bg-secondary',
                                    ];
                                @endphp
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="d-flex align-items-center">
                                        <img src="{{ $affiliate['profile_image'] }}" alt="{{ $affiliate['user_name'] }}"
                                            class="me-2" width="40" height="40">
                                        <h5 class="card-title mb-0">{{ $affiliate['user_name'] }}</h5>
                                    </div>

                                    <span class="badge {{ $statusInfo['class'] }} text-uppercase small">
                                        {{ $statusInfo['label'] }}
                                    </span>
                                </div>

                                <p class="text-muted small">
                                    <i class="bi bi-envelope"></i> {{ $affiliate['email'] }}
                                </p>

                                <ul class="list-unstyled small mb-3">
                                    <li><i class="bi bi-currency-rupee"></i> <strong>{{ labels('admin_labels.commission_label', 'Commission:') }}</strong>
                                        {{ $affiliate['commission'] }}</li>
                                    {{-- <li><i class="bi bi-mouse"></i> <strong>Clicks:</strong> {{ $affiliate['clicks'] }}</li> --}}
                                    <li><i class="bi bi-calendar-check"></i> <strong>{{ labels('admin_labels.joined_label', 'Joined:') }}</strong>
                                        {{ \Carbon\Carbon::parse($affiliate['created_at'])->format('M d, Y') }}</li>
                                </ul>
                                @php $collapseId = 'detailsCollapseCard_' . $affiliate['id']; @endphp
                                <div>
                                    <div class="bg-light border rounded px-3 py-2 mb-2 d-flex justify-content-between align-items-center"
                                        role="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}"
                                        aria-expanded="false" aria-controls="{{ $collapseId }}">
                                        <span class="text-dark fw-semibold">{{ labels('admin_labels.view_details', 'View Details') }}</span>
                                        <i class="bi bi-chevron-down small text-dark"></i>
                                    </div>
                                    <div class="collapse" id="{{ $collapseId }}">
                                        <div class="mt-3">
                                            <strong>{{ labels('admin_labels.categories_label', 'Categories:') }}</strong>
                                            <div class="d-flex flex-wrap gap-1 mb-2 mt-2"
                                                id="category-wrapper-{{ $index }}">
                                                @foreach ($affiliate['categories'] as $i => $category)
                                                    @php $categoryName = $category['name'] ?? ''; @endphp

                                                    @if ($i < 4)
                                                        <div class="category-thumb border rounded d-flex align-items-center justify-content-center"
                                                            title="{{ $categoryName }}">
                                                            <img src="{{ $category['image'] }}" alt="{{ $categoryName }}"
                                                                class="img-fluid" loading="lazy">
                                                        </div>
                                                    @else
                                                        <div class="category-thumb border rounded d-flex align-items-center justify-content-center d-none"
                                                            title="{{ $categoryName }}">
                                                            <img src="{{ $category['image'] }}" alt="{{ $categoryName }}"
                                                                class="img-fluid" loading="lazy">
                                                        </div>
                                                    @endif
                                                @endforeach

                                                @if (count($affiliate['categories']) > 4)
                                                    <button type="button"
                                                        class="btn btn-sm btn-light px-2 py-1 show-more-categories-btn"
                                                        data-index="{{ $index }}">
                                                        +{{ count($affiliate['categories']) - 4 }} more
                                                    </button>
                                                @endif
                                            </div>
                                            <strong>{{ labels('admin_labels.promoted_products', 'Promoted Products:') }}</strong>
                                            <div class="d-flex flex-wrap gap-1 mt-2"
                                                id="product-wrapper-{{ $index }}">
                                                @foreach ($affiliate['products'] as $i => $product)
                                                    @if ($i < 4)
                                                        <div class="product-thumb border rounded d-flex align-items-center justify-content-center"
                                                            title="{{ $product['name'] }}">
                                                            <img src="{{ $product['image'] }}"
                                                                alt="{{ $product['name'] }}" class="img-fluid"
                                                                loading="lazy">
                                                        </div>
                                                    @else
                                                        <div class="product-thumb border rounded d-flex align-items-center justify-content-center d-none"
                                                            data-index="{{ $index }}"
                                                            title="{{ $product['name'] }}">
                                                            <img src="{{ $product['image'] }}"
                                                                alt="{{ $product['name'] }}" class="img-fluid"
                                                                loading="lazy">
                                                        </div>
                                                    @endif
                                                @endforeach

                                                @if (count($affiliate['products']) > 4)
                                                    <button type="button"
                                                        class="btn btn-sm btn-light px-2 py-1 show-more-products-btn"
                                                        data-index="{{ $index }}">
                                                        +{{ count($affiliate['products']) - 4 }} more
                                                    </button>
                                                @endif
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <p>{{ labels('admin_labels.no_affiliate_details_found', 'No affiliate details found.') }}</p>
                @endforelse
            </div>
        </div>
    </section>
@endsection
