<?php

namespace App\Livewire\Header;

use App\Http\Controllers\CategoryController;
use App\Models\Currency;
use App\Models\Language;
use App\Models\Store;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use App\Services\StoreService;
use App\Services\SettingService;
use App\Models\Favorite;

class Header extends Component
{
    protected $listeners = ['cart_count', 'changeLang', 'changeCurrency', 'wishlistUpdated'];

    public $cart_count = "";
    public $wishlist_count = 0;
    public $user_id = "";
    public function __construct()
    {
        $this->user_id = Auth::user() != '' ? Auth::user()->id : NUll;
    }

    public function cart_count($cart_count)
    {
        $this->cart_count = $cart_count;
    }

    public function mount()
    {
        $this->wishlist_count = 0;
        if (auth()->check()) {
            $store_id = session('store_id');
            $data = getFavorites(user_id: auth()->id(), store_id: $store_id);
            $this->wishlist_count = $data['favorites_count'] ?? 0;
        }
    }
    public function wishlistUpdated()
    {
        if (Auth::check()) {
            $this->wishlist_count = Favorite::where('user_id', Auth::id())->count();
        } else {
            $this->wishlist_count = 0;
        }
    }
    public function render()
    {
        $settings = app(SettingService::class)->getSettings('web_settings', true);
        $settings = json_decode($settings);


        $currencies = fetchDetails(Currency::class, ['status' => 1]) ?? [];

        $shipping_settings = app(SettingService::class)->getSettings('shipping_method', true);
        $shipping_settings = json_decode($shipping_settings, true);
        if (isset($shipping_settings['shiprocket_shipping_method']) && $shipping_settings['shiprocket_shipping_method'] == 1) {
            $currencies = $currencies->filter(function ($currency) {
                return $currency->code == 'INR';
            });
        }

        $languages = fetchDetails(Language::class) ?? [];

        $store_id = session('store_id');

        $store_details = fetchDetails(Store::class, ['status' => 1], '*');
        $categoryController = app(CategoryController::class);
        $categories = $categoryController->getCategories(sort: 'row_order', order: "ASC", store_id: $store_id);
        $categories = $categories->original;
        $store_settings = app(StoreService::class)->getStoreSettings();
        $header_style = getHeaderStyle($store_settings);
        // dd($currencies);
        return view($header_style, [
            'settings' => $settings,
            'currencies' => $currencies,
            'languages' => $languages,
            'stores' => $store_details,
            'categories' => $categories,
        ]);
    }

    public function changeLang($lang)
    {
        if ($lang != "") {
            $is_rtl = fetchDetails(Language::class, ['code' => $lang], 'is_rtl');
            $is_rtl = isset($is_rtl) && !empty($is_rtl) ? $is_rtl[0]->is_rtl : '';
            app()->setLocale($lang);
            session()->put('locale', $lang);
            session()->put('is_rtl', $is_rtl);
            return $this->redirect(request()->header('Referer') ?? url()->current());
        }
    }
    public function changeCurrency($currency)
    {
        $shipping_settings = app(SettingService::class)->getSettings('shipping_method', true);
        $shipping_settings = json_decode($shipping_settings, true);
        if (isset($shipping_settings['shiprocket_shipping_method']) && $shipping_settings['shiprocket_shipping_method'] == 1) {
            return;
        }

        if ($currency) {
            session()->put('currency', $currency);
            return $this->redirect(request()->header('Referer') ?? url()->current());
        }
    }
}
