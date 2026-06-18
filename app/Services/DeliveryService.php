<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Address;
use App\Models\City;
use App\Models\Zipcode;
use App\Models\ComboProduct;
use App\Models\Store;
use App\Models\Zone;
use App\Models\Area;
use App\Models\Product;
use App\Models\SellerStore;
use App\Models\PickupLocation;
use App\Libraries\Shiprocket;
use App\Services\CurrencyService;
use App\Services\SettingService;

class DeliveryService
{
    public function getDeliveryChargeSetting($store_id)
    {
        $res = fetchDetails(Store::class, ['id' => $store_id], ['delivery_charge_type', 'delivery_charge_amount', 'minimum_free_delivery_amount', 'product_deliverability_type']);
        if (!$res->isEmpty()) {
            return $res;
        } else {
            return false;
        }
    }
    public function getDeliveryCharge($address_id, $total = 0, $cartData = [], $store_id = "")
    {
        // dd($cartData);
        if (isset($cartData) && isset($cartData[0]['type']) && !empty($cartData[0]['type'])) {
            $has_digital_product = !empty(array_filter($cartData, function ($item) {
                return isset($item['type']) && $item['type'] === 'digital_product';
            }));

            if ($has_digital_product) {
                return number_format(0, 2);
            }
        }


        $total = str_replace(',', '', $total);

        $settings = $this->getDeliveryChargeSetting($store_id);

        $address = Address::where('id', $address_id)->value('pincode');
        $address_city_id = Address::where('id', $address_id)->value('city_id');
        // dd($settings[0]->product_deliverability_type);
        if (isset($settings[0]->product_deliverability_type) && !empty($settings[0]->product_deliverability_type)) {
            if ($settings[0]->product_deliverability_type == 'city_wise_deliverability') {
                // dd($settings[0]->delivery_charge_type);
                if (isset($settings[0]->delivery_charge_type) && !empty($settings[0]->delivery_charge_type) && $settings[0]->delivery_charge_type == 'city_wise_delivery_charge') {

                    // Initialize variables with defaults to prevent undefined variable errors
                    $min_amount = 0;
                    $delivery_charge = 0;

                    if (isset($address_city_id) && !empty($address_city_id)) {
                        $city = City::where('id', $address_city_id)
                            ->select('delivery_charges', 'minimum_free_delivery_order_amount')
                            ->first();
                        
                        if ($city) {
                            $min_amount = $this->sanitizeAmount($city->minimum_free_delivery_order_amount ?? 0);
                            $delivery_charge = $this->sanitizeAmount($city->delivery_charges ?? 0);
                        }
                    }
                    
                    $d_charge = intval($total) < $min_amount || $total === 0 ? $delivery_charge : 0;
                    return $this->formatRaw($d_charge);
                } else if (isset($settings[0]->delivery_charge_type) && !empty($settings[0]->delivery_charge_type) && $settings[0]->delivery_charge_type == 'global_delivery_charge') {

                    $min_amount = $this->sanitizeAmount($settings[0]->minimum_free_delivery_amount);
                    $delivery_charge = $this->sanitizeAmount($settings[0]->delivery_charge_amount);
                    $d_charge = intval($total) < $min_amount || $total === 0 ? $delivery_charge : 0;

                    return $this->formatRaw($d_charge);
                } else if (isset($settings[0]->delivery_charge_type) && !empty($settings[0]->delivery_charge_type) && $settings[0]->delivery_charge_type == 'product_wise_delivery_charge') {
                    $d_charge = [];
                    foreach ($cartData as $row) {
                        // dd($row['product_qty'] < $row['minimum_free_delivery_order_qty']);
                        // $temp['delivery_charge'] = $row['product_qty'] < $row['minimum_free_delivery_order_qty'] ? number_format($row['product_delivery_charge'], 2) : [];
                        $temp['delivery_charge'] = $this->formatRaw($this->sanitizeAmount($row['product_delivery_charge']));
                        array_push($d_charge, $temp);
                    }
                    return $d_charge;
                }
            } else {

                if (isset($settings[0]->delivery_charge_type) && !empty($settings[0]->delivery_charge_type) && $settings[0]->delivery_charge_type == 'zipcode_wise_delivery_charge') {

                    if (isset($address) && !empty($address)) {
                        $zipcode = Zipcode::where('zipcode', $address)->select('delivery_charges', 'minimum_free_delivery_order_amount')->first();

                        if ($zipcode) {
                            $min_amount = $this->sanitizeAmount($zipcode->minimum_free_delivery_order_amount ?? 0);
                            $delivery_charge = $this->sanitizeAmount($zipcode->delivery_charges ?? 0);

                            $d_charge = intval($total) < $min_amount || $total == 0 ? $delivery_charge : 0;
                            return $this->formatRaw($d_charge);
                        } else {
                            // No zipcode found, handle safely
                            return $this->formatRaw(0);
                        }
                    } else {
                        // Address empty, handle safely
                        return $this->formatRaw(0);
                    }
                } else if (isset($settings[0]->delivery_charge_type) && !empty($settings[0]->delivery_charge_type) && $settings[0]->delivery_charge_type == 'global_delivery_charge') {
                    $min_amount = $this->sanitizeAmount($settings[0]->minimum_free_delivery_amount);
                    $delivery_charge = $this->sanitizeAmount($settings[0]->delivery_charge_amount);
                    $d_charge = intval($total) < $min_amount || $total === 0 ? $delivery_charge : 0;
                    return $this->formatRaw($d_charge);
                } else if (isset($settings[0]->delivery_charge_type) && !empty($settings[0]->delivery_charge_type) && $settings[0]->delivery_charge_type == 'product_wise_delivery_charge') {
                    $d_charge = [];
                    foreach ($cartData as $row) {
                        $temp['delivery_charge'] = $this->formatRaw($this->sanitizeAmount($row['product_delivery_charge']));
                        array_push($d_charge, $temp);
                    }
                    return $d_charge;
                }
            }
        }
    }

