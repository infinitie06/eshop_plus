 <!-- Sidebar -->
 @php
 use App\Services\MediaService;
 @endphp

 <nav class="navbar-vertical navbar bg-white" {{ session()->get('is_rtl') == 1 ? 'dir=rtl' : '' }}>
    <div class="nav-scroller bg-white">

        <div class="sidenav-header">

            <a class="navbar-brand m-0" href="{{ route('affiliate.home') }}" target="">
                @php
                    $store_logo =
                        !empty($setting['logo']) &&
                        file_exists(public_path(config('constants.MEDIA_PATH') . $setting['logo']))
                            ? app(MediaService::class)->getMediaImageUrl($setting['logo'])
                            : asset('assets/img/default_full_logo.png');
                @endphp
                <img src="{{ $store_logo }}" class="navbar-brand-img" alt="main_logo">
            </a>
        </div>
        <hr class="horizontal dark mt-0">

        <!-- code for menu search -->

        <div class="ps-2 pe-2">
            <!-- Search Bar -->
            <input type="text" class="form-control menuSearch" placeholder="{{ labels('admin_labels.search_menu', 'Search Menu...') }}">
        </div>


        <ul class="navbar-nav" id="menuList">
            <li class="sidebar-title ms-3"><i class='bx bx-tachometer'></i>
                {{ labels('admin_labels.dashboard', 'Dashboard') }}
            </li>
            <li class="nav-item ms-3">
                <a class="nav-link {{ Request::is('affiliate/home') || Request::is('affiliate/home/*') ? 'active' : '' }}"
                    href="{{ route('affiliate.home') }}">
                    <span class="nav-link-text ms-1">{{ labels('admin_labels.dashboard', 'Dashboard') }}</span>
                </a>
            </li>
            <li class="nav-item ms-3">
                <a class="nav-link {{ Request::is('affiliate/categories') || Request::is('affiliate/categories/*') ? 'active' : '' }}"
                    href="{{ route('affiliate.categories') }}">
                    <span class="nav-link-text ms-1">{{ labels('admin_labels.categories', 'Categories') }}</span>
                </a>
            </li>
            <li class="nav-item ms-3">
                <a class="nav-link {{ Request::is('affiliate/promoted_products') || Request::is('affiliate/promoted_products/*') ? 'active' : '' }}"
                    href="{{ route('affiliate.promoted_products') }}">
                    <span class="nav-link-text ms-1">{{ labels('admin_labels.promoted_products', 'Promoted Products') }}</span>
                </a>
            </li>
            <li class="nav-item ms-3">
                <a class="nav-link {{ Request::is('affiliate/transactions') || Request::is('affiliate/transactions/*') ? 'active' : '' }}"
                    href="{{ route('affiliate.transactions') }}">
                    <span class="nav-link-text ms-1">{{ labels('admin_labels.transactions', 'Transactions') }}</span>
                </a>
            </li>
            <li class="nav-item ms-3">
                <a class="nav-link {{ Request::is('affiliate/request_payment') || Request::is('affiliate/request_payment/*') ? 'active' : '' }}"
                    href="{{ route('affiliate.request_payment') }}">
                    <span class="nav-link-text ms-1">{{ labels('admin_labels.request_payment', 'Request Payment') }}</span>
                </a>
            </li>
            <li class="nav-item ms-3">
                <a class="nav-link {{ Request::is('affiliate/policies') || Request::is('affiliate/policies/*') ? 'active' : '' }}"
                    href="{{ route('affiliate.policies') }}">
                    <span class="nav-link-text ms-1">{{ labels('admin_labels.policies', 'Policies') }}</span>
                </a>
            </li>
        </ul>
    </div>
</nav>
