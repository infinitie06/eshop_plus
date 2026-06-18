@php
    $bread_crumb['page_main_bread_crumb'] = labels('front_messages.favorites', 'Favorites');
@endphp
<div id="page-content">
    @php
        use App\Services\StoreService;
        use App\Services\TranslationService;
        $store_settings = app(StoreService::class)->getStoreSettings();
        $language_code = app(TranslationService::class)->getLanguageCode();
    @endphp
    <x-utility.breadcrumbs.breadcrumbTwo :$bread_crumb />

    <div class="container-fluid">
        <div class="row">
            <x-utility.my_account_slider.account_slider :$user_info />
            @php
                $component = getWishlistDisplayComponent($store_settings);
                //dd($component);
            @endphp
            <x-dynamic-component :component="$component" :regular_wishlist="$regular_wishlist" :combo_wishlist="$combo_wishlist" :favorites_count="$favorites_count"
                :language_code="$language_code" :links="$links" />
        </div>
    </div>
</div>
