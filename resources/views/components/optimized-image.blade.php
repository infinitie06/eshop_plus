@props(['src', 'alt' => '', 'width' => 400, 'height' => null, 'class' => 'blur-up lazyload', 'loading' => 'lazy', 'sizes' => null])

@php
    $mediaService = app(\App\Services\MediaService::class);
    $imageUrl = $mediaService->dynamic_image($src, $width);
    $webpUrl = $imageUrl . '&format=webp';
    $avifUrl = $imageUrl . '&format=avif';

    // Generate responsive sizes if not provided
    if (!$sizes) {
        $sizes = '(max-width: 480px) 100vw, (max-width: 768px) 50vw, (max-width: 1024px) 33vw, 25vw';
    }
@endphp

<picture>
    {{-- AVIF format for most modern browsers --}}
    <source srcset="{{ $avifUrl }}" type="image/avif" {{ $sizes ? "sizes='{$sizes}'" : '' }}>

    {{-- WebP format for modern browsers --}}
    <source srcset="{{ $webpUrl }}" type="image/webp" {{ $sizes ? "sizes='{$sizes}'" : '' }}>

    {{-- Fallback to original format --}}
    <img class="{{ $class }}"
         src="{{ $imageUrl }}"
         alt="{{ $alt }}"
         {{ $width ? "width='{$width}'" : '' }}
         {{ $height ? "height='{$height}'" : '' }}
         loading="{{ $loading }}"
         decoding="async"
         {{ $sizes ? "sizes='{$sizes}'" : '' }}>
</picture>
