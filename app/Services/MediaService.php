<?php

namespace App\Services;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use SplFileInfo;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Filesystem as MediaFilesystem;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;

class MediaService
{
    public function findMediaType($extenstion)
    {
        $mediaTypes = config('eshop_pro.type');

        foreach ($mediaTypes as $mainType => $mediaType) {
            if (in_array(strtolower($extenstion), $mediaType['types'])) {

                return [$mainType, $mediaType['icon']];
            }
        }
        return false;
    }
public function getImageUrl($path, $image_type = '', $image_size = '', $file_type = 'image', $const = 'MEDIA_PATH')
    {
        // If the path is already a full URL (S3 or external), just return it
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        $pathParts = explode('/', $path);
        $subdirectory = implode("/", array_slice($pathParts, 0, -1));
        $image_name = end($pathParts);
        $file_main_dir = str_replace('\\', '/', public_path(config('constants.' . $const) . $subdirectory));

        if ($file_type == 'image') {
            $types = ['thumb', 'cropped'];
            $sizes = ['md', 'sm'];

            if (in_array(strtolower($image_type), $types) && in_array(strtolower($image_size), $sizes)) {
                $filepath = $file_main_dir . '/' . $image_type . '-' . $image_size . '/' . $image_name;

                if (File::exists($filepath)) {
                    return asset(config('constants.' . $const) . '/' . $path);
                } elseif (File::exists($file_main_dir . '/' . $image_name)) {
                    return asset(config('constants.' . $const) . '/' . $path);
                } else {
                    return asset(Config::get('constants.NO_IMAGE'));
                }
            } else {
                if (File::exists($file_main_dir . '/' . $image_name)) {
                    return asset(config('constants.' . $const) . '/' . $path);
                } else {
                    return asset(Config::get('constants.NO_IMAGE'));
                }
            }
        } else {
            $file = new SplFileInfo($file_main_dir . '/' . $image_name);
            $ext = $file->getExtension();
            $media_data = $this->findMediaType($ext);

            if (is_array($media_data) && isset($media_data[1])) {
                $imagePlaceholder = $media_data[1];
            } else {
                return asset(Config::get('constants.NO_IMAGE'));
            }

            $filepath = str_replace('\\', '/', public_path($imagePlaceholder));
            if (File::exists($filepath)) {
                return asset($imagePlaceholder);
            } else {
                return asset(Config::get('constants.NO_IMAGE'));
            }
        }
    }

    public function removeMediaFile($path, $disk)
    {


        // Instantiate the Spatie Media Library Filesystem
        $mediaFileSystem = app(MediaFilesystem::class);

        // Instantiate the FilesystemFactory
        $filesystem = app(FilesystemFactory::class);

        // Instantiate the CustomFileRemover with the dependencies
        $fileRemover = new CustomFileRemover($mediaFileSystem, $filesystem);

        if ($disk == 's3') {
            // Get the last two segments of the path
            $path = implode('/', array_slice(explode('/', $path), -2));
        }


        $fileRemover->removeFile($path, $disk);
    }

 public function dynamic_image($image, $width, $quality = 75)
{
    $sourceUrl = $this->normalizeImageUrl($image);

    // If a previously-resized copy exists on disk, return its direct asset URL
    // so Apache serves it as a static file (with the long-lived cache headers
    // from .htaccess) and Laravel is never booted for that image.
    $cached = $this->cachedDynamicImagePath($sourceUrl, $width, $quality);
    if ($cached !== null && is_file($cached['absolute'])) {
        return $cached['url'];
    }

    return route('front_end.dynamic_image', [
        'url' => $sourceUrl,
        'width' => $width,
        'quality' => $quality,
    ]);
}

public function cachedDynamicImagePath($sourceUrl, $width, $quality)
{
    if (empty($sourceUrl)) {
        return null;
    }
    $path = parse_url($sourceUrl, PHP_URL_PATH) ?? '';
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION)) ?: 'jpg';
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
        $ext = 'jpg';
    }
    $hash = md5($sourceUrl . '|' . intval($width) . '|' . intval($quality));
    $rel = 'cache/dynamic_image/' . substr($hash, 0, 2) . '/' . $hash . '.' . $ext;
    return [
        'relative' => $rel,
        'absolute' => public_path('storage/' . $rel),
        'url'      => asset('storage/' . $rel),
    ];
}

private function normalizeImageUrl($image, $const = 'MEDIA_PATH')
{
    // If empty, fallback to NO_IMAGE
    if (empty($image)) {
        return asset(config('constants.NO_IMAGE'));
    }

    // If already full URL, just return it
    if (Str::startsWith($image, ['http://', 'https://'])) {
        return $image;
    }

    // Otherwise assume it’s a relative path inside your MEDIA_PATH
    return asset(config('constants.' . $const) . $image);
}
private function buildPublicFilePath($image, $const = 'MEDIA_PATH')
{
    $basePath = rtrim(config('constants.' . $const), '/');
    $normalized = ltrim($image, '/');
    return public_path($basePath . '/' . $normalized);
}
public function getMediaImageUrl($image, $const = 'MEDIA_PATH')
{
    if (empty($image)) {
        return asset(config('constants.NO_IMAGE'));
    }

    // Already full URL
    if (Str::startsWith($image, ['http://', 'https://'])) {
        return $image;
    }

    // Build proper path
    $filePath = $this->buildPublicFilePath($image, $const);

    if (File::exists($filePath)) {
        // Corrected asset path generation
        $basePath = rtrim(config('constants.' . $const), '/');
        $normalized = ltrim($image, '/');
        return asset($basePath . '/' . $normalized);
    }

    return asset(config('constants.NO_IMAGE'));
}



}
