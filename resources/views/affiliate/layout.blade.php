<!DOCTYPE html>
<html lang="en">

<meta name="csrf-token" content="{{ csrf_token() }}">

@include('admin.include_css')

<body>
    <div id="db-wrapper">

        <x-affiliate.side-bar />
        <div id="page-content">

            <x-affiliate.header />
            <div class="container-fluid mt-5 px-6" {{ session()->get('is_rtl') == 1 ? 'dir=rtl' : '' }}>
                @yield('content')

            </div>
        </div>
    </div>
    <x-affiliate.footer />
    <!-- Scripts -->
</body>
@include('admin.include_script')

</html>
