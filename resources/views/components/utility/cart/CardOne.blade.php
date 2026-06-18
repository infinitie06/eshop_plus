@props(['cartItem', 'is_save_later_hide', 'is_remove_from_cart', 'product_availability', 'for_checkout'])
@php
    use App\Models\Product;
    use App\Models\ComboProduct;
    use App\Services\TranslationService;
    use App\Services\CurrencyService;
    use App\Services\MediaService;
    $language_code = app(TranslationService::class)->getLanguageCode();
@endphp
{{-- @dd($cartItem->cart_product_type); --}}
@if ($cartItem->cart_product_type == 'regular')
    <tr class="cart-row cart-flex position-relative">
        {{-- @dd($cartItem['product_variants']); --}}
        <td class="cart-delete text-center small-hide">
            @if ($is_remove_from_cart == 0)
                <button type="button" class="cart-remove border-0 remove-icon position-static" data-bs-toggle="tooltip"
                    data-bs-placement="top" title="{{ labels('front_messages.remove_from_cart', 'Remove from Cart') }}" wire:click="remove_from_cart({{ $cartItem->product_variant_id }})">
                    <ion-icon wire:ignore name="close" class="fs-5"></ion-icon>
                </button>
            @endif
        </td>
        <td class="cart-image cart-flex-item">
            @php
                $cart_image = app(MediaService::class)->dynamic_image($cartItem->product->image, 84);
                $product_name = app(TranslationService::class)->getDynamicTranslation(Product::class, 'name', $cartItem->product->id, $language_code);
            @endphp
            <a href="{{ customUrl('products/' . $cartItem->product->slug) }}" wire:navigate><img
                    class="cart-image rounded-0 blur-up lazyload" data-src="{{ $cart_image }}"
                    src="{{ $cart_image }}" alt="{{ $product_name }}" /></a>
        </td>
        <td class="cart-meta small-text-left cart-flex-item">
            <div class="list-view-item-title">
                <a href="{{ customUrl('products/' . $cartItem->product->slug) }}" wire:navigate>{{ $product_name }}</a>
            </div>
            <div class="cart-meta-text">
                @if (isset($product_availability) && $product_availability['is_deliverable'] == false)
                    <p class="m-0 fw-500 text-capitalize text-danger">{{ $product_name }} {{ labels('front_messages.is_not_deliverable_at_selected_address', 'is not deliverable at selected address') }}</p>
                @endif
                @php
                    $variant_values = $cartItem['product_variants'][0]['variant_values'];
                    $variant_values = str_replace(',', '/', $variant_values);
                @endphp
                {!! $variant_values . ($variant_values != '' ? '<br>' : '') !!}{{ labels('front_messages.qty', 'Qty') }}:
                {{ $cartItem->qty }}
            </div>
            <div class="cart-price d-md-none">
                <span class="money fw-500">{{ app(CurrencyService::class)->currentCurrencyPrice($cartItem->productVariant->special_price, true) }}</span>
            </div>
        </td>
        @if ($is_save_later_hide == 0)
            <td class="cart-price cart-flex-item text-center small-hide">
                <button type="button" class="btn btn-brd"
                    wire:click="save_for_later({{ $cartItem->product_variant_id }})">{{ labels('front_messages.save_for_later', 'Save for Later') }}</button>
            </td>
        @endif
        <td class="cart-price cart-flex-item text-center small-hide">
            <span class="money">
                @php
                    $finalPrice =
                        $cartItem->productVariant->special_price && $cartItem->productVariant->special_price > 0
                            ? app(CurrencyService::class)->currentCurrencyPrice($cartItem->productVariant->special_price, true)
                            : app(CurrencyService::class)->currentCurrencyPrice($cartItem->productVariant->price, true);
                @endphp
                {{ $finalPrice }}
            </span>
        </td>

        <td class="cart-update-wrapper cart-flex-item text-end text-md-center">
            <div class="cart-qty d-flex justify-content-end justify-content-md-center">
                @if ($for_checkout != 1)
                    <div class="qtyField">
                        <button type="button" wire:ignore class="qtyBtn minus">
                            <ion-icon name="remove-outline"></ion-icon>
                        </button>

                        <input class="cart-qty-input qty" type="text" name="updates[]"
                            value="{{ $cartItem->qty }}" pattern="[0-9]*"
                            max="{{ $cartItem->product->total_allowed_quantity == 0 ? 'Infinity' : $cartItem->product->total_allowed_quantity }}"
                            min="{{ $cartItem->product->minimum_order_quantity }}"
                            step="{{ $cartItem->product->quantity_step_size }}" data-variant-id="{{ $cartItem->product_variant_id }}"
                            data-product-type="regular" />


                        <button type="button" wire:ignore class="qtyBtn plus">
                            <ion-icon name="add-outline"></ion-icon>
                        </button>
                    </div>
                @else
                    {{ $cartItem->qty }}
                @endif

            </div>
            <button type="button" title="{{ labels('front_messages.save_for_later', 'Save for Later') }}"
                class="removeMb d-md-none d-inline-block text-decoration-underline border-0"
                wire:click="save_for_later({{ $cartItem->product_variant_id }})">{{ labels('front_messages.save_for_later', 'Save for Later') }}</button>
            <button type="button" title="{{ labels('front_messages.remove', 'Remove') }}"
                class="removeMb d-md-none d-inline-block text-decoration-underline border-0"
                wire:click="remove_from_cart({{ $cartItem->product_variant_id }})">{{ labels('front_messages.remove', 'Remove') }}</button>
        </td>
        <td class="cart-price cart-flex-item text-center small-hide">
            @php
                $priceToUse =
                    $cartItem->productVariant->special_price && $cartItem->productVariant->special_price > 0
                        ? $cartItem->productVariant->special_price
                        : $cartItem->productVariant->price;
                $cart_total = $priceToUse * $cartItem->qty;
            @endphp
            <span class="money fw-500">{{ app(CurrencyService::class)->currentCurrencyPrice($cart_total, true) }}</span>
        </td>

    </tr>
