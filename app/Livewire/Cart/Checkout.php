<?php

namespace App\Livewire\Cart;

use App\Http\Controllers\AddressController;
use App\Models\Cart;
use App\Models\TimeSlot;
use App\Models\Zipcode;
use App\Services\PromoCodeService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use App\Services\CartService;
use App\Services\DeliveryService;
use App\Services\CurrencyService;
use App\Services\SettingService;
use App\Services\ShiprocketService;
class Checkout extends Component
{
    protected $listeners = ['refreshComponent', 'get_selected_address', 'get_selected_promo', 'is_wallet_use'];
    public $user_id = "";
    public function __construct()
    {
        $this->user_id = Auth::user() != '' ? Auth::user()->id : NUll;
    }
    public $store_id = "";
    public $product_type = "";
    public $cart_count = "";
    public $selected_address_id = "";
    public $selected_address = "";
    public $selected_promo_code = "";
    public $is_wallet_use = false;
    public $wallet_used_balance = "";
    public $shipping_options = [];
    public $selected_shipping_method = 'recommended';
    public $payment_method_type = 'prepaid';

    public function mount()
    {
        $store_id = session('store_id');
        $this->store_id = $store_id;
        $cart_data = $this->get_user_cart($this->user_id, $store_id);
        if (count($cart_data) < 1) {
            return $this->redirect(customUrl('/'), true);
        }
    }