    /**
     * Normalize formatted currency strings (e.g., "2,000") to float.
     */
    private function sanitizeAmount($value): float
    {
        if (is_null($value) || $value === '') {
            return 0.0;
        }

        // Normalize commas first (e.g., "2,000.00" => "2000.00")
        $normalized = str_replace(',', '', (string) $value);

        // If the remaining string is a standard decimal with max two places, keep the dot.
        if (preg_match('/^\d+\.\d{1,2}$/', $normalized)) {
            return (float) $normalized;
        }

        // Otherwise, treat dots as thousand separators too (e.g., "2.000" => "2000")
        $normalized = str_replace('.', '', $normalized);

        return (float) $normalized;
    }

    /**
     * Return a raw float with 2 decimal precision for consistent downstream math.
     */
    private function formatRaw($value): float
    {
        return round((float) $value, 2);
    }

    public function isProductDelivarable($type, $type_id, $product_id, $product_type = '')
    {
        $zipcode_id = null;
        $city_id = null;

        // Determine location
        switch ($type) {
            case 'zipcode':
                $zipcode_id = $type_id;
                break;
            case 'area':
                $zipcode_id = Area::where('id', $type_id)->value('zipcode_id');
                break;
            case 'city':
                $city_id = $type_id;
                break;
            default:
                return false;
        }

        $isCombo = in_array($product_type, ['combo', 'combo-product']);
        $model = $isCombo ? ComboProduct::class : Product::class;
        $table = $isCombo ? 'combo_products' : 'products';

        // Get zones only if needed
        $zone_ids = [];
        $isDeliverable = $model::join('seller_store', "$table.seller_id", '=', 'seller_store.seller_id')
            ->where("$table.id", $product_id)
            ->where(function ($query) use ($type, $type_id, $table, $model, $product_id, &$zone_ids, $zipcode_id, $city_id) {
                // Always allow deliverable_type = 1
                $query->where("$table.deliverable_type", 1);
                // dd($product_id);
                // Add condition for deliverable_type = 2 only if zone_ids exist
                $query->orWhere(function ($q) use ($table, $zipcode_id, $city_id, $model, $product_id, &$zone_ids) {
                    // Get zone_ids only here
                    if ($zipcode_id) {
                        $zone_ids = $this->getZonesServiceableByZipcode($this->getDeliverableZones($model, $product_id), $zipcode_id);
                    } elseif ($city_id) {
                        $zone_ids = $this->getZonesServiceableByCity($this->getDeliverableZones($model, $product_id), $city_id);
                    }

                    if (!empty($zone_ids)) {
                        $q->where("$table.deliverable_type", 2)
                            ->where(function ($inner) use ($zone_ids, $table) {
                                foreach ($zone_ids as $zoneId) {
                                    $inner->orWhereRaw("FIND_IN_SET(?, $table.deliverable_zones)", [$zoneId]);
                                }
                            });
                    } else {
                        // if zone_ids are empty, this OR condition is ignored
                        $q->whereRaw('0 = 1');
                    }
                });
            });

        return $isDeliverable->exists();

    }


