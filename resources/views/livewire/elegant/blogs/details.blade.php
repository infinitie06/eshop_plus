<div id="page-content">
    <!--Page Header-->
    <x-utility.breadcrumbs.breadcrumbTwo :$bread_crumb />
    <!--End Page Header-->

    <!--Main Content-->
    <div class="container-fluid">
        <div class="row">

            <!-- Blog Content-->
            <div class="col-12">
                <div class="blog-article">
                    <div class="blog-img mb-3">

                        @if (!empty($blog_img))
                            <img class="rounded-0 blur-up lazyload" data-src="{{ $blog_img }}"
                                src="{{ $blog_img }}" alt="{{ $blogTitle }}" />
                        @endif
                    </div>
                    <!-- Blog Content -->
                    <div class="blog-content">
                        <h2 class="h1">{{ $blogTitle }}
                        </h2>
                        <ul class="publish-detail d-flex-wrap">
                            <li><i class="icon anm anm-clock-r"></i> <time
                                    datetime="{{ $blogArray['created_at'] ?? $blogItem->created_at }}">{{ $blogArray['created_at'] ?? $blogItem->created_at }}</time>
                            </li>
                        </ul>
                        <hr />
                        <div class="content">
                            {!! htmlspecialchars_decode($blogArray['description'] ?? $blogItem->description, ENT_QUOTES) !!}
                        </div>
                        <hr class="horizontal light m-0" />
                        <div class="row blog-action d-flex-center justify-content-between">
                            <div class="social-sharing share-icon d-flex-center mx-0 mt-3 justify-content-end">
                                <span class="sharing-lbl">{{ labels('front_messages.share', 'Share') }} :</span>

                                <div class="shareon" data-url="{{ $webUrl }}"
                                    data-deep-link="{{ $deepLinkUrl ?? '' }}">
                                    <a class="facebook" data-text="{{ $shareText }}"></a>
                                    <a class="telegram" data-text="{{ $shareText }}"></a>
                                    <a class="twitter" data-text="{{ $shareText }}"></a>
                                    <a class="whatsapp" data-text="{{ $shareText }}"></a>
                                    <a class="email" data-text="{{ $shareText }}"></a>
                                    <a class="copy-url" data-deep-link="{{ $deepLinkUrl ?? '' }}"></a>
                                </div>
                            </div>
                        </div>
                        <script>
                            // Enhanced share functionality with deep linking support
                            document.addEventListener('DOMContentLoaded', function() {
                                // Handle copy URL with deep linking
                                const copyUrlButtons = document.querySelectorAll('.copy-url');
                                copyUrlButtons.forEach(button => {
                                    button.addEventListener('click', function(e) {
                                        e.preventDefault();
                                        const deepLink = this.getAttribute('data-deep-link');
                                        const webUrl = this.closest('.shareon').getAttribute('data-url');

                                        // Try to detect if user is on mobile and use deep link
                                        const isMobile =
                                            /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
                                                navigator.userAgent);
                                        const urlToCopy = (isMobile && deepLink) ? deepLink : webUrl;

                                        // Copy to clipboard
                                        if (navigator.clipboard && navigator.clipboard.writeText) {
                                            navigator.clipboard.writeText(urlToCopy).then(function() {
                                                alert(
                                                    '{{ labels('front_messages.url_copied', 'URL copied to clipboard') }}'
                                                );
                                            });
                                        } else {
                                            // Fallback for older browsers
                                            const textArea = document.createElement('textarea');
                                            textArea.value = urlToCopy;
                                            document.body.appendChild(textArea);
                                            textArea.select();
                                            document.execCommand('copy');
                                            document.body.removeChild(textArea);
                                            alert(
                                                '{{ labels('front_messages.url_copied', 'URL copied to clipboard') }}'
                                            );
                                        }
                                    });
                                });

                                // Enhance shareon links to include deep linking for mobile
                                const shareonDiv = document.querySelector('.shareon');
                                if (shareonDiv) {
                                    const deepLink = shareonDiv.getAttribute('data-deep-link');
                                    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator
                                        .userAgent);

                                    if (isMobile && deepLink) {
                                        // For mobile, try deep link first, fallback to web
                                        shareonDiv.querySelectorAll('a').forEach(link => {
                                            if (!link.classList.contains('copy-url')) {
                                                const originalHref = link.getAttribute('href');
                                                link.addEventListener('click', function(e) {
                                                    // For WhatsApp and Telegram on mobile, use deep link
                                                    if ((this.classList.contains('whatsapp') || this.classList.contains(
                                                            'telegram')) && deepLink) {
                                                        // Try to open deep link
                                                        window.location.href = deepLink;
                                                        // Fallback to web after a delay
                                                        setTimeout(function() {
                                                            if (originalHref) {
                                                                window.open(originalHref, '_blank');
                                                            }
                                                        }, 500);
                                                    }
                                                });
                                            }
                                        });
                                    }
                                }
                            });
                        </script>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('partials.deep-link-bottom-sheet', ['deepLinkUrl' => $deepLinkUrl])
</div>
