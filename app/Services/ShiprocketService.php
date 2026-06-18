<?php

namespace App\Services;
use App\Models\OrderTracking;
use App\Libraries\Shiprocket;
use App\Models\Parcel;
use App\Models\Parcelitem;
use App\Models\OrderItems;
use App\Models\PickupLocation;
use App\Services\ParcelService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
class ShiprocketService
{

    public function getShiprocketOrder($shiprocket_order_id)
    {
        $shiprocket = new Shiprocket();
        $res = $shiprocket->get_specific_order($shiprocket_order_id);
        return $res;
    }



    public function generateAwb($shipment_id)
    {
        $order_tracking = fetchDetails(OrderTracking::class, ['shipment_id' => $shipment_id], 'courier_company_id');
        $courier_company_id = !$order_tracking->isEmpty() ? $order_tracking[0]->courier_company_id : "";

        // Debug: Log the courier company ID
        Log::info('Shiprocket AWB Generation - Courier Company ID', ['courier_company_id' => $courier_company_id, 'shipment_id' => $shipment_id]);

        $shiprocket = new Shiprocket();
        $res = $shiprocket->generate_awb($shipment_id);

        // Debug: Log the response structure
        Log::info('Shiprocket AWB Generation Response', ['response' => $res]);

        if (isset($res['awb_assign_status']) && $res['awb_assign_status'] == 1) {
            // Safely extract AWB code with proper checks
            $awb_code = '';
            if (isset($res['response']['data']['awb_code'])) {
                $awb_code = $res['response']['data']['awb_code'];
            } elseif (isset($res['awb_code'])) {
                $awb_code = $res['awb_code'];
            } elseif (isset($res['data']['awb_code'])) {
                $awb_code = $res['data']['awb_code'];
            }

            $order_tracking_data = [
                'awb_code' => $awb_code,
            ];
            $res_shippment_data = $shiprocket->get_order($shipment_id);
            updateDetails($order_tracking_data, ['shipment_id' => $shipment_id], OrderTracking::class);

            Log::info('AWB Generated Successfully', ['shipment_id' => $shipment_id, 'awb_code' => $awb_code]);
        } else {
            // AWB generation failed, log the error
            Log::error('AWB Generation Failed', [
                'shipment_id' => $shipment_id,
                'response' => $res,
                'courier_company_id' => $courier_company_id
            ]);

            // Don't try to extract AWB code if generation failed
            // Return the error response as is
        }

        return $res;
    }

    public function sendPickupRequest($shipment_id)
    {

        $shiprocket = new Shiprocket();
        $res = $shiprocket->request_for_pickup($shipment_id);
        if (isset($res['pickup_status']) && $res['pickup_status'] == 1) {

            $order_tracking_data = [
                'pickup_status' => $res['pickup_status'],
                'pickup_scheduled_date' => $res['response']['pickup_scheduled_date'],
                'pickup_token_number' => $res['response']['pickup_token_number'],
                'status' => $res['response']['status'],
                'pickup_generated_date' => json_encode(array($res['response']['pickup_generated_date'])),
                'data' => $res['response']['data'],
            ];
            updateDetails($order_tracking_data, ['shipment_id' => $shipment_id], OrderTracking::class);
        }
        return $res;
    }