@else
    <tr class="cart-row cart-flex position-relative">

        <td class="cart-delete text-center small-hide">
            @if ($is_remove_from_cart == 0)
                <button type="button" class="cart-remove border-0 remove-icon position-static" data-bs-toggle="tooltip"
                    data-bs-placement="top" title="{{ labels('front_messages.remove_from_cart', 'Remove from Cart') }}"
                    wire:click="remove_from_cart({{ $cartItem->product_variant_id }})">
                    <ion-icon wire:ignore name="close" class="fs-5"></ion-icon>
                </button>
            @endif
        </td>
        <td class="cart-image cart-flex-item">
            @php
                $cart_image = app(MediaService::class)->dynamic_image($cartItem->comboProduct->image, 84);
                $combo_product_name = app(TranslationService::class)->getDynamicTranslation(
                    ComboProduct::class,
                    'title',
                    $cartItem->comboProduct->id,
                    $language_code,
                );
            @endphp
            <a href="{{ customUrl('combo-products/' . $cartItem->comboProduct->slug) }}" wire:navigate><img
                    class="cart-image rounded-0 blur-up lazyload" data-src="{{ $cart_image }}"
                    src="{{ $cart_image }}" alt="{{ $combo_product_name }}" /></a>
        </td>
        <td class="cart-meta small-text-left cart-flex-item">
            <div class="list-view-item-title">
                <a href="{{ customUrl('combo-products/' . $cartItem->comboProduct->slug) }}"
                    wire:navigate>{{ $combo_product_name }}</a>
            </div>
            @if (isset($product_availability) && $product_availability['is_deliverable'] == false)
                <p class="m-0 fw-500 text-capitalize text-danger">{{ $combo_product_name }} {{ labels('front_messages.is_not_deliverable_at_selected_address', 'is not deliverable at selected address') }}</p>
            @endif
            <div class="cart-meta-text">{{ labels('front_messages.qty', 'Qty') }}: {{ $cartItem->qty }}
            </div>
            <div class="cart-price d-md-none">
                <span class="money fw-500">{{ app(CurrencyService::class)->currentCurrencyPrice($cartItem->comboProduct->special_price, true) }}</span>
            </div>
        </td>
        @if ($is_save_later_hide == 0)
            <td class="cart-price cart-flex-item text-center small-hide">
                <button type="button" class="btn btn-brd"
                    wire:click="save_for_later({{ $cartItem->product_variant_id }})">{{ labels('front_messages.save_for_later', 'Save for Later') }}</button>
            </td>
        @endif
        <td class="cart-price cart-flex-item text-center small-hide">
            <span class="money">
                @php
                    $priceToDisplay =
                        $cartItem->comboProduct->special_price && $cartItem->comboProduct->special_price > 0
                            ? $cartItem->comboProduct->special_price
                            : $cartItem->comboProduct->price;
                @endphp
                {{ app(CurrencyService::class)->currentCurrencyPrice($priceToDisplay, true) }}
            </span>
        </td>

        <td class="cart-update-wrapper cart-flex-item text-end text-md-center">
            <div class="cart-qty d-flex justify-content-end justify-content-md-center">
                @if ($for_checkout != 1)
                    <div class="qtyField">
                        <button type="button" wire:ignore class="qtyBtn minus">
                            <ion-icon name="remove-outline"></ion-icon>
                        </button>
                        <input class="cart-qty-input qty" type="text" name="updates[]"
                            value="{{ $cartItem->qty }}" pattern="[0-9]*"
                            max="{{ $cartItem->comboProduct->total_allowed_quantity == 0 ? 'Infinity' : $cartItem->comboProduct->total_allowed_quantity }}"
                            min="{{ $cartItem->comboProduct->minimum_order_quantity }}"
                            step="{{ $cartItem->comboProduct->quantity_step_size }}" data-variant-id="{{ $cartItem->product_variant_id }}"
                            data-product-type="combo" />
                        <button type="button" wire:ignore class="qtyBtn plus">
                            <ion-icon name="add-outline"></ion-icon>
                        </button>
                    </div>
                @else
                    {{ $cartItem->qty }}
                @endif


            </div>
            <button type="button" title="{{ labels('front_messages.save_for_later', 'Save for Later') }}"
                class="removeMb d-md-none d-inline-block text-decoration-underline border-0"
                wire:click="save_for_later({{ $cartItem->product_variant_id }})">{{ labels('front_messages.save_for_later', 'Save for Later') }}</button>
            <button type="button" title="{{ labels('front_messages.remove', 'Remove') }}"
                class="removeMb d-md-none d-inline-block text-decoration-underline border-0"
                wire:click="remove_from_cart({{ $cartItem->product_variant_id }})">{{ labels('front_messages.remove', 'Remove') }}</button>
        </td>
        <td class="cart-price cart-flex-item text-center small-hide">
            @php
                $priceToUse =
                    $cartItem->comboProduct->special_price && $cartItem->comboProduct->special_price > 0
                        ? $cartItem->comboProduct->special_price
                        : $cartItem->comboProduct->price;
                $cart_total = $priceToUse * $cartItem->qty;
            @endphp
            <span class="money fw-500">{{ app(CurrencyService::class)->currentCurrencyPrice($cart_total, true) }}</span>
        </td>

    </tr>
@endif
