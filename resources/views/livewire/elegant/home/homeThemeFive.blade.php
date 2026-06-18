@php
    use App\Models\Blog;
    use App\Services\TranslationService;
    use App\Services\StoreService;
    $store_settings = app(StoreService::class)->getStoreSettings();
    $language_code = app(TranslationService::class)->getLanguageCode();
    $category_section_title = $store_settings['category_section_title'] ?? 'Categories';
@endphp
<!--Page Wrapper-->
<div class="page-wrapper falling-snow style1"><!-- Body Container -->
    <div id="page-content" class="mb-0">
        <section class="slideshow slideshow-wrapper slideshow-medium">
            <div class="swiper mySwiper home-mySwiper">
                <div class="swiper-wrapper">
                    @foreach ($sliders as $slider)
                        <div class="swiper-slide slideshow-wrap">
                            @if ($slider['type'] !== 'default')
                                <a @if ($slider['type'] !== 'slider_url') wire:navigate @endif href="{{ $slider['link'] }}"
                                    class="slider-link" data-link="{{ $slider['link'] }}"
                                    target="{{ $slider['type'] == 'slider_url' ? '_blank' : '' }}">
                                    <img class="rounded-4 blur-up lazyload" data-src="{{ $slider['image'] }}"
                                        src="{{ $slider['image'] }}" alt="slideshow" title="" />
                                </a>
                            @else
                                <img class="rounded-4 blur-up lazyload" data-src="{{ $slider['image'] }}"
                                    src="{{ $slider['image'] }}" alt="slideshow" title="" />
                            @endif
                        </div>
                    @endforeach
                </div>
                <div class="swiper-pagination"></div>
            </div>
        </section>
        <!--Popular Categories-->
        @php
            $categories = $categories['categories'];
            // dd($categories);
        @endphp

        @if (is_array($categories) && count($categories) >= 1)
            <section class="section collection-slider">
                <div class="container-fluid">

                    @php
                        $resolved_category_title = is_array($category_section_title)
                            ? ($category_section_title[$language_code] ?? null)
                            : $category_section_title;
                    @endphp
                    <div class="section-header">
                        @if (!empty($resolved_category_title))
                            <h2>{{ $resolved_category_title }}</h2>
                        @else
                            <h2>{{ labels('front_messages.popular_categories', 'Popular Categories') }}</h2>
                        @endif
                        <p>{{ labels('front_messages.explore_categories', 'Explore top picks in our Categories!') }}
                        </p>
                    </div>

                    <div class="gp15 arwOut5 hov-arrow circle-arrow">
                        <div class="swiper home-theme-2-category-mySwiper">
                            <div class="swiper-wrapper">
                                @foreach ($categories as $category)
                                    <div class="swiper-slide zoomscal-hov rounded-4 home_theme_two_category_card">
                                        <a wire:navigate
                                            href="{{ customUrl('categories/' . $category->slug . '/products') }}"
                                            class="category-link clr-none brand-box slider-link"
                                            data-link="{{ customUrl('categories/' . $category->slug . '/products') }}">
                                            <div
                                                class="zoom-scal zoom-scal-nopb rounded-circle home_theme_two_category_image">
                                                <img class="blur-up lazyload" data-src="{{ $category->image }}"
                                                    src="{{ $category->image }}" alt="{!! $category->name !!}"
                                                    title="" />
                                            </div>

                                            <div class="details mt-3 text-center">
                                                <h4 class="category-title mb-0">{!! $category->name !!}</h4>
                                                <p class="counts">{{ $category->product_count }} Products</p>
                                            </div>

                                        </a>
                                    </div>
                                @endforeach
                            </div>
                            <div class="swiper-button-next"></div>
                            <div class="swiper-button-prev"></div>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        {{-- end popular categories  --}}

        <!--Products With Tabs-->
        @foreach ($sections as $count_key => $row)
            @if (!empty($row->product_details) && count((array) $row->product_details) > 0)
                @if ($row->style == 'style_1')
                    <section class="section product-slider tab-slider-product">
                        <div class="container-fluid">
                            <x-utility.section_header.sectionHeaderOne :title="$row" />
                            {{-- remove pro-hover-3 class in all other components keep only in three --}}
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
                    <!--Products Slider-->
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
        @endforeach
        <!--End Products With Tabs-->
        {{-- offers  --}}
        @if (isset($offers) && count($offers) > 0)
            {{-- @dd($offers); --}}
            <section class="section collection-banners pb-0">
                <div class="container-full">
                    <div class="swiper offers-slider">
                        <div class="swiper-wrapper">
                            @foreach ($offers as $offer)
                                <div class="swiper-slide">
                                    <div class="collection-banner-item">
                                        <div class="collection-item">
                                            <a href="{{ $offer->link }}"
                                                class="clr-none zoom-scal home_theme_five_offer_images {{ $offer->type != 'offer_url' ? 'slider-link' : '' }}"
                                                data-link="{{ $offer->link }}"
                                                {{ $offer->type == 'offer_url' ? 'target="_blank"' : 'wire:navigate' }}>
                                                <div class="img">
                                                    <img class="blur-up lazyload" data-src="{{ $offer->image }}"
                                                        src="{{ $offer->image }}" alt="{{ $offer->title }}"
                                                        title="{{ $offer->title }}">
                                                </div>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>
        @endif

        {{-- end offers  --}}
        {{-- marquee text --}}
        @if (isset($offers) && count($offers) > 0)
            <section class="section-sm border-bottom home_theme_five_marquee_section">
                <div class="marquee-text home_theme_five_marquee_text p-0">
                    <div class="top-info-bar d-flex p-0">
                        <div class="flex-item center">
                            <a href="{{ $offer->link }}"
                                class="{{ $offer->type != 'offer_url' ? 'slider-link' : '' }}"
                                data-link="{{ $offer->link }}"
                                {{ $offer->type == 'offer_url' ? 'target="_blank"' : 'wire:navigate' }}>
                                @foreach ($offers as $offer)
                                    <span>{{ $offer->title }}</span>
                                @endforeach
                            </a>
                        </div>
                        <div class="flex-item center">
                            <a href="{{ $offer->link }}"
                                class="{{ $offer->type != 'offer_url' ? 'slider-link' : '' }}"
                                data-link="{{ $offer->link }}"
                                {{ $offer->type == 'offer_url' ? 'target="_blank"' : 'wire:navigate' }}>
                                @foreach ($offers as $offer)
                                    <span>{{ $offer->title }}</span>
                                @endforeach
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        {{-- end marquee text --}}
        {{-- @dd($blogs_count); --}}
        <section class="section home-blog-post pb-0">
            <div class="container-fluid">
                <div class="section-header text-center">
                    <h2 class="mb-0">{{ labels('front_messages.latest_from_our_blog', 'Latest from our Blog') }}</h2>
                    <p>{{ labels('front_messages.top_news_stories_of_the_day', 'Top news stories of the day') }}</p>
                </div>

                <div class="row d-flex flex-wrap">
                    <div class="blog-slider-3items gp15 arwOut5 hov-arrow">
                        @foreach ($blogs->take(3) as $blog)
                            <div class="blog-item">
                                <div class="blog-article zoomscal-hov border bg-white rounded-5">
                                    <div class="blog-img">
                                        <a wire:navigate
                                            class="featured-image rounded-5 rounded-bottom-0 zoom-scal zoom-scal-nopb m-0"
                                            href="{{ customUrl('blogs/' . $blog->slug) }}">
                                            <img class="blur-up lazyloaded"
                                                data-src="{{ asset('storage/' . $blog->image) }}"
                                                src="{{ asset('storage/' . $blog->image) }}"
                                                alt="{{ $blog->title }}">
                                        </a>
                                    </div>
                                    <div class="blog-content text-center p-4">
                                        <h2 class="h3"><a wire:navigate
                                                href="{{ customUrl('blogs/' . $blog->slug) }}">{!! Str::limit(
                                                    app(TranslationService::class)->getDynamicTranslation(Blog::class, 'title', $blog->id, $language_code),
                                                    45,
                                                ) !!}</a>
                                        </h2>
                                        <ul class="publish-detail d-flex-wrap justify-content-center">
                                            <li><i class="icon anm anm-clock-r"></i>
                                                <time datetime="{{ $blog->created_at }}">
                                                    {{ $blog->created_at }}
                                                </time>
                                            </li>
                                        </ul>
                                        <p class="content">{!! Str::limit($blog->description, 80) !!}</p>
                                        <a wire:navigate href="{{ customUrl('blogs/' . $blog->slug) }}"
                                            class="btn btn-brd">{{ labels('front_messages.read_more', 'Read more') }}</a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
        <!--Popular brands-->
        @if (is_array($brands['brands']) && count($brands['brands']) >= 1)
            <section class="section collection-slider">
                <div class="container-fluid">
                    <div class="section-header style2 d-flex-center justify-content-between">
                        <div class="section-header-left text-start">
                            <h2>{{ labels('front_messages.popular_brands', 'Popular Brands') }}</h2>
                            <p>{{ labels('front_messages.explore_brands', 'Explore top picks in our Brands!') }}</p>
                        </div>
                        <div class="section-header-right text-start text-sm-end  mt-sm-0">


                            <a wire:navigate href="{{ customUrl('brands') }}" wire:navigate
                                class="d-flex align-items-center view_more_icon arrow_icon">
                                <i class="anm anm-arrow-alt-right hdr-icon icon"></i></a>
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
                                            <img class="blur-up lazyload" data-src="{{ $brand['brand_img'] }}"
                                                src="{{ $brand['brand_img'] }}"
                                                alt="{!! $brand['brand_name'] !!}" title="{!! $brand['brand_name'] !!}" />
                                        </div>
                                        <span class="brand-square-name">{!! $brand['brand_name'] !!}</span>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                        <div class="swiper-button-next"></div>
                        <div class="swiper-button-prev"></div>
                    </div>
                </div>
            </section>
        @endif
        <!--End Popular brands-->
        {{-- service section  --}}

        <x-utility.safety_and_security.styleThree :$settings />

        {{-- end service section  --}}
    </div>
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

        initSwiper('.home-mySwiper', {
            pagination: true,
            autoplay: { delay: 3000, disableOnInteraction: false, pauseOnMouseEnter: true },
            loop: true,
        });

        initSwiper('.home-theme-2-category-mySwiper', {
            slidesPerView: 8,
            spaceBetween: 20,
            navigation: true,
            breakpoints: {
                200:  { slidesPerView: 3 },
                440:  { slidesPerView: 4 },
                540:  { slidesPerView: 5 },
                768:  { slidesPerView: 6 },
                1200: { slidesPerView: 8 },
            },
        });

        initSwiper('.offers-slider', {
            slidesPerView: 3,
            spaceBetween: 10,
            loop: true,
            navigation: true,
            pagination: true,
            autoplay: { delay: 5000, disableOnInteraction: false },
            breakpoints: {
                220:  { slidesPerView: 1, spaceBetween: 10 },
                320:  { slidesPerView: 1, spaceBetween: 10 },
                768:  { slidesPerView: 2, spaceBetween: 15 },
                1024: { slidesPerView: 3, spaceBetween: 15 },
            },
        });

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
            var $blogs = $('.blog-slider-3items');
            if ($blogs.length) {
                if ($blogs.hasClass('slick-initialized')) {
                    try { $blogs.slick('unslick'); } catch (e) {}
                }
                $blogs.slick({
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

                var refreshBlogSlider = function () {
                    if ($blogs.hasClass('slick-initialized')) {
                        try { $blogs.slick('setPosition'); } catch (e) {}
                    }
                };
                $blogs.find('img').each(function () {
                    if (this.complete && this.naturalHeight !== 0) return;
                    $(this).one('load error', refreshBlogSlider);
                });
                $(window).off('load.blogSlider5').on('load.blogSlider5', refreshBlogSlider);
                setTimeout(refreshBlogSlider, 300);
                setTimeout(refreshBlogSlider, 1000);
            }
        }
    })();
</script>
@endscript
