<!DOCTYPE html>
<html>

    <head>
        <title>Deep Link Tester - Eshop</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            @php
                $system_settings = app(\App\Services\SettingService::class)->getSettings('system_settings', true);
                $system_settings = json_decode($system_settings, true);
                $scheme = str_replace('://', '', $system_settings['deep_link_scheme'] ?? 'eshop');
                $host = $system_settings['deep_link_host'] ?? 'eshop-pro.eshopweb.store';
            @endphp
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                padding: 20px;
                min-height: 100vh;
            }

            .container {
                max-width: 1200px;
                margin: 0 auto;
                background: white;
                border-radius: 20px;
                padding: 30px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            }

            h1 {
                color: #333;
                margin-bottom: 10px;
                font-size: 32px;
            }

            .subtitle {
                color: #666;
                margin-bottom: 30px;
                font-size: 16px;
            }

            .section {
                margin-bottom: 30px;
            }

            .section-title {
                font-size: 20px;
                color: #667eea;
                margin-bottom: 15px;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .badge {
                background: #667eea;
                color: white;
                padding: 4px 12px;
                border-radius: 12px;
                font-size: 12px;
                font-weight: 500;
            }

            .link-card {
                background: #f8f9fa;
                border: 2px solid #e9ecef;
                border-radius: 12px;
                padding: 15px;
                margin-bottom: 12px;
                transition: all 0.3s;
            }

            .link-card:hover {
                border-color: #667eea;
                box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
            }

            .link-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 10px;
            }

            .link-name {
                font-weight: 600;
                color: #333;
                font-size: 16px;
            }

            .link-url {
                font-family: 'Courier New', monospace;
                font-size: 14px;
                color: #0066cc;
                word-break: break-all;
                background: white;
                padding: 10px;
                border-radius: 6px;
                margin-bottom: 10px;
                border: 1px solid #dee2e6;
            }

            .link-actions {
                display: flex;
                gap: 10px;
            }

            .btn {
                padding: 8px 16px;
                border: none;
                border-radius: 6px;
                cursor: pointer;
                font-size: 14px;
                font-weight: 500;
                transition: all 0.2s;
                text-decoration: none;
                display: inline-block;
            }

            .btn-primary {
                background: #667eea;
                color: white;
            }

            .btn-primary:hover {
                background: #5568d3;
                transform: translateY(-1px);
            }

            .btn-secondary {
                background: #6c757d;
                color: white;
            }

            .btn-secondary:hover {
                background: #5a6268;
            }

            .btn-success {
                background: #28a745;
                color: white;
            }

            .alert {
                padding: 12px 16px;
                border-radius: 8px;
                margin-bottom: 20px;
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .alert-info {
                background: #d1ecf1;
                color: #0c5460;
                border: 1px solid #bee5eb;
            }

            .alert-success {
                background: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }

            .stats {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
                margin-bottom: 30px;
            }

            .stat-card {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 20px;
                border-radius: 12px;
                text-align: center;
            }

            .stat-number {
                font-size: 32px;
                font-weight: 700;
                margin-bottom: 5px;
            }

            .stat-label {
                font-size: 14px;
                opacity: 0.9;
            }

            .empty-state {
                text-align: center;
                padding: 40px;
                color: #999;
            }

            @media (max-width: 768px) {
                .container {
                    padding: 20px;
                }

                h1 {
                    font-size: 24px;
                }

                .link-actions {
                    flex-direction: column;
                }

                .btn {
                    width: 100%;
                }
            }
        </style>
    </head>

    <body>
        <div class="container">
            <h1>🔗 Deep Link Tester</h1>
            <p class="subtitle">Test deep links for your mobile app - Click links on your phone with the app installed
            </p>

            <div class="alert alert-info">
                <span>📱</span>
                <div>
                    <strong>How to test:</strong> Open this page on your mobile device and click the links below.
                    If the app is installed, it will open automatically. Otherwise, you'll see an error.
                </div>
            </div>

            <div class="stats">
                <div class="stat-card">
                    <div class="stat-number">{{ $products->count() }}</div>
                    <div class="stat-label">Products</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">{{ $sellers->count() }}</div>
                    <div class="stat-label">Sellers</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">{{ $blogs->count() }}</div>
                    <div class="stat-label">Blogs</div>
                </div>
            </div>

            <!-- Products Section -->
            <div class="section">
                <div class="section-title">
                    <span>📦 Products</span>
                    <span class="badge">{{ $products->count() }} items</span>
                </div>

                @forelse($products as $product)
                    @php
                        $deepLink = $scheme . '://' . $host . '/product/' . $product->slug;
                        $webUrl = url("/products/{$product->slug}");
                    @endphp
                    <div class="link-card">
                        <div class="link-header">
                            <div class="link-name">{{ $product->name }}</div>
                        </div>
                        <div class="link-url">{{ $deepLink }}</div>
                        <div class="link-actions">
                            <a href="javascript:void(0)" onclick="openInApp('{{ $deepLink }}')"
                                class="btn btn-primary">📱 Open in App</a>
                            <button onclick="copyToClipboard('{{ $deepLink }}')" class="btn btn-secondary">📋
                                Copy</button>
                            <a href="{{ $webUrl }}" target="_blank" class="btn btn-success">🌐 Web</a>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">No products found in database</div>
                @endforelse
            </div>

            <!-- Sellers Section -->
            <div class="section">
                <div class="section-title">
                    <span>🏪 Sellers</span>
                    <span class="badge">{{ $sellers->count() }} items</span>
                </div>

                @forelse($sellers as $seller)
                    @php
                        $deepLink = $scheme . '://' . $host . '/seller/' . $seller->slug;
                        $webUrl = url("/seller/{$seller->slug}");
                    @endphp
                    <div class="link-card">
                        <div class="link-header">
                            <div class="link-name">{{ $seller->name }}</div>
                        </div>
                        <div class="link-url">{{ $deepLink }}</div>
                        <div class="link-actions">
                            <a href="javascript:void(0)" onclick="openInApp('{{ $deepLink }}')"
                                class="btn btn-primary">📱 Open in App</a>
                            <button onclick="copyToClipboard('{{ $deepLink }}')" class="btn btn-secondary">📋
                                Copy</button>
                            <a href="{{ $webUrl }}" target="_blank" class="btn btn-success">🌐 Web</a>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">No sellers found in database</div>
                @endforelse
            </div>

            <!-- Blogs Section -->
            <div class="section">
                <div class="section-title">
                    <span>📝 Blogs</span>
                    <span class="badge">{{ $blogs->count() }} items</span>
                </div>

                @forelse($blogs as $blog)
                    @php
                        $deepLink = $scheme . '://' . $host . '/blog/' . $blog->slug;
                        $webUrl = url("/blogs/{$blog->slug}");
                    @endphp
                    <div class="link-card">
                        <div class="link-header">
                            <div class="link-name">{{ $blog->title }}</div>
                        </div>
                        <div class="link-url">{{ $deepLink }}</div>
                        <div class="link-actions">
                            <a href="javascript:void(0)" onclick="openInApp('{{ $deepLink }}')"
                                class="btn btn-primary">📱 Open in App</a>
                            <button onclick="copyToClipboard('{{ $deepLink }}')" class="btn btn-secondary">📋
                                Copy</button>
                            <a href="{{ $webUrl }}" target="_blank" class="btn btn-success">🌐 Web</a>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">No blogs found in database</div>
                @endforelse
            </div>

            <div class="alert alert-success" id="copyAlert" style="display: none;">
                ✅ Deep link copied to clipboard!
            </div>
        </div>

        <script>
            function openInApp(fullScheme) {
                var userAgent = navigator.userAgent || navigator.vendor || window.opera;
                var isAndroid = /android/i.test(userAgent);

                if (isAndroid) {
                    var scheme = "{{ $scheme }}";
                    var host = "{{ $host }}";
                    var packageName = "com.eshop"; // Fallback package
                    var storeLink = "https://play.google.com/store/apps/details?id=" + packageName;

                    var intentPath = fullScheme.replace(scheme + '://', '');
                    intentPath = intentPath.replace(/^\/+/, '');

                    var intentUrl = 'intent://' + intentPath + '#Intent;' +
                        'scheme=' + scheme + ';' +
                        'package=' + packageName + ';' +
                        'S.browser_fallback_url=' + encodeURIComponent(storeLink) + ';' +
                        'end';

                    window.location.href = intentUrl;
                } else {
                    window.location.href = fullScheme;
                }
            }

            function copyToClipboard(text) {
                navigator.clipboard.writeText(text).then(function() {
                    const alert = document.getElementById('copyAlert');
                    alert.style.display = 'flex';
                    setTimeout(() => {
                        alert.style.display = 'none';
                    }, 2000);
                }, function(err) {
                    console.error('Could not copy text: ', err);
                });
            }
        </script>
    </body>

</html>
