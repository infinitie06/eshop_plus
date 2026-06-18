<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Auth;
use App\Services\MediaService;

class NotificationsController extends Controller
{
    public function get_users_by_ids($user_ids)
    {
        // Decode the JSON-encoded string into an array
        $ids_array = json_decode($user_ids);

        // Ensure the array is not empty
        if (empty($ids_array)) {
            return '';
        }

        // Fetch the users based on the array of IDs
        $users = User::whereIn('id', $ids_array)->get();

        // Extract the 'username' attribute from each User model
        $users_array = $users->pluck('username')->toArray();

        // Join the usernames into a comma-separated string
        $comma_separated_users = implode(',', $users_array);
        return $comma_separated_users;
    }


    public function get_notifications($offset = 0, $limit = 10, $sort = 'id', $order = 'ASC')
    {
        $user_id = Auth::id();

        // Override with query parameters if provided
        $offset = request()->input('offset', $offset);
        $limit = request()->input('limit', $limit);
        $sort = request()->input('sort', $sort);
        $order = request()->input('order', $order);

        // Base query
        $query = Notification::query();

        // Apply search filter
        if ($search = trim(request()->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%$search%")
                    ->orWhere('title', 'like', "%$search%")
                    ->orWhere('message', 'like', "%$search%");
            });
        }

        // Clone for total count
        $total = (clone $query)->count();

        // Apply sorting and pagination
        $notifications = $query->orderBy($sort, $order)
            ->skip($offset)
            ->take($limit)
            ->get();

        // Format the data
        $rows = $notifications->map(function ($row) use ($user_id) {
            $userIds = json_decode($row->users_id, true) ?? [];
            $showToUser = $row->send_to === 'all_users' || in_array($user_id, $userIds);

            if (!$showToUser) {
                return null;
            }

            $image = app(MediaService::class)->dynamic_image($row->image, 80);
            $shortMessage = implode(' ', array_slice(explode(' ', $row->message), 0, 10)) .
                (str_word_count($row->message) > 10 ? '...' : '');

            return [
                'id' => $row->id,
                'title' => $row->title,
                'type' => $row->type,
                'message' => $shortMessage,
                'send_to' => ucwords(str_replace('_', ' ', $row->send_to)),
                'image' => '<div class="d-flex justify-content-around"><a href="' . $image . '" data-lightbox="banner-' . $row->id . '"><img src="' . $image . '" alt="Avatar" class="rounded table-image"/></a></div>',
                'link' => $row->link,
                'full_notification' => '<h4>' . e($row->title) . '</h4><p>' . e($row->message) . '</p><img src="' . url($image) . '" alt="Notification Image" />',
            ];
        })->filter()->values();

        return response()->json([
            'total' => $total,
            'rows' => $rows,
        ]);
    }
}
