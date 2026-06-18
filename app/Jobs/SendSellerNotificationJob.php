<?php

namespace App\Jobs;

use App\Services\FirebaseNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSellerNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $sellerTokens;
    protected array $messageData;

    /**
     * Maximum number of attempts before failing permanently
     */
    public int $tries = 3;

    /**
     * Delay between retries in seconds
     */
    public int $backoff = 10; // wait 10 seconds before retry

    /**
     * @param array $sellerTokens
     * @param array $messageData
     */
    public function __construct(array $sellerTokens, array $messageData)
    {
        $this->sellerTokens = $sellerTokens;
        $this->messageData = $messageData;
    }

    public function handle()
    {
        try {
            $tokens = array_filter($this->sellerTokens);

           

            $registrationIDsChunks = array_chunk($tokens, 1000);

            app(FirebaseNotificationService::class)
                ->sendNotification(
                    '',
                    $registrationIDsChunks,
                    $this->messageData
                );

        } catch (\Exception $e) {
            Log::error('SendSellerNotificationJob failed', [
                'exception' => $e->getMessage(),
                'tokens' => $this->sellerTokens,
                'messageData' => $this->messageData,
            ]);

            // Rethrow to mark job as failed / retry
            throw $e;
        }
    }

    /**
     * Optional: define the time until the job should no longer retry
     */
    public function retryUntil(): \DateTime
    {
        // Retry for up to 1 hour
        return now()->addHour();
    }
}
