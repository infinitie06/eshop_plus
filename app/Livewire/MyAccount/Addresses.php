<?php

namespace App\Livewire\MyAccount;

use App\Http\Controllers\AddressController;
use App\Models\Address;
use App\Models\City;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class Addresses extends Component
{
    protected $listeners = [
        'refreshComponent',
        'deleteAddress',
        'resetForm',
        'setType',
        'setCity',
        'setCountry'
    ];

    public $name = '';
    public $type = '';
    public $mobile = '';
    public $alternate_mobile = '';
    public $address = '';
    public $landmark = '';
    public $city_name = '';
    public $pincode = '';
    public $state = '';
    public $country = '';
    public $latitude = '';
    public $longitude = '';
    public $address_id = '';

    // Flash message state
    public $successMessage = '';
    public $errorMessage = '';

    protected function rules()
    {
        return [
            'name' => 'required|string',
            'type' => 'required',
            'mobile' => 'required|digits_between:1,16|numeric',
            'alternate_mobile' => 'nullable|digits_between:1,16|numeric',
            'address' => 'required',
            'landmark' => 'required',
            'city_name' => 'required',
            'pincode' => 'required',
            'state' => 'required',
            'country' => 'required',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ];
    }

    protected $messages = [
        'name.required' => 'Name is required.',
        'type.required' => 'Address type is required.',
        'mobile.required' => 'Mobile number is required.',
        'mobile.numeric' => 'Mobile must be a valid number.',
        'address.required' => 'Address is required.',
        'landmark.required' => 'Landmark is required.',
        'city_name.required' => 'City is required.',
        'pincode.required' => 'Post code is required.',
        'state.required' => 'State is required.',
        'country.required' => 'Country is required.',
    ];

    public function render(AddressController $addressController)
    {
        $user = Auth::user();
        $res = $this->get_Address($addressController);
        return view('livewire.' . config('constants.theme') . '.my-account.addresses', [
            'user_info' => $user,
            'addresses' => $res
        ])->title("Addresses |");
    }

    public function get_address($addressController)
    {
        $user = Auth::user();
        $res = $addressController->getAddress($user->id);
        return $res;
    }

    #[On('setType')]
    public function setType($value)
    {
        $this->type = $value;
    }
    #[On('setCity')]
    public function setCity($value)
    {
        $this->city_name = $value;
    }
    #[On('setCountry')]
    public function setCountry($value)
    {
        $this->country = $value;
    }

    /**
     * Called by wire:click="save" — handles both add and update.
     */
    public function save()
    {
        // dd($this->all());
        $this->successMessage = '';
        $this->errorMessage = '';

        // Modal is wire:ignore, so the usual @error blocks inside it won't repaint.
        // Push failure messages to the blade via an event so the user actually sees them.
        try {
            $this->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('address-save-errors', messages: array_values($e->validator->errors()->all()));
            throw $e;
        }

        $user_id = Auth::id();

        // ── Resolve city ──────────────────────────────────────────────
        $existingAddress = !empty($this->address_id) ? Address::find($this->address_id) : null;

        $cityName = $this->city_name;
        if ($cityName === 'false' || empty($cityName)) {
            $city_id = $existingAddress->city_id ?? null;
            $city_name = $existingAddress->city ?? null;
        } else {
            // city_name may come as a JSON string from Select2 or as plain text
            $decoded = json_decode($cityName, true);
            $city_name = is_array($decoded) ? ($decoded['en'] ?? $cityName) : $cityName;
            $city = City::where('name->en', $city_name)->first();
            $city_id = $city ? $city->id : null;
        }

        // ── Resolve country ───────────────────────────────────────────
        $countryName = $this->country;
        if ($countryName === 'false' || empty($countryName)) {
            $country_code = $existingAddress->country_code ?? null;
            $countryName = $existingAddress->country ?? null;
        } else {
            $countryRow = DB::table('countries')->where('name', $countryName)->first();
            $country_code = $countryRow ? $countryRow->phonecode : null;
        }

        // Store with a leading "+" (e.g. "+20") so the tel: link works as-is
        // and the display doesn't need to reconstruct it.
        if (!empty($country_code) && strpos((string) $country_code, '+') !== 0) {
            $country_code = '+' . ltrim((string) $country_code, '+');
        }

        // ── Build data array ─────────────────────────────────────────
        $address_data = [
            'user_id' => $user_id,
            'name' => $this->name,
            'type' => $this->type,
            'mobile' => $this->mobile,
            'alternate_mobile' => $this->alternate_mobile,
            'address' => $this->address,
            'landmark' => $this->landmark,
            'city' => $city_name,
            'city_id' => $city_id,
            'pincode' => $this->pincode,
            'state' => $this->state,
            'country' => $countryName,
            'country_code' => $country_code,
            'latitude' => $this->latitude ?: null,
            'longitude' => $this->longitude ?: null,
        ];
        // dd($this->address_id);
        // ── Insert or Update ──────────────────────────────────────────
        if (!empty($this->address_id)) {
            if (!$existingAddress) {
                $this->errorMessage = 'Address not found.';
                return;
            }
            $res = updateDetails($address_data, ['id' => $this->address_id], Address::class);
            if (!$res) {
                $this->errorMessage = 'Failed to update address. Please try again.';
                return;
            }
            $this->successMessage = 'Address updated successfully!';
        } else {
            $new_id = Address::insertGetId($address_data);
            if (!$new_id) {
                $this->errorMessage = 'Failed to add address. Please try again.';
                return;
            }
            $this->successMessage = 'Address added successfully!';
        }

        $this->resetForm();
        $this->dispatch('close-modal');
    }

    /**
     * Called by wire:click="edit(id)" — loads the address into the form.
     */
    public function edit($id)
    {
        $this->resetForm();
        $addr = Address::find($id);
        if (!$addr) {
            $this->errorMessage = 'Address not found.';
            return;
        }
        $this->address_id = $addr->id;
        $this->name = $addr->name;
        $this->type = $addr->type;
        $this->mobile = $addr->mobile;
        $this->alternate_mobile = $addr->alternate_mobile;
        $this->address = $addr->address;
        $this->landmark = $addr->landmark;
        $this->city_name = $addr->city ?? '';
        $this->pincode = $addr->pincode;
        $this->state = $addr->state;
        $this->country = $addr->country;
        $this->latitude = $addr->latitude;
        $this->longitude = $addr->longitude;

        // Modal uses wire:ignore, so Livewire won't repaint the fields.
        // Push every value through the event so the blade can sync the DOM.
        $this->dispatch(
            'address-edit-loaded',
            name: $this->name,
            type: $this->type,
            mobile: $this->mobile,
            alternate_mobile: $this->alternate_mobile,
            address: $this->address,
            landmark: $this->landmark,
            city: $this->city_name,
            pincode: $this->pincode,
            state: $this->state,
            country: $this->country,
            latitude: $this->latitude,
            longitude: $this->longitude,
        );
    }
    // #[On('deleteAddress')]
    // public function deleteAddress($id)
    // {
    //     // dd("here");
    //     $user = Auth::user();

    //     $data = [
    //         'user_id' => $user->id,
    //         'id' => $id,
    //     ];
    //     deleteDetails($data, Address::class);
    // }

    public function deleteAddress($id)
    {
        // dd($id); // Now this WILL work

        $user = Auth::user();

        $data = [
            'user_id' => $user->id,
            'id' => $id,
        ];

        deleteDetails($data, Address::class);
    }

    public function setDefault($address_id)
    {
        $user = Auth::user();
        $address = Address::where('id', $address_id)->where('user_id', $user->id)->first();
        if ($address) {
            // Update the is_default status for all addresses of the user
            Address::where('user_id', $user->id)->update(['is_default' => 0]);
            updateDetails(['is_default' => '1'], ['id' => $address_id], Address::class);
        }
    }

    public function openAddModal()
    {
        $this->resetForm();
    }
    #[On('refreshComponent')]
    public function refreshComponent()
    {
        $this->dispatch('$refresh');
    }

    public function resetForm()
    {
        $this->reset([
            'name',
            'type',
            'mobile',
            'alternate_mobile',
            'address',
            'landmark',
            'city_name',
            'pincode',
            'state',
            'country',
            'latitude',
            'longitude',
            'address_id',
        ]);

        $this->resetErrorBag();
        $this->resetValidation();
    }
}
