@extends('admin.layout')

@section('title')
    {{ labels('admin_labels.sitemap_robots', 'Sitemap & Robots.txt') }}
@endsection

@section('content')
    <x-admin.breadcrumb :title="labels('admin_labels.sitemap_robots', 'Sitemap & Robots.txt')" :subtitle="labels('admin_labels.manage_sitemap_robots', 'Manage XML Sitemap and Robots.txt file')" :breadcrumbs="[
        ['label' => labels('admin_labels.seo', 'SEO'), 'url' => route('admin.seo.index')],
        ['label' => labels('admin_labels.sitemap_robots', 'Sitemap & Robots.txt')],
    ]" />

    <div class="row">
        <div class="col-lg-12">
            <div class="card content-area p-4">
                <div class="card-header border-bottom-0">
                    <h4 class="card-title">{{ labels('admin_labels.sitemap_settings', 'Sitemap Settings') }}</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <div class="border rounded p-3 h-100">
                                <h6 class="font-weight-bold mb-2">
                                    <i class="fas fa-file-code text-primary me-1"></i>
                                    {{ labels('admin_labels.sitemap_url', 'Sitemap URL') }}
                                </h6>
                                <p class="mb-2">
                                    <a href="{{ url('/sitemap.xml') }}" target="_blank" class="text-decoration-none">
                                        {{ url('/sitemap.xml') }}
                                    </a>
                                </p>
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    {{ labels('admin_labels.submit_to_google', 'Submit this to Google Search Console') }}
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-info mb-0">
                                <h5><i class="fas fa-info-circle"></i>
                                    {{ labels('admin_labels.about_sitemap', 'About Sitemap') }}</h5>
                                <p class="mb-0">
                                    {{ labels('admin_labels.sitemap_description', 'The sitemap is automatically generated based on your products, categories, and blogs. standard sitemap index containing links to separate sitemaps for each content type.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-12 mt-4">
            <div class="card content-area p-4">
                <div class="card-header border-bottom-0">
                    <h4 class="card-title">{{ labels('admin_labels.robots_txt_editor', 'Robots.txt Editor') }}</h4>
                </div>
                <div class="card-body">

                    <form action="{{ route('admin.seo.robots.update') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="robots_content"
                                class="form-label">{{ labels('admin_labels.robots_txt_content', 'File Content') }}</label>
                            <textarea class="form-control" id="robots_content" name="robots_content" rows="15" style="font-family: monospace;">{{ $robotsTxt }}</textarea>
                            <small
                                class="text-muted">{{ labels('admin_labels.robots_txt_warning', 'Be careful when editing this file. Incorrect configuration can block search engines from crawling your site.') }}</small>
                        </div>

                        <div class="d-flex justify-content-end">

                            <button type="submit"
                                class="btn btn-primary submit_button">{{ labels('admin_labels.save_changes', 'Save Changes') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
