<div id="page-content">
    <x-utility.breadcrumbs.breadcrumbTwo :$bread_crumb />
    {{-- @dd($seller) --}}
    @php
        use App\Services\MediaService;
        $img = app(MediaService::class)->getMediaImageUrl($seller[0]->logo, 'SELLER_IMG_PATH');
        $img = app(MediaService::class)->dynamic_image($img, 230);
        use App\Services\StoreService;
    @endphp
    <div class="container-fluid h-100">
        <div class="orders-card mt-0 h-100 mb-2">
            <div class="row mt-3">
                <div class="col-lg-2 col-md-3 col-sm-4">
                    <div class="product-img mb-3 mb-sm-0">
                        <img class="rounded-0 blur-up lazyload" data-src="{{ $img }}" src="{{ $img }}"
                            alt="product" title="" width="545" height="700" />
                    </div>
                </div>
                <div class="col-lg-10 col-md-9 col-sm-8">
                    <div class="tracking-detail d-flex-center">
                        <ul>
                            <li>
                                <div class="left"><span>{{ labels('front_messages.seller', 'Seller') }}</span></div>
                                <div class="right"><span>{{ $seller_details['username'] ?? '' }}</span></div>
                            </li>
                            <li>
                                <div class="left"><span>{{ labels('front_messages.products', 'Products') }}</span>
                                </div>
                                <div class="right"><span>{{ count($products['product']) }}
                                        {{ labels('front_messages.products', 'Products') }}</span></div>
                            </li>
                            <li>
                                <div class="left"><span>{{ labels('front_messages.ratings', 'Ratings') }}</span></div>
                                <div class="right"><span>{{ $seller[0]->rating }}</span> <ion-icon
                                        name="star"></ion-icon></div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="top-sec d-flex-justify-center justify-content-between my-4">
                <h2 class="mb-0">{{ labels('front_messages.products', 'Products') }}</h2>
            </div>
            @php
                $store_settings = app(StoreService::class)->getStoreSettings();
            @endphp
            @if (count($products['product']) >= 1)
                <div
                    class="grid-products grid-view-items {{ ($store_settings['products_display_style_for_web'] ?? '') == 'products_display_style_for_web_3' ? 'pro-hover3' : '' }}">
                    <div class="row col-row product-options row-cols-lg-4 row-cols-md-3 row-cols-sm-3 row-cols-2">
                        @foreach ($products['product'] as $details)
                            @php
                                $store_settings = app(StoreService::class)->getStoreSettings();
                                $component = getProductDisplayComponent($store_settings);
                                $details = (object) $details;
                            @endphp

                            <x-dynamic-component :component="$component" :details="$details" />
                        @endforeach
                    </div>
                </div>
            @else
                @php
                    $title = labels('front_messages.seller_dont_have_any_products', 'Seller Don\'t Have Any Products');
                @endphp
                <x-utility.others.not-found :$title />
            @endif
        </div>
        <div class="pt-2">{!! $products['links'] !!}</div>
    </div>
    @php
        $store_slug = session('store_slug') ?? '';
        $system_settings = app(\App\Services\SettingService::class)->getSettings('system_settings', true);
        $system_settings = $system_settings ? json_decode($system_settings, true) : null;
        $scheme = str_replace('://', '', $system_settings['deep_link_scheme'] ?? 'eshop');
        $host = $system_settings['deep_link_host'] ?? 'eshop-pro.eshopweb.store';
        $deepLinkUrl =
            $scheme .
            '://' .
            $host .
            '/seller/' .
            ($seller[0]->seller_id ?? '') .
            ($store_slug ? '?store=' . $store_slug : '');
    @endphp
    <script>
        var sellerId = "{{ $seller[0]->seller_id }}";
        var deepLinkUrl = "{{ $deepLinkUrl }}";
    </script>
    @include('partials.deep-link-bottom-sheet', ['deepLinkUrl' => $deepLinkUrl])
</div>
