@php
    $system_settings = app(\App\Services\SettingService::class)->getSettings('system_settings', true);
    $system_settings = $system_settings ? json_decode($system_settings, true) : null;

    $doctor_brown = app(\App\Services\SettingService::class)->getSettings('doctor_brown', true);
    $doctor_brown = $doctor_brown ? json_decode($doctor_brown, true) : null;
    $hasDoctorBrown = !empty($doctor_brown);

    $scheme = str_replace('://', '', $system_settings['deep_link_scheme'] ?? 'eshop');
    $appName = $system_settings['app_name'] ?? 'eShop';

    // CRITICAL: Use the host configured in system settings to match AndroidManifest.xml
    // If not set, fall back to a default that matches your app's manifest
$host = $system_settings['deep_link_host'] ?? 'eshop-pro.eshopweb.store';

$androidLink =
    $system_settings['play_store_link_for_customer_app'] ??
    ($system_settings['play_store_link_for_customer_app'] ??
        'https://play.google.com/store/apps/details?id=com.eshop');
$iosLink =
    $system_settings['app_store_link_for_customer_app'] ??
    ($system_settings['app_store_link_for_customer_app'] ?? 'https://apps.apple.com/app/eshop');

$androidPackage = $system_settings['android_package_name'] ?? 'com.eshop';
if ($androidPackage == 'com.eshop' && preg_match('/id=([^&]+)/', $androidLink, $matches)) {
    $androidPackage = $matches[1];
}

$preCalculatedDeepLink = $deepLinkUrl ?? '';
@endphp


<div id="openInAppSheet" class="deep-link-bottom-sheet d-none">
    <div class="sheet-overlay"></div>
    <div class="sheet-content p-4">
        <div class="sheet-header d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Open in {{ $appName }} App</h5>
            <button class="btn-close" id="closeSheetBtn" aria-label="Close"></button>
        </div>
        <div class="sheet-body">
            <p class="mb-4">For the best shopping experience, open this page in our mobile app!</p>
            <button class="btn btn-primary fw-bold w-100 py-3 mb-3" id="openAppBtn"
                style="border-radius: 12px; border: 2px solid #eee;">
                OPEN IN APP
            </button>
            <button class="btn btn-link w-100 text-muted text-decoration-none" id="continueWebBtn">
                Continue on Web
            </button>
        </div>
    </div>
</div>

<style>
    .deep-link-bottom-sheet {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 99999;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        pointer-events: none;
    }

    .deep-link-bottom-sheet.show {
        pointer-events: auto;
    }

    .deep-link-bottom-sheet .sheet-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.6);
        opacity: 0;
        transition: opacity 0.3s ease-out;
    }

    .deep-link-bottom-sheet.show .sheet-overlay {
        opacity: 1;
    }

    .deep-link-bottom-sheet .sheet-content {
        position: relative;
        background: #fff;
        border-top-left-radius: 24px;
        border-top-right-radius: 24px;
        transform: translateY(100%);
        transition: transform 0.4s cubic-bezier(0.33, 1, 0.68, 1);
        box-shadow: 0 -10px 25px rgba(0, 0, 0, 0.2);
        padding-bottom: calc(20px + env(safe-area-inset-bottom)) !important;
        width: 100%;
        pointer-events: auto;
    }

    .deep-link-bottom-sheet.show .sheet-content {
        transform: translateY(0);
    }

    .deep-link-bottom-sheet.d-none {
        display: none !important;
    }

    @media (min-width: 1025px) {
        .deep-link-bottom-sheet {
            display: none !important;
        }
    }
</style>

