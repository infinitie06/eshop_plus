<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendFcmNotificationJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $timeout = 30; // 30 seconds timeout
    public int $tries = 3; // Retry up to 3 times

    public string $accessToken;
    public string $projectId;
    public array $registrationIDsChunks;
    public array $customBodyFields;
    public string $title;
    public string $message;
    public string $type;
    public string $storeId;

    public function __construct($accessToken, $projectId, $registrationIDsChunks, $customBodyFields, $title, $message, $type, $storeId)
    {
        $this->accessToken = $accessToken;
        $this->projectId = $projectId;
        $this->registrationIDsChunks = $registrationIDsChunks;
        $this->customBodyFields = $customBodyFields;
        $this->title = $title;
        $this->message = $message;
        $this->type = $type;
        $this->storeId = $storeId;
    }

    public function handle()
    {
        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        foreach ($this->registrationIDsChunks as $group) {
            foreach ($group as $token) {

                if (empty($token) || $token === "BLACKLISTED") {
                    continue;
                }

                try {
                    $data = [
                        "message" => [
                            "token" => $token,
                            "notification" => [
                                "title" => $this->customBodyFields['title'] ?? $this->title,
                                "body"  => $this->customBodyFields['body'] ?? $this->message,
                            ],
                            "data" => $this->customBodyFields,
                            "android" => [
                                "notification" => [
                                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                                    'title' => $this->customBodyFields['title'] ?? $this->title,
                                    'body'  => $this->customBodyFields['body'] ?? $this->message,
                                ],
                            ],
                            "apns" => [
                                "headers" => ["apns-priority" => "10"],
                                "payload" => [
                                    "aps" => [
                                        "alert" => [
                                            "title" => $this->customBodyFields['title'] ?? $this->title,
                                            "body" => $this->customBodyFields['body'] ?? $this->message,
                                        ],
                                    ],
                                    "store_id" => strval($this->storeId),
                                    "data" => $this->customBodyFields,
                                ]
                            ],
                        ]
                    ];

                    $response = Http::withToken($this->accessToken)
                        ->withHeaders(['Content-Type' => 'application/json'])
                        ->timeout(10) // 10 seconds HTTP timeout
                        ->post($url, $data);

                    if (!$response->successful()) {
                        Log::error("FCM JOB ERROR: " . $response->body());
                    }

                } catch (\Exception $e) {
                    Log::error("FCM JOB EXCEPTION: " . $e->getMessage());
                    // Continue processing other tokens even if one fails
                    continue;
                }
            }
        }
    }
}
