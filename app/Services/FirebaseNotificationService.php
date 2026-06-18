<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use App\Models\CustomMessage;
use Illuminate\Support\Facades\DB;
use App\Services\StoreService;
use App\Jobs\SendFcmNotificationJob;
use App\Traits\HandlesValidation;
use Illuminate\Validation\ValidationException;

class FirebaseNotificationService
{
    use HandlesValidation;

    public function getAccessTokenOld()
    {
        static $accessToken = null;

        if ($accessToken !== null) {
            return $accessToken;
        }

        $fileName = Setting::where('variable', 'service_account_file')->value('value');
        $filePath = storage_path("app/public/" . $fileName);

        if (empty($fileName) || !file_exists($filePath)) {
            throw new ValidationException(
                validator: \Validator::make([], []),
                response: response()->json([
                    'error'   => true,
                    'message' => 'Firebase service account JSON file not found. Please upload a valid file.',
                    'code'    => 105,
                ], 422)
            );
        }

        $client = new \Google\Client();
        $client->setAuthConfig($filePath);
        $client->setScopes([
            'https://www.googleapis.com/auth/firebase.messaging'
        ]);

        $token = $client->fetchAccessTokenWithAssertion();
        $accessToken = $token['access_token'];

        return $accessToken;
    }

    public function getAccessToken()
    {
        try {
            static $accessToken = null;

            if ($accessToken !== null) {
                return $accessToken;
            }

            $fileName = Setting::where('variable', 'service_account_file')->value('value');
            $filePath = storage_path("app/public/" . $fileName);

            if (empty($fileName) || !file_exists($filePath)) {
                \Log::error('Firebase service account JSON file not found.');
                return null; // 🔥 DO NOT THROW EXCEPTION
            }

            $client = new \Google\Client();
            $client->setAuthConfig($filePath);
            $client->setScopes([
                'https://www.googleapis.com/auth/firebase.messaging'
            ]);

            $token = $client->fetchAccessTokenWithAssertion();

            if (!isset($token['access_token'])) {
                \Log::error('Unable to fetch Firebase access token.', $token);
                return null;
            }

            $accessToken = $token['access_token'];

            return $accessToken;
        } catch (\Exception $e) {
            \Log::error('FCM Token Error: ' . $e->getMessage());
            return null; // 🔥 VERY IMPORTANT
        }
    }

    public function sendNotification($fcmMsg, $registrationIDsChunks, $customBodyFields = [], $title = "test title", $message = "test message", $type = "test type")
    {
        $storeId = app(StoreService::class)->getStoreId();
        $storeId = $storeId ?: ($customBodyFields['store_id'] ?? "");

        $projectId = Setting::where('variable', 'firebase_project_id')->value('value');
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            // Skip notification instead of failing order
            return;
        }

        // Dispatch job instead of direct sending
        dispatch(new SendFcmNotificationJob(
            accessToken: $accessToken,
            projectId: $projectId,
            registrationIDsChunks: $registrationIDsChunks,
            customBodyFields: $customBodyFields,
            title: $title,
            message: $message,
            type: $type,
            storeId: $storeId
        ));

        return true;
    }

    public function sendCustomNotificationOnPaymentSuccess($orderId, $userId)
    {
        $customNotification = fetchDetails(CustomMessage::class, ['type' => 'place_order'], '*');

        $hashtagOrderId = '< order_id >';
        $titleTemplate = !$customNotification->isEmpty() ? json_encode($customNotification[0]->title, JSON_UNESCAPED_UNICODE) : "";
        $title = str_replace($hashtagOrderId, $orderId, html_entity_decode($titleTemplate));
        $title = trim($title, '"');

        $hashtagApplicationName = '< application_name >';
        $messageTemplate = !$customNotification->isEmpty() ? json_encode($customNotification[0]->message, JSON_UNESCAPED_UNICODE) : "";
        $appName = Setting::where('variable', 'app_name')->value('value');
        $message = str_replace($hashtagApplicationName, $appName, html_entity_decode($messageTemplate));
        $message = trim($message, '"');

        $fcmAdminSubject = !empty($customNotification) ? $title : 'New order placed ID #' . $orderId;
        $fcmAdminMsg = !empty($customNotification) ? $message : 'New order received for ' . $appName . ', please process it.';

        $userFcm = fetchDetails(User::class, ['id' => $userId], ['fcm_id']);
        $userFcmId[] = !$userFcm->isEmpty() ? [$userFcm[0]->fcm_id] : [];

        if (!empty($userFcmId)) {
            $fcmMsg = [
                'title' => $fcmAdminSubject,
                'body' => $fcmAdminMsg,
                'image' => '',
                'type' => 'place_order',
            ];

            $this->sendNotification('', $userFcmId, $fcmMsg);
        }
    }
}
