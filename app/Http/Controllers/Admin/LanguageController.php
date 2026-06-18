<?php

namespace App\Http\Controllers\Admin;

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CategorySliders;
use App\Models\City;
use App\Models\ComboProduct;
use App\Models\Language;
use App\Models\Offer;
use App\Models\OfferSliders;
use App\Models\Product;
use App\Models\Promocode;
use App\Models\Section;
use App\Models\Store;
use App\Models\Tax;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use ZipArchive;
use App\Traits\HandlesValidation;
use Illuminate\Support\Facades\Validator;

class LanguageController extends Controller
{
    use HandlesValidation;
    public function index()
    {
        $languages = Language::all();

        $language_code = session()->get('locale') ?? 'en';
        // dd($language_code);
        $current_language = fetchDetails(Language::class, ['code' => $language_code], 'language');
        return view('admin.pages.forms.language', compact('languages', 'language_code', 'current_language'));
        // return view('admin.pages.forms.language');
    }
    public function store(Request $request)
    {
        $actionType = $request->input('action_type', 'create');

        // If selecting existing language, handle it separately
        if ($actionType === 'select') {
            if ($request->hasFile('translation_files_select')) {
                // If any file is uploaded, process them
                $language_code = $request->input('language_code');
                if (!$language_code) {
                    return response()->json(['error' => true, 'message' => 'Language code is required for updating translations.']);
                }

                // Process files for existing language
                $results = [];
                $uploadedFiles = $request->file('translation_files_select');

                foreach ($uploadedFiles as $type => $file) {
                    if (!$file) {
                        continue;
                    }
                    if ($file->getClientOriginalExtension() !== 'json') {
                        return response()->json([
                            'error' => true,
                            'message' => ucfirst($type) . ' file must be a .json file'
                        ], 422);
                    }
                    $validation = $this->validateTranslationJson($file);
                    if ($validation !== true) {
                        return response()->json([
                            'error' => true,
                            'message' => ucfirst($type) . " file error: {$validation}"
                        ], 422);
                    }
                    $results[] = $this->processTranslationFile($file, $type, $language_code, $request);
                }

                if (!empty($results)) {
                    if ($request->ajax()) {
                        $combined = ['error' => false, 'messages' => [], 'missing_labels' => [], 'updated_at' => []];
                        foreach ($results as $res) {
                            if (!empty($res['message'])) {
                                $combined['messages'][] = $res['message'];
                            }
                            if (!empty($res['missing_labels'])) {
                                $combined['missing_labels'] = array_merge($combined['missing_labels'], $res['missing_labels']);
                            }
                            if (!empty($res['updated_at'])) {
                                $combined['updated_at'][] = $res['updated_at'];
                            }
                        }
                        $combined['message'] = implode(' | ', array_filter($combined['messages']));
                        return response()->json($combined);
                    }
                    return redirect()->back()->with('success', labels('admin_labels.language_labels_updated_successfully', 'Language labels updated successfully'));
                }
            } else {
                // If no file, just return success (language already exists)
                if ($request->ajax()) {
                    return response()->json([
                        'error' => false,
                        'message' => labels('admin_labels.language_selected_successfully', 'Language updated successfully')
                    ]);
                }
                return redirect()->back()->with('success', labels('admin_labels.language_selected_successfully', 'Language updated successfully'));
            }

            // Fallback response for select mode
            if ($request->ajax()) {
                return response()->json([
                    'error' => false,
                    'message' => labels('admin_labels.language_selected_successfully', 'Language updated successfully')
                ]);
            }
            return redirect()->back()->with('success', labels('admin_labels.language_selected_successfully', 'Language updated successfully'));
        }

        // Validate for creating new language
        $code = strtolower($request->code ?? '');
        $languageName = strtolower($request->language ?? '');

        // Check if language with this code already exists
        $existingLanguageByCode = Language::where('code', $code)->first();
        // Check if language with this name already exists
        $existingLanguageByName = Language::where('language', $languageName)->first();

        $rules = [
            'language' => 'required|string|max:255',
            'code' => 'required|string|max:10',
        ];

        // Always enforce uniqueness, but exclude current language if updating
        if ($existingLanguageByCode) {
            // Updating existing language - exclude it from uniqueness check
            $rules['language'] .= '|unique:languages,language,' . $existingLanguageByCode->id;
            $rules['code'] .= '|unique:languages,code,' . $existingLanguageByCode->id;

            // Additional check: if name exists for a different language, it's an error
            if ($existingLanguageByName && $existingLanguageByName->id !== $existingLanguageByCode->id) {
                return response()->json([
                    'error' => true,
                    'message' => labels('admin_labels.language_name_already_exists', 'Language name already exists for another language')
                ], 422);
            }
        } else {
            // Creating new language - check full uniqueness
            $rules['language'] .= '|unique:languages,language';
            $rules['code'] .= '|unique:languages,code';

            // Additional check: if code doesn't exist but name does, it's an error
            if ($existingLanguageByName) {
                return response()->json([
                    'error' => true,
                    'message' => labels('admin_labels.language_name_already_exists', 'Language name already exists')
                ], 422);
            }
        }

        if ($response = $this->HandlesValidation($request, $rules)) {
            return $response;
        }

        // For create flow, require all translation files
        if (!$existingLanguageByCode) {
            $requiredTypes = ['panel', 'web', 'app', 'seller', 'delivery'];
            $uploadedFiles = $request->hasFile('translation_files') ? $request->file('translation_files') : [];
            $missingFiles = [];

            foreach ($requiredTypes as $type) {
                // Check if file exists and is not empty/null
                $file = $uploadedFiles[$type] ?? null;

                // Handle both single file and array of files (in case of duplicates)
                if (is_array($file)) {
                    $file = reset($file); // Get first valid file from array
                }

                // Check if file is valid and not empty
                if (!$file || !($file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) || $file->getSize() === 0) {
                    $missingFiles[] = ucfirst($type);
                }
            }

            if (!empty($missingFiles)) {
                return response()->json(['error' => true, 'message' => 'All translation files are required for creating a new language. Missing: ' . implode(', ', $missingFiles)]);
            }
        }

        // Create or update the language
        if ($existingLanguageByCode) {
            // Update existing language
            $existingLanguageByCode->update([
                'language' => $languageName,
                'native_language' => $request->native_language ? strtolower($request->native_language) : $existingLanguageByCode->native_language,
                'is_rtl' => isset($request->is_rtl) && $request->is_rtl == "on" ? 1 : 0,
            ]);
            $language = $existingLanguageByCode;
        } else {
            // Create new language
            $language = Language::create([
                'code' => $code,
                'language' => $languageName,
                'native_language' => $request->native_language ? strtolower($request->native_language) : null,
                'is_rtl' => isset($request->is_rtl) && $request->is_rtl == "on" ? 1 : 0,
            ]);
        }

        // If translation files are uploaded (new array inputs), process each
        $isCreatingNew = !$existingLanguageByCode;
        $language_code = strtolower($request->code);

        // Ensure lang directory exists if any files will be saved
        if ($request->hasFile('translation_files')) {
            $dir = base_path("/resources/lang/{$language_code}");
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
        }

        $results = [];

        // New array-style uploads: translation_files[panel], translation_files[web], etc.
        if ($request->hasFile('translation_files')) {
            $files = $request->file('translation_files');
            foreach ($files as $type => $file) {
                if (!$file) {
                    continue;
                }

                // Handle array of files (in case of duplicate submissions)
                if (is_array($file)) {
                    $file = reset($file); // Get first valid file
                }

                // Skip if file is not valid
                if (!($file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) || $file->getSize() === 0) {
                    continue;
                }

                $file_type = $isCreatingNew ? ($type === 'panel' ? 'panel' : $type) : $type;
                $results[] = $this->processTranslationFile($file, $file_type, $language_code, $request);
            }
        }

        // If there were any results from processing uploads, return response
        if (!empty($results)) {
            // If AJAX, return combined JSON
            if ($request->ajax()) {
                // combine missing labels only, use single success message
                $combined = ['error' => false, 'missing_labels' => [], 'updated_at' => []];
                foreach ($results as $res) {
                    if (!empty($res['missing_labels'])) {
                        $combined['missing_labels'] = array_merge($combined['missing_labels'], $res['missing_labels']);
                    }
                    if (!empty($res['updated_at'])) {
                        $combined['updated_at'][] = $res['updated_at'];
                    }
                }
                // Single success message
                $combined['message'] = labels('admin_labels.language_added_successfully', 'Language Added Successfully');
                return response()->json($combined);
            }
            return redirect()->back()->with('success', labels('admin_labels.language_added_successfully', 'Language Added Successfully'));
        }

        // Return the response (language created without file)
        $message = labels('admin_labels.language_added_successfully', 'Language Added Successfully');
        if ($request->ajax()) {
            return response()->json(['error' => false, 'message' => $message]);
        }
        return redirect()->back()->with('success', $message);
    }


