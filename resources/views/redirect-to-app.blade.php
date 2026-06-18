<!DOCTYPE html>
<html>

    <head>
        <title>Opening App...</title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                text-align: center;
                padding-top: 50px;
                color: #333;
            }

            .loader {
                border: 4px solid #f3f3f3;
                border-top: 4px solid #3498db;
                border-radius: 50%;
                width: 40px;
                height: 40px;
                animation: spin 2s linear infinite;
                margin: 20px auto;
            }

            @keyframes spin {
                0% {
                    transform: rotate(0deg);
                }

                100% {
                    transform: rotate(360deg);
                }
            }

            a {
                color: #3498db;
                text-decoration: none;
                font-weight: bold;
            }
        </style>
        <script>
            (function() {
                const config = {
                    scheme: "{{ $scheme }}",
                    host: "{{ $host }}",
                    androidPackage: "{{ $androidPackage }}",
                    playStore: "{{ $playStore }}",
                    appStore: "{{ $appStore }}",
                    appName: "{{ $appName }}",
                    // Use the values passed from controller
                    type: "{{ $type }}",
                    slug: "{{ $slug }}",
                    queryString: "{{ $queryString }}"
                };

                function getPlatform() {
                    const ua = navigator.userAgent.toLowerCase();
                    const isTouch = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0) || (window.matchMedia(
                        "(pointer: coarse)").matches);
                    const isSmallScreen = window.innerWidth <= 1024;

                    if (/android/.test(ua)) return 'android';
                    if (/iphone|ipad|ipod/.test(ua)) return 'ios';
                    if (isSmallScreen || isTouch) return 'mobile';
                    return 'web';
                }

                function openApp() {
                    const platform = getPlatform();
                    let targetUrl;

                    if (platform === 'android') {
                        // Extract path and query from current URL logic as per user's script
                        let path = window.location.pathname;
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

                        const intentPath = config.host + '/' + path + query;
                        targetUrl = 'intent://' + intentPath + '#Intent;' +
                            'scheme=' + config.scheme + ';' +
                            'package=' + config.androidPackage + ';' +
                            'S.browser_fallback_url=' + encodeURIComponent(config.playStore) + ';' +
                            'end;';
                    } else if (platform === 'ios') {
                        // For iOS, build the direct scheme URL
                        let path = window.location.pathname;
                        while (path.startsWith('/')) {
                            path = path.substring(1);
                        }
                        const query = window.location.search;
                        if (path.startsWith('products/')) path = path.replace('products/', 'product/');
                        else if (path.startsWith('sellers/')) path = path.replace('sellers/', 'seller/');
                        else if (path.startsWith('blogs/')) path = path.replace('blogs/', 'blog/');

                        targetUrl = config.scheme + '://' + config.host + '/' + path + query;
                    } else {
                        // Fallback
                        window.location.href = "/";
                        return;
                    }

                    let hasOpened = false;
                    const onVisibilityChange = () => {
                        if (document.hidden || document.webkitHidden) {
                            hasOpened = true;
                        }
                    };
                    document.addEventListener("visibilitychange", onVisibilityChange, {
                        once: true
                    });

                    // Attempt to open the app
                    window.location.href = targetUrl;

                    // Fallback to store
                    setTimeout(() => {
                        document.removeEventListener("visibilitychange", onVisibilityChange);
                        if (!hasOpened) {
                            window.location.href = platform === 'ios' ? config.appStore : config.playStore;
                        }
                    }, 2000);
                }

                // Trigger redirection immediately
                window.onload = openApp;
            })();
        </script>
    </head>

    <body>
        <div class="loader"></div>
        <p>Redirecting to app...</p>
        <p>If nothing happens, <a href="{{ $deepLink }}">click here to open manually</a>.</p>
    </body>

</html>
