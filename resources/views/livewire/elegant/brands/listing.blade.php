<div id="page-content">
    @php
        use App\Services\StoreService;
        use App\Services\TranslationService;
        $store_settings = app(StoreService::class)->getStoreSettings();
        $language_code = app(TranslationService::class)->getLanguageCode();
    @endphp
    <!--Page Header-->
    <x-utility.breadcrumbs.breadcrumbOne :$breadcrumb />
    @php
        $component = getBrandDisplayComponent($store_settings);
    @endphp
    {{-- @dd($language_code); --}}
    <x-dynamic-component :component="$component" :brands="$brands" :language_code="$language_code" />
    {{-- <x-utility.brands.cards.listCardThree :$brands /> --}}
    <!--End Page Header-->
</div>