    public function change(Request $request)
    {

        $request->validate([
            'lang' => 'required|string|max:255',
        ]);

        $is_rtl = fetchDetails(Language::class, ['code' => $request->lang], 'is_rtl');
        $is_rtl = isset($is_rtl) && !empty($is_rtl) ? $is_rtl[0]->is_rtl : '';

        app()->setLocale($request->lang);

        session()->put('locale', $request->lang);
        session()->put('is_rtl', $is_rtl);

        return redirect()->back();
    }
    // public function savelabel(Request $request, Language $lang)
    // {
    //     $data = $request->except(["_token", "_method"]);

    //     $langstr = '';

    //     foreach ($data as $key => $value) {
    //         $label_data = strip_tags($value);
    //         $label_key = $key;
    //         $langstr .= "'" . $label_key . "' => '$label_data'," . "\n";
    //     }
    //     $langstr_final = "<?php return [" . "\n\n\n" . $langstr . "];";
    //     $root = base_path("/resources/lang");
    //     $dir = $root . '/' . $request->langcode;
    //     if (!file_exists($dir)) {
    //         mkdir($dir, 0755, true);
    //     }
    //     $filename = $dir . '/admin_labels.php';
    //     // dd($filename);
    //     file_put_contents($filename, $langstr_final);
    //     return response()->json(['error' => false, 'message' => labels('admin_labels.language_labels_added_successfully', 'Language labels added successfully')]);
    // }


