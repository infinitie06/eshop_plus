<?php

namespace App\Livewire\Pages;

use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use App\Services\SettingService;
use App\Services\MailService;
class ContactUs extends Component
{
    public $name = "";
    public $email = "";
    public $subject = "";
    public $message = "";
    public function render()
    {
        $web_settings = app(SettingService::class)->getSettings('web_settings', true);
        $contact_us = app(SettingService::class)->getSettings('contact_us', true);

        return view('livewire.' . config('constants.theme') . '.pages.contact-us', [
            "web_settings" => json_decode($web_settings, true),
            'contact_us' => json_decode($contact_us, true)
        ])->title("Contact us |");
    }

    public function send_contact_us_email()
    {
        $validated = Validator::make(
            [
                'name' => $this->name,
                'email' => $this->email,
                'message' => $this->message,
                'subject' => $this->subject,
            ],
            [
                'name' => 'required',
                'email' => 'required|email',
                'message' => 'required',
                'subject' => 'required',
            ]
        );
        if ($validated->fails()) {
            $errors = $validated->errors();
            $this->dispatch('validationErrorshow', ['data' => $errors]);
            $response['error'] = true;
            $response['message'] = $errors;
            return $response;
        }
        $name = $this->name;
        $from = $this->email;
        $subject = $this->subject;
        $emailMessage = $this->message;
        try {
            $mail = app(MailService::class)->sendContactUsMail($from, $subject, $emailMessage, $name);
        } catch (\Throwable $th) {
            $this->dispatch('showError', $th->getMessage());
            return $this->addError('mailError', 'Error: ' . $th->getMessage());
        }
        if ($mail['error'] == true) {
            $response['error'] = true;
            $response['message'] = "Cannot send mail. Error: " . $mail['message'];
            $response['data'] = $mail['message'];
            // session()->flash('message', $response['message']);
            return $this->addError('mailError', $response['message']);
            // return $this->redirect('/contact-us', navigate: true);
        } else {
            $response['error'] = false;
            $response['message'] = 'Mail sent successfully.';
            $response['data'] = array();
            session()->flash('message', $response['message']);
            return $this->redirect('/contact-us', navigate: true);
        }
    }
}
