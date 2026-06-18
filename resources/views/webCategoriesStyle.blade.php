<!DOCTYPE html>
<html lang="en" class="overflow-hidden">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Styles - Showcase Only</title>
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

    <!-- CATEGORY STYLE 1 -->
    <div id="categories_display_style_for_web_1" class="content-section">
        <div class="collection-masonary grid-mr-20">
            <div class="grid-masonary">
                <div class="grid-sizer col-12 col-sm-6 col-md-6 col-lg-4"></div>
                <div class="collection-style4 row m-0 d-flex justify-content-center">
                    <div class="category-item col-12 col-sm-6 col-md-6 col-lg-4 col-item zoomscal-hov masonary-item">
                        <div class="category-link clr-none">
                            <div class="overlay-image"></div>

                            <div class="zoom-scal zoom-scal-nopb rounded-0 category-image">
                                <img class="rounded-0 blur-up w-100 lazyloaded"
                                    src="{{ asset('storage/system_images/category_card_style.png') }}"
                                    alt="Beverages Corner" title="Beverages Corner">
                            </div>

                            <div class="details">
                                <h3 class="category-title mb-0 text-white fs-4">Beverages Corner</h3>
                                <span class="btn btn-secondary btn-sm">Shop Now</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CATEGORY STYLE 2 -->
    <div id="categories_display_style_for_web_2" class="content-section d-none">
        <div class="lookbook-grid">
            <div class="row col-row row-cols-lg-5 row-cols-md-4 row-cols-sm-3 row-cols-2 d-flex justify-content-center">
                <div class="lookbook-item zoomscal-hov col-item">
                    <div class="lookbook-inner rounded-0 category_list_card_2">
                        <div class="zoom rounded-0 d-block zoom-scal zoom-scal-nopb"></div>

                        <img class="rounded-0 blur-up lazyloaded category_list_card_2_image"
                            src="{{ asset('storage/system_images/category_card_style.png') }}"
                            alt="Fruits" title="Fruits">

                        <div class="lookbook-caption d-flex-justify-center mainclr">
                            <div class="content clr-none d-block">
                                <h5 class="text-1 mb-0">Fruits</h5>
                                <p class="text-2 mt-1 d-none d-md-block">13 Products</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CATEGORY STYLE 3 -->
    <div id="categories_display_style_for_web_3" class="content-section d-none">
        <div class="row col-row masonary-filter portfolio-list d-flex justify-content-center">
            <div class="col-6 col-sm-6 col-md-4 col-lg-3 col-item pfashion portfolio-masonary">
                <div class="portfolio-item position-relative overflow-hidden overlay d-block portfolio-popup zoomscal-hov">
                    <div class="portfolio-img zoom-scal rounded-0 category_list_card_3">
                        <img class="rounded-0 blur-up lazyloaded category_list_card_3_image"
                            src="{{ asset('storage/system_images/category_card_style.png') }}"
                            alt="Fruits" title="Fruits">
                    </div>
                    <div class="caption rounded-0">
                        <h3 class="text-white mb-2">Fruits</h3>
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

<!-- Scripts (Kept for styling & visual effects only) -->
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
