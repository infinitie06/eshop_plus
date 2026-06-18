<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    @php
        use App\Services\SettingService;
        use App\Services\SeoService;
        $pwa_settings = app(SettingService::class)->getSettings('pwa_settings', true);
        $pwa_settings = $pwa_settings ? json_decode($pwa_settings, true) : null;
        $background_color =
            $pwa_settings && isset($pwa_settings['background_color']) ? $pwa_settings['background_color'] : '#b52046';

        // Get SEO data
        $seoService = app(SeoService::class);
        $seoData = $seoService->getSeoData('global', null);
        if (!$seoData) {
            $seoData = (object) $seoService->getDefaultSeoData();
        }
    @endphp


    <head>
        <meta name="theme-color" content="{{ $background_color }}" />
        <link rel="apple-touch-icon" href="{{ asset('storage/' . $web_settings['logo']) }}">
        <link rel="manifest" href="{{ route('manifest') }}">
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @if (!file_exists($sqlDumpPath) && !file_exists($installViewPath))
            <meta name="keywords" content='{{ $metaKeys ?? $system_settings['app_name'] }}'>
            <meta name="description" content='{{ $metaDescription ?? $system_settings['app_name'] }}'>
            <meta name="product_image" property="og:image"
                content='{{ $metaImage ?? asset('storage/' . $web_settings['logo']) }}'>
            <link rel="shortcut icon" href="{{ asset('storage/' . $web_settings['favicon']) }}" type="image/x-icon">
            <title>
                {{ $title ?? '' }} {{ $system_settings['app_name'] }}
            </title>
            {!! $structuredData ?? '' !!}
        @endif
        @php
            $url = Request::is('cart/checkout');
        @endphp
        <meta property="og:image:type" content="image/jpg,png,jpeg,gif,bmp,eps">
        <meta property="og:image:width" content="1024">
        <meta property="og:image:height" content="1024">

        <link rel="stylesheet" href="{{ asset('frontend/elegant/css/plugins.css') }}?v={{ $version }}">
        <link rel="stylesheet"
            href="{{ asset('frontend/elegant/css/vendor/photoswipe.min.css') }}?v={{ $version }}">
        <link rel="stylesheet"
            href="{{ asset('frontend/elegant/css/bootstrap-table.min.css') }}?v={{ $version }}">
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
        <link rel="stylesheet" href="{{ asset('assets/admin/css/dropzone.css') }}?v={{ $version }}">
        <link rel="stylesheet" href="{{ asset('frontend/elegant/css/swiper-bundle.min.css') }}?v={{ $version }}">


        <script src="{{ asset('frontend/elegant/js/plugins.js') }}?v={{ $version }}"></script>
        <script src="{{ asset('frontend/elegant/js/moment.min.js') }}?v={{ $version }}"></script>
        <script src="{{ asset('frontend/elegant/js/sweetalert2.all.min.js') }}?v={{ $version }}"></script>
        <script src="{{ asset('frontend/elegant/js/swiper-bundle.min.js') }}?v={{ $version }}"></script>
        <script src="{{ asset('frontend/elegant/js/shareon.iife.js') }}?v={{ $version }}"></script>

        <script type="module" src="{{ asset('frontend/elegant/js/firebase-app.js') }}?v={{ $version }}"></script>
        <script type="module" src="{{ asset('frontend/elegant/js/firebase-auth.js') }}?v={{ $version }}"></script>
        <script type="module" src="{{ asset('frontend/elegant/js/firebase-firestore.js') }}?v={{ $version }}"></script>
        <script type="module" src="{{ asset('frontend/elegant/js/bootstrap-table.min.js') }}?v={{ $version }}"></script>
        <script type="module" src="{{ asset('frontend/elegant/js/bootstrap-table-export.min.js') }}?v={{ $version }}">
        </script>
        <script type="module" src="{{ asset('frontend/elegant/js/main.js') }}?v={{ $version }}"></script>
        <script type="module" src="{{ asset('frontend/elegant/js/daterangepicker.js') }}?v={{ $version }}"></script>
        <script type="module" src="{{ asset('frontend/elegant/js/ionicons.js') }}?v={{ $version }}"></script>
        <script type="module" src="{{ asset('frontend/elegant/js/star-rating.js') }}?v={{ $version }}"></script>
        <script type="module" src="{{ asset('frontend/elegant/js/intlTelInput.js') }}?v={{ $version }}"></script>
        <script src="{{ asset('frontend/elegant/js/intl-tel-login.js') }}?v={{ $version }}"></script>
        <script type="module" src="{{ asset('frontend/elegant/js/iziToast.min.js') }}?v={{ $version }}"></script>
        <script type="module" src="{{ asset('frontend/elegant/js/star-rating.min.js') }}?v={{ $version }}"></script>
        <script type="module" src="{{ asset('frontend/elegant/js/select2.min.js') }}?v={{ $version }}"></script>
        <script src="{{ asset('assets/admin/js/dropzone.js') }}"></script>
        <script type="module" src="{{ asset('frontend/elegant/js/checkout.js') }}?v={{ $version }}"
            @if (Request::is('cart/checkout')) data-navigate-track="reload" @endif></script>
        <script type="module" src="{{ asset('frontend/elegant/js/wallet.js') }}?v={{ $version }}" data-navigate-once ></script>
        {{-- <script src="{{ asset('frontend/elegant/js/plugins.js') }}?v={{ $version }}"></script> --}}
        <script type="module" src="{{ asset('frontend/elegant/js/custom.js') }}?v={{ $version }}"></script>
        <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
        <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
        @if (session('is_rtl') == 1)
            <style>
                [dir="rtl"] .me-1 { margin-right: 0 !important; margin-left: 0.25rem !important; }
                [dir="rtl"] .me-2 { margin-right: 0 !important; margin-left: 0.5rem !important; }
                [dir="rtl"] .me-3 { margin-right: 0 !important; margin-left: 1rem !important; }
                [dir="rtl"] .ms-1 { margin-left: 0 !important; margin-right: 0.25rem !important; }
                [dir="rtl"] .ms-2 { margin-left: 0 !important; margin-right: 0.5rem !important; }
                [dir="rtl"] .ms-3 { margin-left: 0 !important; margin-right: 1rem !important; }
                [dir="rtl"] .button-style i + .button-text,
                [dir="rtl"] .button-style i + .text { margin-right: 0.5rem; }
                [dir="rtl"] .text-left { text-align: right !important; }
                [dir="rtl"] .text-right { text-align: left !important; }
                [dir="rtl"] .float-start { float: right !important; }
                [dir="rtl"] .float-end { float: left !important; }
                [dir="rtl"] .header-vertical-menu .menu-title { padding-right: 15px; padding-left: 40px; }
                [dir="rtl"] .header-vertical-menu .menu-title:after { right: auto; left: 15px; }
                [dir="rtl"] .header-vertical-menu .menu-title .icon { margin-right: 0; margin-left: 10px; }
                [dir="rtl"] .header-vertical-menu .menu-title ion-icon { margin-left: 8px; }
                [dir="rtl"] .moreCategories ion-icon { transform: scaleX(-1); }
                [dir="rtl"] .customer-links li .icon { margin-right: 0 !important; margin-left: 8px !important; }
                [dir="rtl"] .customer-links li a { display: inline-flex; align-items: center; }
                [dir="rtl"] .list-inline-item:not(:last-child) { margin-right: 0 !important; margin-left: 0.5rem !important; }
            </style>
        @endif
    </head>
    @php
        $is_rtl = session('is_rtl') ?? 0;
        use App\Models\Currency;
    @endphp

    <body {{ $is_rtl == 1 ? 'dir=rtl' : '' }}>

        {{-- The full-screen .loading-state .screen overlay (z-index:9999) was
             removed: it was visible-by-default in HTML, only hidden by JS on
             livewire:navigated, and re-introduced visible by wire:navigate's
             morph on every page change. That race ate the first click after
             each navigation and tangled with browser back/forward. The element
             served no functional purpose (no code ever explicitly showed it
             again) so removing it is the cleanest fix. The JS handler at
             livewire:navigated that adds `d-none` to it remains in place as a
             harmless no-op. --}}
        <input type="hidden" id="user_id" name="user_id" value="{{ auth()->id() ?? '' }}">
        <input type="hidden" id="custom_url" name="custom_url" value="{{ url()->full() }}">
        <input type="hidden" id="current_url" name="current_url" value="{{ url()->current() }}">
        <input type="hidden" id="store_slug" name="store_slug" value="{{ session('store_slug') }}">
        <input type="hidden" id="current_store_id" name="current_store_id" value="{{ session('store_id') }}">
        <input type="hidden" id="default_store_slug" name="default_store_slug"
            value="{{ session('default_store_slug') }}">
        @if (!file_exists($sqlDumpPath) && !file_exists($installViewPath))
            @php
                $currency_code = session('currency') ?? $system_settings['currency_setting']['code'];
                $currency_details = fetchDetails(Currency::class, ['code' => $currency_code]);
                $currency_symbol = $currency_details[0]->symbol ?? $system_settings['currency_setting']['symbol'];
            @endphp
            <input type="hidden" id="currency" name="currency" value="{{ $currency_symbol }}">
            <input type="hidden" id="max_items_allowed_in_cart" value="{{ $system_settings['maximum_item_allowed_in_cart'] ?? 10 }}">

            <livewire:header.header />
        @endif
        {{ $slot }}
        @if (!file_exists($sqlDumpPath) && !file_exists($installViewPath))
            <livewire:footer.footer />
        @endif
        <x-include-modal.modals />
        <link rel="stylesheet" href="{{ asset('frontend/elegant/css/lightbox.css') }}">
        <script src="{{ asset('/sw.js') }}"></script>
        <script>
            if ("serviceWorker" in navigator) {
                // Register a service worker hosted at the root of the
                // site using the default scope.
                navigator.serviceWorker.register("/sw.js").then(
                    (registration) => {
                        console.log("Service worker registration succeeded:", registration);
                    },
                    (error) => {
                        console.error(`Service worker registration failed: ${error}`);
                    },
                );
            } else {
                console.error("Service workers are not supported.");
            }
        </script>
    </body>
    {{-- <script src="{{ asset('frontend/elegant/js/checkout.js') }}?v={{ $version }}"></script>
 <script src="{{ asset('frontend/elegant/js/wallet.js') }}?v={{ $version }}"></script>
 <script src="{{ asset('frontend/elegant/js/custom.js') }}?v={{ $version }}"></script> --}}
    <script>
        function home_slider() {
            if (typeof $ === 'undefined') return;
            var $el = $(".home-slideshow");
            if (!$el.length) return;
            // wire:navigate brings us back to a page where the slider DOM is
            // fresh — but a stale slick instance from a previous mount may
            // still be cached on the element. Tear it down before re-init.
            if ($el.hasClass('slick-initialized')) {
                try { $el.slick('unslick'); } catch (e) {}
            }
            const isRTL = {{ session('is_rtl', 0) }} === 1;
            $el.slick({
                dots: true,
                infinite: true,
                slidesToShow: 1,
                slidesToScroll: 1,
                fade: false,
                arrows: false,
                autoplay: true,
                autoplaySpeed: 7000,
                lazyLoad: "ondemand",
                rtl: isRTL
            });
        }

        // Initial hard load: defer to next tick so jQuery + slick (loaded with
        // `defer`) have a chance to attach.
        document.addEventListener('DOMContentLoaded', home_slider);
        // wire:navigate re-renders the slider HTML but does not re-run this
        // inline script, so slick must be re-bound here.
        document.addEventListener('livewire:navigated', home_slider);
    </script>
    <script src="{{ asset('frontend/elegant/js/vendor/jquery.elevatezoom.js') }}"></script>
    <script>
        if (!window._livewireInitBound) {
            window._livewireInitBound = true;

            // ─── wire:navigate duplicate-click guard ─────────────────────
            // Protects against the same anchor's click being processed more
            // than once for a single user click — for example when stacked
            // jQuery click handlers (re-bound on every livewire:navigated
            // without a matching .off) all fire and end up dispatching the
            // navigation twice. A duplicate dispatch pushes a second history
            // entry for the same URL, so the browser's back button has to
            // pop both before the previous page returns — exactly the
            // "back needs 2-3 clicks" symptom.
            //
            // Strategy: capture-phase listener on document, runs BEFORE
            // Livewire's own delegate. Records the href of each click on a
            // wire:navigate link and short-circuits any second click on the
            // same href that arrives within 700ms.
            (function () {
                var lastHref = null;
                var lastAt = 0;
                document.addEventListener('click', function (e) {
                    // Only inspect actual button-0 (left) clicks without
                    // modifier keys — leave middle-click / cmd-click alone.
                    if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
                    var link = e.target && e.target.closest && e.target.closest('a[wire\\:navigate], a[wire\\:navigate\\.hover]');
                    if (!link || !link.href) return;
                    var now = Date.now();
                    if (link.href === lastHref && (now - lastAt) < 700) {
                        // Second dispatch for the same URL within the window —
                        // swallow it. preventDefault stops native nav,
                        // stopImmediatePropagation prevents downstream
                        // listeners (incl. Livewire's) from acting on it.
                        e.preventDefault();
                        e.stopImmediatePropagation();
                        e.stopPropagation();
                        return;
                    }
                    lastHref = link.href;
                    lastAt = now;
                }, true);
            })();
            // ────────────────────────────────────────────────────────────

            // Disable custom.js's capture-phase offcanvas-toggle handler.
            // Bootstrap 5.3 binds its data-api delegate (`click.bs.offcanvas.data-api`
            // for `[data-bs-toggle="offcanvas"]`) on document in CAPTURE phase
            // — not bubble, despite custom.js's comment saying otherwise. Since
            // plugins.js (which loads bootstrap) is a sync <script> and
            // custom.js is <script type="module"> (deferred), Bootstrap's
            // delegate registers FIRST. Capture-phase listeners fire in
            // registration order, so on every cart/search click:
            //   1. Bootstrap fires first, calls show(), adds `.showing`.
            //   2. custom.js fires next, sees `.showing`, reads it as "open",
            //      calls hide(). Drawer flickers and stays closed.
            // The freshen on livewire:navigating below already keeps drawers
            // clean across navigation, so custom.js's click-time freshen is
            // unnecessary. Skip its registration by pre-setting the flag —
            // this inline <script> is sync and runs before the deferred
            // custom.js module, so its `if (!window._offcanvasOpenBound)`
            // evaluates false and the conflicting listener never binds.
            window._offcanvasOpenBound = true;
            // Bootstrap's show()/hide() queue transition-end callbacks via
            // addEventListener('transitionend') + a ~305ms setTimeout fallback.
            // Their bodies re-add .show / re-set visibility / re-activate focus
            // trap EVEN AFTER dispose() — disposal does not cancel the queued
            // listener or the setTimeout. wire:ignore.self preserves the
            // #minicart-drawer / #search-drawer elements across Livewire
            // navigation, so those zombie callbacks fire on the post-morph DOM
            // and re-flip state on the next open: backdrop flashes (flicker)
            // then the zombie immediately re-hides — drawer never appears.
            //
            // Fix: replace the offcanvas/modal element with a fresh DOM node
            // (same tag + same attributes, children TRANSFERRED so wire:click
            // / Alpine state on them survives). Any zombie callbacks then
            // point at the detached old node and become no-ops.
            window._freshenContainer = function (el) {
                if (!el || !el.parentNode) return el;
                // If this offcanvas/modal is itself a Livewire component root
                // (has wire:id), replacing the node detaches Livewire's
                // reference to the component — wire:model.live on its
                // descendants then stops firing updates after wire:navigate
                // (search input, etc. silently breaks until a hard refresh).
                // Clean its state in place instead.
                if (el.hasAttribute('wire:id')) {
                    el.classList.remove('show', 'showing', 'hiding');
                    el.removeAttribute('aria-modal');
                    el.removeAttribute('role');
                    el.removeAttribute('aria-hidden');
                    el.style.visibility = '';
                    el.style.display = '';
                    return el;
                }
                var fresh = document.createElement(el.tagName);
                Array.from(el.attributes).forEach(function (attr) {
                    if (attr.name === 'class') return;
                    fresh.setAttribute(attr.name, attr.value);
                });
                fresh.className = (el.className || '')
                    .split(/\s+/)
                    .filter(function (c) {
                        return c && c !== 'show' && c !== 'showing' && c !== 'hiding';
                    })
                    .join(' ');
                fresh.removeAttribute('aria-modal');
                fresh.removeAttribute('role');
                fresh.style.visibility = '';
                fresh.style.display = '';
                while (el.firstChild) fresh.appendChild(el.firstChild);
                el.parentNode.replaceChild(fresh, el);
                return fresh;
            };
            window._resetAllOffcanvases = function () {
                document.querySelectorAll('.offcanvas, .modal').forEach(function (el) {
                    if (typeof bootstrap !== 'undefined') {
                        if (bootstrap.Offcanvas) {
                            var oc = bootstrap.Offcanvas.getInstance(el);
                            if (oc) { try { oc.dispose(); } catch (e) {} }
                        }
                        if (bootstrap.Modal) {
                            var mo = bootstrap.Modal.getInstance(el);
                            if (mo) { try { mo.dispose(); } catch (e) {} }
                        }
                    }
                    window._freshenContainer(el);
                });
                document.querySelectorAll('.offcanvas-backdrop').forEach(function (el) { el.remove(); });
                document.querySelectorAll('.modal-backdrop').forEach(function (el) { el.remove(); });
                document.body.classList.remove('offcanvas-open', 'modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            };
            // livewire:navigated also fires on the INITIAL page load in
            // Livewire 3, not only after SPA navigation. Without this guard,
            // the 400ms safety-net setTimeout below would queue on every hard
            // refresh — and if the user clicks the cart/search button within
            // that window, _resetAllOffcanvases disposes the drawer mid-open
            // (flicker → close). Only run the post-navigated cleanup once a
            // real wire:navigate has happened.
            var _hasNavigated = false;
            // Never tear down a drawer that's currently opening or open —
            // that is the exact flicker. The freshen on livewire:navigating
            // already wiped pre-morph zombies; this safety-net only needs to
            // run when nothing is on screen.
            function _safeResetAllOffcanvases() {
                if (document.querySelector('.offcanvas.show, .offcanvas.showing, .modal.show, .modal.showing')) {
                    return;
                }
                window._resetAllOffcanvases();
            }
            document.addEventListener('livewire:navigating', function () {
                _hasNavigated = true;
                window.scrollTo(0, 0);
                window._resetAllOffcanvases();
            });
            document.addEventListener('livewire:navigated', function () {
                if (_hasNavigated) {
                    // Run after morph so any stale state introduced during
                    // morph is wiped, then once more past Bootstrap's 305ms
                    // transition timeout to catch zombies that escape the
                    // freshen. Skip on the initial-load fire of this event.
                    _safeResetAllOffcanvases();
                    setTimeout(_safeResetAllOffcanvases, 400);
                }
                var ls = document.querySelector('.loading-state');
                if (ls) ls.classList.add('d-none');
            });
        }
    </script>

    
</html>
