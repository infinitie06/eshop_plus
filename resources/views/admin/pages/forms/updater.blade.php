@extends('admin/layout')
@section('title')
    {{ labels('admin_labels.system_settings', 'System Settings') }}
@endsection
@section('content')
    @php
        $user = auth()->user();
        $role = auth()->user()->role->name;

    @endphp

    <x-admin.breadcrumb :title="labels('admin_labels.system_update', 'System Update')" :subtitle="labels('admin_labels.update_web_and_admin_panel_from_here', 'Update Web and Admin Panel From here')" :breadcrumbs="[
        ['label' => labels('admin_labels.settings', 'Settings'), 'url' => route('settings.index')],
        ['label' => labels('admin_labels.system_update', 'System Update')],
    ]" />


    <div class="row">
        <div class="alert alert-primary alert-dismissible" role="alert">
            <?= labels('post_update_clear_browser_cache', 'Clear your browser cache by pressing CTRL+F5 after updating the system.') ?><button
                type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
        <div class="alert alert-info alert-dismissible" role="alert">
            {{ labels('admin_labels.if_package_includes_web_files_upload', 'If your package includes web files, upload the web updater file immediately after uploading the update file.') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <div class="alert alert-success alert-dismissible" role="alert">
            {{ labels('admin_labels.if_package_includes_app_files_change', 'If your package includes app files, change the app file immediately after uploading the update file. As per the update guide.') }}
            <a href="https://drive.google.com/drive/folders/1kHeOsufjaZu-XxrfGMKYJPfF2BU9VS2X?usp=drive_link" target="_blank" class="alert-link">
                <strong>{{ labels('admin_labels.view_update_guide', 'View Update Guide') }}</strong>
            </a>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="text-center"><span
                                class="badge bg-primary"><?= labels('admin_labels.current_version_label', 'Current version') . ' - ' ?>
                                {{ get_current_version() }}</span>
                        </div>
                        <form class="form-horizontal" id="system-update" action="{{ url('admin/settings/system-update') }}"
                            method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="card-body">
                                <div class="dropzone w-100 d-flex justify-content-center align-items-center"
                                    id="system-update-dropzone">

                                </div>
                                <div class="form-group mt-4 text-center">
                                    <button type="submit" class="btn btn-primary"
                                        id="system_update_btn"><?= labels('update_the_system', 'Update the system') ?></button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        window.systemUpdateDropzoneText = {
            select_files: @json(labels('admin_labels.select_files', 'Select Files')),
            or: @json(labels('admin_labels.or', 'or')),
            drag_drop_message: @json(labels('admin_labels.drag_drop_system_update_message', "Drag & Drop System Update / Installable / Plugin's .zip file Here")),
        };
    </script>
@endsection
