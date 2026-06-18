@extends('admin/layout')
@section('title')
    {{ labels('admin_labels.media', 'Media') }}
@endsection
@section('content')
<x-admin.breadcrumb :title="labels('admin_labels.media', 'Media')" :subtitle="labels('admin_labels.take_command_of_your_media', 'Take Command Of Your Media')" :breadcrumbs="[
    ['label' => labels('admin_labels.media_management', 'Media Management')],
    ['label' => labels('admin_labels.add_media', 'Add Media')],
]" />

<section class="overview-data">
    <div class="card content-area p-4">

        <div class="mt-4 col-md-12 additional-info-nav-header d-flex">
            <div class="col-md-8">
                <nav class="w-100">
                    <div class="nav nav-tabs" id="media-tab" role="tablist">
                        <a class="nav-item nav-link active" data-bs-toggle="tab" href="#media-list" role="tab"
                            aria-controls="media-list" aria-selected="true">{{ labels('admin_labels.select_file', 'Select File') }}</a>
                        <a class="nav-item nav-link" data-bs-toggle="tab" href="#media-upload" role="tab"
                            aria-controls="media-upload" aria-selected="false">{{ labels('admin_labels.upload_new', 'Upload New') }}</a>
                    </div>
                </nav>
            </div>
            <div class="col-md-4">
                <div class="align-items-center d-flex form-group justify-content-end gap-3">
                    <div class="col-md-6">
                        <select class="form-select" id="media-type">
                            <option value="">{{ labels('admin_labels.media_type', 'Media Type') }}</option>
                            <option value="image" {{ request('type') == 'image' ? 'selected' : '' }}>{{ labels('admin_labels.images_option', 'Images') }}</option>
                            <option value="audio" {{ request('type') == 'audio' ? 'selected' : '' }}>{{ labels('admin_labels.audio_option', 'Audio') }}</option>
                            <option value="video" {{ request('type') == 'video' ? 'selected' : '' }}>{{ labels('admin_labels.video_option', 'Video') }}</option>
                            <option value="archive" {{ request('type') == 'archive' ? 'selected' : '' }}>{{ labels('admin_labels.archive_option', 'Archive') }}</option>
                            <option value="spreadsheet" {{ request('type') == 'spreadsheet' ? 'selected' : '' }}>{{ labels('admin_labels.spreadsheet_option', 'Spreadsheet') }}</option>
                            <option value="document" {{ request('type') == 'document' ? 'selected' : '' }}>{{ labels('admin_labels.documents_option', 'Documents') }}</option>
                        </select>
                    </div>
                    <div class="input-group search-input-grp product-search">
                        <span class="search-icon"><i class='bx bx-search-alt'></i></span>
                        <input type="text" name="search_products" class="form-control" id="search_products"
                            value="{{ request('search') }}" placeholder="{{ labels('admin_labels.search_media_placeholder', 'Search Media') }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-content p-3 col-md-12 {{ $user_role == 'super_admin' || $logged_in_user->hasPermissionTo('view media') ? '' : 'd-none' }}"
            id="nav-tabContent">
            <div class="tab-pane fade active show" id="media-list" role="tabpanel" aria-labelledby="media-list-tab">

                <div id="media-list-wrapper">
                    {{-- Media cards + footer --}}
                    <div class="row media-card-container">

                        @if($media->isEmpty())
                            <div class="col-12 d-flex justify-content-center mt-5">
                                <p class="text-muted">{{ labels('admin_labels.no_media_found', 'No media found!!') }}</p>
                            </div>
                        @else
                            @foreach ($media as $row)
                                <div class="col-md-6 col-xl-3 col-xxl-2 col-sm-6 mt-5">
                                    <div class="media-card">
                                        @php
                                            $isPublicDisk = $row->disk == 'public';
                                            $imagePath = $isPublicDisk
                                                ? app(\App\Services\MediaService::class)->getImageUrl($row->sub_directory.'/'.$row->file_name, '', '', $row->type)
                                                : $row->object_url;
                                            $delete_url = route('admin.media.destroy', $row->id);
                                        @endphp
                                        <div class="media-image-box">
                                            <a href="{{ $imagePath }}" data-lightbox="image-{{ $row->id }}">
                                                <img src="{{ route('admin.dynamic_image', ['url' => $imagePath, 'width' => 120, 'quality' => 90]) }}"
                                                    alt="Avatar" class="rounded">
                                            </a>
                                        </div>
                                        <div class="media-title">
                                            <h6>{{ Str::limit($row->name, 22, '...') }}</h6>
                                        </div>
                                        <div class="media-details d-flex justify-content-between">
                                            <p class="text-muted">{{ $row->size }} KB</p>
                                            <div class="d-flex">
                                                <a class="delete-media me-1 delete-data" data-url="{{ $delete_url }}"><i class='bx bx-trash'></i></a>
                                                <span class="path d-none">{{ config('app.url').'storage'.$row->sub_directory.'/'.$row->file_name }}</span>
                                                <a class="copy-to-clipboard me-1"><i class='bx bx-copy-alt'></i></a>
                                                <span class="relative-path d-none">{{ $row->sub_directory.'/'.$row->file_name }}</span>
                                                <a class="copy-relative-path"><i class='bx bx-images'></i></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif

                    </div>

                    @if(!$media->isEmpty())
                        <div class="card-footer text-muted">
                            <div class="d-flex justify-content-between align-items-center">

                                {{-- Limit dropdown --}}
                                <div class="float-left pagination-detail">
                                    <div class="page-list">
                                        <div class="btn-group dropup">
                                            <button class="btn btn-undefined dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                <span class="page-size">{{ $media->perPage() }}</span>
                                                <i class="bx bx-chevron-up"></i>
                                            </button>
                                            <div class="dropdown-menu media-pagination">
                                                @foreach([25, 50, 75] as $size)
                                                    <a class="dropdown-item {{ $media->perPage() == $size ? 'active' : '' }}"
                                                       href="{{ route('admin.media', array_merge(request()->query(), ['limit' => $size])) }}">
                                                        {{ $size }}
                                                    </a>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Pagination links --}}
                                <div class="col-12 d-flex justify-content-end pagination-sm pe-6 ps-1">
                                    {{ $media->links('pagination::bootstrap-4') }}
                                </div>

                            </div>
                        </div>
                    @endif
                </div>

            </div>

            <div class="tab-pane fade" id="media-upload" role="tabpanel" aria-labelledby="media-upload-tab">
                <form action="{{ route('admin.media.upload') }}" class="submit_form" enctype="multipart/form-data"
                    method="POST">
                    @csrf
                    <input type="file" class="filepond" name="documents[]" multiple data-max-file-size="30MB"
                        data-max-files="20" />
                    <button type="submit"
                        class="btn btn-primary submit_button float-end">{{ labels('admin_labels.upload', 'Upload') }}</button>
                </form>
            </div>

        </div>
    </div>
