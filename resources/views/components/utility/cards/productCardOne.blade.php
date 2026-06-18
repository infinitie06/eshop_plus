@props(['details'])
@php
    use App\Services\TranslationService;
    use App\Services\MediaService;
    use App\Services\CurrencyService;
    use App\Models\Brand;
    use App\Models\Category;

    $translationService = app(TranslationService::class);
    $mediaService = app(MediaService::class);
    $currencyService = app(CurrencyService::class);
    $language_code = $translationService->getLanguageCode();

    $isCombo = ($details->type ?? '') == 'combo-product';
    $detailUrl = customUrl(($isCombo ? 'combo-products/' : 'products/') . $details->slug);

    if (!$isCombo) {
        $brandName = $translationService->getDynamicTranslation(Brand::class, 'name', $details->brand, $language_code);
        $categoryName = $translationService->getDynamicTranslation(Category::class, 'name', $details->category_id, $language_code);
        $brandUrl = customUrl('products/?brand=' . $details->brand_slug);
        $categoryUrl = customUrl('categories/' . $details->category_slug . '/products');

        if (($details->type ?? '') == 'variable_product') {
            $rawPrice = $details->min_max_price['max_price'];
            $rawSpecial = $details->min_max_price['special_min_price'];
        } else {
            $rawPrice = $details->variants[0]['price'];
            $rawSpecial = $details->variants[0]['special_price'];
        }
    } else {
        $rawPrice = $details->price;
        $rawSpecial = $details->special_price;
    }

    $price = $currencyService->currentCurrencyPrice($rawPrice, true);
    $special_price = $rawSpecial && $rawSpecial > 0
        ? $currencyService->currentCurrencyPrice($rawSpecial, true)
        : $price;

    $cardImage = $mediaService->dynamic_image($details->image, 400);
    $cardThumb = $mediaService->dynamic_image($details->image, 220);
@endphp
{{-- @dd($details); --}}
<div class="item col-item">
    @if ($details->type != 'combo-product')
        <div class="product-box {{ isset($details->store) && $details->store->status != 1 ? 'store-inactive' : '' }}">
            @if(isset($details->store) && $details->store->status != 1)
                <div class="product-not-available">
                    <h2>{{ labels('front_messages.product_not_available', 'Product Not Available') }}</h2>
                </div>
            @endif
            <div class="product-image m-0">
                <a wire:navigate href="{{ $detailUrl }}"
                    class="all-product-img product-img rounded-3 slider-link"
                    data-link="{{ $detailUrl }}">


                    <img class="blur-up lazyload" src="{{ $cardImage }}" alt="Product"
                        title="{{ $details->name }}" width="625" height="808" loading="lazy" decoding="async" />
                </a>
                <div class="product-labels radius">
                    @if ($details->new_arrival)
                        <span class="lbl pr-label3">{{ labels('front_messages.new_arrivals', 'New Arrival') }}</span>
                    @endif

                    @if ($details->best_seller)
                        <span class="lbl pr-label4">{{ labels('front_messages.best_seller', 'Best Seller') }}</span>
                    @endif
                </div>

                <div class="button-set style1">
                    <a href="#quickview-modal" class="btn-icon quickview quick-view-modal" data-bs-toggle="modal"
                        data-bs-target="#quickview_modal" data-product-id="{{ $details->id }}">
                        <span class="icon-wrap d-flex-justify-center h-100 w-100" data-bs-toggle="tooltip"
                            data-bs-placement="left" title="Quick View"><i
                                class="hdr-icon icon anm anm-search-l"></i><span
                                class="text">{{ labels('front_messages.quick_view', 'Quick View') }}</span>
                    </a>
                    {{-- <a class="btn-icon wishlist card_fav_btn {{ $details->is_favorite == 1 ? 'remove-favorite' : 'add-favorite' }}"
                        data-bs-toggle="tooltip" data-bs-placement="left"
                        title="{{ $details->is_favorite == 1 ? 'Remove From Wishlist' : 'Add To Wishlist' }}"
                        data-product-id="{{ $details->id }}" data-product-type="regular">
                        <i
                            class="hdr-icon anm {{ $details->is_favorite == 1 ? 'anm-heart text-danger' : 'anm-heart-l' }}"></i>
                        <span
                            class="text">{{ $details->is_favorite == 1 ? 'Remove From Wishlist' : 'Add To Wishlist' }}</span>
                    </a> --}}
