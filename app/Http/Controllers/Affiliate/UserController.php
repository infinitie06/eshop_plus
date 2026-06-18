<?php
namespace App\Http\Controllers\Affiliate;
use App\Models\Media;
use App\Models\StorageType;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use App\Services\MediaService;
use App\Traits\HandlesValidation;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
   public function edit(User $user)
    {
        return view('affiliate.pages.forms.account', ['user' => $user]);
    }

    public function update(Request $request, $id)
    {
        // Define validation rules
        $rules = [
            'username' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'mobile' => ['required', 'string'],
            'address' => ['required', 'string', 'max:500'],
            'image' => ['nullable', 'image', 'mimes:jpeg,gif,jpg,png,webp', 'max:30720'], // 30MB
        ];

        if ($request->filled('old_password') || $request->filled('new_password')) {
            $rules['old_password'] = ['required'];
            $rules['new_password'] = ['required', 'confirmed', 'min:8'];
        }

        // Validate input
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json(['errors' => $validator->errors()], 422);
                  session()->flash('errors', $validator->errors());
            }
            // Flash validation errors
            foreach ($validator->errors()->all() as $error) {
                session()->flash('error', $error);
            }
            return redirect()->back()->withInput();
        }

        $user = User::findOrFail($id);

        // Check old password
        if ($request->filled('old_password') && !Hash::check($request->old_password, $user->password)) {
            $errorMessage = labels('admin_labels.incorrect_old_password', 'The old password is incorrect.');
            if ($request->ajax()) {
                return response()->json(['message' => $errorMessage], 422);
            }
            session()->flash('error', $errorMessage);
            return redirect()->back()->withInput();
        }

        // Image upload handling
        $mediaItem = null;
        $image = $user->image;
        $disk = 'public';
        try {
            $media_storage_settings = fetchDetails(StorageType::class, ['is_default' => 1], '*');
            $disk = $media_storage_settings->isEmpty() ? 'public' : $media_storage_settings[0]->name;

            if ($request->hasFile('image')) {
                // Restrict to one file
                if (is_array($request->file('image')) && count($request->file('image')) > 1) {
                    $errorMessage = 'Only one image is allowed.';
                    if ($request->ajax()) {
                        return response()->json(['message' => $errorMessage], 422);
                    }
                    session()->flash('error', $errorMessage);
                    return redirect()->back()->withInput();
                }

                // Delete old image
                if ($user->image) {
                    $path = $disk == 's3' ? $user->image : 'store_images/' . $user->image;
                    app(MediaService::class)->removeMediaFile($path, $disk);
                }

                $mediaFile = $request->file('image');
                $mediaItem = $user->addMedia($mediaFile)
                    ->sanitizingFileName(function ($fileName) use ($user) {
                        $sanitizedFileName = strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));
                        $uniqueId = time() . '_' . mt_rand(1000, 9999);
                        $extension = pathinfo($sanitizedFileName, PATHINFO_EXTENSION);
                        $baseName = pathinfo($sanitizedFileName, PATHINFO_FILENAME);
                        return "{$baseName}-{$uniqueId}.{$extension}";
                    })
                    ->toMediaCollection('user_image', $disk);

                $media_list = $user->getMedia('user_image');
                $image = $disk == 's3' ? $media_list[0]->getUrl() : '/' . $mediaItem->file_name;
            }
        } catch (\Exception $e) {
            $errorMessage = 'Image upload failed: ' . $e->getMessage();
            if ($request->ajax()) {
                return response()->json(['error' => true, 'message' => $errorMessage], 500);
            }
            session()->flash('error', $errorMessage);
            return redirect()->back()->withInput();
        }

        // Update user fields
        $formFields = [
            'username' => $request->username,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'address' => $request->address,
            'image' => $image,
            'disk' => $disk,
        ];
        $user->update($formFields);

        // Update password if provided
        if ($request->filled('new_password')) {
            $user->password = Hash::make($request->new_password);
            $user->save();
        }

        $successMessage = labels('admin_labels.profile_details_updated_successfully', 'Profile details updated successfully!');
        if ($request->ajax()) {
            return response()->json(['message' => $successMessage]);
        }
        session()->flash('success', $successMessage);
        return redirect()->back();
    }
}
?>
