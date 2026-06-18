<?php

namespace App\Http\Controllers\Seller;

use App\Models\Seller;
use Illuminate\Http\Request;
use App\Models\PickupLocation;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use App\Traits\HandlesValidation;

class PickupLocationController extends Controller
{
    use HandlesValidation;
    public function index()
    {
        return view('seller.pages.forms.pickup_locations');
    }

    public function store(Request $request)
    {

        $user_id = Auth::user()->id;
        $seller_id = Seller::where('user_id', $user_id)->value('id');
        $rules = [
            'pickup_location' => 'required',
            'name' => 'required',
            'email' => 'required',
            'phone' => 'required',
            'city' => 'required',
            'state' => 'required',
            'country' => 'required',
            'pincode' => 'required',
            'address' => 'required',
            'address2' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
        ];

        if ($response = $this->HandlesValidation($request, $rules)) {
            return $response;
        }
        $location_data['seller_id'] = $seller_id ?? "";
        $location_data['pickup_location'] = $request->pickup_location ?? "";
        $location_data['name'] = $request->name ?? "";
        $location_data['email'] = $request->email ?? "";
        $location_data['phone'] = $request->phone ?? "";
        $location_data['city'] = $request->city ?? "";
        $location_data['country'] = $request->country ?? "";
        $location_data['state'] = $request->state ?? "";
        $location_data['pincode'] = $request->pincode ?? "";
        $location_data['address'] = $request->address ?? "";
        $location_data['address2'] = $request->address2 ?? "";
        $location_data['longitude'] = $request->longitude ?? "";
        $location_data['latitude'] = $request->latitude ?? "";
        $location_data['status'] = 0; // Pending approval by admin


        try {
            PickupLocation::create($location_data);

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => labels('admin_labels.pickup_location_created_successfully', 'Pickup Location created successfully')]);
            }
        } catch (\Exception $e) {
            // Extract clean error message
            $errorMessage = $e->getMessage();
            
            // Try to decode if it's a JSON string
            $decoded = json_decode($errorMessage, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $firstField = array_key_first($decoded);
                if (isset($decoded[$firstField]) && is_array($decoded[$firstField]) && isset($decoded[$firstField][0])) {
                    $errorMessage = $decoded[$firstField][0];
                } elseif (isset($decoded[$firstField]) && is_string($decoded[$firstField])) {
                    $errorMessage = $decoded[$firstField];
                }
            }
            
            return ['errors' => $errorMessage];
        }
    }

    public function list(Request $request)
    {
        $user_id = Auth::user()->id;
        $seller_id = Seller::where('user_id', $user_id)->value('id');

        // Pagination and sorting settings
        $search = trim($request->input('search'));
        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);
        $sort = $request->input('sort', 'id');
        $order = $request->input('order', 'ASC');

        // Build the query using Eloquent
        $query = PickupLocation::query();

        // Search filters
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('pickup_location', 'LIKE', "%$search%")
                    ->orWhere('email', 'LIKE', "%$search%")
                    ->orWhere('phone', 'LIKE', "%$search%");
            });
        }

        // Seller-specific filter
        if ($seller_id) {
            $query->where('seller_id', $seller_id);
        }

        // Exclude deleted pickup locations (status = 3)
        $query->where('status', '!=', 3);

        // Count total records
        $total = $query->count();

        // Fetch the data with pagination
        $location_data = $query->orderBy($sort, $order)
            ->skip($offset)
            ->take($limit)
            ->get();

        // Format the data
        $bulkData = [
            'total' => $total,
            'rows' => $location_data->map(function ($row) {
                // Format status badge
                $statusBadge = $row->status == 1 
                    ? '<span class="badge bg-success">' . labels('admin_labels.approved', 'Approved') . '</span>'
                    : '<span class="badge bg-warning">' . labels('admin_labels.pending_approval', 'Pending Approval') . '</span>';
                
                // Format action buttons
                $actionButtons = '
                    <button class="btn btn-sm btn-danger delete-pickup-location" 
                            data-id="' . $row->id . '" 
                            title="' . labels('admin_labels.delete', 'Delete') . '">
                        <i class="bx bx-trash"></i>
                    </button>
                ';
                
                return [
                    'id' => $row->id,
                    'pickup_location' => $row->pickup_location,
                    'name' => $row->name,
                    'email' => $row->email,
                    'phone' => $row->phone,
                    'address' => $row->address,
                    'address2' => $row->address2,
                    'city' => $row->city,
                    'state' => $row->state,
                    'country' => $row->country,
                    'pincode' => $row->pincode,
                    'status' => $statusBadge,
                    'operate' => $actionButtons,
                ];
            })->toArray()
        ];

        return response()->json($bulkData);
    }

    public function destroy($id)
    {
        $user_id = Auth::user()->id;
        $seller_id = Seller::where('user_id', $user_id)->value('id');

        // Find the pickup location
        $pickupLocation = PickupLocation::where('id', $id)
            ->where('seller_id', $seller_id)
            ->first();

        if (!$pickupLocation) {
            return response()->json([
                'error' => true,
                'message' => labels('admin_labels.pickup_location_not_found', 'Pickup location not found'),
            ], 404);
        }

        // Remove pickup location reference from all associated products
        Product::where('pickup_location', $pickupLocation->id)->update(['pickup_location' => null]);
        
        // Remove pickup location reference from all associated combo products
        \App\Models\ComboProduct::where('pickup_location', $pickupLocation->id)->update(['pickup_location' => null]);

        // Soft delete: set status to 3
        $pickupLocation->status = 3;
        $pickupLocation->save();

        return response()->json([
            'error' => false,
            'message' => labels('admin_labels.pickup_location_deleted_successfully', 'Pickup location deleted successfully'),
        ]);
    }
}