@livewire('products.wishlist-button', ['product_id' => $details->id], key('wishlist-'.$details->id))

                    <a class="btn-icon compare add-compare" data-product-id="{{ $details->id }}"
                        data-bs-toggle="tooltip" data-bs-placement="left" title="Add to Compare"><i
                            class="icon anm anm-random-r"></i><span
                            class="text">{{ labels('front_messages.add_to_compare', 'Add to Compare') }}</span></a>
                </div>
            </div>
            <div class="product-details">
                <a wire:navigate href="{{ $brandUrl }}"
                    class="slider-link product-vendor text-uppercase"
                    data-link="{{ $brandUrl }}">{!! $brandName !!}</a>
                <div class="product-name text-capitalize">
                    <a wire:navigate href="{{ $detailUrl }}"
                        class="slider-link text-ellipsis" data-link="{{ $detailUrl }}"
                        title="{!! $details->name !!}">{!! $details->name !!}</a>
                </div>
                <div class="product-price">
                    <span class="price old-price">{{ $special_price !== $price ? $price : '' }}</span>
                    <span class="price fw-500"><span wire:model="price">{{ $special_price }}</span></span>
                </div>

                <div>
                    <a wire:navigate href="{{ $categoryUrl }}"
                        data-link="{{ $categoryUrl }}"
                        class="slider-link text-ellipsis hidden text-secondary"
                        title="{!! $categoryName !!}"><ion-icon name="layers-outline"
                            class="custom-icon fs-6 me-1"></ion-icon>{!! $categoryName !!}
                    </a>
                </div>
                <div class="product-review">
                    <input id="input-3-ltr-star-md" name="input-3-ltr-star-md"
                        class="kv-ltr-theme-svg-star rating-loading d-none" value="{{ $details->rating }}"
                        dir="ltr" data-size="xs" data-show-clear="false" data-show-caption="false" readonly>
                </div>

                @if ($details->type == 'variable_product')
                    <div class="button-action mt-2">
                        <div class="addtocart-btn">
                            <a href="#quickview-modal"
                                class="button-style d-flex align-items-center btn btn-md quickview quick-view-modal p-2"
                                data-bs-toggle="modal" data-bs-target="#quickview_modal"
                                data-product-id="{{ $details->id }}" data-product-variant-id=''>
                                <i class="anm anm-bag-l hdr-icon me-2"></i>
                                <span
                                    class="text button-text">{{ labels('front_messages.add_to_cart', 'Add to Cart') }}</span>

                            </a>
                        </div>
                    </div>
                @else
                    <div class="button-action mt-2">
                        <div class="addtocart-btn add_cart" id="add_cart"
                            data-product-variant-id="{{ $details->variants[0]['id'] }}"
                            data-name='{{ $details->name }}' data-slug='{{ $details->slug }}'
                            data-brand-name='{!! $brandName !!}'
                            data-image='{{ $cardThumb }}' data-product-type='regular'
                            data-max='{{ $details->total_allowed_quantity }}'
                            data-step='{{ $details->quantity_step_size }}'
                            data-min='{{ $details->minimum_order_quantity }}'
                            data-stock-type='{{ $details->stock_type }}' data-store-id='{{ $details->store_id }}'
                            data-variant-price="{{ $currencyService->currentCurrencyPrice($details->variants[0]['special_price']) }}">
                            <a class="btn btn-md p-2 button-style d-flex align-items-center"
                                data-product-id="{{ $details->id }}">
                                <i class="anm anm-bag-l hdr-icon me-2"></i>
                                <span
                                    class="text button-text">{{ labels('front_messages.add_to_cart', 'Add to Cart') }}</span>

                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @else
        <div class="product-box {{ isset($details->store) && $details->store->status != 1 ? 'store-inactive' : '' }}">
            @if(isset($details->store) && $details->store->status != 1)
                <div class="product-not-available">
                    <h2>{{ labels('front_messages.product_not_available', 'Product Not Available') }}</h2>
                </div>
            @endif
            <div class="product-image m-0">
                <a wire:navigate href="{{ $detailUrl }}"
                    class="all-product-img product-img rounded-3 slider-link"
                    data-link="{{ $detailUrl }}">
                    <img class="blur-up lazyload" src="{{ $cardImage }}"
                        alt="{{ $details->name }}" title="{{ $details->name }}" width="625" height="808" loading="lazy" decoding="async" />
                </a>
                <div class="product-labels radius">
                    @if ($details->new_arrival)
                        <span class="lbl pr-label3">{{ labels('front_messages.new_arrivals', 'New Arrival') }}</span>
                    @endif

                    @if ($details->best_seller)
                        <span class="lbl pr-label4">{{ labels('front_messages.best_seller', 'Best Seller') }}</span>
                    @endif
                </div>
                <div class="button-set style1">
                    <a href="#quickview-modal" class="btn-icon quickview quick-view-modal" data-bs-toggle="modal"
                        data-bs-target="#quickview_modal" data-product-id="{{ $details->id }}"
                        data-product-type="{{ $details->type }}">
                        <span class="icon-wrap d-flex-justify-center h-100 w-100" data-bs-toggle="tooltip"
                            data-bs-placement="left" title="Quick View"><i
                                class="hdr-icon icon anm anm-search-l"></i><span
                                class="text">{{ labels('front_messages.quick_view', 'Quick View') }}</span>
                    </a>
                    {{-- <a class="btn-icon wishlist card_fav_btn {{ $details->is_favorite == 1 ? 'remove-favorite' : 'add-favorite' }}"
                        data-bs-toggle="tooltip" data-bs-placement="left"
                        title="{{ $details->is_favorite == 1 ? 'Remove From Wishlist' : 'Add To Wishlist' }}"
                        data-product-id="{{ $details->id }}" data-product-type="combo">
                        <i
                            class="hdr-icon anm {{ $details->is_favorite == 1 ? 'anm-heart text-danger' : 'anm-heart-l' }}"></i>
                        <span
                            class="text">{{ $details->is_favorite == 1 ? 'Remove From Wishlist' : 'Add To Wishlist' }}</span>
                    </a> --}}
