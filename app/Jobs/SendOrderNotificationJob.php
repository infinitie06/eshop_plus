<?php

namespace App\Jobs;

use App\Services\FirebaseNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendOrderNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $fcmChunks;
    protected $fcmMsg;
    protected $status;
    protected $userEmail;
    protected $userName;
    protected $orderId;
    protected $appName;
    protected $invoiceUrl;

    public function __construct($fcmChunks, $fcmMsg, $status, $userEmail, $userName, $orderId, $appName, $invoiceUrl)
    {
        $this->fcmChunks  = $fcmChunks;
        $this->fcmMsg     = $fcmMsg;
        $this->status     = $status;
        $this->userEmail  = $userEmail;
        $this->userName   = $userName;
        $this->orderId    = $orderId;
        $this->appName    = $appName;
        $this->invoiceUrl = $invoiceUrl;
    }

    public function handle(): void
    {
        if ($this->status !== 'awaiting' && $this->status !== 'Awaiting') {

            app(FirebaseNotificationService::class)
                ->sendNotification('', $this->fcmChunks, $this->fcmMsg);

            $pdfContent = file_get_contents($this->invoiceUrl);
            $tempPath = storage_path("app/temp_invoice_{$this->orderId}.pdf");
            file_put_contents($tempPath, $pdfContent);

            $subject = "{$this->appName}: Invoice for Order #{$this->orderId}";
            $messageContent = "
                <p>Dear {$this->userName},</p>
                <p>Thank you for shopping with us.</p>
                <p>Your invoice is attached.</p>
            ";
            $userEmail = $this->userEmail;

            Mail::send([], [], function ($message) use ($tempPath, $subject, $messageContent, $userEmail) {
                $message->to($userEmail)
                    ->subject($subject)
                    ->html($messageContent)
                    ->attach($tempPath, [
                        'as' => 'Invoice.pdf',
                        'mime' => 'application/pdf',
                    ]);
            });

            unlink($tempPath);
        }
    }
}
