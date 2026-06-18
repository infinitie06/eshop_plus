<!DOCTYPE html>
<html lang="en" class="overflow-hidden">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Page Themes - Showcase Only</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('frontend/elegant/css/plugins.css') }}?v={{ $version }}">
    <link rel="stylesheet" href="{{ asset('frontend/elegant/css/vendor/photoswipe.min.css') }}?v={{ $version }}">
    <link rel="stylesheet" href="{{ asset('frontend/elegant/css/bootstrap-table.min.css') }}?v={{ $version }}">
    <link rel="stylesheet" href="{{ asset('frontend/elegant/css/style.css') }}?v={{ $version }}">
    <link rel="stylesheet" href="{{ asset('frontend/elegant/css/theme.min.css') }}?v={{ $version }}">
    <link rel="stylesheet" href="{{ asset('frontend/elegant/css/star-rating.css') }}?v={{ $version }}">
    <link rel="stylesheet" href="{{ asset('frontend/elegant/css/star-rating.min.css') }}?v={{ $version }}">
    <link rel="stylesheet" href="{{ asset('frontend/elegant/css/intlTelInput.css') }}?v={{ $version }}">
    <link rel="stylesheet" href="{{ asset('frontend/elegant/css/select2.min.css') }}?v={{ $version }}">
    <link rel="stylesheet" href="{{ asset('frontend/elegant/css/iziToast.css') }}?v={{ $version }}">
    <link rel="stylesheet" href="{{ asset('frontend/elegant/css/daterangepicker.css') }}?v={{ $version }}">
    <link rel="stylesheet" href="{{ asset('frontend/elegant/css/responsive.css') }}?v={{ $version }}">
    <link rel="stylesheet" href="{{ asset('frontend/elegant/css/shareon.min.css') }}?v={{ $version }}">
    <link rel="stylesheet" href="{{ asset('frontend/elegant/css/app.css') }}?v={{ $version }}">
    <link rel="stylesheet" href="{{ asset('frontend/elegant/css/swiper-bundle.min.css') }}?v={{ $version }}">
</head>

<body>

    <!-- THEME 1: Fashion Store -->
    <div id="web_home_page_theme_1" class="content-section">
        <iframe src="https://plus.eshopweb.store/?store=fashion-1"
                width="100%" height="600px" frameborder="0"
                title="Fashion Store Theme"
                sandbox="allow-same-origin allow-scripts allow-popups allow-forms">
        </iframe>
    </div>

    <!-- THEME 2: Prime Pantry -->
    <div id="web_home_page_theme_2" class="content-section d-none">
        <iframe src="https://plus.eshopweb.store/?store=prime-pantry"
                width="100%" height="600px" frameborder="0"
                title="Prime Pantry Theme"
                sandbox="allow-same-origin allow-scripts allow-popups allow-forms">
        </iframe>
    </div>

    <!-- THEME 3: Luxeline Ecommerce -->
    <div id="web_home_page_theme_3" class="content-section d-none">
        <iframe src="https://plus.eshopweb.store/?store=luxeline-ecommerce"
                width="100%" height="600px" frameborder="0"
                title="Luxeline Ecommerce Theme"
                sandbox="allow-same-origin allow-scripts allow-popups allow-forms">
        </iframe>
    </div>

    <!-- THEME 4: Pharmacy -->
    <div id="web_home_page_theme_4" class="content-section d-none">
        <iframe src="https://plus.eshopweb.store/?store=new-pharmacy"
                width="100%" height="600px" frameborder="0"
                title="Pharmacy Theme"
                sandbox="allow-same-origin allow-scripts allow-popups allow-forms">
        </iframe>
    </div>

    <!-- THEME 5: Electronics -->
    <div id="web_home_page_theme_5" class="content-section d-none">
        <iframe src="https://plus.eshopweb.store/?store=electronics"
                width="100%" height="600px" frameborder="0"
                title="Electronics Theme"
                sandbox="allow-same-origin allow-scripts allow-popups allow-forms">
        </iframe>
    </div>
      <div id="web_home_page_theme_6" class="content-section d-none">
        <iframe src="https://plus.eshopweb.store?store=grocery-shop"
                width="100%" height="600px" frameborder="0"
                title="Electronics Theme"
                sandbox="allow-same-origin allow-scripts allow-popups allow-forms">
        </iframe>
    </div>

    <!-- Theme Switcher (Safe - Only Toggles Visibility) -->
    <script>
        window.addEventListener("message", function(event) {
            const selectedStyle = event.data;
            document.querySelectorAll(".content-section").forEach(section => {
                section.classList.add("d-none");
            });
            const target = document.getElementById(selectedStyle);
            if (target) {
                target.classList.remove("d-none");
            }
        });
    </script>

</body>

<!-- Scripts (Minimal - Only for iframe compatibility & icons) -->
<script src="{{ asset('frontend/elegant/js/plugins.js') }}?v={{ $version }}"></script>
<script src="{{ asset('frontend/elegant/js/vendor/jquery.elevatezoom.js') }}?v={{ $version }}"></script>
<script src="{{ asset('frontend/elegant/js/swiper-bundle.min.js') }}?v={{ $version }}"></script>
<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>

</html>