    public function savelabel(Request $request)
    {
        // Support both old single file format and new multi-file array format
        $file = null;
        $file_type = $request->input('file_type', 'panel');

        // Check for new array-based format: translation_files[type] (create mode)
        if (!$file && $request->hasFile('translation_files')) {
            $files = $request->file('translation_files');
            if (is_array($files)) {
                // Look for any uploaded file in the array
                foreach ($files as $type => $uploadedFile) {
                    if ($uploadedFile && !is_array($uploadedFile) && ($uploadedFile instanceof \Symfony\Component\HttpFoundation\File\UploadedFile)) {
                        $file = $uploadedFile;
                        $file_type = $type; // Use the actual type from the array key
                        break;
                    } elseif (is_array($uploadedFile)) {
                        // Handle array of files
                        foreach ($uploadedFile as $singleFile) {
                            if ($singleFile && ($singleFile instanceof \Symfony\Component\HttpFoundation\File\UploadedFile)) {
                                $file = $singleFile;
                                $file_type = $type;
                                break 2;
                            }
                        }
                    }
                }
            }
        }

        // Check for select mode files: translation_files_select[type]
        if (!$file && $request->hasFile('translation_files_select')) {
            $files = $request->file('translation_files_select');
            if (is_array($files)) {
                // Look for any uploaded file in the array
                foreach ($files as $type => $uploadedFile) {
                    if ($uploadedFile && !is_array($uploadedFile) && ($uploadedFile instanceof \Symfony\Component\HttpFoundation\File\UploadedFile)) {
                        $file = $uploadedFile;
                        $file_type = $type;
                        break;
                    } elseif (is_array($uploadedFile)) {
                        // Handle array of files
                        foreach ($uploadedFile as $singleFile) {
                            if ($singleFile && ($singleFile instanceof \Symfony\Component\HttpFoundation\File\UploadedFile)) {
                                $file = $singleFile;
                                $file_type = $type;
                                break 2;
                            }
                        }
                    }
                }
            }
        }

        // Fall back to old single file format
        if (!$file && $request->hasFile('translation_file')) {
            $file = $request->file('translation_file');
        }

        if (!$file || !($file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile)) {
            return response()->json(['error' => true, 'message' => 'No file uploaded.']);
        }

        if (!$file->isValid()) {
            return response()->json(['error' => true, 'message' => 'Uploaded file is invalid.']);
        }

        $language_code = $request->input('language_code') ?: session()->get('locale');

        if (!$language_code) {
            return response()->json(['error' => true, 'message' => 'Language code is required.']);
        }

        // Define the directory for the language
        $dir = base_path("/resources/lang/{$language_code}");
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        $fileExtension = strtolower($file->getClientOriginalExtension());

        // Handle JSON files
        if ($fileExtension === 'json') {
            $jsonContent = file_get_contents($file->getRealPath());
            $data = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json(['error' => true, 'message' => 'Invalid JSON file: ' . json_last_error_msg()]);
            }

            if (!is_array($data)) {
                return response()->json(['error' => true, 'message' => 'JSON file must contain an object/array.']);
            }

            // Determine filename based on file type
            $filename = match ($file_type) {
                'app' => $dir . '/app_labels.json',
                'panel', 'admin' => $dir . '/panel_labels.json',
                'web' => $dir . '/web_labels.json',
                'seller' => $dir . '/seller_labels.json',
                'delivery' => $dir . '/delivery_labels.json',
                default => $dir . '/panel_labels.json'
            };

            // Get reference/master file (English) for comparison
            $reference_file = match ($file_type) {
                'app' => base_path("/resources/lang/en/app_labels.json"),
                'panel', 'admin' => base_path("/resources/lang/en/panel_labels.json"),
                'web' => base_path("/resources/lang/en/web_labels.json"),
                'seller' => base_path("/resources/lang/en/seller_labels.json"),
                'delivery' => base_path("/resources/lang/en/delivery_labels.json"),
                default => base_path("/resources/lang/en/panel_labels.json")
            };

            // Compare with reference file to find missing labels
            $missing_labels = [];
            $reference_data = [];
            if (file_exists($reference_file)) {
                $referenceContent = file_get_contents($reference_file);
                $reference_data = json_decode($referenceContent, true);
                if (is_array($reference_data)) {
                    $missing_labels = array_diff_key($reference_data, $data);
                }
            }

            // Get file's actual upload time instead of server time
            $fileUploadTime = \Carbon\Carbon::createFromTimestamp(filemtime($file->getRealPath()))->toIso8601String();

            // Add metadata to JSON (updated_at and missing_labels count)
            $metadata = [
                'updated_at' => $fileUploadTime,
                'missing_labels_count' => count($missing_labels),
                'total_labels' => count($data),
                'reference_labels' => count($reference_data)
            ];

            // Save JSON file with metadata
            $jsonData = [...$data];
            file_put_contents($filename, json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // Also save as PHP for backward compatibility (panel/admin only)
            if ($file_type === 'panel' || $file_type === 'admin') {
                $langstr = '';
                foreach ($data as $key => $value) {
                    $label_data = strip_tags($value);
                    $label_key = $key;
                    $langstr .= var_export($label_key, true) . ' => ' . var_export($label_data, true) . ",\n";
                }
                $langstr_final = "<?php return [\n\n" . $langstr . "];";
                $php_filename = $dir . '/admin_labels.php';
                file_put_contents($php_filename, $langstr_final);
            }

            $message = labels('admin_labels.language_labels_added_successfully', 'Language labels added successfully');
            if (count($missing_labels) > 0) {
                $message .= '. ' . count($missing_labels) . ' label(s) missing compared to reference.';
            }

            return response()->json([
                'error' => false,
                'message' => $message,
                'missing_labels_count' => count($missing_labels),
                'missing_labels' => array_keys($missing_labels),
                'updated_at' => $metadata['updated_at']
            ]);
        }
        // Handle PHP files (backward compatibility)
        elseif ($fileExtension === 'php') {
            $data = include($file->getRealPath());

            if (!is_array($data)) {
                return response()->json(['error' => true, 'message' => 'Uploaded PHP file must return an array.']);
            }

            // Prepare data for saving
            $langstr = '';
            foreach ($data as $key => $value) {
                $label_data = strip_tags($value);
                $label_key = $key;
                $langstr .= var_export($label_key, true) . ' => ' . var_export($label_data, true) . ",\n";
            }

            // Final content to be written in the PHP file
            $langstr_final = "<?php return [\n\n" . $langstr . "];";

            // Set the filename to 'admin_labels.php'
            $filename = $dir . '/admin_labels.php';

            // Save the PHP file with the fixed name
            file_put_contents($filename, $langstr_final);

            // Also save as JSON for panel/admin
            if ($file_type === 'panel' || $file_type === 'admin') {
                $json_filename = $dir . '/panel_labels.json';
                file_put_contents($json_filename, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }

            return response()->json([
                'error' => false,
                'message' => labels('admin_labels.language_labels_added_successfully', 'Language labels added successfully')
            ]);
        } else {
            return response()->json(['error' => true, 'message' => 'Invalid file format. Only JSON and PHP files are allowed.']);
        }
    }

    private function processTranslationFile($file, $file_type, $language_code, $request)
    {
        try {
            if ($file->isValid()) {
                $extension = $file->getClientOriginalExtension();
                $language_details = Language::where('code', $language_code)->first();

                if (!$language_details) {
                    return ['error' => true, 'message' => 'Language not found.', 'missing_labels' => [], 'updated_at' => now()->toIso8601String()];
                }

                $dir = base_path("resources/lang/{$language_code}");
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }

                // Handle JSON files
                if ($extension === 'json') {
                    $content = file_get_contents($file->getRealPath());
                    $data = json_decode($content, true);

                    if (!is_array($data)) {
                        return ['error' => true, 'message' => 'Invalid JSON format.', 'missing_labels' => [], 'updated_at' => now()->toIso8601String()];
                    }

                    // Get English reference file
                    $english_file = base_path("resources/lang/en/{$file_type}_labels.json");
                    if (file_exists($english_file)) {
                        $english_content = file_get_contents($english_file);
                        $english_data = json_decode($english_content, true);
                        $missing_labels = array_diff_key($english_data, $data);
                    } else {
                        $missing_labels = [];
                    }

                    // Get file's actual upload time instead of server time
                    $fileUploadTime = \Carbon\Carbon::createFromTimestamp(filemtime($file->getRealPath()))->toIso8601String();

                    // Save JSON file
                    $filename = $dir . '/' . $file_type . '_labels.json';
                    $metadata = [
                        '_metadata' => [
                            'language' => $language_details->language,
                            'code' => $language_code,
                            'last_updated' => $fileUploadTime
                        ],
                        '_missing_labels' => $missing_labels
                    ];

                    $final_data = $data;
                    file_put_contents($filename, json_encode($final_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                    // If panel, also save as PHP
                    if ($file_type === 'panel' || $file_type === 'admin') {
                        $langstr = '';
                        foreach ($data as $key => $value) {
                            $label_data = strip_tags($value);
                            $label_key = $key;
                            $langstr .= var_export($label_key, true) . ' => ' . var_export($label_data, true) . ",\n";
                        }
                        $langstr_final = "<?php return [\n\n" . $langstr . "];";
                        $php_filename = $dir . '/admin_labels.php';
                        file_put_contents($php_filename, $langstr_final);
                    }

                    return [
                        'error' => false,
                        'message' => labels('admin_labels.language_labels_added_successfully', 'Language labels added successfully'),
                        'missing_labels' => $missing_labels,
                        'updated_at' => $fileUploadTime
                    ];
                }
                // Handle PHP files
                elseif ($extension === 'php') {
                    include $file->getRealPath();
                    $data = isset($GLOBALS['labels']) ? $GLOBALS['labels'] : null;

                    if ($data === null) {
                        $data = (array) $this->extractPhpReturn($file->getRealPath());
                    }

                    if (!is_array($data)) {
                        return ['error' => true, 'message' => 'Invalid PHP format.', 'missing_labels' => [], 'updated_at' => now()->toIso8601String()];
                    }

                    // Get English reference file
                    $english_file = base_path("resources/lang/en/{$file_type}_labels.json");
                    if (file_exists($english_file)) {
                        $english_content = file_get_contents($english_file);
                        $english_data = json_decode($english_content, true);
                        $missing_labels = array_diff_key($english_data, $data);
                    } else {
                        $missing_labels = [];
                    }

                    // Get file's actual upload time instead of server time
                    $fileUploadTime = \Carbon\Carbon::createFromTimestamp(filemtime($file->getRealPath()))->toIso8601String();

                    // Save as JSON
                    $filename = $dir . '/' . $file_type . '_labels.json';
                    $metadata = [
                        '_metadata' => [
                            'language' => $language_details->language,
                            'code' => $language_code,
                            'last_updated' => $fileUploadTime
                        ],
                        '_missing_labels' => $missing_labels
                    ];

                    $final_data = $data;
                    file_put_contents($filename, json_encode($final_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                    // Save as PHP
                    $langstr = '';
                    foreach ($data as $key => $value) {
                        $label_data = strip_tags($value);
                        $label_key = $key;
                        $langstr .= var_export($label_key, true) . ' => ' . var_export($label_data, true) . ",\n";
                    }
                    $langstr_final = "<?php return [\n\n" . $langstr . "];";
                    $php_filename = $dir . '/admin_labels.php';
                    file_put_contents($php_filename, $langstr_final);

                    return [
                        'error' => false,
                        'message' => labels('admin_labels.language_labels_added_successfully', 'Language labels added successfully'),
                        'missing_labels' => $missing_labels,
                        'updated_at' => $fileUploadTime
                    ];
                } else {
                    return ['error' => true, 'message' => 'Invalid file format. Only JSON and PHP files are allowed.', 'missing_labels' => [], 'updated_at' => \Carbon\Carbon::now()->toIso8601String()];
                }
            } else {
                return ['error' => true, 'message' => 'Uploaded file is invalid.', 'missing_labels' => [], 'updated_at' => now()->toIso8601String()];
            }
        } catch (\Exception $e) {
            return ['error' => true, 'message' => $e->getMessage(), 'missing_labels' => [], 'updated_at' => now()->toIso8601String()];
        }
    }
    private function validateTranslationJson(\Symfony\Component\HttpFoundation\File\UploadedFile $file)
{
    if ($file->getSize() === 0) {
        return 'File is empty';
    }

    $content = file_get_contents($file->getRealPath());

    try {
        $json = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    } catch (\JsonException $e) {
        return 'Invalid JSON format';
    }

    if (!is_array($json)) {
        return 'Root JSON must be an object';
    }

    $stack = [$json];

    while ($stack) {
        $current = array_pop($stack);

        foreach ($current as $key => $value) {
            if (!is_string($key)) {
                return 'All JSON keys must be strings';
            }

            if (is_array($value)) {
                $stack[] = $value;
            } elseif (!is_string($value)) {
                return "Invalid value for key '{$key}'. Only strings or nested objects allowed";
            }
        }
    }

    return true;
}


    private function extractPhpReturn($filePath)
    {
        $content = file_get_contents($filePath);
        // Use regex to extract the return array from PHP file
        if (preg_match('/return\s*\[(.*?)\];/s', $content, $matches)) {
            $arrayContent = '[' . $matches[1] . ']';
            // Try to evaluate the array (safer way using var_export)
            $result = eval('return ' . $arrayContent . ';');
            return is_array($result) ? $result : [];
        }
        return [];
    }

    public function setLanguage($locale)
    {
        config(['app.locale' => $locale]);
        session()->put('locale', $locale);

        return redirect()->back();
    }

    public function manageLanguage()
    {
        return view('admin.pages.tables.manage_languages');
    }

    public function delete($id)
    {
        $language = Language::findOrFail($id);
        $code = $language->code;

        // Path to the language folder
        $folderPath = resource_path("lang/$code");

        // Check if folder exists and delete it
        if (File::isDirectory($folderPath)) {
            File::deleteDirectory($folderPath);
        }

        // Delete language from the database
        if ($language->delete()) {
            return response()->json([
                'error' => false,
                'message' => labels('admin_labels.language_deleted_successfully', 'Language Deleted Successfully')
            ]);
        } else {
            return response()->json([
                'error' => true,
                'message' => labels('admin_labels.error_occurred_while_deleting_language', 'Error occurred while deleting language')
            ]);
        }
    }

    function list()
    {
        $search = trim(request('search', ''));
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $limit = request('limit', 5);
        $offset = request('offset', 0);
        $pageNumber = ($offset / $limit) + 1;

        $languages = Language::query()
            ->when($search, function ($query) use ($search) {
                return $query->where('language', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            })
            ->orderBy($sort, $order)
            ->paginate($limit, ['*'], 'page', $pageNumber);

        $languages->transform(function ($item) {
            // Adjust route names if needed
            $edit_url = route('languages.edit', $item['id']);
            $delete_url = route('languages.destroy', $item['id']);

            // Action dropdown menu
            $item['operate'] = '<div class="dropdown bootstrap-table-dropdown">
                    <a href="#" class="text-dark" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="bx bx-dots-horizontal-rounded"></i>
                    </a>
                    <div class="dropdown-menu table_dropdown language_action_dropdown">
                        <a class="dropdown-item edit-language"
                            href="#"
                            data-bs-toggle="modal"
                            data-bs-target="#editLanguageModal"
                            data-id="' . $item['id'] . '"
                            data-code="' . $item['code'] . '"
                            data-name="' . htmlspecialchars($item['language'], ENT_QUOTES, 'UTF-8') . '">
                            <i class="bx bx-pencil mx-2"></i> ' . labels('admin_labels.edit', 'Edit') . '
                        </a>';

            if ($item['code'] !== 'en') {
                $item['operate'] .= '<a class="dropdown-item delete-language"
                            href="#"
                            data-url="' . $delete_url . '">
                            <i class="bx bx-trash mx-2"></i> ' . labels('admin_labels.delete', 'Delete') . '
                        </a>';
            }

            $item['operate'] .= '</div>
                </div>';


            return $item;
        });

        return response()->json([
            "rows" => $languages->items(),
            'total' => $languages->total(),
        ]);
    }
    public function update(Request $request, $id)
    {
        $request->validate([
            'language' => 'required|string|max:255'
        ]);

        $language = Language::find($id);
        if (!$language) {
            return response()->json(['error' => true, 'message' => labels('admin_labels.language_not_found', 'Language not found')]);
        }

        $language->update([
            'language' => $request->language
        ]);
        return response()->json(['error' => false, 'message' => labels('admin_labels.language_updated_successfully', 'Language updated successfully')]);
    }
    public function bulk_upload()
    {
        return view('admin.pages.forms.translation_bulk_upload');
    }

    public function process_bulk_upload(Request $request)
    {
        if (!$request->hasFile('upload_file')) {
            return response()->json(['error' => true, 'message' => 'Please choose a file']);
        }

        $allowed_mime_types = [
            'text/x-comma-separated-values',
            'text/comma-separated-values',
            'application/x-csv',
            'text/x-csv',
            'text/csv',
            'application/csv',
        ];

        $uploaded_file = $request->file('upload_file');
        $uploaded_mime_type = $uploaded_file->getClientMimeType();
        $type = $request->type;

        if (!in_array($uploaded_mime_type, $allowed_mime_types)) {
            return response()->json(['error' => true, 'message' => 'Invalid file format']);
        }

        $model_type = [
            'brands' => ['model' => Brand::class, 'column' => ['name']],
            'categories' => ['model' => Category::class, 'column' => ['name']],
            'category_sliders' => ['model' => CategorySliders::class, 'column' => ['title']],
            'taxes' => ['model' => Tax::class, 'column' => ['title']],
            'cities' => ['model' => City::class, 'column' => ['name']],
            'blogs' => ['model' => Blog::class, 'column' => ['title']],
            'offers' => ['model' => Offer::class, 'column' => ['title']],
            'offer_sliders' => ['model' => OfferSliders::class, 'column' => ['title']],
            'zones' => ['model' => Zone::class, 'column' => ['name']],
            'stores' => ['model' => Store::class, 'column' => ['name', 'description']],
            'sections' => ['model' => Section::class, 'column' => ['title', 'short_description']],
            'products' => ['model' => Product::class, 'column' => ['name', 'short_description']],
            'combo_products' => ['model' => ComboProduct::class, 'column' => ['title', 'short_description']],
            'promo_codes' => ['model' => Promocode::class, 'column' => ['title', 'message']],
            'blog_categories' => ['model' => BlogCategory::class, 'column' => ['name']],
        ];
        $csv = fopen($uploaded_file->getRealPath(), 'r');

        $headers = fgetcsv($csv);
        $notFoundIds = [];
        $mismatchedRows = [];

        $model = $model_type[$type]['model'];
        $column_names = $model_type[$type]['column'];

        while (($row = fgetcsv($csv)) !== false) {
            if (count($headers) !== count($row)) {
                $mismatchedRows[] = $row;
                continue;
            }

            $rowData = array_combine($headers, $row);
            $recordId = $rowData['id'] ?? null;

            if (!$model::find($recordId)) {
                $notFoundIds[] = $recordId;
                continue;
            }

            $data = [];

            // foreach ($column_names as $column) {
            //     $jsonString = trim($rowData[$column] ?? '');
            //     $jsonString = stripslashes($jsonString);
            //     $decoded = json_decode($jsonString, true);

            //     if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            //         $data[$column] = json_encode($decoded, JSON_UNESCAPED_UNICODE);
            //     }
            // }
            foreach ($column_names as $column) {
                $jsonString = trim($rowData[$column] ?? '');
                $jsonString = stripslashes($jsonString);
                $decoded = json_decode($jsonString, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $data[$column] = json_encode($decoded, JSON_UNESCAPED_UNICODE);
                } else {
                    // If it's not JSON, just store it as plain text
                    $data[$column] = $jsonString;
                }
            }
            // dd($data);
            $model::updateOrCreate(
                ['id' => $recordId],
                $data
            );
        }

        fclose($csv);

        if (!empty($notFoundIds)) {
            return response()->json([
                'error' => true,
                'message' => 'These IDs were not found in the database.' . implode(', ', $notFoundIds),
                'not_found_ids' => $notFoundIds,
            ]);
        }
        if (!empty($mismatchedRows)) {
            $response['error'] = true;
            $response['message'] = ($response['message'] ?? '') . ' Some rows had column mismatch.';
            $response['mismatched_rows'] = $mismatchedRows;
        }


        return response()->json(['error' => 'false', 'message' =>  labels('admin_labels.upload_complete', 'Upload Complete')]);
    }
    public function export_translation_csv()
    {
        $tableMappings = [
            'brands' => ['table' => 'brands', 'columns' => ['id', 'name']],
            'categories' => ['table' => 'categories', 'columns' => ['id', 'name']],
            'category_sliders' => ['table' => 'category_sliders', 'columns' => ['id', 'title']],
            'taxes' => ['table' => 'taxes', 'columns' => ['id', 'title']],
            'cities' => ['table' => 'cities', 'columns' => ['id', 'name']],
            'blogs' => ['table' => 'blogs', 'columns' => ['id', 'title']],
            'offers' => ['table' => 'offers', 'columns' => ['id', 'title']],
            'offer_sliders' => ['table' => 'offer_sliders', 'columns' => ['id', 'title']],
            'zones' => ['table' => 'zones', 'columns' => ['id', 'name']],
            'stores' => ['table' => 'stores', 'columns' => ['id', 'name', 'description']],
            'sections' => ['table' => 'sections', 'columns' => ['id', 'title', 'short_description']],
            'products' => ['table' => 'products', 'columns' => ['id', 'name', 'short_description']],
            'combo_products' => ['table' => 'combo_products', 'columns' => ['id', 'title', 'short_description']],
            'promo_codes' => ['table' => 'promo_codes', 'columns' => ['id', 'title', 'message']],
            'blog_categories' => ['table' => 'blog_categories', 'columns' => ['id', 'name']],
        ];

        $zipFileName = 'bulk_translations_' . now()->format('Y-m-d_H-i-s') . '.zip';
        $zipFilePath = storage_path("app/{$zipFileName}");

        $zip = new ZipArchive;

        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return response()->json(['error' => 'Could not create ZIP archive.'], 500);
        }

        foreach ($tableMappings as $key => $config) {
            $tableName = $config['table'];
            $columns = $config['columns'];

            $rows = DB::table($tableName)->select($columns)->orderBy('id')->get();

            $csvHandle = fopen('php://temp', 'r+');
            fputcsv($csvHandle, $columns);

            foreach ($rows as $row) {
                $data = [];
                foreach ($columns as $col) {
                    $data[] = $row->$col;
                }
                fputcsv($csvHandle, $data);
            }

            rewind($csvHandle);
            $csvContent = stream_get_contents($csvHandle);
            fclose($csvHandle);

            $zip->addFromString("{$key}_bulk_translation.csv", $csvContent);
        }

        $zip->close();

        // ✅ Response to download
        return response()->download($zipFilePath)->deleteFileAfterSend(true);
    }

    public function downloadLanguageFile($language_code)
    {
        $filePath = base_path("resources/lang/{$language_code}/admin_labels.php");
        if (file_exists($filePath)) {
            return response()->download($filePath, "admin_labels.php");
        } else {
            return redirect()->back()->with('error', 'Language file not found for code: ' . $language_code);
        }
    }

    public function downloadLanguageJsonFile($language_code, $type = 'panel')
    {
        $filename = match ($type) {
            'app' => 'app_labels.json',
            'panel', 'admin' => 'panel_labels.json',
            'web' => 'web_labels.json',
            'seller' => 'seller_labels.json',
            'delivery' => 'delivery_labels.json',
            default => 'panel_labels.json'
        };

        $filePath = base_path("resources/lang/{$language_code}/{$filename}");
        if (file_exists($filePath)) {
            // Include language code in the downloaded filename
            $downloadFilename = "{$language_code}_{$filename}";
            return response()->download($filePath, $downloadFilename);
        } else {
            return redirect()->back()->with('error', 'Language file not found for code: ' . $language_code);
        }
    }

    public function compareLanguageLabels(Request $request)
    {
        $rules = [
            'language_code' => 'required|string',
            'file_type' => 'required|in:app,panel,web,seller,delivery,admin',
        ];

        // Validate manually to ensure JSON response
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        $language_code = $request->input('language_code');
        $file_type = $request->input('file_type');

        // Determine file paths
        $current_file = match ($file_type) {
            'app' => base_path("/resources/lang/{$language_code}/app_labels.json"),
            'panel', 'admin' => base_path("/resources/lang/{$language_code}/panel_labels.json"),
            'web' => base_path("/resources/lang/{$language_code}/web_labels.json"),
            'seller' => base_path("/resources/lang/{$language_code}/seller_labels.json"),
            'delivery' => base_path("/resources/lang/{$language_code}/delivery_labels.json"),
            default => base_path("/resources/lang/{$language_code}/panel_labels.json")
        };

        $reference_file = match ($file_type) {
            'app' => base_path("/resources/lang/en/app_labels.json"),
            'panel', 'admin' => base_path("/resources/lang/en/panel_labels.json"),
            'web' => base_path("/resources/lang/en/web_labels.json"),
            'seller' => base_path("/resources/lang/en/seller_labels.json"),
            'delivery' => base_path("/resources/lang/en/delivery_labels.json"),
            default => base_path("/resources/lang/en/panel_labels.json")
        };

        $current_data = [];
        $reference_data = [];
        $metadata = null;
        $updated_at = null;

        // Load current language file
        if (file_exists($current_file)) {
            $currentContent = file_get_contents($current_file);
            $current_data = json_decode($currentContent, true);

            // Get file's actual modification time
            $updated_at = date('Y-m-d H:i:s', filemtime($current_file));

            // Extract metadata if exists
            if (isset($current_data['_metadata'])) {
                unset($current_data['_metadata']);
            }

            // Remove missing labels list
            if (isset($current_data['_missing_labels'])) {
                unset($current_data['_missing_labels']);
            }
        }

        // Load reference file (English)
        if (file_exists($reference_file)) {
            $referenceContent = file_get_contents($reference_file);
            $reference_data = json_decode($referenceContent, true);

            // Remove metadata from reference if exists
            if (isset($reference_data['_metadata'])) {
                unset($reference_data['_metadata']);
            }
            if (isset($reference_data['_missing_labels'])) {
                unset($reference_data['_missing_labels']);
            }
        }

        // Compare
        $missing_labels = [];
        $extra_labels = [];

        if (!empty($reference_data)) {
            $missing_labels = array_diff_key($reference_data, $current_data);
            $extra_labels = array_diff_key($current_data, $reference_data);
        }

        $response_data = [
            'error' => false,
            'message' => 'Comparison completed successfully',
            'data' => [
                'language_code' => $language_code,
                'file_type' => $file_type,
                'current_labels_count' => count($current_data),
                'reference_labels_count' => count($reference_data),
                'missing_labels_count' => count($missing_labels),
                'extra_labels_count' => count($extra_labels),
                'missing_labels' => array_values(array_keys($missing_labels)),
                'extra_labels' => array_values(array_keys($extra_labels)),
                'updated_at' => $updated_at,
                'file_exists' => file_exists($current_file),
                'reference_exists' => file_exists($reference_file),
            ]
        ];

        return response()->json($response_data, 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function getLanguageFileInfo(Request $request)
    {
        $rules = [
            'language_code' => 'required|string',
            'file_type' => 'sometimes|in:app,panel,web,seller,delivery,admin',
        ];

        $messages = [
            'language_code.required' => 'Please select language code from the update side.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        $language_code = $request->input('language_code');
        $file_types = $request->has('file_type') ? [$request->input('file_type')] : ['app', 'panel', 'web', 'seller', 'delivery'];

        $files_info = [];

        foreach ($file_types as $file_type) {
            $filename = match ($file_type) {
                'app' => 'app_labels.json',
                'panel', 'admin' => 'panel_labels.json',
                'web' => 'web_labels.json',
                'seller' => 'seller_labels.json',
                'delivery' => 'delivery_labels.json',
                default => 'panel_labels.json'
            };

            $filePath = base_path("resources/lang/{$language_code}/{$filename}");

            $info = [
                'file_type' => $file_type,
                'filename' => $filename,
                'exists' => file_exists($filePath),
                'updated_at' => null,
                'missing_labels_count' => 0,
                'total_labels' => 0,
            ];

            if (file_exists($filePath)) {
                $content = file_get_contents($filePath);
                $data = json_decode($content, true);

                if (isset($data['_metadata'])) {
                    $file_modified_time = filemtime($filePath);
                    $info['updated_at'] = date('d-m-Y H:i:s', $file_modified_time);
                    $info['missing_labels_count'] = $data['_metadata']['missing_labels_count'] ?? 0;
                    $info['total_labels'] = $data['_metadata']['total_labels'] ?? count($data);
                } else {
                    // Remove metadata keys if they exist
                    unset($data['_metadata']);
                    unset($data['_missing_labels']);
                    $info['total_labels'] = count($data);
                }

                $info['file_size'] = filesize($filePath);
                $info['last_modified'] = date('Y-m-d H:i:s', filemtime($filePath));
            }

            $files_info[] = $info;
        }

        return response()->json([
            'error' => false,
            'message' => 'Language file information retrieved successfully',
            'data' => $files_info
        ]);
    }

    public function getMissingLabelsSummary()
    {
        $languages = Language::all();
        $file_types = ['app', 'panel', 'web', 'seller', 'delivery'];
        $summary = [];

        foreach ($languages as $language) {
            $language_code = $language->code;
            $language_summary = [
                'language_id' => $language->id,
                'language_name' => $language->language,
                'language_code' => $language_code,
                'files' => []
            ];

            foreach ($file_types as $file_type) {
                $filename = match ($file_type) {
                    'app' => 'app_labels.json',
                    'panel', 'admin' => 'panel_labels.json',
                    'web' => 'web_labels.json',
                    'seller' => 'seller_labels.json',
                    'delivery' => 'delivery_labels.json',
                    default => 'panel_labels.json'
                };

                $current_file = base_path("/resources/lang/{$language_code}/{$filename}");
                $reference_file = match ($file_type) {
                    'app' => base_path("/resources/lang/en/app_labels.json"),
                    'panel', 'admin' => base_path("/resources/lang/en/panel_labels.json"),
                    'web' => base_path("/resources/lang/en/web_labels.json"),
                    'seller' => base_path("/resources/lang/en/seller_labels.json"),
                    'delivery' => base_path("/resources/lang/en/delivery_labels.json"),
                    default => base_path("/resources/lang/en/panel_labels.json")
                };

                $current_data = [];
                $reference_data = [];
                $missing_labels = [];
                $updated_at = null;

                // Load current file
                if (file_exists($current_file)) {
                    $currentContent = file_get_contents($current_file);
                    $current_data = json_decode($currentContent, true);

                    // Get file's actual modification time
                    $updated_at = date('Y-m-d H:i:s', filemtime($current_file));

                    if (isset($current_data['_metadata'])) {
                        unset($current_data['_metadata']);
                    }
                    if (isset($current_data['_missing_labels'])) {
                        unset($current_data['_missing_labels']);
                    }
                }

                // Load reference file
                if (file_exists($reference_file)) {
                    $referenceContent = file_get_contents($reference_file);
                    $reference_data = json_decode($referenceContent, true);

                    if (isset($reference_data['_metadata'])) {
                        unset($reference_data['_metadata']);
                    }
                    if (isset($reference_data['_missing_labels'])) {
                        unset($reference_data['_missing_labels']);
                    }
                }

                // Compare
                if (!empty($reference_data) && !empty($current_data)) {
                    $missing_labels = array_diff_key($reference_data, $current_data);
                } elseif (!empty($reference_data) && empty($current_data)) {
                    $missing_labels = $reference_data;
                }

                $language_summary['files'][] = [
                    'file_type' => $file_type,
                    'exists' => file_exists($current_file),
                    'missing_labels_count' => count($missing_labels),
                    'missing_labels' => array_values(array_keys($missing_labels)),
                    'total_labels' => count($current_data),
                    'reference_labels' => count($reference_data),
                    'updated_at' => $updated_at,
                ];
            }

            // Only add to summary if there are missing labels
            $total_missing = array_sum(array_column($language_summary['files'], 'missing_labels_count'));
            if ($total_missing > 0) {
                $language_summary['total_missing_labels'] = $total_missing;
                $summary[] = $language_summary;
            }
        }

        return $summary;
    }
}
