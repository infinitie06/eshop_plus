<?php

namespace App\Services;

use App\Models\Store;
class StoreService
{
    public function getStoreSettings()
    {
        $store_id = session('store_id');
        $store_settings = null;
        if (!empty($store_id)) {
            $store_settings = $this->getCurrentStoreData($store_id);
            if ($store_settings !== null && $store_settings !== []) {
                $store_settings = json_decode($store_settings, true);
                $store_settings = $store_settings[0]['store_settings'];
            }
        }
        return $store_settings;
    }
    public function getCurrentStoreData($store_id)
    {
        $store_details = session('store_details');

        // Re-read from DB whenever the cached snapshot is missing, points at a
        // different store, or is older than the current row. updated_at is bumped
        // by Eloquent on every admin save/update, so this picks up theme and
        // store_settings changes on the very next request without admins needing
        // to touch every visitor's session.
        $latest_updated_at = Store::where('id', $store_id)
            ->where('status', 1)
            ->value('updated_at');

        $cached = $store_details !== null ? json_decode($store_details) : null;
        $is_fresh = is_array($cached)
            && isset($cached[0]->id, $cached[0]->updated_at)
            && $cached[0]->id == $store_id
            && (string) $cached[0]->updated_at === (string) $latest_updated_at;

        if (!$is_fresh) {
            $store_details = Store::where('id', $store_id)
                ->where('status', 1)
                ->get();
            session()->forget("store_details");
            session()->put("store_details", json_encode($store_details));
        }

        return $store_details;
    }
    public function getStoreId()
    {
        return session('store_id') !== null && !empty(session('store_id')) ? session('store_id') : "";
    }

}