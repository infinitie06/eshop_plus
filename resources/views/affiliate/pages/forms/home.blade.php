@extends('affiliate/layout')
@section('title')
    {{ labels('admin_labels.home', 'Home') }}
@endsection
@php
    use App\Services\TranslationService;
    use App\Services\MediaService;
    use App\Models\Category;
    use Illuminate\Support\Str;
@endphp
@section('content')
    <x-affiliate.breadcrumb :title="labels('admin_labels.dashboard', 'Dashboard')" :subtitle="labels('admin_labels.all_information_about_your_statistics', 'All Information About your statictics')" :breadcrumbs="[]" />
    <section class="dashboard overview-data">
        <section class="my-4">
            <div class="card">
                <div class="card-body">
                    <div class="mb-4 d-flex justify-content-between align-items-center flex-wrap">
                        <div class="d-flex align-items-center">
                            <h5 class="mb-0 me-2">{{ labels('admin_labels.total_profit', 'Total Profit') }}</h5>
                            <button class="btn btn-sm p-0 border-0 bg-transparent">
                                <i class="bi bi-question-circle"></i>
                            </button>
                        </div>
                        <h4 class="text-dark fw-bold mb-0">{{ $currency }}{{ $earningData['total_profit'] }}</h4>
                    </div>
                    <div class="d-flex justify-content-end mb-3">
                        <i class="fas fa-info-circle text-secondary ms-1" data-bs-toggle="modal"
                            data-bs-target="#earningsModal">
                        </i>
                    </div>
                    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-4">
                        <!-- Pending -->
                        <div class="col">
                            <div class="d-flex justify-content-between align-items-center border rounded p-3 h-100">
                                <div class="d-flex align-items-center">
                                    <div class="icon-circle bg-warning text-warning me-2">
                                        <i class="bx bx-time-five "></i>
                                    </div>
                                    <span class="fw-semibold">{{ labels('admin_labels.pending', 'Pending') }}</span>
                                </div>
                                <span class="fw-bold">{{ $currency }}{{ $earningData['pending'] }}</span>
                            </div>
                        </div>

                        <!-- Confirmed -->
                        <div class="col">
                            <div class="d-flex justify-content-between align-items-center border rounded p-3 h-100">
                                <div class="d-flex align-items-center">
                                    <div class="icon-circle bg-success text-success me-2">
                                        <i class="bx bx-check-circle "></i>
                                    </div>
                                    <span class="fw-semibold">{{ labels('admin_labels.confirmed', 'Confirmed') }}</span>
                                </div>
                                <span class="fw-bold">{{ $currency }}{{ $earningData['confirmed'] }}</span>
                            </div>
                        </div>

                        <!-- Requested -->
                        <div class="col">
                            <div class="d-flex justify-content-between align-items-center border rounded p-3 h-100">
                                <div class="d-flex align-items-center">
                                    <div class="icon-circle bg-primary text-primary me-2">
                                        <i class="bx bx-envelope "></i>
                                    </div>
                                    <span class="fw-semibold">{{ labels('admin_labels.requested', 'Requested') }}</span>
                                </div>
                                <span class="fw-bold">{{ $currency }}{{ $earningData['requested'] }}</span>
                            </div>
                        </div>

                        <!-- Paid -->
                        <div class="col">
                            <div class="d-flex justify-content-between align-items-center border rounded p-3 h-100">
                                <div class="d-flex align-items-center">
                                    <div class="icon-circle bg-info text-info me-2">
                                        <i class="bx bx-wallet"></i>
                                    </div>
                                    <span class="fw-semibold">{{ labels('admin_labels.paid', 'Paid') }}</span>
                                </div>
                                <span class="fw-bold">{{ $currency }}{{ $earningData['paid'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section>
            <div class="col-md-12">
                <div class="row">
                    <!-- CATEGORY CHART (with dropdown) -->
                    <div class="col-md-6 d-flex">
                        <div class="card flex-fill d-flex flex-column">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">{{ labels('admin_labels.affiliate_category_earnings', 'Affiliate Category Earnings') }}</h5>
                                <select id="chartModeSelector" class="form-select form-select-sm w-auto">
                                    <option value="top" selected>{{ labels('admin_labels.top_five', 'Top 5') }}</option>
                                    <option value="all">{{ labels('admin_labels.all', 'All') }}</option>
                                </select>
                            </div>
                            <div class="card-body flex-grow-1 d-flex">
                                <div id="categoryChart" data-url="{{ route('affiliate.category_earnings') }}"
                                    class="flex-fill w-100 h-100">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MONTHLY CHART -->
                    <div class="col-md-6 d-flex">
                        <div class="card flex-fill d-flex flex-column">
                            <div class="card-body flex-grow-1 d-flex">
                                <div id="monthlyChart" data-url="{{ route('affiliate.monthly_earnings') }}"
                                    class="flex-fill w-100 h-100">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </section>
        <section class="my-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card p-2">
                        <h4 class="mb-4">{{ labels('admin_labels.most_selling_products', 'Most Selling Products') }}</h4>
                        @foreach ($topProducts as $product)
                            {{-- @dd($product); --}}
                            <div class="row g-2 align-items-center mb-3">
                                <!-- Image -->
                                <div class="col-auto">
                                    <div
                                        class="d-flex align-items-center justify-content-center rounded overflow-hidden affiliate_most_selling_products_image">
                                        <img src="{{ $product['image'] }}" class="img-fluid w-100 h-100"
                                            alt="{{ $product['product'] }}">
                                    </div>
                                </div>

                                <!-- Product Name & Category -->
                                <div class="col">
                                    <h6 class="mb-1 fw-semibold">{{ $product['product'] }}</h6>
                                    <small class="text-muted">{{ $product['category'] ?? 'N/A' }}</small>
                                </div>

                                <!-- Sales -->
                                <div class="col-auto text-end">
                                    <span class="badge bg-primary">
                                        {{ labels('admin_labels.sales', 'Sales') }}: {{ $product['quantity_sold'] }}
                                    </span>
                                </div>
                            </div>
                        @endforeach

                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card p-2">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4>{{ labels('admin_labels.most_selling_category', 'Most Selling Category') }}</h4>
                            <select class="form-select w-auto" id="most_selling_category_filter">
                                <option value="weekly">{{ labels('admin_labels.weekly', 'Weekly') }}</option>
                                <option value="monthly" selected>{{ labels('admin_labels.monthly', 'Monthly') }}</option>
                                <option value="yearly">{{ labels('admin_labels.yearly', 'Yearly') }}</option>
                            </select>
                        </div>

                        <div class="affiliate-category-list p-3">
                        </div>
                    </div>
                </div>

            </div>
        </section>
        <section>
            <div class="card p-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">{{ labels('admin_labels.categories', 'Categories') }}</h5>
                    <a href="{{ route('affiliate.categories') }}"
                        class="small">{{ labels('admin_labels.view_all', 'View All') }}</a>
                </div>
                <div class="row g-2">
                    @foreach ($categories->take(10) as $category)
                        <div class="col-2 affiliate_categories_image">
                            <a href="{{ route('affiliate.products.category', ['id' => $category->id]) }}"
                                title="{{ app(TranslationService::class)->getDynamicTranslation(Category::class, 'name', $category->id, $languageCode) }}">
                                <img src="{{ app(MediaService::class)->getMediaImageUrl($category->image) }}"
                                    alt="{{ app(TranslationService::class)->getDynamicTranslation(Category::class, 'name', $category->id, $languageCode) }}"
                                   class="w-100 h-100 object-fit-cover category-image">
                            </a>
                            <span class="d-block small text-center mt-2">
                                {{ Str::limit(app(TranslationService::class)->getDynamicTranslation(Category::class, 'name', $category->id, $languageCode), 20, '...') }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    </section>
    <div class="modal fade" id="earningsModal" tabindex="-1" aria-labelledby="earningsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">

                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="earningsModalLabel">
                        <i class="bx bx-info-circle me-1"></i> Profit Status Explained
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    {{-- Total Profit --}}
                    <div class="d-flex align-items-start mb-4">
                        <i class="bx bx-dollar-circle fs-2 text-success me-3"></i>
                        <div>
                            <h6 class="mb-1 fw-bold">Total Profit</h6>
                            <p class="mb-0 text-muted">
                                This is the total commission you've earned from orders that were successfully delivered and
                                settled.
                            </p>
                        </div>
                    </div>

                    {{-- Pending --}}
                    <div class="d-flex align-items-start mb-4">
                        <i class="bx bx-time-five fs-2 text-warning me-3"></i>
                        <div>
                            <h6 class="mb-1 fw-bold">Pending</h6>
                            <p class="mb-0 text-muted">
                                This commission is tracked but not yet confirmed. It will be verified after the order is
                                delivered
                                and the return or cancellation period ends.
                            </p>
                        </div>
                    </div>

                    {{-- Confirmed --}}
                    <div class="d-flex align-items-start mb-4">
                        <i class="bx bx-check-circle fs-2 text-primary me-3"></i>
                        <div>
                            <h6 class="mb-1 fw-bold">Confirmed</h6>
                            <p class="mb-0 text-muted">
                                This commission is confirmed and ready to be withdrawn. You can now request a payment for
                                this
                                amount.
                            </p>
                        </div>
                    </div>

                    {{-- Requested --}}
                    <div class="d-flex align-items-start mb-4">
                        <i class="bx bx-envelope fs-2 text-info me-3"></i>
                        <div>
                            <h6 class="mb-1 fw-bold">Requested</h6>
                            <p class="mb-0 text-muted">
                                You've submitted a payment request for this amount. It is currently being reviewed or
                                processed.
                            </p>
                        </div>
                    </div>

                    {{-- Paid --}}
                    <div class="d-flex align-items-start">
                        <i class="bx bx-wallet fs-2 text-success me-3"></i>
                        <div>
                            <h6 class="mb-1 fw-bold">Paid</h6>
                            <p class="mb-0 text-muted">
                                This amount has already been paid to you and credited to your account.
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