    public function cancelShiprocketOrder($shiprocket_order_id)
    {
        $shiprocket = new Shiprocket();
        $res = $shiprocket->cancel_order($shiprocket_order_id);

        $should_cancel_locally = false;
        if ((isset($res['status']) && $res['status'] == 200) || (isset($res['status_code']) && $res['status_code'] == 200)) {
            $should_cancel_locally = true;
        } else {
            // If the order is already cancelled or not found in Shiprocket, we should allow local cancellation to enable recreation
            $message = $this->extractErrorMessage($res);
            if (stripos($message, 'already cancelled') !== false || stripos($message, 'not found') !== false || stripos($message, 'invalid order') !== false || stripos($message, 'Cancelation is not allowed') !== false) {
                $should_cancel_locally = true;
                Log::info("Shiprocket order already cancelled or not found, proceeding with local cancellation", [
                    'shiprocket_order_id' => $shiprocket_order_id,
                    'message' => $message
                ]);
            }
        }

        if ($should_cancel_locally) {
            
            // Log for debugging
            Log::info("Attempting local cancellation for Shiprocket Order ID: " . $shiprocket_order_id);

            // Use direct update for reliability - ensure we update ALL matching records
            $updated = OrderTracking::where('shiprocket_order_id', $shiprocket_order_id)
                ->update(['is_canceled' => 1]);
            
            Log::info("Local cancellation update result: " . $updated . " rows affected.");

            // Verify the update was successful
            $verifyUpdate = OrderTracking::where('shiprocket_order_id', $shiprocket_order_id)
                ->where('is_canceled', 0)
                ->count();
            
            if ($verifyUpdate > 0) {
                Log::error("Cancellation flag update failed! Still have " . $verifyUpdate . " non-cancelled records", [
                    'shiprocket_order_id' => $shiprocket_order_id
                ]);
            }

            // $is_canceled = [
            //     'is_canceled' => 1,
            // ];
            // updateDetails($is_canceled, ['shiprocket_order_id' => $shiprocket_order_id], OrderTracking::class);
            $order_tracking = fetchDetails(OrderTracking::class, ['shiprocket_order_id' => $shiprocket_order_id]);

            $parcel_id = !$order_tracking->isEmpty() ? $order_tracking[0]->parcel_id : "";
            $shipment_id = !$order_tracking->isEmpty() ? $order_tracking[0]->shipment_id : "N/A";

            if (!empty($parcel_id)) {
                $uniqueStatus = ["processed"];
                $active_status = "cancelled";

                $old_active_status_data = fetchDetails(Parcel::class, ['id' => $parcel_id], ['active_status', 'store_id']);

                $old_active_status = !$old_active_status_data->isEmpty() ? $old_active_status_data[0]->active_status : "";
                $store_id = !$old_active_status_data->isEmpty() ? $old_active_status_data[0]->store_id : "";


                if ($old_active_status != "cancelled" && $old_active_status != "returned") {
                    // Only cancel the parcel, not the order items
                    // This allows sellers to recreate the Shiprocket order or use alternate shipping
                    app(OrderService::class)->updateOrder(['status' => 'cancelled'], ['id' => $parcel_id], true, "parcels", false, 0, Parcel::class);
                    app(OrderService::class)->updateOrder(['active_status' => $active_status], ['id' => $parcel_id], false, "parcels", false, 0, Parcel::class);
                    
                    Log::info("Cancelled Shiprocket order - parcel marked as cancelled, order items unchanged", [
                        'parcel_id' => $parcel_id,
                        'shipment_id' => $shipment_id
                    ]);
                }
                $parcel_details = app(ParcelService::class)->viewAllParcels($order_tracking[0]->order_id, $parcel_id, '', 0, 10, 'DESC', 1, '', '', $store_id);
                $res['data'] = $parcel_details->original['data'][0];
            }
            $res['status'] = 200; // Force success status for local flow if we've handled it
        }
        return $res;
    }
    public function updateShiprocketOrderStatus($tracking_id)
    {
        $order_tracking_details = fetchDetails(OrderTracking::class, ['tracking_id' => $tracking_id, 'is_canceled' => 0], ['order_id', 'parcel_id', 'shiprocket_order_id', 'order_item_id']);

        if ($order_tracking_details->isEmpty()) {
            return [
                'error' => true,
                'message' => "Something Went Wrong. Order Not Found.",
                'data' => []
            ];
        }
        $parcel_id = $order_tracking_details[0]->parcel_id;
        $order_id = $order_tracking_details[0]->order_id;
        $shiprocket_order_id = $order_tracking_details[0]->shiprocket_order_id;
        $shiprocket = new Shiprocket();
        $res = $shiprocket->tracking_order($shiprocket_order_id);

       

        if (isset($res[0]['tracking_data']) && !empty($res[0]['tracking_data'])) {

            $active_status = "";
            $status = [];
            $active_status_code = $res[0]['tracking_data']['shipment_status'];

            $awb_code = $res[0]['tracking_data']['shipment_track'][0]['awb_code'];
            $track_url = $res[0]['tracking_data']['track_url'];
            $data = [
                'url' => $track_url,
                'awb_code' => $awb_code
            ];

            if ($active_status_code != 8) {
                updateDetails($data, ['tracking_id' => $tracking_id], OrderTracking::class);
            }

            $track_activities = $res[0]['tracking_data']['shipment_track_activities'];
            $shiprocket_status_codes = config('eshop_pro.shiprocket_status_codes');

            foreach ($shiprocket_status_codes as $status) {

                if ($active_status_code == $status['code']) {
                    $active_status = $status['description'];
                }
                if (($track_activities) != null) {
                    foreach ($track_activities as $track_list) {
                        if ($track_list['sr-status'] == $status['code']) {
                            $data = [
                                $status['description'],
                                $track_list['date'],
                            ];
                            array_push($status, $data);
                        }
                    }
                }
            }

            if ($active_status == 'delivered') {
                $data = [
                    $active_status,
                    $res[0]['tracking_data']['shipment_track'][0]['delivered_date'] ?? date("Y-m-d") . " " . date("h:i:sa")
                ];
                array_push($status, $data);
            }
            if (empty($active_status) && empty($status)) {
                $response['error'] = true;
                $response['message'] = "Check Status Manually From Given Tracking Url!";
                $response['data'] = [
                    'track_url' => $track_url
                ];
                return $response;
            }
            $parcel_item_details = fetchDetails(ParcelItem::class, ['parcel_id' => $parcel_id]);

            $parcel_items = fetchDetails(Parcel::class, ['id' => $parcel_id]);
            // Relax validation: allow if parcel_items exists, even if parcel_item_details is empty (we'll fallback to order_item_id)
            if ($parcel_items->isEmpty()) {
                $response['error'] = true;
                $response['message'] = "Something Went Wrong. Order Not Found.";
                $response['data'] = [
                    'track_url' => $track_url
                ];
                return $response;
            }

            if (!empty($active_status) && empty($status)) {
                $status = [[$active_status, date("Y-m-d") . " " . date("h:i:sa")]];
            }
            if (empty($active_status) && !empty($status)) {
                $active_status = $parcel_items[0]->active_status;
            }

            $uniqueStatus = [];
            // remove duplicate status
            foreach ($status as $entry) {

                $status = $entry;
                if (!in_array($status, array_column($uniqueStatus, 0))) {
                    $uniqueStatus[] = $entry;
                }
            }

            $response_data = [];
            $active_status = str_replace(" ", "_", $active_status);
            if ($active_status == "cancelled" || $active_status == "cancellation_requested") {
                $data += [
                    'is_canceled' => 1
                ];
                $uniqueStatus = ["processed"];
                $active_status = "cancelled";
                updateDetails($data, ['tracking_id' => $tracking_id], OrderTracking::class);
            }
            $status = json_encode($uniqueStatus);
            if (app(OrderService::class)->updateOrder(['status' => 'cancelled'], ['id' => $parcel_id], true, "parcels", false, 0, Parcel::class)) {
                app(OrderService::class)->updateOrder(['active_status' => $active_status], ['id' => $parcel_id], false, "parcels", false, 0, Parcel::class);

                // Try to use parcel items first
                if (!$parcel_item_details->isEmpty()) {
                    foreach ($parcel_item_details as $item) {
                        app(OrderService::class)->updateOrder(['status' => 'cancelled'], ['id' => $item->order_item_id], true, "order_items", false, 0, OrderItems::class);
                        app(OrderService::class)->updateOrder(['active_status' => $active_status], ['id' => $item->order_item_id], false, "order_items", false, 0, OrderItems::class);
                        $data = [
                            'consignment_id' => $parcel_id,
                            'order_item_id' => $item->order_item_id,
                            'status' => $active_status
                        ];
                        array_push($response_data, $data);
                    }
                } elseif (isset($order_tracking_details[0]->order_item_id) && !empty($order_tracking_details[0]->order_item_id)) {
                    // Fallback to order_item_id from OrderTracking
                    $order_item_ids = explode(',', $order_tracking_details[0]->order_item_id);
                    foreach ($order_item_ids as $order_item_id) {
                        app(OrderService::class)->updateOrder(['status' => 'cancelled'], ['id' => $order_item_id], true, "order_items", false, 0, OrderItems::class);
                        app(OrderService::class)->updateOrder(['active_status' => $active_status], ['id' => $order_item_id], false, "order_items", false, 0, OrderItems::class);
                         $data = [
                            'consignment_id' => $parcel_id,
                            'order_item_id' => $order_item_id,
                            'status' => $active_status
                        ];
                        array_push($response_data, $data);
                    }
                }
            }
            if ($active_status == "cancelled") {
                $response['error'] = true;
                $response['message'] = "Shiprocket Order Is Cancelled!";
                $response['data'] = [
                    'track_url' => $track_url
                ];
            } else {
                $response['error'] = false;
                $response['message'] = "Status Updated Successfully";
                $response['data'] = $response_data;
            }
            return $response;
        } else {
            return [
                'error' => true,
                'message' => $tracking_data['error'] ?? 'Tracking data not available'
            ];
        }
    }

