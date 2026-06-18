@extends('admin/layout')

@section('title')
    {{ labels('admin_labels.blog_seo', 'Blog SEO Management') }}
@endsection

@section('content')
    <x-admin.breadcrumb :title="labels('admin_labels.blog_seo', 'Blog SEO Management')" :subtitle="labels('admin_labels.manage_seo_for_blogs', 'Manage SEO settings for individual blogs')" :breadcrumbs="[
        ['label' => labels('admin_labels.seo', 'SEO'), 'url' => route('admin.seo.index')],
        ['label' => labels('admin_labels.blog_seo', 'Blog SEO')],
    ]" />

    {{-- table  --}}
    <section class="overview-data">
        <div class="card content-area p-4">
            <div class="row align-items-center d-flex heading mb-5">
                <div class="col-md-12">
                    <div class="row">
                        <div class="col-md-6">
                            <h4>{{ labels('admin_labels.blog_seo', 'Blog SEO Management') }}</h4>
                        </div>
                        <div class="col-md-6 d-flex justify-content-end">
                            <div class="input-group me-2 search-input-grp">
                                <span class="search-icon"><i class='bx bx-search-alt'></i></span>
                                <input type="text" data-table="blog_seo_table" class="form-control searchInput"
                                    placeholder="{{ labels('admin_labels.search', 'Search') }}">
                                <span class="input-group-text">{{ labels('admin_labels.search', 'Search') }}</span>
                            </div>
                            <a class="btn me-2" id="tableRefresh" data-table="blog_seo_table"><i
                                    class='bx bx-refresh'></i></a>
                            <div class="dropdown">
                                <a class="btn dropdown-toggle export-btn" type="button" id="exportOptionsDropdown"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class='bx bx-download'></i>
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="exportOptionsDropdown">
                                    <li><button class="dropdown-item" type="button"
                                            onclick="exportTableData('blog_seo_table','csv')">CSV</button></li>
                                    <li><button class="dropdown-item" type="button"
                                            onclick="exportTableData('blog_seo_table','json')">JSON</button></li>
                                    <li><button class="dropdown-item" type="button"
                                            onclick="exportTableData('blog_seo_table','excel')">Excel</button></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="pt-0">
                        <div class="table-responsive">
                            <table class='table' id="blog_seo_table" data-toggle="table"
                                data-loading-template="loadingTemplate" data-url="{{ route('admin.seo.blogs.list') }}"
                                data-side-pagination="server" data-pagination="true"
                                data-page-list="[5, 10, 20, 50, 100, 200]" data-search="false" data-show-columns="false"
                                data-show-refresh="false" data-trim-on-search="false" data-sort-name="id"
                                data-sort-order="desc" data-mobile-responsive="true" data-toolbar=""
                                data-show-export="false" data-export-types='["txt","excel"]'
                                data-query-params="queryParams">
                                <thead>
                                    <tr>
                                        <th data-field="id" data-sortable="true" data-visible='true'>
                                            {{ labels('admin_labels.id', 'ID') }}
                                        </th>
                                        <th data-field="title" data-sortable="true">
                                            {{ labels('admin_labels.blog_title', 'Blog Title') }}
                                        </th>
                                        <th data-field="seo_status" data-sortable="false">
                                            {{ labels('admin_labels.seo_status', 'SEO Status') }}
                                        </th>
                                        <th data-field="meta_title" data-sortable="false">
                                            {{ labels('admin_labels.meta_title', 'Meta Title') }}
                                        </th>
                                        <th data-field="actions" data-sortable="false">
                                            {{ labels('admin_labels.action', 'Action') }}
                                        </th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
