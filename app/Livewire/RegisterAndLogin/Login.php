<?php

namespace App\Livewire\RegisterAndLogin;

use App\Models\User;
use Livewire\Component;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Services\MailService;
class Login extends Component
{
    public $mobile = "";
    public $password = "";

    public function mount(){
        $this->mobile = (config('constants.ALLOW_MODIFICATION') == 0) ? "9876543210" : "";
        $this->password = (config('constants.ALLOW_MODIFICATION') == 0) ? "12345678" : "";
    }
    /**
     * Convert numeric country code to ISO country code
     */
    private function getIsoCountryCode($numericCode)
    {
        $countryCodeMap = [
            '91' => 'in',  // India
            '1' => 'us',   // United States
            '44' => 'gb',  // United Kingdom
            '20' => 'eg',  // Egypt
            '971' => 'ae', // UAE
            '966' => 'sa', // Saudi Arabia
            '92' => 'pk',  // Pakistan
            '880' => 'bd', // Bangladesh
            '94' => 'lk',  // Sri Lanka
            '977' => 'np', // Nepal
        ];
        
        return $countryCodeMap[$numericCode] ?? 'in'; // Default to India
    }

    public function render()
    {
        $system_settings = app(\App\Services\SettingService::class)->getSettings('system_settings', true);
        $system_settings = json_decode($system_settings, true);
        
        // Get country code from system settings (defaults to 91 for India)
        $numericCountryCode = $system_settings['country_code'] ?? '91';
        $defaultCountry = $this->getIsoCountryCode($numericCountryCode);
        
        return view('livewire.' . config('constants.theme') . '.register-and-login.login', [
            'system_settings' => $system_settings,
            'default_country' => $defaultCountry
        ])->title("Sign In |");
    }
    // public function login(Request $request)
    // {
    //     $validator = Validator::make([
    //         'mobile' => $this->mobile,
    //         'password' => $this->password,
    //     ],[
    //         'mobile' => ['required', Rule::exists('users', 'mobile')],
    //         'password' => 'required'
    //     ],[
    //         'mobile.exists' => 'Mobile Number is Not Registered'
    //     ]);

    //     if ($validator->fails()) {
    //         $errors = $validator->errors();
    //         $this->dispatch('validationErrorshow',['data' => $errors]);
    //         return;
    //     }


    //     $user = User::where('mobile', $this->password)->first();
    //     $device = $request->header('sec-ch-ua-platform');
    //     $date = new \DateTime();
    //     $currentDateTime = $date->format('Y-m-d H:i:s');
    //     $timeZone = $date->getTimezone()->getName();
    //     $data = [
    //         'device' => $device,
    //         'currentDateTime' => $currentDateTime,
    //         'timeZone' => $timeZone
    //     ];
    //     $validate['mobile'] = $this->mobile;
    //     $validate['password'] = $this->password;
    //     if (Auth::attempt($validate)) {
    //         try {
    //             sendMailTemplate(to: $user['email'], template_key: "user_login", data: [
    //                 "username" => $user['username'],
    //                 "device" => $data['device'],
    //                 "currentDateTime" => $data['currentDateTime'],
    //                 "timeZone" => $data['timeZone']
    //             ]);
    //         } catch (\Throwable $th) {}
    //         $this->dispatch('showSuccess','User Loggedin Successfully');
    //         return redirect('/');
    //     }
    //     return $this->dispatch('showError','Invalid Credentials');
    // }
    public function login(Request $request)
{
    $validator = Validator::make([
        'mobile' => $this->mobile,
        'password' => $this->password,
    ], [
        'mobile' => ['required', Rule::exists('users', 'mobile')],
        'password' => 'required'
    ], [
        'mobile.exists' => 'Mobile Number is Not Registered'
    ]);

    if ($validator->fails()) {
        $errors = $validator->errors();
        $this->dispatch('validationErrorshow', ['data' => $errors]);
        return;
    }

    $user = User::where('mobile', $this->mobile)->first(); // <-- fixed: was $this->password

    if (!$user) {
        return $this->dispatch('showError', 'User not found.');
    }

    if ($user->active != 1) {
        return $this->dispatch('showError', 'Your account has been deactivated.');
    }

    $device = $request->header('sec-ch-ua-platform');
    $date = new \DateTime();
    $currentDateTime = $date->format('Y-m-d H:i:s');
    $timeZone = $date->getTimezone()->getName();
    $data = [
        'device' => $device,
        'currentDateTime' => $currentDateTime,
        'timeZone' => $timeZone
    ];

    $validate = [
        'mobile' => $this->mobile,
        'password' => $this->password
    ];

    if (Auth::attempt($validate)) {
        try {
                app(MailService::class)->sendMailTemplate(to: $user['email'], template_key: "user_login", data: [
                "username" => $user['username'],
                "device" => $data['device'],
                "currentDateTime" => $data['currentDateTime'],
                "timeZone" => $data['timeZone']
            ]);
        } catch (\Throwable $th) {
            // optional: log the error
        }

        $this->dispatch('showSuccess', 'User Logged in Successfully');
        return redirect('/');
    }

    return $this->dispatch('showError', 'Invalid Credentials');
}

}