    public function generateLabel($shipment_id)
    {
        $shiprocket = new Shiprocket();
        $res = $shiprocket->generate_label($shipment_id);

        if (isset($res['label_created']) && $res['label_created'] == 1) {
            $label_data = [
                'label_url' => $res['label_url'],
            ];
            updateDetails($label_data, ['shipment_id' => $shipment_id], OrderTracking::class);
        }
        return $res;
    }

    public function generateInvoice($shiprocket_order_id)
    {
        $shiprocket = new Shiprocket();
        $res = $shiprocket->generate_invoice($shiprocket_order_id);

        if (isset($res['is_invoice_created']) && $res['is_invoice_created'] == 1) {
            $invoice_data = [
                'invoice_url' => $res['invoice_url'],
            ];
            updateDetails($invoice_data, ['shiprocket_order_id' => $shiprocket_order_id], OrderTracking::class);
        }
        return $res;
    }

    public function checkParcelsDeliverability($parcels, $userPincode, $cartTotal = 0)
    {
        $shiprocket = new Shiprocket();

        $minDays = $maxDays = $deliveryChargeWithCod = $deliveryChargeWithoutCod = 0;
        $data = [];

        foreach ($parcels as $sellerId => $parcel) {
            foreach ($parcel as $pickupLocation => $parcelWeight) {
                $pickupPostcode = fetchDetails(PickupLocation::class, ['id' => $pickupLocation], 'pincode');

                if (isset($parcel[$pickupLocation]['weight']) && $parcel[$pickupLocation]['weight'] > 15) {
                    $data = "More than 15kg weight is not allowed";
                } else {
                    $availabilityData = [
                        'pickup_postcode' => !$pickupPostcode->isEmpty() ? $pickupPostcode[0]->pincode : "",
                        'delivery_postcode' => $userPincode,
                        'cod' => 0,
                        'weight' => $parcelWeight['weight'],
                        'declared_value' => $cartTotal,
                    ];

                    $checkDeliverability = $shiprocket->check_serviceability($availabilityData);
                    $shiprocketData = $this->shiprocketRecommendedData($checkDeliverability);

                    $availabilityDataWithCod = [
                        'pickup_postcode' => !$pickupPostcode->isEmpty() ? $pickupPostcode[0]->pincode : "",
                        'delivery_postcode' => $userPincode,
                        'cod' => 1,
                        'weight' => $parcelWeight['weight'],
                        'declared_value' => $cartTotal,
                    ];

                    $checkDeliverabilityWithCod = $shiprocket->check_serviceability($availabilityDataWithCod);
                    $shiprocketDataWithCod = $this->shiprocketRecommendedData($checkDeliverabilityWithCod);

                    $data[$sellerId][$pickupLocation]['parcel_weight'] = $parcelWeight['weight'];
                    $data[$sellerId][$pickupLocation]['pickup_availability'] = isset($shiprocketData['pickup_availability']) ? $shiprocketData['pickup_availability'] : '';
                    $data[$sellerId][$pickupLocation]['courier_name'] = isset($shiprocketData['courier_name']) ? $shiprocketData['courier_name'] : '';
                    // Shiprocket rates are in INR only - no currency conversion
                    $data[$sellerId][$pickupLocation]['delivery_charge_with_cod'] = isset($shiprocketDataWithCod['rate']) ? $shiprocketDataWithCod['rate'] : 0;
                    $data[$sellerId][$pickupLocation]['currency_delivery_charge_with_cod'] = 0;
                    $data[$sellerId][$pickupLocation]['delivery_charge_without_cod'] = isset($shiprocketData['rate']) ? $shiprocketData['rate'] : 0;
                    $data[$sellerId][$pickupLocation]['currency_delivery_charge_without_cod'] = 0;

                    $data[$sellerId][$pickupLocation]['estimate_date'] = isset($shiprocketData['etd']) ? $shiprocketData['etd'] : '';
                    $data[$sellerId][$pickupLocation]['estimate_days'] = isset($shiprocketData['estimated_delivery_days']) ? $shiprocketData['estimated_delivery_days'] : '';

                    $minDays = isset($shiprocketData['estimated_delivery_days']) && (empty($minDays) || $shiprocketData['estimated_delivery_days'] < $minDays) ? $shiprocketData['estimated_delivery_days'] : $minDays;
                    $maxDays = isset($shiprocketData['estimated_delivery_days']) && (empty($maxDays) || $shiprocketData['estimated_delivery_days'] > $maxDays) ? $shiprocketData['estimated_delivery_days'] : $maxDays;

                    $deliveryChargeWithCod += $data[$sellerId][$pickupLocation]['delivery_charge_with_cod'];
                    $deliveryChargeWithoutCod += $data[$sellerId][$pickupLocation]['delivery_charge_without_cod'];
                }
            }
        }


        $deliveryDay = ($minDays == $maxDays) ? $minDays : $minDays . '-' . $maxDays;
        // Shiprocket rates are in INR only - show INR currency data only
        $shippingParcels = [
            'error' => false,
            'estimated_delivery_days' => $deliveryDay,
            'estimate_date' => isset($shiprocketData['etd']) ? $shiprocketData['etd'] : '',
            'delivery_charge' => 0,
            'delivery_charge_with_cod' => round($deliveryChargeWithCod),
            'delivery_charge_without_cod' => round($deliveryChargeWithoutCod),
            'data' => $data
        ];

        return $shippingParcels;
    }

