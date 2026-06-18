<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Address;
use App\Models\City;
use App\Models\Zipcode;
use App\Models\ComboProduct;
use App\Models\Product_variants;
use Illuminate\Support\Str;
use App\Services\DeliveryService;
use App\Services\MediaService;
use App\Services\CurrencyService;
use App\Services\SettingService;
use App\Models\AffiliateTracking;
use Illuminate\Support\Facades\Cache;
class CartService
{
    public function addToCart($data, $check_status = true, $fromApp = false)
    {

        $data = array_map('htmlspecialchars', $data);
        $product_type = $data['product_type'] != null ? explode(',', Str::lower($data['product_type'])) : [];
        $product_variant_ids = explode(',', $data['product_variant_id']);
        $store_id = explode(',', $data['store_id']);
        $qtys = explode(',', $data['qty']);
        // dd($data);

        //  store reference in cache

        if (isset($data['product_reference_id']) && !empty($data['product_reference_id'])) {
            $existingAffiliateReference = json_decode(Cache::get('affiliate_reference'), true) ?? [];

            if (!is_array($existingAffiliateReference)) {
                $existingAffiliateReference = [];
            }

            foreach ($product_variant_ids as $index => $product_variant_id) {
                $existingAffiliateReference[$product_variant_id] = $data['product_reference_id'];
            }

            Cache::put('affiliate_reference', json_encode($existingAffiliateReference), now()->addDays(30));
        }
        //  store reference in cache end

        if ($check_status == true) {

            $check_current_stock_status = validateStock($product_variant_ids, $qtys, $product_type);
            if (!empty($check_current_stock_status) && $check_current_stock_status['error'] == true) {
                return $check_current_stock_status;
            }
        }

        foreach ($product_variant_ids as $index => $product_variant_id) {
            $cart_data = [
                'user_id' => $data['user_id'],
                'product_variant_id' => $product_variant_id,
                'qty' => $qtys[$index],
                'is_saved_for_later' => (isset($data['is_saved_for_later']) && !empty($data['is_saved_for_later']) && $data['is_saved_for_later'] == '1') ? $data['is_saved_for_later'] : '0',
                'store_id' => (isset($store_id) && !empty($store_id)) ? $store_id[$index] : '',
                'product_type' => (isset($product_type) && !empty($product_type)) ? $product_type[$index] : '',
            ];

            if ($qtys[$index] == 0) {

                $this->removeFromCart($cart_data);
            } else {
                $existing_cart_item = Cart::where(['user_id' => $data['user_id'], 'product_variant_id' => $product_variant_id, 'store_id' => $data['store_id'], 'product_type' => $data['product_type']])->first();


                if (!empty($existing_cart_item) && $existing_cart_item != null) {

                    // Set quantity to the new value (replace, not increment)
                    $cart_data['qty'] = $qtys[$index];
                    $existing_cart_item->update($cart_data);

                    if ($fromApp == true) {

                        return true;
                    } else {
                        return true;
                    }
                } else {

                    Cart::create($cart_data);
                    if ($fromApp == true) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public function removeFromCart($data)
    {
        $is_saved_for_later = isset($data['is_saved_for_later']) ? $data['is_saved_for_later'] : 0;
        if (isset($data['user_id']) && !empty($data['user_id'])) {
            $query = Cart::where('user_id', $data['user_id']);

            if (isset($data['product_variant_id'])) {
                $product_variant_ids = explode(',', $data['product_variant_id']);
                $query->whereIn('product_variant_id', $product_variant_ids);
            }
            if (isset($data['product_type'])) {
                $product_types = explode(',', $data['product_type']);
                $query->whereIn('product_type', $product_types);
            }
            $query->where('store_id', $data['store_id']);
            $query->where('is_saved_for_later', $is_saved_for_later);

            return $query->delete();
        } else {
            return false;
        }
    }
    public function getUserCart($user_id, $address_id = '', $store_id = '', $is_saved_for_later = 0, $affiliate_reference = '')
    {
        return $this->getCartTotal(
            $user_id,
            false,
            $is_saved_for_later,
            $address_id,
            $store_id,
            $affiliate_reference
        );
    }

    public function getCartTotal($user_id, $product_variant_id = false, $is_saved_for_later = 0, $address_id = '', $store_id = '', $affiliate_reference = '')
    {
        $query = [];
        // get product details from products table
        $productQuery = Cart::with([
            'productVariant.product' => function ($query) {
                $query->with('sellerData', 'category');
            }
        ])
            ->where('user_id', $user_id)
            ->where('qty', '>=', 0)
            ->where('is_saved_for_later', intval($is_saved_for_later))
            ->where('store_id', $store_id)
            ->where('product_type', 'regular')
            ->whereHas('productVariant.product', function ($query) {
                $query->where('status', 1)->whereHas('sellerData', function ($q) {
                    $q->where('status', 1);
                });
            })
            ->whereHas('productVariant', function ($query) {
                $query->where('status', 1);
            })
            ->when($product_variant_id, function ($query) use ($product_variant_id) {
                $query->where('product_variant_id', $product_variant_id);
            })
            ->orderBy('id', 'desc')
            ->get();

        // get product details from combo_products table

        $comboProductQuery = Cart::with('comboProduct.sellerData')
            ->where('user_id', $user_id)
            ->where('qty', '>=', 0)
            ->where('is_saved_for_later', intval($is_saved_for_later))
            ->where('store_id', $store_id)
            ->where('product_type', 'combo')
            ->whereHas('comboProduct', function ($query) {
                $query->where('status', 1)->whereHas('sellerData', function ($q) {
                    $q->where('status', 1);
                });
            })
            ->when($product_variant_id, function ($query) use ($product_variant_id) {
                $query->where('product_variant_id', $product_variant_id);
            })
            ->orderBy('id', 'desc')
            ->get();
        $query = $productQuery->merge($comboProductQuery);
        $total = [];
        $item_total = [];
        $variant_id = [];
        $quantity = [];
        $percentage = [];
        $amount = [];
        $cod_allowed = 1;
        $download_allowed = [];
        $totalItems = 0;
        $product_qty = '';
        $product_ids = [];
        $cart_product_type = [];


        // get affiliate reference from cache
        // dd($affiliate_reference);
        if (isset($affiliate_reference) && !empty($affiliate_reference)) {
            if (is_array($affiliate_reference)) {
                $affiliateReference = $affiliate_reference; // Already array
            } else {
                $affiliateReference = json_decode($affiliate_reference, true) ?? [];
            }
        }

        // $affiliate_data = [];


        // if (!empty($affiliateReference) && is_array($affiliateReference)) {
        //     $variantIds = array_keys($affiliateReference);
        //     // dd($variantIds);
        //     // Step 1: Get variants using Eloquent
        //     $variants = Product_variants::whereIn('id', $variantIds)->get(['id', 'product_id']);

        //     // Step 2: Build product_id => token mapping
        //     foreach ($variants as $variant) {
        //         $variantId = $variant->id;
        //         $productId = $variant->product_id;

        //         if (isset($affiliateReference[$variantId])) {
        //             $affiliate_data[$productId] = $affiliateReference[$variantId];
        //         }
        //     }

        //     // Step 3: Query AffiliateTracking model using OR conditions
        //     $affiliateQuery = AffiliateTracking::where(function ($q) use ($affiliate_data) {
        //         foreach ($affiliate_data as $productId => $token) {
        //             $q->orWhere(function ($subQ) use ($productId, $token) {
        //                 $subQ->where('product_id', $productId)
        //                     ->where('token', $token);
        //             });
        //         }
        //     });

        //     $affiliate_commission_data = $affiliateQuery->get();
        // }
        // dd($affiliate_commission_data);

        $affiliate_data = [];

        // Prepare regular product affiliate data
        if (!empty($affiliateReference) && is_array($affiliateReference)) {
            $variantIds = array_keys($affiliateReference);
            // dd($variantIds);
            // Fetch variants only for regular products
            $variants = Product_variants::whereIn('id', $variantIds)->get(['id', 'product_id']);

            foreach ($variants as $variant) {
                $variantId = $variant->id;
                $productId = $variant->product_id;

                if (isset($affiliateReference[$variantId])) {
                    $affiliate_data[$productId] = $affiliateReference[$variantId];
                }
            }

            // Handle combo products
            foreach ($comboProductQuery as $comboCartItem) {
                $product = $comboCartItem->comboProduct; // Should already be eager loaded

                if ($product && isset($affiliateReference[$comboCartItem->product_variant_id])) {
                    $affiliate_data[$product->id] = $affiliateReference[$comboCartItem->product_variant_id];
                }
            }
            // dd($affiliate_data);
            // Query affiliate tracking records
            $affiliateQuery = AffiliateTracking::where(function ($q) use ($affiliate_data) {
                foreach ($affiliate_data as $productId => $token) {
                    $q->orWhere(function ($subQ) use ($productId, $token) {
                        $subQ->where('product_id', $productId)
                            ->where('token', $token);
                    });
                }
            });

            $affiliate_commission_data = $affiliateQuery->get();
        }
        // dd($affiliate_commission_data);
        if (!$query->isEmpty()) {

            foreach ($query as $result) {
                $totalItems += $result->qty;
            }

            foreach ($query as $i => $item) {
                $type = $item->product_type;
                if ($type == 'combo') {
                    $product = $item->comboProduct;
                } else {
                    $product = $item->product;
                    $category_ids[$i] = $product->category_id;
                }

                $product_ids[$i] = $product?->id;
                $cart_product_type[$i] = $type;
                $tax_percentage = $product->getTaxPercentages();
                $tax_titles = $product->getTaxTitles();

                // Set tax info on item
                $item->item_tax_percentage = implode(',', $tax_percentage);
                $item->tax_title = implode(',', $tax_titles);
                // dd($item->comboProduct);
                // Calculate tax amounts if prices are exclusive of tax
                $p = ($type == 'combo') ? ($product->price ?? 0) : ($item->productVariant['price'] ?? 0);
                $sp = ($type == 'combo') ? ($product->special_price ?? 0) : ($item->productVariant['special_price'] ?? 0);

                if (isset($product->is_prices_inclusive_tax) && $product->is_prices_inclusive_tax == 0) {
                    $total_tax = array_sum(array_map('floatval', $tax_percentage));

                    $price_tax_amount = $p * ($total_tax / 100);
                    $special_price_tax_amount = $sp * ($total_tax / 100);
                } else {
                    $price_tax_amount = 0;
                    $special_price_tax_amount = 0;
                }
                if ($product['cod_allowed'] == 0) {
                    $cod_allowed = 0;
                }
                $variant_id[$i] = $item->product_variant_id;
                $quantity[$i] = intval($item->qty);
                if ($type == 'combo') {
                    $combo_price = $product->price ?? 0;
                    $combo_special_price = $product->special_price ?? 0;

                    if ($combo_special_price > 0 && $combo_special_price < $combo_price) {
                        $total[$i] = ($combo_special_price + $special_price_tax_amount) * $item->qty;
                    } else {
                        $total[$i] = ($combo_price + $price_tax_amount) * $item->qty;
                    }
                } else {
                    $variant_price = $item->productVariant['price'] ?? 0;
                    $variant_special_price = $item->productVariant['special_price'] ?? 0;

                    if ($variant_special_price > 0 && $variant_special_price < $variant_price) {
                        $total[$i] = ($variant_special_price + $special_price_tax_amount) * $item->qty;
                    } else {
                        $total[$i] = ($variant_price + $price_tax_amount) * $item->qty;
                    }
                }


                // affilicate code
                $item->total_subtotal_amount = array_sum($total);

                // if (isset($affiliate_commission_data) && !empty($affiliate_commission_data)) {
                //     foreach ($affiliate_commission_data as $affiliate_commission_data_item) {
                //         $affiliate_commission_amount = ($item->total_subtotal_amount * $affiliate_commission_data_item['category_commission']) / 100;
                //         dd($item->comboProduct);
                //         if ($item->product?->id == $affiliate_commission_data_item['product_id']) {
                //             $item->affiliate_id = $affiliate_commission_data_item['affiliate_id'];
                //             $item->affiliate_token = $affiliate_commission_data_item['token'];
                //             $item->category_commission = $affiliate_commission_data_item['category_commission'];
                //             $item->affiliate_commission_amount = $affiliate_commission_amount;
                //         }
                //     }
                // } else {
                //     $item->affiliate_token = '';
                //     $item->affiliate_id = '';
                //     $item->category_commission = '';
                //     $item->affiliate_commission_amount = '';
                // }


                if (isset($affiliate_commission_data) && !empty($affiliate_commission_data)) {
                    foreach ($affiliate_commission_data as $affiliate_commission_data_item) {
                        $affiliate_commission_amount = ($item->total_subtotal_amount * $affiliate_commission_data_item['category_commission']) / 100;

                        // Check based on product_type
                        if (
                            ($item->product_type == 'regular' && $item->product?->id == $affiliate_commission_data_item['product_id']) ||
                            ($item->product_type == 'combo' && $item->comboProduct?->id == $affiliate_commission_data_item['product_id'])
                        ) {
                            $item->affiliate_id = $affiliate_commission_data_item['affiliate_id'];
                            $item->affiliate_token = $affiliate_commission_data_item['token'];
                            $item->category_commission = $affiliate_commission_data_item['category_commission'];
                            $item->affiliate_commission_amount = $affiliate_commission_amount;
                            break;
                        }
                    }
                } else {
                    $item->affiliate_token = '';
                    $item->affiliate_id = '';
                    $item->category_commission = '';
                    $item->affiliate_commission_amount = '';
                }

                // affilicate code end

                $item_total[$i] = ($p + $price_tax_amount) * $item->qty;

                if ($item->productVariant) {
                    $item->productVariant['special_price'] = $item->productVariant['special_price'] + $special_price_tax_amount;
                    $item->productVariant['id'] = $item->product_variant_id;
                    $item->productVariant['price'] = $item->productVariant['price'] + $price_tax_amount;
                }
                if ($item->comboProduct) {
                    $item->comboProduct['special_price'] = $item->comboProduct['special_price'] + $special_price_tax_amount;
                    $item->comboProduct['price'] = $item->comboProduct['price'] + $price_tax_amount;
                }
                $item->id = $item->product_variant_id;

                $percentage[$i] = (isset($item->tax_percentage) && ($item->tax_percentage) > 0) ? $item->tax_percentage : 0;

                if ($percentage[$i] !== null && $percentage[$i] > 0) {
                    $amount[$i] = !empty($special_price_tax_amount) ? $special_price_tax_amount : $price_tax_amount;
                    $amount[$i] = $amount[$i] * $item->qty;
                } else {
                    $amount[$i] = 0;
                    $percentage[$i] = 0;
                }
                // dd($item->product_type);
                if ($item->product_type != 'combo') {
                    $item->product_variants = app(ProductService::class)->getVariantsValuesById($item->id);
                } else {
                    $item->type = 'combo';
                }
                array_push($download_allowed, $item->download_allowed);

                $item->cart_product_type = $item->product_type;
                $item->cart_count = $query->count();

                $item->total_items = $totalItems;
                $product_qty .= $item->qty . ',';

                $query[$i] = (object) $item;

                $item->image = app(MediaService::class)->getMediaImageUrl($item->image);
                // dd($item->productVariant);
                $items[] = $item;
                // dd($items);
            }

            $total = array_sum($total);
            $item_total = array_sum($item_total);


            $settings = app(DeliveryService::class)->getDeliveryChargeSetting($store_id);

            $shipping_settings = app(SettingService::class)->getSettings('shipping_method', true);
            $shipping_settings = json_decode($shipping_settings, true);

            $delivery_charge = '';
            // dd($address_id);
            if (!empty($address_id)) {
                $address = fetchDetails(Address::class, ['id' => $address_id], ['area_id', 'area', 'pincode', 'city']);
                $pincode = !$address->isEmpty() ? $address[0]->pincode : 0;
                $address_city = !$address->isEmpty() ? $address[0]->city : '';
                $zipcode_id = !$address->isEmpty() ? fetchDetails(Zipcode::class, ['zipcode' => $pincode], 'id') : collect();
                $city_id = !$address->isEmpty() ? fetchDetails(City::class, ['name->en' => $address_city], 'id') : collect();

                // NEW LOGIC: Check deliverability for ALL items first
                $zipcode_id_val = !$zipcode_id->isEmpty() ? $zipcode_id[0]->id : '';
                $city_id_val = !$city_id->isEmpty() ? $city_id[0]->id : '';
                $city_val = $address_city;

                $product_availability = app(DeliveryService::class)->checkCartProductsDeliverable($user_id, $pincode, $zipcode_id_val, $store_id, $city_val, $city_id_val, $is_saved_for_later);

                $use_standard_shipping = false;
                if (isset($shipping_settings['shiprocket_shipping_method']) && $shipping_settings['shiprocket_shipping_method'] == 1) {
                    if (!empty($product_availability)) {
                        foreach ($product_availability as $pa) {
                            if (isset($pa['delivery_by']) && $pa['delivery_by'] === 'standard_shipping') {
                                $use_standard_shipping = true;
                                break;
                            }
                        }
                    }
                }

                if ($use_standard_shipping) {
                    // Force Shiprocket Logic for the entire cart
                    // Use collect($items) instead of $query to ensure we use the processed items (matching Checkout logic)
                    $parcels = app(ShiprocketService::class)->makeShippingParcels(collect($items));
                    $parcels_details = app(ShiprocketService::class)->checkParcelsDeliverability($parcels, $pincode, $total);
                    $delivery_charge = $parcels_details['delivery_charge_without_cod'];
                } else {
                    // Local Shipping Logic
                    if (!empty($product_availability)) {
                        for ($i = 0; $i < count($query); $i++) {
                            if (isset($product_availability[$i])) {
                                $cart[$i]['product_qty'] = $product_availability[$i]['product_qty'] ?? $cart[$i]['qty'];
                                $cart[$i]['minimum_free_delivery_order_qty'] = $product_availability[$i]['minimum_free_delivery_order_qty'] ?? 0;
                                $cart[$i]['product_delivery_charge'] = $product_availability[$i]['product_delivery_charge'] ?? 0;
                                $cart[$i]['currency_product_delivery_charge_data'] = app(CurrencyService::class)->getPriceCurrency($cart[$i]['product_delivery_charge']);

                                if (isset($product_availability[$i]['delivery_by']) && $product_availability[$i]['delivery_by'] == "standard_shipping") {
                                    $standard_shipping_cart[] = $cart[$i];
                                } else {
                                    $local_shipping_cart[] = $cart[$i];
                                }
                            }
                        }
                    }

                    $delivery_charge = app(DeliveryService::class)->getDeliveryCharge($address_id, $total, $local_shipping_cart, $store_id);

                    if (isset($settings[0]->delivery_charge_type) && !empty($settings[0]->delivery_charge_type) && $settings[0]->delivery_charge_type == 'product_wise_delivery_charge') {
                        $deliveryCharge = 0;
                        if (is_array($delivery_charge)) {
                            foreach ($delivery_charge as $row) {
                                $deliveryCharge += isset($row['delivery_charge']) && !empty($row['delivery_charge']) ? (float) str_replace(',', '', $row['delivery_charge']) : 0;
                            }
                        }
                        $delivery_charge = $deliveryCharge;
                    } else {
                        $delivery_charge = (float) str_replace(',', '', $delivery_charge);
                    }
                }
            }

            // dd($items);
            $delivery_charge = isset($query[0]->type) && $query[0]->type == 'digital_product' ? 0 : $delivery_charge;
            $discount = $item_total - $total;
            // dd($total);
            $tax_amount = array_sum($amount);
            $overall_amt = (float) $total + (float) $delivery_charge;
            $query[0]->is_cod_allowed = $cod_allowed;
            $query['sub_total'] = strval($total);
            $query['item_total'] = strval($item_total);
            $query['discount'] = strval($discount);
            $query['currency_sub_total_data'] = app(CurrencyService::class)->getPriceCurrency($query['sub_total']);
            $query['product_quantity'] = $product_qty;
            $query['quantity'] = strval(array_sum($quantity));
            $query['tax_percentage'] = strval(array_sum(array_map('floatval', is_string($percentage) ? explode(',', $percentage) : $percentage)));
            $query['tax_amount'] = strval(array_sum($amount));
            $query['currency_tax_amount_data'] = app(CurrencyService::class)->getPriceCurrency($query['tax_amount']);
            $query['total_arr'] = $total;
            $query['currency_total_arr_data'] = app(CurrencyService::class)->getPriceCurrency($query['total_arr']);
            $query['variant_id'] = $variant_id;
            $query['delivery_charge'] = $delivery_charge;
            // Shiprocket operates in INR only - do not convert currency for Shiprocket delivery charges
            if (isset($use_standard_shipping) && $use_standard_shipping) {
                $query['currency_delivery_charge_data'] = 0;
            } else {
                $query['currency_delivery_charge_data'] = app(CurrencyService::class)->getPriceCurrency($query['delivery_charge']);
            }
            $query['overall_amount'] = strval($overall_amt);
            $query['currency_overall_amount_data'] = app(CurrencyService::class)->getPriceCurrency($query['overall_amount']);
            $query['amount_inclusive_tax'] = strval($overall_amt + $tax_amount);
            $query['currency_amount_inclusive_tax_data'] = app(CurrencyService::class)->getPriceCurrency($query['amount_inclusive_tax']);
            $query['download_allowed'] = $download_allowed;
            $query['cart_items'] = $items;
            // dd($query);
        }
        return $query;
    }

    public function isSingleSeller($product_variant_id, $user_id, $product_type = "", $store_id = '')
    {
        if (empty($product_variant_id) || empty($user_id)) {
            return false;
        }

        $variantIds = is_string($product_variant_id) && strpos($product_variant_id, ',') !== false
            ? explode(',', $product_variant_id)
            : (array) $product_variant_id;

        $carts = Cart::with([
            'productVariant.product.sellerData',
            'comboProduct'
        ])
            ->where('user_id', $user_id)
            ->where('is_saved_for_later', 0)
            ->where('store_id', $store_id)
            ->get();

        $sellerIds = [];

        foreach ($carts as $cart) {
            if ($cart->productVariant && $cart->productVariant->product && $cart->productVariant->product->sellerData) {
                $sellerIds[] = $cart->productVariant->product->sellerData->id;
            }

            if ($cart->comboProduct) {
                $sellerIds[] = $cart->comboProduct->seller_id;
            }
        }

        $uniqueSellerIds = array_values(array_unique(array_filter($sellerIds)));

        if (empty($uniqueSellerIds)) {
            return true;
        }

        $newSellerId = null;

        if ($product_type == 'regular') {
            $variant = Product_variants::with('product.sellerData')
                ->whereIn('id', $variantIds)
                ->first();

            $newSellerId = $variant?->product?->sellerData?->id;
        } else {
            $comboProduct = ComboProduct::whereIn('id', $variantIds)->first();
            $newSellerId = $comboProduct?->seller_id;
        }

        if (!empty($newSellerId)) {
            return in_array($newSellerId, $uniqueSellerIds);
        }

        return false;
    }
    public function isSingleProductType($product_variant_id, $user_id, $product_type, $store_id = '')
    {
        if (empty($product_variant_id) || empty($user_id)) {
            return false;
        }

        $variantIds = is_string($product_variant_id) && strpos($product_variant_id, ',') !== false
            ? explode(',', $product_variant_id)
            : (array) $product_variant_id;

        $productTypes = [];

        // 1️⃣ Get types from incoming product(s)
        if ($product_type == 'regular') {
            $productVariants = Product_variants::with('product')
                ->whereIn('id', $variantIds)
                ->get();

            foreach ($productVariants as $variant) {
                if ($variant->product) {
                    $productTypes[] = $variant->product->type;
                }
            }
        } else {
            $comboProducts = ComboProduct::whereIn('id', $variantIds)->get();
            foreach ($comboProducts as $combo) {
                $productTypes[] = $combo->product_type;
            }
        }

        // Flatten + clean types
        $productTypes = array_unique(array_filter($productTypes));

        $hasDigitalProduct = in_array('digital_product', $productTypes);
        $hasSimpleOrPhysical = array_intersect(['simple_product', 'variable_product', 'physical_product'], $productTypes);

        if ($hasDigitalProduct && !empty($hasSimpleOrPhysical)) {
            return false;
        }

        // 2️⃣ Get existing cart product types
        $carts = Cart::with(['productVariant.product', 'comboProduct'])
            ->where('user_id', $user_id)
            ->where('store_id', $store_id)
            ->where('is_saved_for_later', 0)
            ->get();

        $existingTypes = [];

        foreach ($carts as $cart) {
            if ($cart->productVariant && $cart->productVariant->product) {
                $existingTypes[] = $cart->productVariant->product->type;
            }
            if ($cart->comboProduct) {
                $existingTypes[] = $cart->comboProduct->product_type;
            }
        }

        $existingTypes = array_values(array_unique(array_filter($existingTypes)));

        // If no products in cart, allow
        if (empty($existingTypes)) {
            return true;
        }

        // 3️⃣ Get the new product type for comparison (assume first only)
        $newProductType = $productTypes[0] ?? null;

        if (!$newProductType) {
            return false;
        }

        // 4️⃣ Validate product type consistency
        if (in_array($newProductType, $existingTypes)) {
            return true;
        }

        if (
            !in_array('digital_product', $existingTypes) &&
            in_array($newProductType, ['simple_product', 'variable_product', 'physical_product'])
        ) {
            return true;
        }

        return false;
    }

    public function getCartCount($user_id, $store_id = '')
    {
        if (!empty($user_id)) {
            $count = Cart::where('user_id', $user_id)
                ->where('qty', '!=', 0)
                ->where('store_id', $store_id)
                ->where('is_saved_for_later', 0)
                ->distinct()
                ->count();
        } else {
            $count = 0;
        }
        return $count;
    }
    public function isVariantAvailableInCart($product_variant_id, $user_id)
    {
        // Use Eloquent to check if the variant is available in the cart\
        $cartItem = Cart::where('product_variant_id', $product_variant_id)
            ->where('user_id', $user_id)
            ->where('qty', '>', 0)
            ->where('is_saved_for_later', 0)
            ->select('id')
            ->first();


        return !is_null($cartItem);
    }
}
