<?php

namespace App\Services;

use App\Services\SettingService;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class MailService
{
    private function buildResponse(bool $error, string $message)
    {
        return [
            'error'   => $error,
            'message' => $message
        ];
    }

    private function sendMail($to, $callback)
    {
        try {
            Mail::send([], [], function ($message) use ($to, $callback) {
                $message->to($to);
                $callback($message);
            });

            return $this->buildResponse(false, "Email Sent");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Mail Error: " . $e->getMessage());
            return $this->buildResponse(true, $e->getMessage());
        }
    }

    public function sendDigitalProductMail($to, $subject, $emailMessage, $attachment)
    {
        return $this->sendMail($to, function (Message $message) use ($to, $subject, $emailMessage) {
            $email_settings = json_decode(app(SettingService::class)->getSettings('email_settings', true), true);

            $message->subject($subject)
                ->html($emailMessage)
                ->from($email_settings['email'], env('APP_NAME'));
        });
    }

    public function sendCustomMail($to, $subject, $emailMessage, $attachment)
    {
        return $this->sendMail($to, function (Message $message) use ($to, $subject, $emailMessage) {
            $email_settings = json_decode(app(SettingService::class)->getSettings('email_settings', true), true);

            $message->subject($subject)
                ->html($emailMessage)
                ->from($email_settings['email'], env('APP_NAME'));
        });
    }

    public function sendContactUsMail($from, $subject, $emailMessage, $name)
    {
        $email_settings = json_decode(app(SettingService::class)->getSettings('email_settings', true), true);
        $to = $email_settings['email'];

        return $this->sendMail($to, function (Message $message) use ($from, $subject, $emailMessage, $email_settings, $name) {
            $message->from($email_settings['email'], $name)
                ->replyTo($from)
                ->subject($subject)
                ->html($emailMessage);
        });
    }

    public function sendMailTemplate($to, $template_key, $givenLanguage = "", $data = [], $subjectData = [])
    {
        $givenLanguage = $givenLanguage ?: (session("locale") ?? "default");

        $viewpath = "components.utility.email_templates.$template_key.";
        $viewpath .= View::exists($viewpath . $givenLanguage) ? $givenLanguage : "default";

        $emailMessage = view($viewpath, $data)->render();
        $subject = strip_tags(view($viewpath . "-subject", $subjectData)->render());

        return $this->sendCustomMail($to, $subject, $emailMessage, "");
    }

    public function isEmailConfigured()
    {
        $email_settings = json_decode(app(SettingService::class)->getSettings('email_settings', true), true);

        return !empty($email_settings['email'])
            && !empty($email_settings['password'])
            && !empty($email_settings['smtp_host'])
            && !empty($email_settings['smtp_port']);
    }
}