    public function getShipmentId($itemId, $orderId)
    {
        $query = OrderTracking::select('*')
            ->where('order_id', $orderId)
            ->whereRaw('FIND_IN_SET(?, order_item_id) <> 0', [$itemId])
            ->get()
            ->toArray();

        return !empty($query) ? $query : false;
    }
    public function shiprocketRecommendedData($shiprocketData)
    {
        $result = [];


        if (isset($shiprocketData['data']) && !empty($shiprocketData['data'])) {

            if (isset($shiprocketData['data']['recommended_courier_company_id'])) {
                foreach ($shiprocketData['data']['available_courier_companies'] as $rd) {
                    if ($shiprocketData['data']['recommended_courier_company_id'] == $rd['courier_company_id']) {
                        $result = $rd;
                        break;
                    }
                }
            } else {
                foreach ($shiprocketData['data']['available_courier_companies'] as $rd) {
                    if ($rd['courier_company_id']) {
                        $result = $rd;
                        break;
                    }
                }
            }
            return $result;
        } else {
            return $shiprocketData;
        }
    }

    public function getAvailableShippingOptions($parcels, $userPincode, $isCod = 0, $cartTotal = 0)
    {
        $shiprocket = new Shiprocket();
        $options = [
            'recommended' => ['rate' => 0, 'etd' => '', 'days' => 0, 'courier' => ''],
            'cheapest' => ['rate' => 0, 'etd' => '', 'days' => 0, 'courier' => ''],
            'fastest' => ['rate' => 0, 'etd' => '', 'days' => 0, 'courier' => ''],
        ];

        foreach ($parcels as $sellerId => $parcel) {
            foreach ($parcel as $pickupLocation => $parcelWeight) {
                $pickupPostcode = fetchDetails(PickupLocation::class, ['id' => $pickupLocation], 'pincode');
                $availabilityData = [
                    'pickup_postcode' => !$pickupPostcode->isEmpty() ? $pickupPostcode[0]->pincode : "",
                    'delivery_postcode' => $userPincode,
                    'cod' => $isCod,
                    'weight' => $parcelWeight['weight'],
                    'declared_value' => $cartTotal,
                ];

                $serviceability = $shiprocket->check_serviceability($availabilityData);

                if (isset($serviceability['status']) && $serviceability['status'] == 200 && !empty($serviceability['data']['available_courier_companies'])) {
                    $couriers = $serviceability['data']['available_courier_companies'];
                    $recommendedId = $serviceability['data']['recommended_courier_company_id'] ?? null;

                    $bestRecommended = null;
                    $bestCheapest = null;
                    $bestFastest = null;

                    foreach ($couriers as $courier) {
                        // Recommended
                        if ($courier['courier_company_id'] == $recommendedId) {
                            $bestRecommended = $courier;
                        }
                        if ($bestRecommended === null && $courier['courier_company_id']) {
                             $bestRecommended = $courier; // Fallback to first if recommended not found
                        }

                        // Cheapest
                        if ($bestCheapest === null || $courier['rate'] < $bestCheapest['rate']) {
                            $bestCheapest = $courier;
                        }

                        // Fastest
                        if ($bestFastest === null || $courier['estimated_delivery_days'] < $bestFastest['estimated_delivery_days']) {
                            $bestFastest = $courier;
                        }
                    }

                    // Aggregate
                    if ($bestRecommended) {
                        $options['recommended']['rate'] += $bestRecommended['rate'];
                        $options['recommended']['days'] = max($options['recommended']['days'], $bestRecommended['estimated_delivery_days']);
                        $options['recommended']['etd'] = $bestRecommended['etd'];
                        $options['recommended']['courier'] = $bestRecommended['courier_name'];
                    }
                    if ($bestCheapest) {
                        $options['cheapest']['rate'] += $bestCheapest['rate'];
                        $options['cheapest']['days'] = max($options['cheapest']['days'], $bestCheapest['estimated_delivery_days']);
                        $options['cheapest']['etd'] = $bestCheapest['etd'];
                        $options['cheapest']['courier'] = $bestCheapest['courier_name'];
                    }
                    if ($bestFastest) {
                        $options['fastest']['rate'] += $bestFastest['rate'];
                        $options['fastest']['days'] = max($options['fastest']['days'], $bestFastest['estimated_delivery_days']);
                        $options['fastest']['etd'] = $bestFastest['etd'];
                        $options['fastest']['courier'] = $bestFastest['courier_name'];
                    }
                }
            }
        }

        // Shiprocket rates are in INR only - show INR formatted price only
        foreach ($options as $key => &$opt) {
            $opt['format_rate'] = '₹' . number_format($opt['rate'], 2);
        }

        return $options;
    }

