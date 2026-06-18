<div wire:ignore.self class="offcanvas offcanvas-end" tabindex="-1" id="minicart-drawer">
    @php
        use App\Models\Product;
        use App\Models\ComboProduct;
        use App\Services\TranslationService;
        use App\Services\MediaService;
        use App\Services\CurrencyService;

        $translationService = app(TranslationService::class);
        $mediaService = app(MediaService::class);
        $currencyService = app(CurrencyService::class);
        $language_code = $translationService->getLanguageCode();
    @endphp
    @if (count($cart_data) < 1)
        <!--MiniCart Empty-->
        <div id="cartEmpty" class="cartEmpty d-flex-justify-center flex-column text-center p-3 text-muted">
            <div class="minicart-header d-flex-center justify-content-between w-100">
                <h4 class="fs-6">{{ labels('front_messages.your_cart', 'Your cart') }} (<span
                        class="cart_count">0</span> {{ labels('front_messages.items', 'Items') }})</h4>
                <button class="close-cart border-0" data-bs-dismiss="offcanvas" aria-label="Close"><ion-icon
                        name="close-outline" class="icon" data-bs-toggle="tooltip" data-bs-placement="left"
                        title="Close"></ion-icon></button>
            </div>
            <div id="display_cart">
                <div class="cartEmpty-content mt-4">
                    <ion-icon name="cart-outline" class="icon text-muted fs-1"></ion-icon>
                    <p class="my-3">{{ labels('front_messages.no_products_in_cart', 'No Products in the Cart') }}</p>
                    <a wire:navigate href="{{ customUrl('products') }}" class="btn btn-primary cart-btn">
                        {{ labels('front_messages.continue_shopping', 'Continue shopping') }}
                    </a>
                </div>
            </div>
        </div>
        <!--End MiniCart Empty-->
    @else
        <!--MiniCart Content-->
        <div id="cart-drawer" class="block block-cart">
            <div class="minicart-header">
                <button class="close-cart border-0" data-bs-dismiss="offcanvas" aria-label="Close">
                    <ion-icon class="icon" data-bs-toggle="tooltip" data-bs-placement="left"
                        name="close-outline"></ion-icon></button>
                <h4 class="fs-6">{{ labels('front_messages.your_cart', 'Your cart') }}
                    ({{ count($cart_data['cart_items']) }} {{ labels('front_messages.items', 'Items') }})</h4>
            </div>
            {{-- @dd($cart_data['cart_items'][0]); --}}
            <div class="minicart-content">
                <ul class="m-0 clearfix">
                    @foreach ($cart_data['cart_items'] as $items)
                        @php
                            $isCombo = $items->cart_product_type == 'combo';
                            $sourceProduct = $isCombo ? $items->comboProduct : $items->product;
                            $productData = $sourceProduct;

                            $nameCol = $isCombo ? ($sourceProduct->title ?? '') : ($sourceProduct->name ?? '');
                            $translations = json_decode($nameCol, true);
                            $product_name = is_array($translations)
                                ? ($translations[$language_code] ?? ($translations['en'] ?? $nameCol))
                                : $nameCol;

                            $pro_image = $mediaService->getMediaImageUrl($sourceProduct->image);

                            $slug = $sourceProduct->slug;
                            $prefix = $isCombo ? 'combo-products/' : 'products/';
                            $itemUrl = customUrl($prefix . $slug);

                            $rawPrice = $isCombo ? ($sourceProduct->price ?? 0) : $items->productVariant->price;
                            $rawSpecial = $isCombo
                                ? $sourceProduct->special_price
                                : $items->productVariant->special_price;

                            $price = $currencyService->currentCurrencyPrice($rawPrice, true);
                            $special_price = $rawSpecial && $rawSpecial > 0
                                ? $currencyService->currentCurrencyPrice($rawSpecial, true)
                                : $price;
                        @endphp
                        <li class="item d-flex justify-content-center align-items-center">
                            <a class="product-image rounded-3" wire:navigate href="{{ $itemUrl }}">
                                <img class="blur-up lazyload" data-src="{{ $pro_image }}"
                                    src="data:image/svg+xml;utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1 1'%3E%3C/svg%3E"
                                    loading="lazy" decoding="async" width="70" height="70"
                                    alt="{{ $product_name }}" title="{{ $product_name }}" />
                            </a>

                            <div class="product-details">
                                <a class="product-title" wire:navigate
                                    href="{{ $itemUrl }}">{{ $product_name }}</a>
                                @if (!$isCombo)
                                    <div class="variant-cart my-2">
                                        {{ $items['product_variants'][0]['variant_values'] }}
                                    </div>
                                @endif
                                <div class="priceRow">
                                    <div class="product-price">
                                        @if ($special_price !== $price)
                                            <span class="price old-price">{{ $price }}</span>
                                        @endif
                                        <span class="price">{{ $special_price }}</span>
                                    </div>
                                </div>

                            </div>

                            <div class="qtyDetail text-end cart-qtyDetail">
                                <div class="qtyField">
                                    <button wire:ignore class="qtyBtn minus" href="#;">
                                        <ion-icon name="remove-outline"></ion-icon>
                                    </button>
                                    <input type="number" name="quantity" value="{{ $items->qty }}" class="qty"
                                        max='{{ $productData->total_allowed_quantity == 0 ? 'Infinity' : $productData->total_allowed_quantity }}'
                                        step='{{ $productData->quantity_step_size }}'
                                        min='{{ $productData->minimum_order_quantity }}'
                                        data-variant-id='{{ $items->product_variant_id }}'>

                                    <button wire:ignore class="qtyBtn plus" href="#;">
                                        <ion-icon name="add-outline"></ion-icon>
                                    </button>
                                </div>

                                <a wire:click="remove_from_cart({{ $items->product_variant_id }})"
                                    class="remove_from_cart remove pointer"
                                    data-variant-id="{{ $items->product_variant_id }}">
                                    <ion-icon wire:ignore class="icon" data-bs-toggle="tooltip"
                                        data-bs-placement="top" name="close-outline"></ion-icon>
                                </a>
                            </div>

                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="minicart-bottom">
                <div class="subtotal clearfix my-3">
                    <div class="totalInfo clearfix"><span>{{ labels('front_messages.total', 'Total') }}:</span><span
                            class="item product-price">{{ $currencyService->currentCurrencyPrice($cart_data['sub_total'], true) }}</span>
                    </div>

                </div>
                <div class="agree-check customCheckbox">
                    <input id="prTearm" name="tearm" type="checkbox" value="tearm" required />
                    <label for="prTearm">{{ labels('front_messages.i_agree_with_the', 'I agree with the') }}
                    </label><a wire:navigate href="{{ url('term-and-conditions') }}"
                        class="ms-1 text-link">{{ labels('front_messages.terms_and_conditions', 'Terms & conditions') }}</a>
                </div>
                <div class="minicart-action d-flex mt-3">
                    <a href="{{ customUrl('cart.checkout') }}" 
                    onclick="window.location.href=this.href; return false;"
                        class="cart-checkout proceed-to-checkout btn btn-primary w-50 me-1 disabled">{{ labels('front_messages.check_out', 'Check Out') }}</a>
                    <a href="{{ customUrl('cart') }}" 
                    onclick="window.location.href=this.href; return false;"
                        class="cart-checkout cart-btn btn btn-secondary w-50 ms-1 disabled">{{ labels('front_messages.view_cart', 'View Cart') }}</a>
                </div>
            </div>
        </div>
    @endif

    <!--MiniCart Content-->
</div>
