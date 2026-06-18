@php
    use App\Services\StoreService;
    use App\Services\TranslationService;
    $store_settings = app(StoreService::class)->getStoreSettings();
    $categories = $categories['categories'];
    $language_code = app(TranslationService::class)->getLanguageCode();
    $category_section_title = $store_settings['category_section_title'] ?? 'Categories';
    // dd(count($categories));
@endphp
<div>{{-- safety and security  --}}
    <x-utility.safety_and_security.styleFour :$settings />
    {{-- end safety and security  --}}

    {{-- category section  --}}

    @if (is_array($categories) && count($categories) >= 4)
        <section class="section collection-banners four-bnr py-0">
            <div class="container-fluid px-0">
                <div class="section-header d-none">
                    <h2>
                        {{ is_array($category_section_title) ? $category_section_title[$language_code] ?? ($category_section_title['en'] ?? reset($category_section_title)) : $category_section_title }}
                    </h2>
                </div>

                <div class="collection-banner-grid">
                    <div class="collection-banner-items home-theme-six-slick-slider">
                        @foreach ($categories as $category)
                            <div class="slick-slide">
                                <div class="collection-item">
                                    <a wire:navigate
                                        href="{{ customUrl('categories/' . $category->slug . '/products') }}"
                                        data-link="{{ customUrl('categories/' . $category->slug . '/products') }}"
                                        class="zoom-scal">
                                        <div class="img">
                                            <img class="lazy w-100" src="{{ $category->banner }}"
                                                alt="{{ $category->name }}" title="{{ $category->name }}">
                                        </div>
                                        <div class="details middle-center p-lg-0">
                                            <div class="inner">
                                                <span class="btn btn-light btn-lg text-capitalize head-font">
                                                    {!! $category->name !!}
                                                    @if ($category->product_count > 0)
                                                        <sup>{{ $category->product_count }}</sup>
                                                    @endif
                                                </span>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- end category section  --}}
    {{-- first feature section  --}}
    @foreach ($sections as $count_key => $row)
        @if ($count_key == 0)
            <section class="section product-collection pb-0">
                <div class="container-fluid">
                    <x-utility.section_header.sectionHeaderOne :title="$row" />
                    <div class="row">
                        <div class="col-12 col-sm-12 col-md-6 col-lg-6 product-collection-bnr mb-4 mb-md-0">
                            <div class="collection-banner-grid two-bnr h-100">
                                <div class="row h-100">
                                    <div class="col-12 col-sm-12 col-md-12 col-lg-12 collection-banner-item">
                                        <div class="collection-item h-100 home_theme_six_feature_section_image">
                                            <a wire:navigate
                                                href="{{ customUrl('section/' . $row->slug . '/' . $row->id . '/' . ($row->product_type == 'custom_combo_products' ? 'combo-' : '') . 'products') }}"
                                                class="zoom-scal clr-none h-100">
                                                <div class="img h-100">
                                                    <img class="w-100 h-100 blur-up lazyloaded"
                                                        data-src="{{ $row->banner_image }}"
                                                        src="{{ $row->banner_image }}" alt="{{ $row->title }}"
                                                        title="{{ $row->title }}">
                                                </div>
                                                <div class="details middle-center">
                                                    <div class="inner text-center">
                                                        <h3 class="title">{{ $row->title }}</h3>
                                                        <p class="subtitle">{{ $row->short_description }}</p>
                                                        <span
                                                            class="btn btn-primary rounded-pill">{{ labels('front_messages.shop_now', 'Shop Now') }}</span>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-12 col-md-6 col-lg-6 product-collection-grd">
                            <div class="grid-products grid-view-items">
                                <div class="row col-row row-cols-lg-2 row-cols-md-2 row-cols-sm-2 row-cols-2">
                                    @foreach (collect($row->product_details)->take(4) as $details)
                                        @php
                                            $component = getProductDisplayComponent($store_settings);
                                            $details = (object) $details;
                                        @endphp

                                        <x-dynamic-component :component="$component" :details="$details" />
                                    @endforeach

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif
    @endforeach
    {{-- end feature section --}}
    {{-- offer section --}}
    @if (count($offers) > 1)
        {{-- @dd('here'); --}}
        <section class="section collection-banners two-one-bnr pb-0">
            <div class="container-fluid px-0">
                <div class="collection-banner-grid two-bnr">
                    <div class="row g-0">
                        <div class="col-12 col-sm-12 col-md-6 col-lg-6 collection-banner-item mb-4 mb-md-0">
                            <div class="collection-item sp-col">
                                <a href="{{ $offers[0]->link }}" data-link="{{ $offers[0]->link }}"
                                    class="zoom-scal clr-none home_theme_six_offer_box  {{ $offers[0]->type != 'offer_url' ? 'slider-link' : '' }}"
                                    {{ $offers[0]->type == 'offer_url' ? 'target="_blank"' : 'wire:navigate' }}>
                                    <div class="img home_theme_six_offer_image">
                                        <img class="blur-up lazyloaded" data-src="{{ $offers[0]->image }}"
                                            src="{{ $offers[0]->image }}" alt="{{ $offers[0]->title }}">
                                    </div>
                                    <div class="details middle-center">
                                        <div class="inner text-center">
                                            <p class="subtitle mt-0">{{ $offers[0]->title ?? "Don't Miss Our Deals" }}
                                            </p>
                                            <span class="btn btn-primary rounded-pill mt-3">
                                                {{ labels('front_messages.shop_now', 'Shop Now') }}
                                                <i class="icon anm anm-arw-right ms-2"></i>
                                            </span>
                                        </div>
                                    </div>
                                </a>
                            </div>

                        </div>
                        <div {{-- class="col-12 col-sm-12 col-md-6 col-lg-6 collection-banner-item image-below-content-mobile"> --}} class="col-12 col-sm-12 col-md-6 col-lg-6 collection-banner-item">
                            <div class="collection-item sp-col">
                                <a href="{{ $offers[1]->link }}" data-link="{{ $offers[1]->link }}"
                                    class="zoom-scal clr-none home_theme_six_offer_box {{ $offers[1]->type != 'offer_url' ? 'slider-link' : '' }}"
                                    {{ $offers[1]->type == 'offer_url' ? 'target="_blank"' : 'wire:navigate' }}>
                                    <div class="img home_theme_six_offer_image">
                                        <img class="blur-up lazyloaded" data-src="{{ $offers[1]->image }}"
                                            src="{{ $offers[1]->image }}" alt="{{ $offers[1]->title }}">
                                    </div>
                                    <div class="details middle-center">
                                        <div class="inner text-center">
                                            <p class="subtitle mt-0">{{ $offers[1]->title ?? "Don't Miss Our Deals" }}
                                            </p>
                                            <span
                                                class="btn btn-primary rounded-pill mt-3">{{ labels('front_messages.shop_now', 'Shop Now') }}
                                                <i class="icon anm anm-arw-right ms-2"></i></span>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif
    {{-- end offer section --}}
    {{-- second feature section  --}}
    @foreach ($sections as $count_key => $row)
        @if ($count_key == 1)
            <section class="section product-collection pb-0">
                <div class="container-fluid">
                    <div class="section-header">
                        <p>{{ $row->title }}</p>
                        <h2>{{ $row->short_description }}</h2>
                    </div>
                    <div class="row">
                        <div class="col-12 col-sm-12 col-md-6 col-lg-6 product-collection-grd">
                            <div class="grid-products grid-view-items">
                                <div class="row col-row row-cols-lg-2 row-cols-md-2 row-cols-sm-2 row-cols-2">
                                    @foreach (collect($row->product_details)->take(4) as $details)
                                        @php
                                            $component = getProductDisplayComponent($store_settings);
                                            $details = (object) $details;
                                        @endphp

                                        <x-dynamic-component :component="$component" :details="$details" />
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6 col-lg-6 product-collection-bnr mt-4 mt-md-0">
                            <div class="collection-banner-grid two-bnr h-100">
                                <div class="row h-100">
                                    <div class="col-12 col-sm-12 col-md-12 col-lg-12 collection-banner-item">
                                        <div class="collection-item h-100 home_theme_six_feature_section_image">
                                            <a wire:navigate
                                                href="{{ customUrl('section/' . $row->slug . '/' . $row->id . '/' . ($row->product_type == 'custom_combo_products' ? 'combo-' : '') . 'products') }}"
                                                class="zoom-scal clr-none h-100">
                                                <div class="img h-100">
                                                    <img class="w-100 h-100 blur-up lazyloaded"
                                                        data-src="{{ $row->banner_image }}"
                                                        src="{{ $row->banner_image }}" alt="{{ $row->title }}"
                                                        title="{{ $row->title }}">
                                                </div>
                                                <div class="details middle-center">
                                                    <div class="inner text-center">
                                                        <h3 class="title">{{ $row->title }}</h3>
                                                        <p class="subtitle">{{ $row->short_description }}</p>
                                                        <span
                                                            class="btn btn-primary rounded-pill">{{ labels('front_messages.shop_now', 'Shop Now') }}</span>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif
    @endforeach
    {{-- end second feature section  --}}

    {{-- service section  --}}

    <x-utility.safety_and_security.styleThree :$settings />

    {{-- end service section  --}}
    {{-- brands  --}}
    @if (isset($brands['brands']) && is_array($brands['brands']) && count($brands['brands']) >= 1)
        <div class="section home-instagram pb-0 mb-3">
            <div class="container-fluid">
                <div class="section-header style2 d-flex-center justify-content-between">
                    <div class="section-header-left text-start">
                        <h2>{{ labels('front_messages.popular_brands', 'Popular Brands') }}</h2>
                        <p>{{ labels('front_messages.explore_brands', 'Explore top picks in our Brands!') }}</p>
                    </div>
                    <div class="section-header-right text-start text-sm-end  mt-sm-0">
                        <a wire:navigate href="{{ customUrl('brands') }}"
                            class="d-flex align-items-center view_more_icon arrow_icon">
                            <i class="anm anm-arrow-alt-right hdr-icon icon"></i>
                        </a>
                    </div>
                </div>

                <div class="swiper brands-mySwiper gp15 arwOut5 hov-arrow circle-arrow">
                    <div class="swiper-wrapper">
                        @foreach ($brands['brands'] as $brand)
                            <div class="swiper-slide">
                                <a wire:navigate href="{{ customUrl('products/?brand=' . $brand['brand_slug']) }}"
                                    class="brand-square-card clr-none text-decoration-none"
                                    data-link="{{ customUrl('products/?brand=' . $brand['brand_slug']) }}">
                                    <div class="brand-square-img">
                                        <img class="blur-up lazyload" src="{{ $brand['brand_img'] }}"
                                            alt="{{ $brand['brand_name'] }}" title="{{ $brand['brand_name'] }}">
                                    </div>
                                    <span class="brand-square-name">{{ $brand['brand_name'] }}</span>
                                </a>
                            </div>
                        @endforeach
                    </div>
                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                </div>
            </div>
        </div>
    @endif

    {{-- end brands  --}}
    {{-- Remaining Featured Sections --}}
    @foreach ($sections as $count_key => $row)
        @if ($count_key > 1)
            @if (!empty($row->product_details) && count((array) $row->product_details) > 0)
                @if ($row->style == 'style_1')
                    <section class="section product-slider tab-slider-product">
                        <div class="container-fluid">
                            <x-utility.section_header.sectionHeaderOne :title="$row" />
                            <div
                                class="swiper style1-mySwiper gp15 arwOut5 hov-arrow grid-products {{ ($store_settings['products_display_style_for_web'] ?? '') == 'products_display_style_for_web_3' ? 'pro-hover3' : '' }}">
                                <div class="swiper-wrapper">
                                    @foreach ($row->product_details as $details)
                                        <div class="swiper-slide">
                                            @php
                                                $component = getProductDisplayComponent($store_settings);
                                                $details = (object) $details;
                                            @endphp

                                            <x-dynamic-component :component="$component" :details="$details" />
                                        </div>
                                    @endforeach
                                </div>
                                <div class="swiper-button-next"></div>
                                <div class="swiper-button-prev"></div>
                            </div>
                        </div>
                    </section>
                @endif

                @if ($row->style == 'style_2')
                    <section class="section product-banner-slider pt-0">
                        <div class="container-fluid">
                            <div class="row">
                                <div class="col-12 col-sm-12 col-md-12 col-lg-9">
                                    <div
                                        class="grid-products swiper style2-mySwiper gp15 arwOut5 hov-arrow circle-arrow arrowlr-0 {{ ($store_settings['products_display_style_for_web'] ?? '') == 'products_display_style_for_web_3' ? 'pro-hover3' : '' }}">
                                        <div class="swiper-wrapper">
                                            @foreach ($row->product_details as $details)
                                                <div class="swiper-slide ">
                                                    @php
                                                        $component = getProductDisplayComponent($store_settings);
                                                        $details = (object) $details;
                                                    @endphp

                                                    <x-dynamic-component :component="$component" :details="$details" />
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="swiper-button-next"></div>
                                        <div class="swiper-button-prev"></div>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-12 col-md-12 col-lg-3 mt-4 mt-lg-0">
                                    <div class="ctg-bnr-wrap two position-relative h-100">
                                        <div class="ctg-image ratio ratio-1x1 h-100">
                                            <img class="blur-up lazyload object-fit-cover"
                                                data-src="{{ $row->banner_image }}" src="{{ $row->banner_image }}"
                                                alt="{{ $row->title }}" width="309" height="483" />
                                        </div>
                                        <div
                                            class="ctg-content text-white d-flex-justify-center flex-nowrap flex-column h-100">
                                            <h2 class="ctg-title text-white m-0">{{ $row->title }}</h2>
                                            <p class="ctg-des mt-1 mb-4">{{ $row->short_description }}</p>
                                            <a wire:navigate
                                                href="{{ customUrl('section/' . $row->slug . '/' . $row->id . '/' . ($row->product_type == 'custom_combo_products' ? 'combo-' : '') . 'products') }}"
                                                class="btn btn-secondary explore-btn button-style" href="">
                                                <span
                                                    class="text button-text">{{ labels('front_messages.shop_now', 'Shop Now') }}</span>
                                                <span class="button-icon button-icon-right"><ion-icon
                                                        name="arrow-forward-outline"></ion-icon></span></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                @endif
                @if ($row->style == 'style_3')
                    <section class="section product-banner-slider">
                        <div class="container-fluid">
                            <div class="row">
                                <div class="col-12 col-sm-12 col-md-12 col-lg-3 mb-4 mb-lg-0">
                                    <div class="ctg-bnr-wrap one position-relative h-100">
                                        <div class="ctg-image ratio ratio-1x1 h-100">
                                            <img class="blur-up lazyload object-fit-cover"
                                                data-src="{{ $row->banner_image }}" src="{{ $row->banner_image }}"
                                                alt="{{ $row->title }}" width="390" height="483" />
                                        </div>
                                        <div
                                            class="ctg-content text-white d-flex-justify-center flex-nowrap flex-column h-100">
                                            <h2 class="ctg-title text-white m-0">{{ $row->title }}
                                            </h2>
                                            <p class="ctg-des mt-3 mb-4">{{ $row->short_description }}</p>
                                            <a wire:navigate
                                                href="{{ customUrl('section/' . $row->slug . '/' . $row->id . '/' . ($row->product_type == 'custom_combo_products' ? 'combo-' : '') . 'products') }}"
                                                class="btn btn-secondary explore-btn"
                                                href="">{{ labels('front_messages.explore_now', 'Explore Now') }}
                                                <ion-icon class="ms-1" name="arrow-forward-outline"></ion-icon></a>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 col-sm-12 col-md-12 col-lg-9">
                                    <div
                                        class="grid-products swiper style2-mySwiper gp15 arwOut5 hov-arrow circle-arrow arrowlr-0 {{ ($store_settings['products_display_style_for_web'] ?? '') == 'products_display_style_for_web_3' ? 'pro-hover3' : '' }}">
                                        <div class="swiper-wrapper">
                                            @foreach ($row->product_details as $details)
                                                <div class="swiper-slide ">
                                                    @php
                                                        $component = getProductDisplayComponent($store_settings);
                                                        $details = (object) $details;
                                                    @endphp

                                                    <x-dynamic-component :component="$component" :details="$details" />
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="swiper-button-next"></div>
                                        <div class="swiper-button-prev"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                @endif
            @endif
        @endif
    @endforeach

    {{-- End Section --}}
    {{-- rating  --}}
    @if (isset($ratings) && is_array($ratings) && count($ratings) >= 1)
        <section class="section testimonial-slider style1 pb-0 mb-4">
            <div class="container-fluid">
                <div class="section-header style2 d-flex-center justify-content-sm-between">
                    <div class="section-header-left text-start">
                        <h2 class="mb-0">What People Are Saying</h2>
                    </div>
                </div>

                <div class="testimonial-wraper">
                    <div class="testimonial-slider-3items gp15 rounded-pill-dots slick-arrow-dots arwOut5">
                        @foreach (array_slice($ratings, 0, 6) as $rating)
                            <div class="testimonial-slide border bg-white rounded-5">
                                <div class="testimonial-content">
                                    <div class="auhimg d-flex align-items-center border-bottom">
                                        <div class="image home_theme_four_user_image">
                                            <img class="rounded-circle blur-up lazyload"
                                                src="{{ $rating->user_profile }}"
                                                alt="{{ $rating->user_name ?: 'Anonymous' }}">
                                        </div>
                                        <div class="auhtext ms-3">
                                            <h4 class="mb-2 pb-1">{{ $rating->title ?: 'Customer Feedback' }}</h4>

                                            <div class="product-review">
                                                @for ($i = 1; $i <= 5; $i++)
                                                    <i
                                                        class="icon anm {{ $i <= round($rating->rating) ? 'anm-star' : 'anm-star-o' }}"></i>
                                                @endfor
                                            </div>
                                            <p class="mt-2">{{ $rating->comment ?: '' }}</p>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center justify-content-between mt-3 pt-3">
                                        <div class="authour">
                                            <h5 class="mb-0">{{ $rating->user_name ?: 'Anonymous' }}</h5>
                                        </div>
                                        <div class="auhtext ms-auto">
                                            <p class="text-muted">Posted on
                                                {{ \Carbon\Carbon::parse($rating->created_at)->format('d/m/Y') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif
    {{-- end rating  --}}

</div>

@script
<script>
    (function () {
        'use strict';

        function qsAll(selector, ctx) {
            return Array.prototype.slice.call((ctx || document).querySelectorAll(selector));
        }

        function destroy(el) {
            if (el && el.swiper) {
                try { el.swiper.destroy(true, true); } catch (e) {}
            }
        }

        function maxView(cfg) {
            var m = cfg.slidesPerView || 1;
            if (cfg.breakpoints) {
                for (var bp in cfg.breakpoints) {
                    var v = cfg.breakpoints[bp].slidesPerView;
                    if (typeof v === 'number' && v > m) m = v;
                }
            }
            return m;
        }

        function initSwiper(selector, config) {
            if (typeof Swiper === 'undefined') return;
            qsAll(selector).forEach(function (el) {
                destroy(el);
                var cfg = JSON.parse(JSON.stringify(config));
                var nextEl = el.querySelector('.swiper-button-next');
                var prevEl = el.querySelector('.swiper-button-prev');
                if (nextEl && prevEl) {
                    cfg.navigation = { nextEl: nextEl, prevEl: prevEl };
                } else {
                    delete cfg.navigation;
                }
                var pagEl = el.querySelector('.swiper-pagination');
                if (pagEl) {
                    cfg.pagination = { el: pagEl, clickable: true };
                } else {
                    delete cfg.pagination;
                }
                var slideCount = el.querySelectorAll('.swiper-wrapper > .swiper-slide').length;
                if (cfg.loop && slideCount <= maxView(cfg)) {
                    cfg.loop = false;
                }
                if (slideCount === 0) return;
                new Swiper(el, cfg);
            });
        }

        initSwiper('.style1-mySwiper', {
            slidesPerView: 5,
            spaceBetween: 30,
            navigation: true,
            breakpoints: {
                200:  { slidesPerView: 2 },
                440:  { slidesPerView: 2 },
                540:  { slidesPerView: 3 },
                768:  { slidesPerView: 4 },
                1200: { slidesPerView: 5 },
            },
        });

        initSwiper('.style2-mySwiper', {
            slidesPerView: 4,
            spaceBetween: 30,
            navigation: true,
            breakpoints: {
                200:  { slidesPerView: 2 },
                440:  { slidesPerView: 2 },
                768:  { slidesPerView: 3 },
                1200: { slidesPerView: 4 },
            },
        });

        if (typeof window.jQuery !== 'undefined') {
            var $ = window.jQuery;

            var $sixSlider = $('.home-theme-six-slick-slider');
            if ($sixSlider.length) {
                if ($sixSlider.hasClass('slick-initialized')) {
                    try { $sixSlider.slick('unslick'); } catch (e) {}
                }
                $sixSlider.slick({
                    slidesToShow: 4,
                    slidesToScroll: 1,
                    infinite: true,
                    autoplay: true,
                    autoplaySpeed: 10000,
                    prevArrow: '<button type="button" class="slick-prev">&#10094;</button>',
                    nextArrow: '<button type="button" class="slick-next">&#10095;</button>',
                    lazyLoad: 'ondemand',
                    responsive: [
                        { breakpoint: 1024, settings: { slidesToShow: 3 } },
                        { breakpoint: 768,  settings: { slidesToShow: 2 } },
                        { breakpoint: 480,  settings: { slidesToShow: 1 } },
                    ],
                });
            }

            var $testimonials = $('.testimonial-slider-3items');
            if ($testimonials.length) {
                if ($testimonials.hasClass('slick-initialized')) {
                    try { $testimonials.slick('unslick'); } catch (e) {}
                }
                $testimonials.slick({
                    dots: true,
                    arrows: true,
                    infinite: true,
                    autoplay: true,
                    autoplaySpeed: 5000,
                    slidesToShow: 3,
                    slidesToScroll: 1,
                    responsive: [
                        { breakpoint: 1024, settings: { slidesToShow: 2 } },
                        { breakpoint: 768,  settings: { slidesToShow: 1 } },
                    ],
                });
            }
        }
    })();
</script>
@endscript
