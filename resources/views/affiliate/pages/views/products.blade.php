@extends('affiliate/layout')
@section('title')
    {{ labels('admin_labels.products', 'Products') }}
@endsection
@php
    use App\Services\TranslationService;
    use App\Services\MediaService;
    use App\Models\Category;
    use App\Models\Product;
    use App\Models\Tax;
@endphp
@section('content')
    <x-affiliate.breadcrumb :title="labels('admin_labels.manage_products', 'Manage Products')" :subtitle="labels('admin_labels.track_and_manage_products', 'Track and manage products with power and simplicity')" :breadcrumbs="[
        [
            'label' => app(TranslationService::class)->getDynamicTranslation(
                Category::class,
                'name',
                $category->id,
                $languageCode,
            ),
        ],
        ['label' => labels('admin_labels.products', 'Products')],
    ]" />

    <div class="row g-2">
        @forelse($products as $product)
            @php
                $variant = $product->firstVariant;

                $taxRate = 0;
                if ($variant && !$variant->price_inclusive_tax && !empty($variant->tax)) {
                    $taxIds = explode(',', $variant->tax);
                    $taxRate = Tax::whereIn('id', $taxIds)->sum('percentage');
                }

                $basePrice =
                    $variant && $variant->special_price && $variant->special_price < $variant->price
                        ? $variant->special_price
                        : $variant->price ?? 0;

                $finalPrice =
                    $variant && $variant->price_inclusive_tax ? $basePrice : $basePrice + ($basePrice * $taxRate) / 100;

                $commissionPercent = $category->affiliate_commission ?? 0;
                $profit = ($finalPrice * $commissionPercent) / 100;
            @endphp

            <div class="col-xxl-2 col-lg-3 col-md-4 col-sm-6 col-6 d-flex">
    <div class="card flex-fill text-center p-2">
        <div class="col-2 affiliate_categories_image justify-content-center mx-auto mb-2 position-relative">
            <img src="{{ app(MediaService::class)->getMediaImageUrl($product->image) }}"
                 alt="{{ app(TranslationService::class)->getDynamicTranslation(Product::class, 'name', $product->id, $languageCode) }}"
                 class="w-100 h-100 object-fit-contain">
        </div>

        <div class="card-body p-2 d-flex flex-column">
            <h6 class="card-title small text-truncate">
                {{ app(TranslationService::class)->getDynamicTranslation(Product::class, 'name', $product->id, $languageCode) }}
            </h6>

            @if ($variant)
                <p class="mb-1 small mt-auto">
                    @if ($variant->special_price && $variant->special_price < $variant->price)
                        <span class="text-success fw-bold">
                            {{ $currency . number_format($finalPrice, 2) }}
                        </span>
                        <span class="text-muted text-decoration-line-through">
                            {{ $currency . number_format($variant->price, 2) }}
                        </span><br>
                        <span class="text-danger">
                            ({{ $currency . number_format($variant->price - $variant->special_price, 2) }} off)
                        </span>
                    @else
                        <span class="fw-bold">
                            {{ $currency . number_format($finalPrice, 2) }}
                        </span>
                    @endif
                </p>
            @else
                <p class="text-muted small mt-auto">No variant</p>
            @endif

            <p class="card-text small">
                <strong>{{ labels('admin_labels.profit', 'Profit') }}:</strong>
                {{ $currency . number_format($profit, 2) }}{{ $commissionPercent ? ' (' . $commissionPercent . '%)' : '' }}
            </p>

            <div class="d-grid mt-auto">
                <button class="btn btn-outline-secondary btn-sm generate-token-btn"
                    data-product-id="{{ $product->id }}"
                    data-product-name="{{ app(TranslationService::class)->getDynamicTranslation(Product::class, 'name', $product->id, $languageCode) }}"
                    data-product-slug="{{ $product->slug }}"
                    data-category-id="{{ $category->id }}"
                    data-affiliate-uuid="{{ auth()->user()->affiliateUser->uuid }}">
                    <i class="bx bx-link me-1"></i> {{ labels('admin_labels.copy_link', 'Copy Link') }}
                </button>
            </div>
        </div>
    </div>
</div>

        @empty
            <div class="d-flex flex-column justify-content-center align-items-center w-100">
                <img src="{{ app(MediaService::class)->getImageUrl('system_images/no_data_found.png') }}" alt="No products"
                    class="mb-3">
                <h5 class="text-muted">Oops! No affiliate products found in this category.</h5>
                <p class="text-secondary small">Try exploring other categories or check back later.</p>
                <a href="{{ route('affiliate.home') }}" class="btn btn-outline-primary mt-2">Go to Home</a>
            </div>
        @endempty
</div>
@endsection