<script>
    (function() {
        const config = {
            scheme: "{{ $scheme }}",
            host: "{{ $host }}",
            androidPackage: "{{ $androidPackage }}",
            playStore: "{{ $androidLink }}",
            appStore: "{{ $iosLink }}",
            preCalculatedDeepLink: "{{ $preCalculatedDeepLink }}",
            appName: "{{ $appName }}"
        };

        const sheet = document.getElementById('openInAppSheet');
        const openBtn = document.getElementById('openAppBtn');
        const closeBtn = document.getElementById('closeSheetBtn');
        const webBtn = document.getElementById('continueWebBtn');

        function getPlatform() {
            const ua = navigator.userAgent.toLowerCase();
            const isTouch = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0) || (window.matchMedia(
                "(pointer: coarse)").matches);
            const isSmallScreen = window.innerWidth <= 1024;

            console.log("DeepLink Debug: UA=" + ua + " Touch=" + isTouch + " Width=" + window.innerWidth);

            if (/android/.test(ua)) return 'android';
            if (/iphone|ipad|ipod/.test(ua)) return 'ios';

            if (isSmallScreen || isTouch) return 'mobile';

            return 'web';
        }

        function buildDeepLink() {
            if (config.preCalculatedDeepLink && config.preCalculatedDeepLink.includes('://')) {
                return config.preCalculatedDeepLink;
            }

            let path = window.location.pathname;
            // Remove leading slashes
            while (path.startsWith('/')) {
                path = path.substring(1);
            }
            const query = window.location.search;

            // Mapping: ensure path matches app expectations
            if (path.startsWith('products/')) path = path.replace('products/', 'product/');
            else if (path.startsWith('combo-products/')) path = path.replace('combo-products/', 'combo-product/');
            else if (path.startsWith('sellers/')) path = path.replace('sellers/', 'seller/');
            else if (path.startsWith('blogs/')) path = path.replace('blogs/', 'blog/');

            // Use host in URL as per user's example: scheme://host/path
            return config.scheme + '://' + config.host + '/' + path + query;
        }

        function getPageSpecificKey() {
            // Create a page-specific key using the current path
            // This ensures the sheet can appear on different pages
            return 'deepLinkDismissed_' + window.location.pathname;
        }

        function showSheet() {
            console.log("DeepLink Debug: Executing showSheet()");
            // Removed sessionStorage check to allow sheet to show on every page load/refresh
            if (!sheet) {
                console.error("DeepLink Debug: Sheet element not found in DOM!");
                return;
            }
            sheet.classList.remove('d-none');
            setTimeout(() => {
                sheet.classList.add('show');
                console.log("DeepLink Debug: 'show' class added.");
            }, 50);
        }

        function hideSheet() {
            if (!sheet) return;
            sheet.classList.remove('show');
            setTimeout(() => sheet.classList.add('d-none'), 400);
            // Removed sessionStorage to allow sheet to show again on page refresh
            console.log("DeepLink Debug: Sheet hidden (will show again on refresh)");
        }

        function openApp() {
            const platform = getPlatform();
            let targetUrl;

            console.log("DeepLink Debug: Platform detected = " + platform);

            if (platform === 'android') {
                // Android requires Intent URL format for reliable deep linking
                // Extract path and query from current URL
                let path = window.location.pathname;
                // Remove leading slashes
                while (path.startsWith('/')) {
                    path = path.substring(1);
                }
                const query = window.location.search;

                // Apply path mapping
                if (path.startsWith('products/')) path = path.replace('products/', 'product/');
                else if (path.startsWith('combo-products/')) path = path.replace('combo-products/',
                    'combo-product/');
                else if (path.startsWith('sellers/')) path = path.replace('sellers/', 'seller/');
                else if (path.startsWith('blogs/')) path = path.replace('blogs/', 'blog/');

                // Build Android Intent URL
                // Format: intent://host/path?query#Intent;scheme=SCHEME;package=PACKAGE;S.browser_fallback_url=FALLBACK;end;
                const intentPath = config.host + '/' + path + query;
                targetUrl = 'intent://' + intentPath + '#Intent;' +
                    'scheme=' + config.scheme + ';' +
                    'package=' + config.androidPackage + ';' +
                    'S.browser_fallback_url=' + encodeURIComponent(config.playStore) + ';' +
                    'end;';

                console.log("DeepLink Debug: Android Intent URL = " + targetUrl);
            } else if (platform === 'ios') {
                // iOS uses direct scheme URL
                const deepLink = buildDeepLink();
                targetUrl = deepLink;
                console.log("DeepLink Debug: iOS Scheme URL = " + targetUrl);
            } else {
                // Fallback for other mobile platforms
                const deepLink = buildDeepLink();
                targetUrl = deepLink;
                console.log("DeepLink Debug: Generic Deep Link = " + targetUrl);
            }

            // Track if app opened successfully
            let hasOpened = false;
            const onVisibilityChange = () => {
                if (document.hidden || document.webkitHidden) {
                    hasOpened = true;
                    console.log("DeepLink Debug: Visibility changed - App opened successfully");
                }
            };
            document.addEventListener("visibilitychange", onVisibilityChange, {
                once: true
            });

            // Attempt to open the app
            window.location.href = targetUrl;

            // Fallback logic for all mobile platforms
            // If the app doesn't open (or the browser doesn't handle the intent), 
            // the page remains visible and after 2 seconds we redirect to the store.
            setTimeout(() => {
                document.removeEventListener("visibilitychange", onVisibilityChange);
                if (!hasOpened) {
                    console.log("DeepLink Debug: App not opened or Intent failed, redirecting to store");
                    window.location.href = platform === 'ios' ? config.appStore : config.playStore;
                }
            }, 2000);
        }

        openBtn?.addEventListener('click', openApp);
        closeBtn?.addEventListener('click', hideSheet);
        webBtn?.addEventListener('click', hideSheet);
        sheet?.querySelector('.sheet-overlay')?.addEventListener('click', hideSheet);

        const platform = getPlatform();
        if (platform !== 'web') {
            console.log("DeepLink Debug: Mobile detected, scheduling showSheet() in 1.5s");
            setTimeout(showSheet, 150);
        } else {
            console.log("DeepLink Debug: Web detected, sheet will remain hidden.");
        }
    })();
</script>
