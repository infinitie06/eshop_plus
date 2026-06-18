@extends('affiliate/layout')
@section('title')
    {{ labels('admin_labels.categories', 'Categories') }}
@endsection
@php
    use App\Services\TranslationService;
    use App\Services\MediaService;
    use App\Models\Category;
@endphp
@section('content')
    <x-affiliate.breadcrumb :title="labels('admin_labels.manage_products', 'Manage Categories')" :subtitle="labels(
        'admin_labels.track_and_manage_categories',
        'Track and manage categories with power and simplicity',
    )" :breadcrumbs="[
        [
            'label' => labels('admin_labels.manage_categories', 'Manage Categories'),
        ],
    ]" />
    <div class="row g-4">
        @foreach ($categories as $category)
            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                <div class="category-hover-card position-relative overflow-hidden rounded shadow-sm">
                    <a href="{{ route('affiliate.products.category', ['id' => $category->id]) }}">
                        <img src="{{ app(MediaService::class)->getMediaImageUrl($category->image) }}"
                            alt="{{ app(TranslationService::class)->getDynamicTranslation(Category::class, 'name', $category->id, $languageCode) }}"
                            class="w-100 h-100 object-fit-cover category-image">
                        <div
                            class="category-overlay position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center">
                            <h6 class="text-white text-center m-0 fw-semibold category-name">
                                {{ Str::limit(app(TranslationService::class)->getDynamicTranslation(Category::class, 'name', $category->id, $languageCode), 25, '...') }}
                            </h6>
                        </div>
                    </a>
                </div>
            </div>
        @endforeach
    </div>
@endsection