    function makeShippingParcels(Collection $data)
{
    $parcels = collect();
    $data->each(function ($item) use (&$parcels) {
        // Cart items from getCartTotal() are stdClass objects
        // Product data is nested under 'product' or 'comboproduct' property
        $product = null;
        $isCombo = false;

        if (is_object($item)) {
            if (isset($item->product)) {
                $product = $item->product;
            } elseif (isset($item->comboproduct)) {
                $product = $item->comboproduct;
                $isCombo = true;
            }
        }

        if (!$product) {
            return; // Skip if no product data
        }

        // Get pickup location from product (handle both object and array)
        $pickupLocation = is_object($product) ? ($product->pickup_location ?? '') : ($product['pickup_location'] ?? '');

        if (trim($pickupLocation) !== '') {
            $sellerId = is_object($product) ? ($product->seller_id ?? 0) : ($product['seller_id'] ?? 0);

            // Get weight from product variants
            $weight = 0;
            if ($isCombo) {
                $weight = is_object($product) ? ($product->weight ?? 0) : ($product['weight'] ?? 0);
            } else {
                // For regular products, get from variants
                if (is_object($product) && isset($product->variants) && !empty($product->variants)) {
                    $firstVariant = is_object($product->variants) && method_exists($product->variants, 'first') ? $product->variants->first() : (is_array($product->variants) ? $product->variants[0] : null);
                    if ($firstVariant) {
                        $weight = is_object($firstVariant) ? ($firstVariant->weight ?? 0) : ($firstVariant['weight'] ?? 0);
                    }
                    } elseif (is_array($product) && isset($product['variants']) && !empty($product['variants'])) {
                    $weight = $product['variants'][0]['weight'] ?? 0;
                }
            }

            // Get quantity from cart item
            $qty = is_object($item) ? ($item->qty ?? 1) : ($item['qty'] ?? 1);
            if (!$parcels->has($sellerId)) {
                $parcels->put($sellerId, collect());
            }
            if (!$parcels[$sellerId]->has($pickupLocation)) {
                $parcels[$sellerId]->put($pickupLocation, collect(['weight' => 0]));
            }
            $parcels[$sellerId][$pickupLocation]['weight'] += $weight * $qty;
        }
    });
    return $parcels;
}