    public function isSellerDeliverable($type, $type_id, $seller_id, $store_id = '')
    {
        if ($type == 'zipcode') {
            $zipcode_id = $type_id;
        } elseif ($type == 'area') {
            $zipcode_id = Area::where('id', $type_id)->value('zipcode_id');
        } elseif ($type == 'city') {
            $city_id = $type_id;
        } else {
            return false;
        }


        $seller_store = SellerStore::where('seller_id', $seller_id)->where('store_id', $store_id)->first();
        if (!$seller_store) {
             return false;
        }

        if ($seller_store->deliverable_type == 1) {
                return true;
            }

            if (!empty($zipcode_id) && $zipcode_id != 0) {
                // dd('here');
                $deliverable_zones = $this->getSellerDeliverableZones($seller_id, $store_id);

                if ($seller_store) {
                    if ($seller_store->deliverable_type == 1) {
                        return true;
                    } else {
                        // Check using FIND_IN_SET to match within comma-separated values
                        $zones_serviceable_zipcodes = $this->getZonesServiceableByZipcode($deliverable_zones, $zipcode_id);
                        if (count($zones_serviceable_zipcodes) == 1) {
                            if ($zones_serviceable_zipcodes) {
                                $product = SellerStore::whereRaw("FIND_IN_SET(?, deliverable_zones)", [$zones_serviceable_zipcodes])
                                    ->where('seller_id', $seller_id)
                                    ->where('store_id', $store_id)
                                    ->count();


                                return $product > 0;
                            }
                        } else {
                            if ($zones_serviceable_zipcodes) {
                                $product = SellerStore::where('store_id', $store_id)->where('seller_id', $seller_id)
                                    ->where(function ($query) use ($zones_serviceable_zipcodes) {
                                        $query->where(function ($subquery) use ($zones_serviceable_zipcodes) {
                                            $subquery->where("seller_store.deliverable_type", '2')
                                                ->whereIn("seller_store.deliverable_zones", $zones_serviceable_zipcodes);
                                        });
                                    })
                                    ->count();
                                return $product > 0;
                            }
                        }
                        return false;
                    }
                }
            } elseif (!empty($city_id) && $city_id != 0) {
                $deliverable_zones = $this->getSellerDeliverableZones($seller_id, $store_id);
                // $seller_store = SellerStore::where('seller_id', $seller_id)->where('store_id', $store_id)->first();
                if ($seller_store) {
                    if ($seller_store->deliverable_type == 1) {
                        return true;
                    } else {
                        // Check using FIND_IN_SET to match within comma-separated values
                        $zones_serviceable_cities = $this->getZonesServiceableByCity($deliverable_zones, $city_id);
                        if (count($zones_serviceable_cities) == 1) {
                            // dd('here');
                            $product = SellerStore::whereRaw("FIND_IN_SET(?, deliverable_zones)", [$zones_serviceable_cities])
                                ->where('seller_id', $seller_id)
                                ->where('store_id', $store_id)
                                ->count();
                            // dd($product);
                            return $product > 0;
                        } else {
                            if ($zones_serviceable_cities) {
                                $product = SellerStore::where('store_id', $store_id)->where('seller_id', $seller_id)
                                    ->where(function ($query) use ($zones_serviceable_cities) {
                                        $query->where(function ($subquery) use ($zones_serviceable_cities) {
                                            $subquery->where("seller_store.deliverable_type", '2')
                                                ->whereIn("seller_store.deliverable_zones", $zones_serviceable_cities);
                                        });
                                    })
                                    ->count();
                                // dd($product);
                                return $product > 0;
                            }
                        }
                        // return false;
                    }
                }
            } else {
                return false;
            }
    }
    public function getSellerDeliverableZones($seller_id, $store_id)
    {
        $seller_deliverable_data = fetchDetails(SellerStore::class, ['seller_id' => $seller_id, 'store_id' => $store_id], 'deliverable_zones');
        return !$seller_deliverable_data->isEmpty() ? explode(',', $seller_deliverable_data[0]->deliverable_zones) : [];
    }
    public function getDeliverableZonesOld($productTypeTable, $productId)
    {
        $deliverable_zones = fetchDetails($productTypeTable, ['id' => $productId], 'deliverable_zones');
        return !$deliverable_zones->isEmpty() ? explode(',', $deliverable_zones[0]->deliverable_zones) : [];
    }

    public function getDeliverableZones(string $modelClass, ?int $productId): array
    {
        if (is_null($productId)) {
            return [];
        }

        $product = $modelClass::find($productId);

        if (!$product || empty($product->deliverable_zones)) {
            return [];
        }

        return explode(',', $product->deliverable_zones);
    }

    public function getZonesServiceableByZipcode($deliverableZones, $zipcodeId)
    {
        return Zone::whereIn('id', $deliverableZones)
            ->where('status', 1)
            ->get(['id', 'serviceable_zipcode_ids'])
            ->filter(function ($zone) use ($zipcodeId) {
                return in_array($zipcodeId, explode(',', $zone->serviceable_zipcode_ids));
            })
            ->pluck('id')
            ->all();
    }

    public function getZonesServiceableByCity($deliverableZones, $cityId)
    {
        return Zone::whereIn('id', $deliverableZones)
            ->where('status', 1)
            ->get(['id', 'serviceable_city_ids'])
            ->filter(function ($zone) use ($cityId) {
                return in_array($cityId, explode(',', $zone->serviceable_city_ids));
            })
            ->pluck('id')
            ->all();
    }

