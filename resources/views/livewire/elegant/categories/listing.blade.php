<div id="page-content">
    @php
        use App\Services\StoreService;
        use App\Services\TranslationService;
        $store_settings = app(StoreService::class)->getStoreSettings();
        $language_code = app(TranslationService::class)->getLanguageCode();
    @endphp
    <x-utility.breadcrumbs.breadcrumbOne :$breadcrumb />
    @php
        $component = getCategoryDisplayComponent($store_settings);
    @endphp

    <x-dynamic-component :component="$component" :categories="$categories" :language_code="$language_code" />
</div>
