@extends('admin.layout')

@section('title')
    {{ labels('admin_labels.seo_global_settings', 'Global SEO Settings') }}
@endsection

@section('content')
    <x-admin.breadcrumb title="{{ labels('admin_labels.seo_global_settings', 'Global SEO Settings') }}" subtitle="{{ labels('admin_labels.configure_default_seo_settings_for_website', 'Configure default SEO settings for your website') }}"
        :breadcrumbs="[['label' => labels('admin_labels.seo', 'SEO'), 'url' => route('admin.seo.index')], ['label' => labels('admin_labels.global_settings', 'Global Settings')]]" />

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ labels('admin_labels.seo_global_settings', 'Global SEO Settings') }}</h4>
                        <p class="text-muted">{{ labels('admin_labels.configure_default_seo_settings_for_website', 'Configure default SEO settings for your website') }}</p>
                    </div>
                    <div class="card-body">

                        <form action="{{ route('admin.seo.global.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf

                            <!-- Basic Meta Tags -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h5>{{ labels('admin_labels.basic_meta_tags', 'Basic Meta Tags') }}</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="meta_title" class="form-label">{{ labels('admin_labels.meta_title', 'Meta Title') }}</label>
                                        <input type="text" class="form-control @error('meta_title') is-invalid @enderror"
                                            id="meta_title" name="meta_title" maxlength="60"
                                            value="{{ old('meta_title', $seo->meta_title ?? ($systemSettings['app_name'] ?? '')) }}"
                                            placeholder="{{ labels('admin_labels.enter_meta_title_placeholder', 'Enter meta title (max 60 characters)') }}">
                                        <small class="text-muted">{{ labels('admin_labels.character_count', 'Character count:') }} <span
                                                id="title-count">0</span>/60</small>
                                        @error('meta_title')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="meta_description" class="form-label">{{ labels('admin_labels.meta_description', 'Meta Description') }}</label>
                                        <textarea class="form-control @error('meta_description') is-invalid @enderror" id="meta_description"
                                            name="meta_description" rows="3" maxlength="160" placeholder="{{ labels('admin_labels.enter_meta_description_placeholder', 'Enter meta description (max 160 characters)') }}">{{ old('meta_description', $seo->meta_description ?? '') }}</textarea>
                                        <small class="text-muted">{{ labels('admin_labels.character_count', 'Character count:') }} <span
                                                id="desc-count">0</span>/160</small>
                                        @error('meta_description')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="meta_keywords" class="form-label">{{ labels('admin_labels.meta_keywords', 'Meta Keywords') }}</label>
                                        <input type="text"
                                            class="form-control @error('meta_keywords') is-invalid @enderror"
                                            id="meta_keywords" name="meta_keywords"
                                            value="{{ old('meta_keywords', $seo->meta_keywords ?? '') }}"
                                            placeholder="{{ labels('admin_labels.enter_keywords_placeholder', 'Enter keywords separated by commas') }}">
                                        @error('meta_keywords')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="canonical_url" class="form-label">{{ labels('admin_labels.canonical_url', 'Canonical URL') }}</label>
                                        <input type="url"
                                            class="form-control @error('canonical_url') is-invalid @enderror"
                                            id="canonical_url" name="canonical_url"
                                            value="{{ old('canonical_url', $seo->canonical_url ?? url('/')) }}"
                                            placeholder="{{ labels('admin_labels.enter_canonical_url_placeholder', 'https://example.com') }}">
                                        @error('canonical_url')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="robots" class="form-label">{{ labels('admin_labels.robots', 'Robots') }}</label>
                                        <select class="form-select @error('robots') is-invalid @enderror" id="robots"
                                            name="robots">
                                            <option value="index,follow"
                                                {{ old('robots', $seo->robots ?? 'index,follow') == 'index,follow' ? 'selected' : '' }}>
                                                {{ labels('admin_labels.index_follow', 'Index, Follow') }}</option>
                                            <option value="noindex,follow"
                                                {{ old('robots', $seo->robots ?? '') == 'noindex,follow' ? 'selected' : '' }}>
                                                {{ labels('admin_labels.noindex_follow', 'No Index, Follow') }}</option>
                                            <option value="index,nofollow"
                                                {{ old('robots', $seo->robots ?? '') == 'index,nofollow' ? 'selected' : '' }}>
                                                {{ labels('admin_labels.index_nofollow', 'Index, No Follow') }}</option>
                                            <option value="noindex,nofollow"
                                                {{ old('robots', $seo->robots ?? '') == 'noindex,nofollow' ? 'selected' : '' }}>
                                                {{ labels('admin_labels.noindex_nofollow', 'No Index, No Follow') }}</option>
                                        </select>
                                        @error('robots')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Open Graph Tags -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h5>{{ labels('admin_labels.open_graph_tags', 'Open Graph Tags (Facebook, LinkedIn)') }}</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="og_title" class="form-label">{{ labels('admin_labels.og_title', 'OG Title') }}</label>
                                        <input type="text" class="form-control @error('og_title') is-invalid @enderror"
                                            id="og_title" name="og_title"
                                            value="{{ old('og_title', $seo->og_title ?? '') }}"
                                            placeholder="{{ labels('admin_labels.enter_open_graph_title_placeholder', 'Enter Open Graph title') }}">
                                        @error('og_title')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="og_description" class="form-label">{{ labels('admin_labels.og_description', 'OG Description') }}</label>
                                        <textarea class="form-control @error('og_description') is-invalid @enderror" id="og_description" name="og_description"
                                            rows="3" placeholder="{{ labels('admin_labels.enter_open_graph_description_placeholder', 'Enter Open Graph description') }}">{{ old('og_description', $seo->og_description ?? '') }}</textarea>
                                        @error('og_description')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="og_type" class="form-label">{{ labels('admin_labels.og_type', 'OG Type') }}</label>
                                        <select class="form-select @error('og_type') is-invalid @enderror" id="og_type"
                                            name="og_type">
                                            <option value="website"
                                                {{ old('og_type', $seo->og_type ?? 'website') == 'website' ? 'selected' : '' }}>
                                                {{ labels('admin_labels.website', 'Website') }}</option>
                                            <option value="article"
                                                {{ old('og_type', $seo->og_type ?? '') == 'article' ? 'selected' : '' }}>
                                                {{ labels('admin_labels.article', 'Article') }}</option>
                                            <option value="product"
                                                {{ old('og_type', $seo->og_type ?? '') == 'product' ? 'selected' : '' }}>
                                                {{ labels('admin_labels.product', 'Product') }}</option>
                                        </select>
                                        @error('og_type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="og_image" class="form-label">{{ labels('admin_labels.og_image', 'OG Image') }}</label>
                                        @if ($seo && $seo->og_image)
                                            <div class="mb-2">
                                                <img src="{{ $seo->og_image }}" alt="OG Image" class="img-thumbnail"
                                                    style="max-width: 200px;">
                                            </div>
                                        @endif
                                        <input type="file" class="form-control @error('og_image') is-invalid @enderror"
                                            id="og_image" name="og_image" accept="image/*">
                                        <small class="text-muted">{{ labels('admin_labels.recommended_size_1200x630px', 'Recommended size: 1200x630px') }}</small>
                                        @error('og_image')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Twitter Card Tags -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h5>{{ labels('admin_labels.twitter_card_tags', 'Twitter Card Tags') }}</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="twitter_card" class="form-label">{{ labels('admin_labels.twitter_card_type', 'Twitter Card Type') }}</label>
                                        <select class="form-select @error('twitter_card') is-invalid @enderror"
                                            id="twitter_card" name="twitter_card">
                                            <option value="summary"
                                                {{ old('twitter_card', $seo->twitter_card ?? 'summary_large_image') == 'summary' ? 'selected' : '' }}>
                                                {{ labels('admin_labels.summary', 'Summary') }}</option>
                                            <option value="summary_large_image"
                                                {{ old('twitter_card', $seo->twitter_card ?? 'summary_large_image') == 'summary_large_image' ? 'selected' : '' }}>
                                                {{ labels('admin_labels.summary_large_image', 'Summary Large Image') }}</option>
                                        </select>
                                        @error('twitter_card')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="twitter_title" class="form-label">{{ labels('admin_labels.twitter_title', 'Twitter Title') }}</label>
                                        <input type="text"
                                            class="form-control @error('twitter_title') is-invalid @enderror"
                                            id="twitter_title" name="twitter_title"
                                            value="{{ old('twitter_title', $seo->twitter_title ?? '') }}"
                                            placeholder="{{ labels('admin_labels.enter_twitter_title_placeholder', 'Enter Twitter card title') }}">
                                        @error('twitter_title')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="twitter_description" class="form-label">{{ labels('admin_labels.twitter_description', 'Twitter Description') }}</label>
                                        <textarea class="form-control @error('twitter_description') is-invalid @enderror" id="twitter_description"
                                            name="twitter_description" rows="3" placeholder="{{ labels('admin_labels.enter_twitter_description_placeholder', 'Enter Twitter card description') }}">{{ old('twitter_description', $seo->twitter_description ?? '') }}</textarea>
                                        @error('twitter_description')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="twitter_image" class="form-label">{{ labels('admin_labels.twitter_image', 'Twitter Image') }}</label>
                                        @if ($seo && $seo->twitter_image)
                                            <div class="mb-2">
                                                <img src="{{ $seo->twitter_image }}" alt="Twitter Image"
                                                    class="img-thumbnail" style="max-width: 200px;">
                                            </div>
                                        @endif
                                        <input type="file"
                                            class="form-control @error('twitter_image') is-invalid @enderror"
                                            id="twitter_image" name="twitter_image" accept="image/*">
                                        <small class="text-muted">{{ labels('admin_labels.recommended_size_1200x675px', 'Recommended size: 1200x675px') }}</small>
                                        @error('twitter_image')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit"
                                    class="btn btn-primary submit_button text-white">{{ labels('admin_labels.save_global_seo_settings', 'Save Global SEO Settings') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // Character counter for meta title
        document.getElementById('meta_title').addEventListener('input', function() {
            document.getElementById('title-count').textContent = this.value.length;
        });

        // Character counter for meta description
        document.getElementById('meta_description').addEventListener('input', function() {
            document.getElementById('desc-count').textContent = this.value.length;
        });

        // Initialize counters on page load
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('title-count').textContent = document.getElementById('meta_title').value.length;
            document.getElementById('desc-count').textContent = document.getElementById('meta_description').value
                .length;
        });
    </script>
@endsection