    public function render()
    {
        $store_id = $this->store_id;
        $addressController = app(AddressController::class);
        $addresses = $addressController->getAddress($this->user_id);
        // dd($addresses);
        $default_address = [];

        if (!empty($addresses)) {
            if (isset($this->selected_address_id) && !empty($this->selected_address_id)) {
                $default_address = $this->selected_address;
            } else {
                $default_address = array_values(array_filter($addresses->all(), function ($item) {
                    return $item->is_default == 1;
                }));
            }

            if (empty($default_address)) {
                $default_address = $addresses;
            }
        }

        $user_details = fetchUsers($this->user_id);
        $wallet_balance = $user_details['balance'];

        $promo_codes = app(abstract: PromoCodeService::class)->getPromoCodes(store_id: $store_id);
        $cart_data = $this->get_user_cart($this->user_id, $store_id, ($default_address[0]->id ?? ""));
        if (count($cart_data) < 1) {
            return $this->redirect(customUrl('/'), true);
        }
        $final_total = $cart_data['overall_amount'];
        if (isset($this->selected_promo_code) && !empty($this->selected_promo_code)) {
            $is_promo_valid = app(abstract: PromoCodeService::class)->validatePromoCode($this->selected_promo_code, $this->user_id, $final_total, 1);
            if ($is_promo_valid->original['error'] == false) {
                $is_promo_valid->original['data'][0]->final_discount = app(CurrencyService::class)->currentCurrencyPrice($is_promo_valid->original['data'][0]->final_discount, true);
                $final_total = $is_promo_valid->original['data'][0]->final_total;
                $this->dispatch('validate_promo_code', is_promo_valid: $is_promo_valid->original);
            } else {
                $this->dispatch('validate_promo_code', is_promo_valid: $is_promo_valid->original);
            }
        }
        $this->cart_count = (count($cart_data) >= 1) ? count($cart_data['cart_items']) : "";
        $this->store_id = $store_id;
        $bread_crumb = [
            'page_main_bread_crumb' => '<a wire:navigate href="' . customUrl('cart') . '">' . labels('front_messages.cart', 'Cart') . '</a>',
            'right_breadcrumb' => array(labels('front_messages.checkout', 'Checkout'))
        ];

        $pincode = $default_address[0]->pincode ?? "";
        $zipcode = fetchDetails(Zipcode::class, ['zipcode' => $pincode], 'id');
        $zipcode_id = $zipcode[0]->id ?? "";

        $city = $default_address[0]->city ?? "";
        $city_id = $default_address[0]->city_id ?? "";

        $settings = app(DeliveryService::class)->getDeliveryChargeSetting($store_id);
        $product_availability = [];

        $is_digital_only_cart = !empty($cart_data['cart_items']) && collect($cart_data['cart_items'])->every(function ($item) {
            $product = $item['product'] ?? ($item['comboproduct'] ?? null);
            return $product && (($product['type'] ?? '') === 'digital_product');
        });

        try {
            if (!$is_digital_only_cart && isset($settings[0]->product_deliverability_type) && !empty($settings[0]->product_deliverability_type)) {
                if ($settings[0]->product_deliverability_type == 'city_wise_deliverability') {
                    $product_availability = app(DeliveryService::class)->checkCartProductsDeliverable($this->user_id, '', '', $store_id, $city, $city_id);
                } else {
                    $product_availability = app(DeliveryService::class)->checkCartProductsDeliverable($this->user_id, $pincode, $zipcode_id, $store_id);
                }
            } elseif ($is_digital_only_cart) {
                foreach ($cart_data['cart_items'] as $item) {
                    $product_availability[] = [
                        'is_deliverable' => true,
                        'delivery_by' => '',
                        'product_id' => $item['product']['id'] ?? ($item['comboproduct']['id'] ?? null),
                        'product_qty' => $item->qty ?? 1,
                        'message' => '',
                    ];
                }
            }
        } catch (\Exception $e) {
            \Log::error('Deliverability check failed', [
                'error' => $e->getMessage(),
                'user_id' => $this->user_id,
                'pincode' => $pincode
            ]);

            // Create a fallback error message for all cart items
            foreach ($cart_data['cart_items'] as $item) {
                $product_availability[] = [
                    'is_deliverable' => false,
                    'message' => 'Unable to check deliverability. Database connection error. Please contact support.',
                    'product_id' => $item['product_id'],
                    'name' => $item['name']
                ];
            }
        }

        // Ensure $product_availability is always an array
        if (!is_array($product_availability)) {
            $product_availability = [];
        }

        // 1. Fetch Shipping Options if standard shipping is active
        if ($pincode != "") {
            $standard_shipping = false;
            if (isset($product_availability) && is_array($product_availability)) {
                foreach ($product_availability as $product) {
                    if ($product['delivery_by'] == 'standard_shipping') {
                        $standard_shipping = true;
                        break;
                    }
                }
            }

            if ($standard_shipping) {
                $parcels = app(ShiprocketService::class)->makeShippingParcels(collect($cart_data['cart_items']));
                $isCod = ($this->payment_method_type == 'cod') ? 1 : 0;
                $this->shipping_options = app(ShiprocketService::class)->getAvailableShippingOptions($parcels, $pincode, $isCod, $cart_data['sub_total']);

                if (!empty($this->shipping_options)) {
                    $selected = $this->shipping_options[$this->selected_shipping_method] ?? $this->shipping_options['recommended'];
                    $cart_data['delivery_charge'] = $selected['rate'];
                    
                    
                    $cart_data['overall_amount'] = (float) $cart_data['sub_total'] + (float) $cart_data['delivery_charge'];
                }
            }
        }

        $final_total = $cart_data['overall_amount'];

        // 2. Apply Promo Code
        if (isset($this->selected_promo_code) && !empty($this->selected_promo_code)) {
            $is_promo_valid = app(PromoCodeService::class)->validatePromoCode($this->selected_promo_code, $this->user_id, $final_total, 1);
            if ($is_promo_valid->original['error'] == false) {
                $cart_data['promo_discount'] = $is_promo_valid->original['data'][0]->final_discount;
                $final_total = $is_promo_valid->original['data'][0]->final_total;
                $is_promo_valid->original['data'][0]->final_discount = app(CurrencyService::class)->currentCurrencyPrice($is_promo_valid->original['data'][0]->final_discount, true);
                $this->dispatch('validate_promo_code', is_promo_valid: $is_promo_valid->original);
            } else {
                $this->dispatch('validate_promo_code', is_promo_valid: $is_promo_valid->original);
            }
        }

        // 3. Apply Wallet
        if ($this->is_wallet_use == true) {
            $balance = $wallet_balance;
            if ($balance >= $final_total) {
                $this->wallet_used_balance = $final_total;
                $wallet_balance = $balance - $final_total;
                $final_total = 0;
            } else {
                $this->wallet_used_balance = $balance;
                $final_total = $final_total - $balance;
                $wallet_balance = 0;
            }
        }

        $this->cart_count = (count($cart_data) >= 1) ? count($cart_data['cart_items']) : "";
        $this->store_id = $store_id;
        $bread_crumb = [
            'page_main_bread_crumb' => '<a wire:navigate href="' . customUrl('cart') . '">' . labels('front_messages.cart', 'Cart') . '</a>',
            'right_breadcrumb' => array(labels('front_messages.checkout', 'Checkout'))
        ];

        $time_slot_config = app(SettingService::class)->getSettings('time_slot_config', true);
        $time_slot_config = json_decode($time_slot_config);
        $time_slots = fetchDetails(TimeSlot::class, ['status' => 1]);

        $payment_method = app(SettingService::class)->getSettings('payment_method', true);
        $payment_method = json_decode($payment_method);
        return view('livewire.' . config('constants.theme') . '.cart.checkout', [
            'cart_data' => $cart_data,
            'final_total' => $final_total,
            'product_availability' => $product_availability,
            'addresses' => $addresses,
            'promo_codes' => $promo_codes,
            'wallet_balance' => $wallet_balance,
            'default_address' => $default_address,
            'bread_crumb' => $bread_crumb,
            'time_slot_config' => $time_slot_config,
            'time_slots' => $time_slots,
            'payment_method' => $payment_method,
            'user_details' => $user_details,
        ])->title('Checkout |');
    }

