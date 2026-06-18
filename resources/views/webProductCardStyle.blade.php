<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iframe Content - Showcase Only</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('frontend/elegant/css/plugins.css') }}?v={{ $version }}">
    <link rel="stylesheet" href="{{ asset('frontend/elegant/css/vendor/photoswipe.min.css') }}?v={{ $version }}">
    <link rel="stylesheet" href="{{ asset('frontend/elegant/css/bootstrap-table.min.css') }}?v={{ $version }}">
    <link rel="stylesheet" href="{{ asset('frontend/elegant/css/style.css') }}?v={{ $version }}">
    <link rel="stylesheet" href="{{ asset('frontend/elegant/css/theme.min.css') }}?v={{ $version }}">
    <link rel="stylesheet" href="{{ asset('frontend/elegant/css/star-rating.css') }}?v={{ $version }}">
    <link rel="stylesheet" href="{{ asset('frontend/elegant/css/star-rating.min.css') }}?v={{ $version }}">
    <link rel="stylesheet" href="{{ asset('frontend/elegant/css/intlTelInput.css') }}?v={{ $version }}">
    <link rel="stylesheet" href="{{ asset('frontend/elegant/css/select2.minstraße.css') }}?v={{ $version }}">
    <link rel="stylesheet" href="{{ asset('frontend/elegant/css/iziToast.css') }}?v={{ $version }}">
    <link rel="stylesheet" href="{{ asset('frontend/elegant/css/daterangepicker.css') }}?v={{ $version }}">
    <link rel="stylesheet" href="{{ asset('frontend/elegant/css/responsive.css') }}?v={{ $version }}">
    <link rel="stylesheet" href="{{ asset('frontend/elegant/css/shareon.min.css') }}?v={{ $version }}">
    <link rel="stylesheet" href="{{ asset('frontend/elegant/css/app.css') }}?v={{ $version }}">
    <link rel="stylesheet" href="{{ asset('frontend/elegant/css/swiper-bundle.min.css') }}?v={{ $version }}">
</head>