    public function checkProductDeliverable($product_id, $zipcode = "", $zipcode_id = "", $store_id = '', $city_id = "", $product_type = 'regular', $declared_value = 0)
    {
        $products = $tmpRow = array();
        $settings = app(SettingService::class)->getSettings('shipping_method', true);
        $settings = json_decode($settings, true);
        $product_weight = 0;
        if ($product_type == "combo") {
            $product = app(ComboProductService::class)->fetchComboProduct(id: $product_id);
        } else {
            $product = app(ProductService::class)->fetchProduct(id: $product_id);
        }
        /* check in local shipping first */
        $tmpRow['is_deliverable'] = false;
        $tmpRow['delivery_by'] = '';
                if (isset($product['total']) && $product['total'] >= 1) {
            if ($product_type == "combo") {
                if (isset($product['combo_product']) && is_array($product['combo_product']) && !empty($product['combo_product'])) {
                    $product = $product['combo_product'][0];
                } else {
                    $tmpRow['is_deliverable'] = false;
                    $tmpRow['message'] = "Product data not found";
                    $products[] = $tmpRow;
                    return $products;
                }
            } else {
                if (isset($product['product']) && is_array($product['product']) && !empty($product['product'])) {
                    $product = $product['product'][0];
                } else {
                    $tmpRow['is_deliverable'] = false;
                    $tmpRow['message'] = "Product data not found";
                    $products[] = $tmpRow;
                    return $products;
                }
            }
            if (isset($settings['local_shipping_method']) && $settings['local_shipping_method'] == 1) {
                $deliverabilitySettings = $this->getDeliveryChargeSetting($store_id);
                if (isset($deliverabilitySettings[0]->product_deliverability_type) && !empty($deliverabilitySettings[0]->product_deliverability_type)) {
                    if ($deliverabilitySettings[0]->product_deliverability_type == 'city_wise_deliverability') {
                        $tmpRow['is_deliverable'] = (!empty($city_id) && $city_id > 0) ?
                            $this->isProductDelivarable('city', $city_id, $product['id'], $product_type)
                            : false;
                    } else {
                        $tmpRow['is_deliverable'] = !empty($zipcode_id) && $zipcode_id > 0 ?
                            $this->isProductDelivarable('zipcode', $zipcode_id, $product['id'], $product_type) :
                            false;
                    }
                }
                $tmpRow['delivery_by'] = isset($tmpRow['is_deliverable']) && $tmpRow['is_deliverable'] ? 'local' : '';
            }
            /* check in standard shipping then */
            if (isset($settings['shiprocket_shipping_method']) && $settings['shiprocket_shipping_method'] == 1) {
                if (!$tmpRow['is_deliverable'] && $product['pickup_location'] != "") {
                    $shiprocket = new Shiprocket();
                    $pickup_location_data = fetchDetails(PickupLocation::class, ['id' => $product['pickup_location'], 'status' => 1], ['pincode', 'pickup_location']);
                    
                    if ($pickup_location_data->isEmpty()) {
                        // Try to get the location name even if not verified
                        $unverified_location = fetchDetails(PickupLocation::class, ['id' => $product['pickup_location']], 'pickup_location');
                        $location_display = !$unverified_location->isEmpty() ? $unverified_location[0]->pickup_location : "ID {$product['pickup_location']}";
                        
                        \Log::warning('Shiprocket: Pickup location not verified', [
                            'product_id' => $product['id'],
                            'pickup_location_id' => $product['pickup_location'],
                            'context' => 'checkProductDeliverable'
                        ]);
                        $tmpRow['is_deliverable'] = false;
                        $tmpRow['message'] = "Pickup location '{$location_display}' is not verified. Please verify it in Admin Panel > Pickup Locations.";
                        $tmpRow['product_id'] = $product['id'];
                        $tmpRow['product_qty'] = 1;
                        $products[] = $tmpRow;
                        return $products;
                    }
                    
                    // Check if pickup location has a valid pincode
                    $pickup_pincode = !$pickup_location_data->isEmpty() ? $pickup_location_data[0]->pincode : "";
                    if (empty($pickup_pincode)) {
                        $location_name = !$pickup_location_data->isEmpty() ? $pickup_location_data[0]->pickup_location : "ID {$product['pickup_location']}";
                        \Log::warning('Shiprocket: Pickup location missing pincode', [
                            'product_id' => $product['id'],
                            'pickup_location_id' => $product['pickup_location'],
                            'pickup_location_name' => $location_name,
                            'context' => 'checkProductDeliverable'
                        ]);
                        $tmpRow['is_deliverable'] = false;
                        $tmpRow['message'] = "Pickup location '{$location_name}' does not have a pincode set. Please add pincode in Admin Panel > Pickup Locations.";
                        $tmpRow['product_id'] = $product['id'];
                        $tmpRow['product_qty'] = 1;
                        $products[] = $tmpRow;
                        return $products;
                    }
                    
                    if (isset($product['variants']) && is_array($product['variants']) && !empty($product['variants']) && isset($product['variants'][0]['weight'])) {
                        $product_weight += $product['variants'][0]['weight'] * 1;
                    }
                    
                    if (isset($zipcode) && !empty($zipcode)) {
                        if ($product_weight > 15) {
                            $tmpRow['is_deliverable'] = false;
                            $tmpRow['is_valid_wight'] = 0;
                            $tmpRow['message'] = "You cannot ship weight more then 15 KG";
                        } else {
                            $availibility_data = [
                                'pickup_postcode' => $pickup_pincode,
                                'delivery_postcode' => $zipcode,
                                'cod' => 0,
                                'weight' => $product_weight,
                                'declared_value' => $declared_value,
                            ];
                            
                            \Log::info('Shiprocket serviceability check', [
                                'product_id' => $product['id'],
                                'product_name' => $product['name'] ?? 'Unknown',
                                'request' => $availibility_data,
                                'pickup_location_id' => $product['pickup_location'],
                                'product_weight_calculated' => $product_weight,
                                'variant_weight' => $product['variants'][0]['weight'] ?? 'NOT SET',
                                'pickup_pincode_empty' => empty($availibility_data['pickup_postcode']) ? 'YES - THIS IS THE PROBLEM!' : 'NO',
                                'delivery_pincode_empty' => empty($availibility_data['delivery_postcode']) ? 'YES - THIS IS THE PROBLEM!' : 'NO',
                                'weight_is_zero' => $product_weight == 0 ? 'YES - THIS IS THE PROBLEM!' : 'NO',
                                'context' => 'checkProductDeliverable'
                            ]);
                          
                            
                            $check_deliveribility = $shiprocket->check_serviceability($availibility_data);
                            
                            \Log::info('Shiprocket response', [
                                'product_id' => $product['id'],
                                'response_status' => $check_deliveribility['status'] ?? $check_deliveribility['status_code'] ?? 'unknown',
                                'full_response' => $check_deliveribility,
                                'context' => 'checkProductDeliverable'
                            ]);
                            
                            if (isset($check_deliveribility['status_code']) && $check_deliveribility['status_code'] == 422) {
                                $diagnostic = [];
                                if (empty($availibility_data['pickup_postcode'])) {
                                    $diagnostic[] = "Pickup location pincode is missing";
                                }
                                if (empty($availibility_data['delivery_postcode'])) {
                                    $diagnostic[] = "Delivery pincode is missing";
                                }
                                if ($product_weight == 0) {
                                    $diagnostic[] = "Product weight is not set";
                                }
                                
                                $diagnosticMsg = !empty($diagnostic) ? " [" . implode(", ", $diagnostic) . "]" : "";
                                $tmpRow['is_deliverable'] = false;
                                $tmpRow['message'] = "Shiprocket: Invalid delivery pincode '{$zipcode}'" . $diagnosticMsg;
                            } else {
                                if (isset($check_deliveribility['status']) && $check_deliveribility['status'] == 200 && !empty($check_deliveribility['data']['available_courier_companies'])) {
                                    $tmpRow['is_deliverable'] = true;
                                    $tmpRow['delivery_by'] = "standard_shipping";
                                    $estimate_date = $check_deliveribility['data']['available_courier_companies'][0]['etd'];
                                    $tmpRow['estimate_date'] = $estimate_date;
                                    $_SESSION['valid_zipcode'] = $zipcode;
                                    $tmpRow['message'] = 'Product is deliverable by ' . $estimate_date;
                                } else {
                                    // Build diagnostic message
                                    $diagnostic = [];
                                    if (empty($availibility_data['pickup_postcode'])) {
                                        $diagnostic[] = "Pickup pincode missing";
                                    }
                                    if (empty($availibility_data['delivery_postcode'])) {
                                        $diagnostic[] = "Delivery pincode missing";
                                    }
                                    if ($product_weight == 0) {
                                        $diagnostic[] = "Weight is 0";
                                    }
                                    
                                    $diagnosticMsg = !empty($diagnostic) ? " [ISSUE: " . implode(", ", $diagnostic) . "]" : "";
                                    
                                    $tmpRow['is_deliverable'] = false;
                                    $tmpRow['message'] = ($check_deliveribility['message'] ?? 'Shiprocket: No courier companies service this pincode') . $diagnosticMsg;
                                    \Log::warning('Shiprocket: No courier available', [
                                        'product_id' => $product['id'],
                                        'zipcode' => $zipcode,
                                        'diagnostic' => $diagnostic,
                                        'response' => $check_deliveribility
                                    ]);
                                }
                            }
                        }
                    } else {
                        $tmpRow['is_deliverable'] = false;
                        $tmpRow['message'] = 'Please select zipcode to check the deliveribility of item.';
                    }
                }
            }
            $tmpRow['product_id'] = $product['id'];
            $tmpRow['product_qty'] = 1;
            $products[] = $tmpRow;
            if (!empty($products)) {
                return $products;
            } else {
                return false;
            }
        }
    }