@livewire('products.wishlist-button', ['product_id' => $details->id], key('wishlist-'.$details->id))

                    <a class="btn-icon compare add-compare" data-product-id="{{ $details->id }}"
                        data-product-type="combo" data-bs-toggle="tooltip" data-bs-placement="left"
                        title="Add to Compare"><i class="icon anm anm-random-r"></i><span
                            class="text">{{ labels('front_messages.add_to_compare', 'Add to Compare') }}</span></a>
                </div>
            </div>
            <div class="product-details">
                <div class="product-name text-capitalize">
                    <a wire:navigate href="{{ $detailUrl }}"
                        class="slider-link text-ellipsis"
                        data-link="{{ $detailUrl }}"
                        title="{!! $details->name !!}">{!! $details->name !!}</a>
                </div>
                <div class="product-price">
                    <span
                        class="price old-price">{{ $details->special_price && $details->special_price > 0 ? $price : '' }}</span>
                    <span class="price fw-500">{{ $special_price }}</span>
                </div>
                <div class="product-review">
                    <input id="input-3-ltr-star-md" name="input-3-ltr-star-md"
                        class="kv-ltr-theme-svg-star rating-loading d-none" value="{{ $details->rating }}"
                        dir="ltr" data-size="xs" data-show-clear="false" data-show-caption="false" readonly>
                </div>

                @if ($details->type == 'variable_product')
                    <div class="button-action mt-2">
                        <div class="addtocart-btn">
                            <a href="#quickview-modal"
                                class="button-style d-flex align-items-center btn btn-md quickview quick-view-modal p-2"
                                data-bs-toggle="modal" data-bs-target="#quickview_modal"
                                data-product-id="{{ $details->id }}" data-product-variant-id=''>
                                <i class="anm anm-bag-l hdr-icon me-2"></i>
                                <span
                                    class="text button-text">{{ labels('front_messages.add_to_cart', 'Add to Cart') }}</span>

                            </a>
                        </div>
                    </div>
                @else
                    <div class="button-action mt-2">
                        <div class="addtocart-btn add_cart" id="add_cart"
                            data-product-variant-id="{{ $details->id }}" data-name='{{ $details->name }}'
                            data-slug='{{ $details->slug }}' data-image='{{ $cardThumb }}'
                            data-product-type='combo' data-max='{{ $details->total_allowed_quantity }}'
                            data-step='{{ $details->quantity_step_size }}'
                            data-min='{{ $details->minimum_order_quantity }}'
                            data-stock-type='{{ $details->stock_type }}' data-store-id='{{ $details->store_id }}'
                            data-variant-price="{{ $currencyService->currentCurrencyPrice($details->special_price) }}">
                            <a class="btn btn-md p-2 button-style d-flex align-items-center"
                                data-product-id="{{ $details->id }}">
                                <i class="anm anm-bag-l hdr-icon me-2"></i>
                                <span
                                    class="text button-text">{{ labels('front_messages.add_to_cart', 'Add to Cart') }}</span>

                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
