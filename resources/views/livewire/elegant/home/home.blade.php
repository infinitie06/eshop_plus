@php
    use App\Services\StoreService;
    use App\Services\TranslationService;

    $store_settings = app(StoreService::class)->getStoreSettings();
    $language_code = app(TranslationService::class)->getLanguageCode();
    $category_section_title = $store_settings['category_section_title'] ?? 'Categories';
@endphp

<style>
    #page-content h2,
    #page-content h4,
    #page-content p,
    #page-content .ctg-title,
    #page-content .ctg-des,
    #page-content .category-title,
    #page-content .btn,
    #page-content .button-text,
    #page-content a {
        line-height: 1;
    }
</style>

<div id="page-content" class="index-demo1" wire:ignore>

    {{-- SLIDER --}}
    <section class="slideshow slideshow-wrapper slideshow-medium">
        <div class="swiper mySwiper home-mySwiper">
            <div class="swiper-wrapper">
                @foreach ($sliders as $slider)
                    <div class="swiper-slide slideshow-wrap">
                        @if ($slider['type'] !== 'default')
                            <a href="{{ $slider['link'] }}" @if ($slider['type'] !== 'slider_url') wire:navigate @endif
                                target="{{ $slider['type'] == 'slider_url' ? '_blank' : '_self' }}" class="slider-link">
                                <img class="rounded-4 blur-up lazyload" src="{{ $slider['image'] }}"
                                    data-src="{{ $slider['image'] }}" alt="slider">
                            </a>
                        @else
                            <img class="rounded-4 blur-up lazyload" src="{{ $slider['image'] }}"
                                data-src="{{ $slider['image'] }}" alt="slider">
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="swiper-pagination"></div>
        </div>
    </section>

    {{-- POPULAR CATEGORIES --}}
    @php $categories = $categories['categories'] ?? []; @endphp
    @if (count($categories))
        <section class="section collection-slider">
            <div class="container-fluid skinned">
                <div class="section-header style2 d-flex justify-content-between">
                    @php
                        $resolved_category_title = is_array($category_section_title)
                            ? ($category_section_title[$language_code] ?? null)
                            : $category_section_title;
                    @endphp
                    <div>
                        <h2>
                            {{ !empty($resolved_category_title) ? $resolved_category_title : labels('front_messages.popular_categories', 'Popular Categories') }}
                        </h2>
                        <p>{{ labels('front_messages.explore_categories', 'Explore top picks in our Categories!') }}</p>
                    </div>
                    <a wire:navigate href="{{ customUrl('categories') }}" class="view_more_icon">
                        <i class="anm anm-arrow-alt-right"></i>
                    </a>
                </div>

                <x-utility.categories.sliders.sliderThree :categories="$categories" />
            </div>
        </section>
    @endif

    {{-- CATEGORY SECTIONS --}}
    @if (!empty($categories_section))
        @foreach ($categories_section as $category_section)
            <section class="section collection-slider">
                <div class="container-fluid">
                    <div class="section-header style2 d-flex justify-content-between">
                        <div>
                            <h2>{{ $category_section->title }}</h2>
                            <p>{{ labels('front_messages.explore_categories', 'Explore top picks in our Categories!') }}
                            </p>
                        </div>
                        <a wire:navigate href="{{ customUrl('categories') }}" class="view_more_icon">
                            <i class="anm anm-arrow-alt-right"></i>
                        </a>
                    </div>

                    <x-utility.categories.sliders.sliderThree :categories="$category_section->categories_detail" />
                </div>
            </section>
        @endforeach
    @endif

    {{-- BRANDS --}}
    @if (!empty($brands['brands']))
        <section class="section collection-slider">
            <div class="container-fluid">
                <div class="section-header style2 d-flex justify-content-between">
                    <div>
                        <h2>{{ labels('front_messages.popular_brands', 'Popular Brands') }}</h2>
                        <p>{{ labels('front_messages.explore_brands', 'Explore top picks in our Brands!') }}</p>
                    </div>
                    <a wire:navigate href="{{ customUrl('brands') }}" class="view_more_icon">
                        <i class="anm anm-arrow-alt-right"></i>
                    </a>
                </div>

                <div class="swiper category-mySwiper">
                    <div class="swiper-wrapper">
                        @foreach ($brands['brands'] as $brand)
                            <div class="swiper-slide slider-brand rounded-4">
                                <a wire:navigate href="{{ customUrl('products/?brand=' . $brand['brand_slug']) }}"
                                    class="brand-box">
                                    <img class="blur-up lazyload" src="{{ $brand['brand_img'] }}"
                                        data-src="{{ $brand['brand_img'] }}" alt="{{ $brand['brand_name'] }}">
                                    @if (($store_settings['brand_style'] ?? '') === 'brands_style_1')
                                        <h4 class="text-center">{{ $brand['brand_name'] }}</h4>
                                    @endif
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- PRODUCT SECTIONS --}}
    @foreach ($sections as $row)
        @if (!empty($row->product_details))
            {{-- STYLE 1 --}}
            @if ($row->style === 'style_1')
                <section class="section product-slider">
                    <div class="container-fluid">
                        <x-utility.section_header.sectionHeaderOne :title="$row" />
                        <div class="swiper style1-mySwiper">
                            <div class="swiper-wrapper">
                                @foreach ($row->product_details as $details)
                                    <div class="swiper-slide">
                                        <x-dynamic-component :component="getProductDisplayComponent($store_settings)" :details="(object) $details" />
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </section>
            @endif

            {{-- STYLE 3 --}}
            @if ($row->style === 'style_3')
                <section class="section product-banner-slider">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-lg-3">
                                <img class="w-100 rounded" src="{{ $row->banner_image }}" alt="{{ $row->title }}">
                            </div>
                            <div class="col-lg-9">
                                <div class="swiper style2-mySwiper">
                                    <div class="swiper-wrapper">
                                        @foreach ($row->product_details as $details)
                                            <div class="swiper-slide">
                                                <x-dynamic-component :component="getProductDisplayComponent($store_settings)" :details="(object) $details" />
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            @endif
        @endif
    @endforeach

    {{-- SERVICES --}}
    @if (
        $web_settings['support_mode'] ||
            $web_settings['shipping_mode'] ||
            $web_settings['safety_security_mode'] ||
            $web_settings['return_mode']
    )
        <section class="section service-section">
            <x-utility.others.serviceSection />
        </section>
    @endif

</div>
