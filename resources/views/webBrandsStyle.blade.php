<!DOCTYPE html>
<html lang="en" class="overflow-hidden">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brand Styles - Showcase Only</title>
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

    <!-- BRAND STYLE 1 -->
    <div id="brands_display_style_for_web_1" class="content-section">
        <div class="collection-style1 row col-row row-cols-xl-8 row-cols-lg-auto row-cols-md-4 row-cols-3 d-flex justify-content-center">
            <div class="category-item col-item zoomscal-hov">
                <div class="category-link clr-none">
                    <div class="zoom-scal zoom-scal-nopb brands-image">
                        <img class="blur-up w-100 lazyloaded"
                            src="{{ asset('storage/system_images/brand_card_style.png') }}"
                            alt="Verdant Sip" title="Verdant Sip">
                    </div>
                    <div class="details mt-3 d-flex justify-content-center align-items-center">
                        <h4 class="category-title mb-0">Verdant Sip</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- BRAND STYLE 2 -->
    <div id="brands_display_style_for_web_2" class="content-section d-none">
        <div class="collection-style1 row col-row row-cols-xl-8 row-cols-lg-auto row-cols-md-4 row-cols-3 d-flex justify-content-center">
            <div class="category-item col-item">
                <div class="category-link clr-none brand-item">
                    <div class="brands-image">
                        <img class="blur-up w-100 lazyloaded"
                            src="{{ asset('storage/system_images/brand_card_style.png') }}"
                            alt="Verdant Sip" title="Verdant Sip">
                    </div>
                    <div class="details mt-3 d-flex justify-content-center align-items-center">
                        <h4 class="category-title mb-0">Verdant Sip</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- BRAND STYLE 3 -->
    <div id="brands_display_style_for_web_3" class="content-section d-none">
        <div class="collection-style1 row col-row row-cols-xl-8 row-cols-lg-auto row-cols-md-4 row-cols-3 d-flex justify-content-center">
            <div class="category-item col-item">
                <div class="category-link clr-none brand-item">
                    <div class="brands-image zoom-scal zoom-scal-nopb rounded-circle">
                        <img class="blur-up rounded-circle lazyloaded"
                            src="{{ asset('storage/system_images/brand_card_style.png') }}"
                            alt="Verdant Sip" title="Verdant Sip">
                    </div>
                    <div class="details mt-3 d-flex justify-content-center align-items-center">
                        <h4 class="category-title mb-0">Verdant Sip</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Style Switcher (Safe - Only Toggles Visibility) -->
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

<!-- Scripts (Kept for styling & icons only - No interactivity) -->
<script src="{{ asset('frontend/elegant/js/plugins.js') }}?v={{ $version }}"></script>
<script src="{{ asset('frontend/elegant/js/vendor/jquery.elevatezoom.js') }}?v={{ $version }}"></script>
<script src="{{ asset('frontend/elegant/js/moment.min.js') }}?v={{ $version }}"></script>
<script src="{{ asset('frontend/elegant/js/sweetalert2.all.min.js') }}?v={{ $version }}"></script>
<script src="{{ asset('frontend/elegant/js/swiper-bundle.min.js') }}?v={{ $version }}"></script>
<script src="{{ asset('frontend/elegant/js/shareon.iife.js') }}?v={{ $version }}"></script>
<script type="module" src="{{ asset('frontend/elegant/js/bootstrap-table.min.js') }}?v={{ $version }}"></script>
<script type="module" src="{{ asset('frontend/elegant/js/main.js') }}?v={{ $version }}"></script>
<script type="module" src="{{ asset('frontend/elegant/js/ionicons.js') }}?v={{ $version }}"></script>
<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>

</html>