</section>

@push('scripts')
<script>
$(document).ready(function() {

    function fetchMedia(params = {}) {
        $.ajax({
            url: "{{ route('admin.media') }}",
            type: 'GET',
            data: params,
            beforeSend: function() {
                $('#media-list-wrapper').html('<p class="text-center mt-5">Loading...</p>');
            },
            success: function(res) {
                $('#media-list-wrapper').html(res);
            },
            error: function() {
                $('#media-list-wrapper').html('<p class="text-center mt-5 text-danger">Error loading media</p>');
            }
        });
    }

    // Filter by type
    $('#media-type').on('change', function() {
        fetchMedia({type: $(this).val(), search: $('#search_products').val(), limit: $('.page-size').text()});
    });

    // Search input
    $('#search_products').on('keyup', function(e) {
        if(e.keyCode === 13) { // Enter key
            fetchMedia({type: $('#media-type').val(), search: $(this).val(), limit: $('.page-size').text()});
        }
    });

    // Pagination click
    $(document).on('click', '.pagination a', function(e) {
        e.preventDefault();
        let url = $(this).attr('href');
        $.ajax({
            url: url,
            type: 'GET',
            success: function(res) {
                $('#media-list-wrapper').html(res);
            }
        });
    });

    // Limit dropdown click
    $(document).on('click', '.media-pagination a', function(e) {
        e.preventDefault();
        let url = $(this).attr('href');
        $.ajax({
            url: url,
            type: 'GET',
            success: function(res) {
                $('#media-list-wrapper').html(res);
            }
        });
    });

});
</script>
@endpush
@endsection
