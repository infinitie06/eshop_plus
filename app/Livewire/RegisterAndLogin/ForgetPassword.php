<?php

namespace App\Livewire\RegisterAndLogin;

use App\Models\User;
use Livewire\Component;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\SettingService;
use App\Services\MailService;
class ForgetPassword extends Component
{
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
        $system_settings = json_decode($system_settings, true);
        $authentication_method = $system_settings['authentication_method'] ?? "";
        $defaultCountry = $this->getIsoCountryCode($system_settings['country_code'] ?? '91');

        return view('livewire.' . config('constants.theme') . '.register-and-login.forget-password', [
            'authentication_method' => $authentication_method,
            'default_country' => $defaultCountry,
        ])->title("Password Recovery |");
    }

    public function check_number(Request $request)
    {
        $mobile = $request->input('mobile');
        $user = User::where('mobile', $mobile)->first();
        if ($user) {
            return response()->json(['error' => false, 'message' => 'Mobile Number Registered']);
        } else {
            return response()->json(['error' => true, 'message' => 'Mobile Number is Not Registered']);
        }
    }

    public function new_password(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => 'required',
            'new_password' => 'required',
            'verify_password' => 'required_with:new_password|same:new_password|min:8',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            $response['error'] = true;
            $response['message'] = $errors;
            return $response;
        }

        $user_data = User::where('mobile', $request['mobile'])->first();
        $password = bcrypt($request['verify_password']);
        $user_data->update([
            'password' => $password,
        ]);
        if ($user_data) {
            try {
                app(MailService::class)->sendMailTemplate(to: $user_data['email'], template_key: "forget_password", data: [
                    "username" => $user_data['username']
                ]);
            } catch (\Throwable $th) {
            }
            $response = [
                'error' => false,
                'message' => 'Password Updated successfully!'
            ];
            return $response;
        }
        $response = [
            'error' => true,
            'message' => 'Something Went Wrong Please Try Again Later!!'
        ];
        return $response;
    }
}
