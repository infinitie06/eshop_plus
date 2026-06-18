<?php

namespace App\Livewire\RegisterAndLogin;

use Livewire\Component;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Validator;
use App\Services\SettingService;
use App\Services\MailService;
class Register extends Component
{

    public $username = "";
    public $otp = "";
    public $mobile = "";
    public $password = "";
    public $password_confirmation = "";

    private function getIsoCountryCode($numericCode)
    {
        $countryCodeMap = [
            '91' => 'in',
            '1' => 'us',
            '44' => 'gb',
            '20' => 'eg',
            '971' => 'ae',
            '966' => 'sa',
            '92' => 'pk',
            '880' => 'bd',
            '94' => 'lk',
            '977' => 'np',
        ];

        return $countryCodeMap[$numericCode] ?? 'in';
    }

    public function render()
    {
        $system_settings = app(SettingService::class)->getSettings('system_settings', true);
        $system_settings_obj = json_decode($system_settings);
        $system_settings_arr = json_decode($system_settings, true);
        $authentication_method = $system_settings_obj->authentication_method ?? "";

        $numericCountryCode = $system_settings_arr['country_code'] ?? '91';
        $defaultCountry = $this->getIsoCountryCode($numericCountryCode);

        return view('livewire.' . config('constants.theme') . '.register-and-login.register', [
            'authentication_method' => $authentication_method,
            'default_country' => $defaultCountry,
        ])->title("Sign Up |");
    }

    public function store(Request $request)
    {
        
        if ((config('constants.ALLOW_MODIFICATION') == 0)) {
            $response['error'] = true;
            $response['message'] = "Register is Not Allowed In Demo Mode";
            return $response;
        }

        $validator = Validator::make(
            $request->all(),
            [
                'username' => 'required',
                'mobile' => 'required|numeric',
                'email' => ['required', 'email', Rule::unique('users', 'email')],
                'password' => 'required|confirmed|min:8'
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors();
            $response['error'] = true;
            $response['message'] = $errors;
            return $response;
        }
        $data['username'] = $request['username'];
        $data['mobile'] = $request['mobile'];
        $data['email'] = $request['email'];
        $data['password'] = bcrypt($request['password']);
        $country_code = $request['country_code'] ?? '91';
        $data['country_code'] = str_replace('+', '', $country_code);
        $data['role_id'] = "2";
        $data['active'] = "1";
        $user = User::create($data);

        auth()->login($user);
        $response = [
            'error' => false,
            'message' => "Welcome " . $request['username'],
        ];
        try {
            app(MailService::class)->sendMailTemplate(to: $data['email'], template_key: "welcome", data: [
                "username" => $data['username']
            ]);
        } catch (\Throwable $th) {
        }
        return $response;
    }

    public function check_mobile_number(Request $request)
    {
        if ((config('constants.ALLOW_MODIFICATION') == 0)) {
            $response['error'] = true;
            $response['allow_modification_error'] = true;
            $response['message'] = "Register is Not Allowed In Demo Mode";
            return $response;
        }

        $validator = Validator::make(
            $request->all(),
            [
                'mobile' => ['required', Rule::unique('users', 'mobile')],
            ]
        );
        if ($validator->fails()) {
            $errors = $validator->errors();
            $response['error'] = true;
            $response['message'] = $errors;
            return $response;
        }
    }

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        $user = Socialite::driver('google')->user();
        $finduser = User::where('email', $user->email)->first();

        if ($finduser) {
            Auth::login($finduser);
           return redirect()->to(config('app.url'))->with('message', 'Logged In Successfully');
        } else {
            $newUser = User::create([
                'username' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'image' => $user->avatar,
                'role_id' => "2",
                'active' => "1",
                'type' => "google",
            ]);

            Auth::login($newUser);

            try {
                app(MailService::class)->sendMailTemplate(
                    to: $newUser['email'],
                    template_key: "welcome",
                    data: [
                        "username" => $newUser['username']
                    ]
                );
            } catch (\Throwable $th) {
                // Optional: log or ignore
            }

            // ✅ Corrected: add `return` before redirect
         return redirect()->to(config('app.url'))->with('message', 'Logged In Successfully');
        }
    }

    public function redirectToFacebook()
    {
        return Socialite::driver('facebook')->redirect();
    }

    public function handleFacebookCallback()
    {
        $user = Socialite::driver('facebook')->user();
        $finduser = User::where('email', $user->email)->first();
        if ($finduser) {
            Auth::login($finduser);
            return redirect('/')->with('message', 'Logged In Successfully');
        } else {
            $newUser = User::create([
                'username' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'image' => $user->avatar,
                'active' => "1",
                'role_id' => "2",
                'type' => "facebook",
            ]);
            Auth::login($newUser);
            redirect("/")->with('message', 'Registered Successfully');
            try {
                app(MailService::class)->sendMailTemplate(to: $newUser['email'], template_key: "welcome", data: [
                    "username" => $newUser['username']
                ]);
            } catch (\Throwable $th) {
            }
            return;
        }
    }
}