    public function get_user_cart($user_id, $store_id, $address_id = "")
    {
        $cart_data = app(CartService::class)->getCartTotal($user_id, false, 0, $address_id, $store_id);
        return $cart_data;
    }

    public function get_selected_address($address_id)
    {
        $this->selected_address_id = $address_id;
        $addressController = app(AddressController::class);
        $selected_address = $addressController->getAddress($this->user_id, $address_id);
        $this->selected_address = $selected_address;
        $this->resetShippingOptions();
    }

    public function resetShippingOptions()
    {
        $this->shipping_options = [];
        $this->selected_shipping_method = 'recommended';
    }

    public function updatedSelectedShippingMethod()
    {
        // Triggers re-render
    }

    public function updatedPaymentMethodType()
    {
        // Triggers re-render to update shipping rates
    }
    public function save_for_later($product_variant_id = '')
    {
        try {
            $user_id = Auth::user()->id != '' ? Auth::user()->id : 0;
            
            if (empty($product_variant_id)) {
                session()->flash('error', 'Invalid product variant');
                return;
            }

            $cart_item = Cart::where('product_variant_id', intval($product_variant_id))
                ->where('user_id', intval($user_id))
                ->where('store_id', $this->store_id)
                ->first();

            if (!$cart_item) {
                session()->flash('error', 'Cart item not found');
                return;
            }

            // Toggle saved for later status
            $saved_for_later = $cart_item->is_saved_for_later == '1' ? '0' : '1';

            $cart_item->update([
                'is_saved_for_later' => $saved_for_later,
            ]);

            session()->flash('success', $saved_for_later == '1' ? 'Item saved for later' : 'Item moved to cart');
            
            return $this->redirect(
                route('cart.checkout'),
                navigate: true
            );

        } catch (\Exception $e) {
            \Log::error('Save for later error: ' . $e->getMessage());
            session()->flash('error', 'Something went wrong. Please try again.');
            return;
        }
    }
    public function get_selected_promo($promo_code)
    {
        // dd($promo_code);
        $this->selected_promo_code = $promo_code;
    }
    public function is_wallet_use($is_wallet_use)
    {
        $this->is_wallet_use = $is_wallet_use;
    }

    public function validatePromoCode($promo_code, $user_id, $final_total)
    {
        $validate_promo = app(abstract: PromoCodeService::class)->validatePromoCode($promo_code, $user_id, $final_total);
        return $validate_promo;
    }

    public function refreshComponent()
    {
        $this->dispatch('$refresh');
    }

    public function remove_from_cart($id)
    {
        $data = [
            'variant_id' => $id,
            'product_type' => $this->product_type,
            'store_id' => $this->store_id,
            'user_id' => $this->user_id,
            'cart_count' => $this->cart_count,
        ];
        $this->dispatch('remove_from_cart', data: $data);
    }
}