    public function checkCartProductsDeliverable($user_id, $zipcode = "", $zipcode_id = "", $store_id = '', $city = "", $city_id = "", $is_saved_for_later = 0, $language_code = '')
    {
        $products = $tmpRow = array();
        
        $cart = app(CartService::class)->getCartTotal($user_id, false, $is_saved_for_later, '', $store_id);
        
        $settings = app(SettingService::class)->getSettings('shipping_method', true);
       
        $settings = json_decode($settings, true);

        if (!$cart->isEmpty()) {

            $product_weight = 0;

            for ($i = 0; $i < $cart[0]->cart_count; $i++) {
                $tmpRow['is_deliverable'] = false;
                $tmpRow['delivery_by'] = '';

                $productType = $cart[$i]->cart_product_type;
                $product = $productType === 'combo' ? $cart[$i]['comboproduct'] : $cart[$i]['product'];

                // Check if product is disabled
                if (isset($product['status']) && $product['status'] == 0) {
                    $tmpRow['is_deliverable'] = false;
                    $tmpRow['message'] = 'Product not available';
                    $tmpRow['language_message_key'] = 'product_not_available';
                    
                    // Product-specific values
                    $tmpRow['product_id'] = $product['id'];
                    $tmpRow['product_qty'] = $cart[$i]->qty;
                    $tmpRow['minimum_free_delivery_order_qty'] = $product['minimum_free_delivery_order_qty'] ?? 0;
                    $tmpRow['product_delivery_charge'] = $product['delivery_charges'] ?? 0;
                    $tmpRow['currency_product_delivery_charge_data'] = isset($product['delivery_charges']) ?
                        app(CurrencyService::class)->getPriceCurrency($product['delivery_charges']) : 0;
                    $tmpRow['variant_id'] = $cart[$i]['product_variant_id'];
                    
                    // Translation
                    $tmpRow['name'] = app(TranslationService::class)->getDynamicTranslation(
                        $productType === 'combo' ? ComboProduct::class : Product::class,
                        $productType === 'combo' ? 'title' : 'name',
                        $product['id'],
                        $language_code
                    );
                    
                    $products[] = $tmpRow;
                    continue;
                }
                
                if ((isset($settings['local_shipping_method']) && $settings['local_shipping_method'] == 1)) {
                    $deliverabilitySettings = $this->getDeliveryChargeSetting($store_id);

                    if (!empty($deliverabilitySettings[0]->product_deliverability_type)) {
                        if ($deliverabilitySettings[0]->product_deliverability_type == 'city_wise_deliverability') {
                            $seller_deliverable = $this->isSellerDeliverable('city', $city_id, $product['seller_id'], $store_id);

                            if ($seller_deliverable) {
                                $tmpRow['is_deliverable'] = $this->isProductDelivarable('city', $city_id, $product['id'], $productType);
                            }
                        } else {
                            $seller_deliverable = $this->isSellerDeliverable('zipcode', $zipcode_id, $product['seller_id'], $store_id);

                            if ($seller_deliverable) {
                                $tmpRow['is_deliverable'] = $this->isProductDelivarable('zipcode', $zipcode_id, $product['id'], $productType);
                            }
                        }
                    }
                    $tmpRow['delivery_by'] = $tmpRow['is_deliverable'] ? 'local' : '';
                }


                if (isset($settings['shiprocket_shipping_method']) && $settings['shiprocket_shipping_method'] == 1) {
                    if (!$tmpRow['is_deliverable'] && $product['pickup_location'] != "") {
                        $shiprocket = new Shiprocket();
                        $pickup_location_data = fetchDetails(PickupLocation::class, ['id' => $product['pickup_location'], 'status' => 1], ['pincode', 'pickup_location']);

                        if ($pickup_location_data->isEmpty()) {
                            // Try to get the location name even if not verified
                            $unverified_location = fetchDetails(PickupLocation::class, ['id' => $product['pickup_location']], 'pickup_location');
                            $location_display = !$unverified_location->isEmpty() ? $unverified_location[0]->pickup_location : "ID {$product['pickup_location']}";
                            
                            \Log::warning('Shiprocket: Pickup location not verified', [
                                'product_id' => $product['id'],
                                'pickup_location_id' => $product['pickup_location'],
                                'context' => 'checkCartProductsDeliverable'
                            ]);
                            $tmpRow['is_deliverable'] = false;
                            $tmpRow['message'] = "Pickup location '{$location_display}' is not verified. Please verify it in Admin Panel > Pickup Locations.";
                            $tmpRow['product_id'] = $product['id'];
                            $tmpRow['product_qty'] = $cart[$i]->qty;
                            $tmpRow['minimum_free_delivery_order_qty'] = $product['minimum_free_delivery_order_qty'];
                            $tmpRow['product_delivery_charge'] = $product['delivery_charges'];
                            $tmpRow['currency_product_delivery_charge_data'] = isset($product['delivery_charges']) ?
                                app(CurrencyService::class)->getPriceCurrency($product['delivery_charges']) : 0;
                            $tmpRow['variant_id'] = $cart[$i]['product_variant_id'];
                            $tmpRow['name'] = app(TranslationService::class)->getDynamicTranslation(
                                $productType === 'combo' ? ComboProduct::class : Product::class,
                                $productType === 'combo' ? 'title' : 'name',
                                $product['id'],
                                $language_code
                            );
                            $products[] = $tmpRow;
                            continue;
                        }

                        // Check if pickup location has a valid pincode
                        $pickup_pincode = !$pickup_location_data->isEmpty() ? $pickup_location_data[0]->pincode : "";
                        if (empty($pickup_pincode)) {
                            $location_name = !$pickup_location_data->isEmpty() ? $pickup_location_data[0]->pickup_location : "ID {$product['pickup_location']}";
                            \Log::warning('Shiprocket: Pickup location missing pincode', [
                                'product_id' => $product['id'],
                                'pickup_location_id' => $product['pickup_location'],
                                'pickup_location_name' => $location_name,
                                'context' => 'checkCartProductsDeliverable'
                            ]);
                            $tmpRow['is_deliverable'] = false;
                            $tmpRow['message'] = "Pickup location '{$location_name}' does not have a pincode set. Please add pincode in Admin Panel > Pickup Locations.";
                            $tmpRow['product_id'] = $product['id'];
                            $tmpRow['product_qty'] = $cart[$i]->qty;
                            $tmpRow['minimum_free_delivery_order_qty'] = $product['minimum_free_delivery_order_qty'];
                            $tmpRow['product_delivery_charge'] = $product['delivery_charges'];
                            $tmpRow['currency_product_delivery_charge_data'] = isset($product['delivery_charges']) ?
                                app(CurrencyService::class)->getPriceCurrency($product['delivery_charges']) : 0;
                            $tmpRow['variant_id'] = $cart[$i]['product_variant_id'];
                            $tmpRow['name'] = app(TranslationService::class)->getDynamicTranslation(
                                $productType === 'combo' ? ComboProduct::class : Product::class,
                                $productType === 'combo' ? 'title' : 'name',
                                $product['id'],
                                $language_code
                            );
                            $products[] = $tmpRow;
                            continue;
                        }

                        // FIX: Get weight from cart item's variant data
                        $variant_weight = 0;
                        
                        // Try to get weight from cart item directly first
                        if (isset($cart[$i]->variant_weight) && $cart[$i]->variant_weight > 0) {
                            $variant_weight = $cart[$i]->variant_weight;
                        } 
                        // Otherwise get from product data
                        elseif ($productType === 'combo') {
                            $variant_weight = $product['weight'] ?? 0;
                        } else {
                            // For regular products, try different possible locations for weight
                            if (isset($cart[$i]['weight']) && $cart[$i]['weight'] > 0) {
                                $variant_weight = $cart[$i]['weight'];
                            } elseif (isset($product['weight']) && $product['weight'] > 0) {
                                $variant_weight = $product['weight'];
                            } elseif (isset($product['variants']) && !empty($product['variants'])) {
                                // Handle both array and Collection
                                $firstVariant = is_array($product['variants']) ? ($product['variants'][0] ?? null) : $product['variants']->first();
                                if ($firstVariant && isset($firstVariant['weight'])) {
                                    $variant_weight = $firstVariant['weight'];
                                }
                            }
                        }
                        
                        $product_weight = $variant_weight * $cart[$i]->qty;

                        if (!empty($zipcode)) {
                            if ($product_weight > 15) {
                                $tmpRow['is_deliverable'] = false;
                                $tmpRow['is_valid_wight'] = 0;
                                $tmpRow['message'] = "You cannot ship weight more than 15 KG";
                            } else {
                                $availability_data = [
                                    'pickup_postcode' => $pickup_pincode,
                                    'delivery_postcode' => $zipcode,
                                    'cod' => 0,
                                    'weight' => $product_weight,
                                    'declared_value' => $cart['sub_total'] ?? 0,
                                ];

                                \Log::info('Shiprocket cart serviceability check - BEFORE API CALL', [
                                    'product_id' => $product['id'],
                                    'product_name' => $product['name'] ?? 'Unknown',
                                    'cart_weight' => $cart[$i]->weight ?? 'NOT SET',
                                    'cart_qty' => $cart[$i]->qty,
                                    'calculated_weight' => $product_weight,
                                    'request' => $availability_data,
                                    'pickup_pincode_empty' => empty($availability_data['pickup_postcode']) ? 'YES - PROBLEM!' : 'NO',
                                    'delivery_pincode_empty' => empty($availability_data['delivery_postcode']) ? 'YES - PROBLEM!' : 'NO',
                                    'weight_is_zero' => $product_weight == 0 ? 'YES - PROBLEM!' : 'NO',
                                    'context' => 'checkCartProductsDeliverable'
                                ]);

                                $check_deliveribility = $shiprocket->check_serviceability($availability_data);

                                \Log::info('Shiprocket cart serviceability check - AFTER API CALL', [
                                    'product_id' => $product['id'],
                                    'response_status' => $check_deliveribility['status'] ?? $check_deliveribility['status_code'] ?? 'unknown',
                                    'full_response' => $check_deliveribility,
                                    'context' => 'checkCartProductsDeliverable'
                                ]);

                                if (isset($check_deliveribility['status_code']) && $check_deliveribility['status_code'] == 422) {
                                    // Build diagnostic message to help identify the actual problem
                                    $diagnostic = [];
                                    if (empty($availability_data['pickup_postcode'])) {
                                        $diagnostic[] = "Pickup location pincode is missing";
                                    }
                                    if (empty($availability_data['delivery_postcode'])) {
                                        $diagnostic[] = "Delivery pincode is missing";
                                    }
                                    if ($product_weight == 0) {
                                        $diagnostic[] = "Product weight is not set";
                                    }
                                    
                                    $diagnosticMsg = !empty($diagnostic) ? " [Issue: " . implode(", ", $diagnostic) . "]" : "";
                                    $tmpRow['is_deliverable'] = false;
                                    $tmpRow['message'] = "Shiprocket: Invalid delivery pincode '{$zipcode}'" . $diagnosticMsg;
                                } else {
                                    if (
                                        isset($check_deliveribility['status']) &&
                                        $check_deliveribility['status'] == 200 &&
                                        !empty($check_deliveribility['data']['available_courier_companies'])
                                    ) {
                                        $tmpRow['is_deliverable'] = true;
                                        $tmpRow['delivery_by'] = "standard_shipping";
                                        $tmpRow['estimate_date'] = $check_deliveribility['data']['available_courier_companies'][0]['etd'];
                                        $_SESSION['valid_zipcode'] = $zipcode;
                                        $tmpRow['message'] = 'Product is deliverable by ' . $tmpRow['estimate_date'];
                                    } else {
                                        $tmpRow['is_deliverable'] = false;
                                        $tmpRow['message'] = $check_deliveribility['message'] ?? 'Shiprocket: No courier companies service this pincode';
                                        \Log::warning('Shiprocket: No courier available for cart item', [
                                            'product_id' => $product['id'],
                                            'zipcode' => $zipcode,
                                            'response' => $check_deliveribility
                                        ]);
                                    }
                                }
                            }
                        } else {
                            $tmpRow['is_deliverable'] = false;
                            $tmpRow['message'] = 'Please select zipcode to check the deliveribility of item.';
                        }
                    }
                }

                // Product-specific values
                $tmpRow['product_id'] = $product['id'];
                $tmpRow['product_qty'] = $cart[$i]->qty;
                $tmpRow['minimum_free_delivery_order_qty'] = $product['minimum_free_delivery_order_qty'];
                $tmpRow['product_delivery_charge'] = $product['delivery_charges'];
                $tmpRow['currency_product_delivery_charge_data'] = isset($product['delivery_charges']) ?
                    app(CurrencyService::class)->getPriceCurrency($product['delivery_charges']) : 0;

                $tmpRow['variant_id'] = $cart[$i]['product_variant_id'];

                // Translation
                $tmpRow['name'] = app(TranslationService::class)->getDynamicTranslation(
                    $productType === 'combo' ? ComboProduct::class : Product::class,
                    $productType === 'combo' ? 'title' : 'name',
                    $product['id'],
                    $language_code
                );

                $products[] = $tmpRow;
            }



            if (!empty($products)) {

                return $products;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    public function recalulateDeliveryCharge($address_id, $total, $old_delivery_charge, $store_id = '')
    {

        $settings = $this->getDeliveryChargeSetting($store_id);

        $min_amount = $settings[0]->minimum_free_delivery_amount;
        $d_charge = $old_delivery_charge;

        if ((isset($settings[0]->delivery_charge_type) && !empty($settings[0]->delivery_charge_type) && $settings[0]->delivery_charge_type == 'zipcode_wise_delivery_charge')) {


            if (isset($address_id) && !empty($address_id)) {
                $address = Address::where('id', $address_id)->value('pincode');
                $zipcode = Zipcode::where('zipcode', $address)->select('delivery_charges', 'minimum_free_delivery_order_amount')->first();

                if ($zipcode && isset($zipcode->minimum_free_delivery_order_amount)) {
                    $min_amount = $zipcode->minimum_free_delivery_order_amount;
                }
            }
        }

        if ($total < $min_amount) {
            if ($old_delivery_charge == 0) {
                if (isset($address_id) && !empty($address_id)) {
                    $d_charge = $this->getDeliveryCharge($address_id, '', '', $store_id);
                } else {
                    $d_charge = 0;
                }
            }
        }

        return $d_charge;
    }
}
