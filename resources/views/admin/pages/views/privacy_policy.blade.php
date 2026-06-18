<!DOCTYPE html>
<html lang="en">
@php
    use App\Services\MediaService;
@endphp

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ app(MediaService::class)->getMediaImageUrl($setting['favicon']) }}">
    <title>{{ labels('admin_labels.privacy_policy_placeholder', 'Privacy Policy') }}</title>
</head>

<body>
    <h2>{{ labels('admin_labels.privacy_policy_placeholder', 'Privacy Policy') }}</h2>
    @if (!empty($privacy_policy['privacy_policy']))
        {!! $privacy_policy['privacy_policy'] !!}
    @elseif (!empty($privacy_policy['seller_privacy_policy']))
        {!! $privacy_policy['seller_privacy_policy'] !!}
    @elseif (!empty($privacy_policy['delivery_boy_privacy_policy']))
        {!! $privacy_policy['delivery_boy_privacy_policy'] !!}
    @endif

</body>

</html>
