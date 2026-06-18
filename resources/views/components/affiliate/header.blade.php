<div class="header @@classList bg-white">
    <link rel="stylesheet" href="{{ asset('/assets/boxicons/css/boxicons.css') }}">

    <!-- navbar -->
    <nav class="navbar navbar-main navbar-expand-lg px-0 shadow-none border-radius-xl" id="navbarBlur" data-scroll="false">
        <div id="app_url" data-app-url="{{ config('app.url') }}"></div>
        <div class="align-items-center d-flex px-6 py-1 w-100">
            <div class=" mt-2 navbar-collapse justify-content-end" id="navbar">
            </div>
            @php
                use Illuminate\Support\Facades\Auth;
                use App\Services\MediaService;
                use App\Models\Language;
                use App\Models\Store;
                $languages = Language::all();
                $languageCode = session()->get('locale') ?? 'en';
                $selectedLanguage = fetchDetails(Language::class, ['code' => $languageCode], 'language');
                $selectedLanguage =
                    isset($selectedLanguage) && !empty($selectedLanguage) ? $selectedLanguage[0]->language : 'English';
                $user = Auth::user();
                $userImage = app(MediaService::class)->getMediaImageUrl($user->image, 'USER_IMG_PATH');
                $stores = Store::where('is_default_store', 1)->where('status', 1)->get();
            @endphp
            @if (!empty($selectedLanguage))
                <label for="" class="badge bg-primary mx-3">{{ $selectedLanguage }}</label>
            @endif


            <li class="nav-item dropdown  d-flex justify-content-center me-3 notifiationDropDown">
                <a href="javascript:;" class="nav-link p-0" id="languageDropdown" data-bs-toggle="dropdown"
                    aria-expanded="false">
                    <i class="bx bx-globe"></i>
                </a>

                <ul class="dropdown-menu  dropdown-menu-end  px-2 py-3 me-sm-n4" aria-labelledby="languageDropdown">
                    @foreach ($languages as $language)
                        <li>
                            <a class="dropdown-item changeLang" data-lang-code="{{ $language->code }}">
                                {{ ucwords($language->language) }} - {{ strtoupper($language->code) }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </li>

            <div class="d-flex">
                <div id="profileDropDown" class="input-group-text">
                    <li class="nav-item dropdown pe-2 d-flex align-items-center">
                        <a href="javascript:;" class="nav-link text-white p-0 nav-link-text ms-1"
                            id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                            <img class="avatar rounded-circle avatar-sm" src="{{ $userImage }}">
                            {{ $user->username }}
                            <i class="fas fa-angle-down"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                            <li>
                                <a class="dropdown-item text-dark" href="/affiliate/account/{{ auth()->user()->id }}"><i
                                        class='bx bx-user-circle'></i>
                                    {{ labels('admin_labels.profile', 'Profile') }}</a>
                            </li>
                            <li>
                                <a class="dropdown-item text-dark" href="{{ route('affiliate.logout') }}"><i
                                        class='bx bx-log-in-circle'></i>{{ labels('admin_labels.logout', 'Logout') }}</a>
                            </li>
                        </ul>
                    </li>
                </div>
            </div>
        </div>
    </nav>
    @php

        $primary_colour =
            isset($stores[0]->primary_color) && !empty($stores[0]->primary_color)
                ? $stores[0]->primary_color
                : '#B52046';
        $background_opacity_color = $primary_colour . '10';
        $secondary_color =
            isset($stores[0]->secondary_color) && !empty($stores[0]->secondary_color)
                ? $stores[0]->secondary_color
                : '#201A1A';
        $hover_color =
            isset($stores[0]->hover_color) && !empty($stores[0]->hover_color) ? $stores[0]->hover_color : '#911A38';
        $active_color =
            isset($stores[0]->active_color) && !empty($stores[0]->active_color) ? $stores[0]->active_color : '#6D132A';

    @endphp

    <style>
        * {
            --primary-theme-color: <?=$primary_colour ?>;
            --background_opacity_color: <?=$background_opacity_color ?>;
            --secondary-theme-color: <?=$secondary_color ?>;
            --hover-color: <?=$hover_color ?>;
            --active-color: <?=$active_color ?>;
        }
    </style>
    <!-- End Navbar -->
</div>