    /**
     * Extract a meaningful error message from Shiprocket response
     * 
     * @param array $res Shiprocket API response
     * @param string $default Default message if none found
     * @return string
     */
    public function extractErrorMessage($res, $default = 'Something went wrong')
    {
        if (empty($res)) {
            return $default;
        }

        // Check for specific courier remarks (often in AWB assignment failures)
        if (isset($res['response']['packages'][0]['remarks'])) {
            $remarks = $res['response']['packages'][0]['remarks'];
            if (is_array($remarks)) {
                return implode('. ', $remarks);
            }
            if (is_string($remarks)) {
                return $remarks;
            }
        }

        // Check for top-level message
        if (isset($res['message']) && !empty($res['message']) && is_string($res['message'])) {
            return $res['message'];
        }

        // Check for validation errors
        if (isset($res['errors']) && !empty($res['errors'])) {
            if (is_array($res['errors'])) {
                $first = reset($res['errors']);
                if (is_array($first)) {
                    return reset($first);
                }
                return (string)$first;
            }
            return (string)$res['errors'];
        }

        return $default;
    }

    /**
     * Get INR-only currency data for Shiprocket charges
     * Since Shiprocket only supports India, we return only INR currency info
     *
     * @param float $price Price in INR
     * @return array Currency data with only INR
     */
    private function getINRCurrencyData($price)
    {
        return [
            'INR' => [
                'currency_code' => 'INR',
                'symbol' => '₹',
                'exchange_rate' => '1.00',
                'amount' => number_format((float)$price, 2, '.', '')
            ]
        ];
    }
}