<body>

    <!-- STYLE 1 -->
    <div id="products_display_style_for_web_1" class="content-section">
        <div class="swiper style1-mySwiper gp15 arwOut5 hov-arrow grid-products pro-hover3">
            <div class="align-items-center d-flex justify-content-center swiper-wrapper">

                <div class="swiper-slide" style="width: 278.6px; margin-right: 30px;">
                    <div class="item col-item">
                        <div class="product-box">
                            <div class="product-image m-0">
                                <div class="all-product-img product-img rounded-3">
                                    <img class="blur-up lazyloaded"
                                        src="{{ asset('storage/system_images/product_card_style.jpeg') }}"
                                        alt="Product" title="ChocoLuxe Muffins" width="625" height="808">
                                </div>
                                <div class="button-set style1">
                                    <div class="btn-icon">
                                        <span class="icon-wrap d-flex-justify-center h-100 w-100">
                                            <ion-icon name="search-outline" class="fs-6 md hydrated"></ion-icon>
                                            <span class="text">Quick View</span>
                                        </span>
                                    </div>
                                    <div class="btn-icon">
                                        <ion-icon name="heart-outline" class="fs-6 md hydrated"></ion-icon>
                                        <span class="text">Add To Wishlist</span>
                                    </div>
                                    <div class="btn-icon">
                                        <i class="icon anm anm-random-r"></i>
                                        <span class="text">Add to Compare</span>
                                    </div>
                                </div>
                            </div>
                            <div class="product-details">
                                <div class="product-vendor text-uppercase text-muted small"></div>
                                <div class="product-name text-capitalize">
                                    <div class="text-ellipsis" title="ChocoLuxe Muffins">ChocoLuxe Muffins</div>
                                </div>
                                <div class="product-price">
                                    <span class="price old-price">$50.00</span>
                                    <span class="price fw-500">$40.00</span>
                                </div>
                                <div>
                                    <div class="text-ellipsis text-secondary small">
                                        <ion-icon name="layers-outline" class="custom-icon fs-6 me-1 md hydrated"></ion-icon>
                                        Chocolate muffins
                                    </div>
                                </div>
                                <div class="product-review">
                                    <div class="rating-container theme-krajee-svg rating-xs rating-animate rating-disabled">
                                        <div class="rating-stars">
                                            <span class="empty-stars">
                                                <span class="star"><span class="krajee-icon krajee-icon-star"></span></span>
                                                <span class="star"><span class="krajee-icon krajee-icon-star"></span></span>
                                                <span class="star"><span class="krajee-icon krajee-icon-star"></span></span>
                                                <span class="star"><span class="krajee-icon krajee-icon-star"></span></span>
                                                <span class="star"><span class="krajee-icon krajee-icon-star"></span></span>
                                            </span>
                                            <span class="filled-stars" style="width: 60%;">
                                                <span class="star"><span class="krajee-icon krajee-icon-star"></span></span>
                                                <span class="star"><span class="krajee-icon krajee-icon-star"></span></span>
                                                <span class="star"><span class="krajee-icon krajee-icon-star"></span></span>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="button-action mt-2">
                                    <div class="btn btn-md p-2 button-style">
                                        <span class="button-icon button-icon-left"><ion-icon name="bag-handle-outline"></ion-icon></span>
                                        <span class="text button-text">Add to Cart</span>
                                        <span class="button-icon button-icon-right"><ion-icon name="bag-handle-outline"></ion-icon></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- STYLE 2 -->
    <div id="products_display_style_for_web_2" class="content-section d-none">
        <div class="swiper style1-mySwiper gp15 arwOut5 hov-arrow grid-products pro-hover3">
            <div class="swiper-wrapper d-flex justify-content-center">
                <div class="swiper-slide" style="width: 278.6px; margin-right: 30px;">
                    <div class="item col-item">
                        <div class="product-box">
                            <div class="product-image m-0">
                                <div class="img-box-h300px product_card_style_two_image product-img">
                                    <img class="primary blur-up lazyloaded"
                                        src="{{ asset('storage/system_images/product_card_style.jpeg') }}"
                                        alt="Product" title="ChocoLuxe Muffins">
                                    <img class="hover blur-up lazyloaded product_card_style_two_image"
                                        src="{{ asset('storage/system_images/product_card_style.jpeg') }}"
                                        alt="Product" title="ChocoLuxe Muffins">
                                </div>
                                <div class="button-set style1">
                                    <div class="btn-icon"><ion-icon name="search-outline" class="fs-6 md hydrated"></ion-icon><span class="text">Quick View</span></div>
                                    <div class="btn-icon"><ion-icon name="heart-outline" class="fs-6 md hydrated"></ion-icon><span class="text">Add To Wishlist</span></div>
                                    <div class="btn-icon"><i class="icon anm anm-random-r"></i><span class="text">Add to Compare</span></div>
                                </div>
                            </div>
                            <div class="product-details text-left">
                                <div class="product-vendor text-uppercase text-muted small"></div>
                                <div class="product-name text-capitalize">
                                    <div class="text-ellipsis" title="ChocoLuxe Muffins">ChocoLuxe Muffins</div>
                                </div>
                                <div class="product-price">
                                    <span class="price old-price">$50.00</span>
                                    <span class="price fw-500">$40.00</span>
                                </div>
                                <div class="product-review">
                                    <div class="rating-container theme-krajee-svg rating-xs rating-animate rating-disabled">
                                        <div class="rating-stars">
                                            <span class="empty-stars">
                                                <span class="star"><span class="krajee-icon krajee-icon-star"></span></span>
                                                <span class="star"><span class="krajee-icon krajee-icon-star"></span></span>
                                                <span class="star"><span class="krajee-icon krajee-icon-star"></span></span>
                                                <span class="star"><span class="krajee-icon krajee-icon-star"></span></span>
                                                <span class="star"><span class="krajee-icon krajee-icon-star"></span></span>
                                            </span>
                                            <span class="filled-stars" style="width: 60%;">
                                                <span class="star"><span class="krajee-icon krajee-icon-star"></span></span>
                                                <span class="star"><span class="krajee-icon krajee-icon-star"></span></span>
                                                <span class="star"><span class="krajee-icon krajee-icon-star"></span></span>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="button-action pt-2">
                                    <div class="btn btn-md p-2 button-style">
                                        <span class="button-icon button-icon-left"><ion-icon name="bag-handle-outline"></ion-icon></span>
                                        <span class="text button-text">Add to Cart</span>
                                        <span class="button-icon button-icon-right"><ion-icon name="bag-handle-outline"></ion-icon></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- STYLE 3 -->
    <div id="products_display_style_for_web_3" class="content-section d-none">
        <div class="swiper style1-mySwiper gp15 arwOut5 hov-arrow grid-products pro-hover3">
            <div class="swiper-wrapper d-flex justify-content-center">
                <div class="swiper-slide" style="width: 278.6px; margin-right: 30px;">
                    <div class="item col-item">
                        <div class="product-box">
                            <div class="product-image">
                                <div class="all-product-img product-img rounded-3">
                                    <img class="blur-up lazyloaded"
                                        src="{{ asset('storage/system_images/product_card_style.jpeg') }}"
                                        alt="Product" title="ChocoLuxe Muffins">
                                </div>
                                <div class="product-labels round-pill">
                                    <span class="lbl pr-label2">20%</span>
                                </div>
                            </div>
                            <div class="product-details text-left">
                                <div class="product-name-price">
                                    <div class="product-name text-capitalize">
                                        <div class="text-ellipsis" title="ChocoLuxe Muffins">ChocoLuxe Muffins</div>
                                    </div>
                                </div>
                                <div class="product-price">
                                    <span class="price old-price">$50.00</span>
                                    <span class="price fw-500">$40.00</span>
                                </div>
                                <div class="product-review">
                                    <div class="rating-container theme-krajee-svg rating-xs rating-animate rating-disabled">
                                        <div class="rating-stars">
                                            <span class="empty-stars">
                                                <span class="star"><span class="krajee-icon krajee-icon-star"></span></span>
                                                <span class="star"><span class="krajee-icon krajee-icon-star"></span></span>
                                                <span class="star"><span class="krajee-icon krajee-icon-star"></span></span>
                                                <span class="star"><span class="krajee-icon krajee-icon-star"></span></span>
                                                <span class="star"><span class="krajee-icon krajee-icon-star"></span></span>
                                            </span>
                                            <span class="filled-stars" style="width: 0%;"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="button-bottom-action style1">
                                    <div class="button-left">
                                        <div class="btn btn-md p-2 button-style">
                                            <ion-icon name="bag-handle-outline" class="mx-1 md hydrated"></ion-icon>
                                            <span class="text">Add to Cart</span>
                                        </div>
                                    </div>
                                    <div class="button-right">
                                        <div class="btn-icon"><ion-icon name="search-outline" class="fs-6 md hydrated"></ion-icon></div>
                                        <div class="btn-icon"><ion-icon name="heart-outline" class="fs-6 md hydrated"></ion-icon></div>
                                        <div class="btn-icon"><i class="icon anm anm-random-r"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- STYLE 4 -->
    <div id="products_display_style_for_web_4" class="content-section d-none">
        <div class="swiper style1-mySwiper gp15 arwOut5 hov-arrow grid-products">
            <div class="swiper-wrapper d-flex justify-content-center">
                <div class="swiper-slide" style="width: 278.6px; margin-right: 30px;">
                    <div class="item col-item">
                        <div class="product-box border bg-white rounded-5">
                            <div class="product-image m-0">
                                <div class="all-product-img product-img rounded-3">
                                    <img class="blur-up lazyloaded"
                                        src="{{ asset('storage/system_images/product_card_style.jpeg') }}"
                                        alt="Product" title="ChocoLuxe Muffins">
                                </div>
                                <div class="product-labels radius"><span class="lbl on-sale">Sale</span></div>
                                <div class="button-set-top style11">
                                    <div class="btn-icon rounded-5"><i class="icon anm anm-random-r"></i><span class="text">Add to Compare</span></div>
                                </div>
                            </div>
                            <div class="product-details text-center">
                                <div class="product-vendor text-muted small"></div>
                                <div class="product-name text-capitalize mx-2">
                                    <div class="text-ellipsis" title="ChocoLuxe Muffins">ChocoLuxe Muffins</div>
                                </div>
                                <div class="product-price">
                                    <span class="price old-price">$50.00</span>
                                    <span class="price fw-500">$40.00</span>
                                </div>
                                <div class="product-review">
                                    <div class="rating-container theme-krajee-svg rating-xs rating-animate rating-disabled">
                                        <div class="rating-stars">
                                            <span class="empty-stars">
                                                <span class="star"><span class="krajee-icon krajee-icon-star"></span></span>
                                                <span class="star"><span class="krajee-icon krajee-icon-star"></span></span>
                                                <span class="star"><span class="krajee-icon krajee-icon-star"></span></span>
                                                <span class="star"><span class="krajee-icon krajee-icon-star"></span></span>
                                                <span class="star"><span class="krajee-icon krajee-icon-star"></span></span>
                                            </span>
                                            <span class="filled-stars" style="width: 0%;"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="button-bottom-action style11">
                                    <div class="btn btn-icon rounded-5"><ion-icon name="heart-outline" class="fs-6 md hydrated"></ion-icon></div>
                                    <div class="btn btn-primary btn-md rounded-5">
                                        <ion-icon name="bag-handle-outline" class="fs-6 md hydrated"></ion-icon>
                                        <span class="text mx-2">Add to Cart</span>
                                    </div>
                                    <div class="btn btn-icon rounded-5">
                                        <ion-icon name="search-outline" class="fs-6 md hydrated"></ion-icon>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- STYLE 5 -->
    <div id="products_display_style_for_web_5" class="content-section d-none">
        <div class="swiper style1-mySwiper gp15 arwOut5 hov-arrow grid-products pro-hover3">
            <div class="swiper-wrapper d-flex justify-content-center">
                <div class="swiper-slide" style="width: 278.6px; margin-right: 30px;">
                    <div class="item col-item">
                        <div class="product-box">
                            <div class="product-image">
                                <div class="all-product-img product-img rounded-3">
                                    <img class="blur-up lazyloaded"
                                        src="{{ asset('storage/system_images/product_card_style.jpeg') }}"
                                        alt="Product" title="ChocoLuxe Muffins">
                                    <img class="hover blur-up lazyloaded"
                                        src="{{ asset('storage/system_images/product_card_style.jpeg') }}"
                                        alt="Product" title="ChocoLuxe Muffins">
                                </div>
                                <div class="product-labels radius"><span class="lbl on-sale">Sale</span></div>
                                <div class="button-set style2">
                                    <div class="btn-icon">
                                        <ion-icon name="bag-handle-outline" class="fs-6 md hydrated"></ion-icon>
                                        <span class="text mx-2">Add to Cart</span>
                                    </div>
                                    <div class="btn-icon"><ion-icon name="search-outline" class="fs-6 md hydrated"></ion-icon><span class="text">Quick View</span></div>
                                    <div class="btn-icon"><ion-icon name="heart-outline" class="fs-6 md hydrated"></ion-icon><span class="text">Add to Wishlist</span></div>
                                    <div class="btn-icon"><i class="icon anm anm-random-r"></i><span class="text">Add to Compare</span></div>
                                </div>
                            </div>
                            <div class="product-details text-left mt-3">
                                <div class="product-vendor text-muted small"></div>
                                <div class="product-name text-capitalize">
                                    <div class="text-ellipsis" title="ChocoLuxe Muffins">ChocoLuxe Muffins</div>
                                </div>
                                <div class="product-price">
                                    <span class="price old-price">$50.00</span>
                                    <span class="price fw-500">$40.00</span>
                                </div>
                                <div class="product-review">
                                    <div class="rating-container theme-krajee-svg rating-xs rating-animate rating-disabled">
                                        <div class="rating-stars">
                                            <span class="empty-stars">
                                                <span class="star"><span class="krajee-icon krajee-icon-star"></span></span>
                                                <span class="star"><span class="krajee-icon krajee-icon-star"></span></span>
                                                <span class="star"><span class="krajee-icon krajee-icon-star"></span></span>
                                                <span class="star"><span class="krajee-icon krajee-icon-star"></span></span>
                                                <span class="star"><span class="krajee-icon krajee-icon-star"></span></span>
                                            </span>
                                            <span class="filled-stars" style="width: 0%;"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Style Switcher Script (Safe - Only Toggles Visibility) -->
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

<!-- Scripts (Kept for styling, Swiper, icons, etc. - No interactivity) -->
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
