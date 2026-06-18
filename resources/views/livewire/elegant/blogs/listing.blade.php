@php
    use App\Models\Blog;
    use App\Models\BlogCategory;
    use App\Services\TranslationService;
    use App\Services\MediaService;
    $bread_crumb['page_main_bread_crumb'] = labels('front_messages.blogs', 'Blogs');
    $language_code = app(TranslationService::class)->getLanguageCode();
@endphp
<div id="page-content">
    <x-utility.breadcrumbs.breadcrumbTwo :$bread_crumb />
        <div class="container-fluid">
            <div class="row">
                {{-- Sidebar Column --}}
                <div class="col-lg-3 col-md-12 sidebar">
                    <div class="sidebar_widget">
                        <div class="widget-title">
                            <h2>{{ labels('front_messages.categories', 'Categories') }}</h2>
                        </div>
                        <div class="widget-content" style="">
                            <ul class="sidebar_categories">
                                <li class="lvl-1 ">
                                    <a href="javascript:void(0)" wire:click.prevent="$set('category_id', '')"
                                        class="site-nav {{ $category_id == '' ? 'active' : '' }}">
                                        {{ labels('front_messages.all_categories', 'All Categories') }}
                                    </a>
                                </li>
                                @foreach ($categories as $category)
                                    <li class="lvl-1" wire:key="category-{{ $category->id }}">
                                        <a href="javascript:void(0)"
                                            wire:click.prevent="$set('category_id', {{ $category->id }})"
                                            class="site-nav {{ $category_id == $category->id ? 'active' : '' }}">
                                            {{ app(TranslationService::class)->getDynamicTranslation(BlogCategory::class, 'name', $category->id, $language_code) }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Main Content Column --}}
                <div class="col-lg-9 col-md-12">
                    <div class="toolbar toolbar-wrapper blog-toolbar">
                        <div class="row align-items-center">
                            <div
                                class="col-12 col-sm-6 col-md-6 col-lg-6 text-left filters-toolbar-item d-flex justify-content-center justify-content-sm-start">
                                <div class="search-form mb-3 mb-sm-0">
                                    <input wire:model.live.debounce.250ms="search" class="search-input" type="text"
                                        placeholder="Blog {{ labels('admin_labels.search', 'Search') }}"
                                        value="{{ $search }}">
                                    <button wire:ignore class="search-btn"><ion-icon name="search-outline"
                                            class="icon fs-5"></ion-icon></button>
                                </div>
                            </div>

                            <div
                                class="col-12 col-sm-6 col-md-6 col-lg-6 text-right filters-toolbar-item d-flex justify-content-between justify-content-sm-end">
                                <div class="filters-item d-flex align-items-center">
                                    <label for="ShowBy"
                                        class="mb-0 me-2">{{ labels('front_messages.show', 'Show') }}:</label>
                                    <select name="ShowBy" id="perPage" class="filters-toolbar-sort">
                                        <option value="9" {{ $perPage == '9' ? 'selected' : '' }}>9</option>
                                        <option value="18" {{ $perPage == '18' ? 'selected' : '' }}>18</option>
                                        <option value="27" {{ $perPage == '27' ? 'selected' : '' }}>27</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if ($blogs_count >= 1)
                        <div class="container my-5">
                            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-4">
                                @foreach ($blogs['listing'] as $blog)
                                    @php
                                        // Handle both array and object access
                                        $blogItem = is_array($blog) ? (object) $blog : $blog;
                                        $blogId = is_array($blog) ? $blog['id'] ?? null : $blog->id ?? null;
                                        $blogSlug = is_array($blog) ? $blog['slug'] ?? '' : $blog->slug ?? '';
                                        $blogImage = is_array($blog)
                                            ? (isset($blog['image']) && is_string($blog['image'])
                                                ? $blog['image']
                                                : '')
                                            : (isset($blog->image) && is_string($blog->image)
                                                ? $blog->image
                                                : '');
                                        $blogCreatedAt = is_array($blog)
                                            ? $blog['created_at'] ?? ''
                                            : $blog->created_at ?? '';
                                        $blogShortDesc = is_array($blog)
                                            ? $blog['short_description'] ?? ''
                                            : $blog->short_description ?? '';
                                        $blogDescription = is_array($blog)
                                            ? $blog['description'] ?? ''
                                            : $blog->description ?? '';
                                        $image =
                                            !empty($blogImage) && is_string($blogImage)
                                                ? app(MediaService::class)->dynamic_image($blogImage, 600)
                                                : '';
                                    @endphp
                                    <div class="col">
                                        <div class="card h-100 d-flex flex-column">
                                            <!-- Uniform image height using ratio -->
                                            <div class="ratio ratio-4x3">
                                                @if (!empty($image))
                                                    <a wire:navigate
                                                        class="d-flex justify-content-center align-items-center w-100 h-100"
                                                        href="{{ customUrl('blogs/' . $blogSlug) }}">
                                                        <img class="img-fluid w-100 h-100 object-fit-cover"
                                                            src="{{ $image }}"
                                                            alt="{{ app(TranslationService::class)->getDynamicTranslation(Blog::class, 'title', $blogId, $language_code) }}">
                                                    </a>
                                                @endif
                                            </div>

                                            <!-- Blog content -->
                                            <div class="card-body d-flex flex-column">
                                                <h2 class="h5">
                                                    <a wire:navigate href="{{ customUrl('blogs/' . $blogSlug) }}">
                                                        {{ app(TranslationService::class)->getDynamicTranslation(Blog::class, 'title', $blogId, $language_code) }}
                                                    </a>
                                                </h2>
                                                <ul class="list-unstyled small text-muted mb-2">
                                                    <li>
                                                        <i class="icon anm anm-clock-r"></i>
                                                        <time
                                                            datetime="{{ $blogCreatedAt }}">{{ $blogCreatedAt }}</time>
                                                    </li>
                                                </ul>
                                                <p class="card-text flex-grow-1">
                                                    @if (!empty($blogShortDesc))
                                                        {{ \Illuminate\Support\Str::limit($blogShortDesc, 100) }}
                                                    @else
                                                        {{ \Illuminate\Support\Str::limit(strip_tags($blogDescription), 100) }}
                                                    @endif
                                                </p>
                                                <a wire:navigate href="{{ customUrl('blogs/' . $blogSlug) }}"
                                                    class="btn btn-outline-secondary mt-auto">{{ labels('front_messages.read_more', 'Read more') }}</a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Pagination -->
                            <nav class="mt-5">
                                {!! $blogs['links'] !!}
                            </nav>
                        </div>
                    @else
                        @php
                            $title = labels('front_messages.no_blog_found', 'No Blog Found');
                        @endphp
                        <x-utility.others.not-found :$title />
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle perPage dropdown change
    const perPageSelect = document.getElementById('perPage');
    if (perPageSelect) {
        perPageSelect.addEventListener('change', function() {
            const url = new URL(window.location);
            url.searchParams.set('perPage', this.value);
            url.searchParams.delete('page'); // Reset to first page when changing per page
            window.location.href = url.toString();
        });
    }
    
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Initialize any blog-specific scroll functionality
    if (typeof blog_sidebar_dropdown === 'function') {
        blog_sidebar_dropdown();
    }
});
</script>
