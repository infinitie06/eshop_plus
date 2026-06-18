@extends('admin/layout')
@section('title')
    {{ labels('admin_labels.notification_and_contact_settings', 'Notification & Contact Settings') }}
@endsection
@section('content')
    @php
        $user = auth()->user();
        $role = auth()->user()->role->name;
    @endphp
    <x-admin.breadcrumb :title="labels('admin_labels.notification_and_contact', 'Notification & Contact')" :subtitle="labels(
        'admin_labels.unify_communication_with_notifications_contact_and_about_us',
        'Unify Communication with Notifications, Contact, and About Us',
    )" :breadcrumbs="[
        ['label' => labels('admin_labels.settings', 'Settings'), 'url' => route('settings.index')],
        ['label' => labels('admin_labels.notification_and_contact_settings', 'Notification & Contact Settings')],
    ]" />


    <div class="col-md-12">
        <div class="row">
            <div class="col-md-12 col-xxl-6">
                <div class="card">
                    <div class="card-body">
                        <form id="" action="{{ route('contact_us.store') }}" class="submit_form"
                            enctype="multipart/form-data" method="POST">
                            @csrf
                            <h5 class="mb-3">
                                {{ labels('admin_labels.contact_us', 'Contact Us') }}
                            </h5>
                            <textarea class="form-control" name="contact_us" placeholder="Contact Us" rows="5">{{ isset($contact_us['contact_us']) ? $contact_us['contact_us'] : '' }}</textarea>
                            <div class="d-flex justify-content-end mt-4">
                                <button type="reset" class="btn mx-2 reset_button"
                                    id="">{{ labels('admin_labels.reset', 'Reset') }}</button>
                                <button type="submit"
                                    class="btn btn-primary submit_button">{{ labels('admin_labels.update_settings', 'Update Settings') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-12 col-xxl-6 mt-md-4 mt-xxl-0">
                <div class="card">
                    <div class="card-body">
                        <form id="" action="{{ route('about_us.store') }}" class="submit_form"
                            enctype="multipart/form-data" method="POST">
                            @csrf
                            <h5 class="mb-3">
                                {{ labels('admin_labels.about_us', 'About Us') }}
                            </h5>
                            <textarea class="form-control" name="about_us" placeholder="About Us" rows="5">{{ isset($about_us['about_us']) ? $about_us['about_us'] : '' }}</textarea>
                            <div class="d-flex justify-content-end mt-4">
                                <button type="reset" class="btn mx-2 reset_button"
                                    id="">{{ labels('admin_labels.reset', 'Reset') }}</button>
                                <button type="submit"
                                    class="btn btn-primary submit_button">{{ labels('admin_labels.update_settings', 'Update Settings') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-4">
        <div class="col-md-12 col-xxl-6">
            <div class="card">
                <div class="card-body">
                    <form id="" action="{{ route('notification_settings.store') }}" class="submit_form"
                        enctype="multipart/form-data" method="POST">
                        @csrf
                        <div class="alert alert-warning d-flex align-items-start gap-2">
                            <i class="fas fa-bell-exclamation mt-1 text-warning"></i>
                            <div class="small">
                                <strong>{{ __('Cron required for notifications:') }}</strong>
                                {{ __('Add a cron to call') }} <code>{{ url('/run-queue') }}</code>
                                {{ __('periodically (e.g., every minute) so queued push notifications are processed.') }}
                            </div>
                        </div>
                        <h5 class="mb-3">
                            {{ labels('admin_labels.notification_setting', 'Notification Setting') }}
                        </h5>

                        <label for="firebase_project_id">
                            {{ labels('admin_labels.firebase_project_id', 'Firebase Project ID') }}
                        </label>
                        <input type="text" id="firebase_project_id" class="form-control mt-2" name="firebase_project_id"
                            placeholder='Firebase Project ID'
                            value="<?= isset($firebase_project_id) ? outputEscaping($firebase_project_id) : '' ?>">

                        <label for="service_account_file">
                            {{ labels('admin_labels.service_account_file', 'Service Account File') }}
                            <span class="text-danger fs-12">*({{ labels('admin_labels.only_json_file_is_allowed', 'Only JSON File is allowed') }})</span> :

                        </label>
                        <div class="custom-file-input-wrapper mt-2 d-flex align-items-stretch form-control p-0 overflow-hidden">
                            <label for="service_account_file"
                                class="btn btn-light border-0 mb-0 px-3 d-flex align-items-center"
                                style="cursor: pointer; border-radius: 0;">
                                {{ labels('admin_labels.choose_file', 'Choose file') }}
                            </label>
                            <span id="service_account_file_name"
                                class="flex-grow-1 d-flex align-items-center px-3 text-muted text-truncate">
                                {{ labels('admin_labels.no_file_chosen', 'No file chosen') }}
                            </span>
                        </div>
                        <input type="file" name="service_account_file" id="service_account_file"
                            class="d-none" accept=".json"
                            onchange="document.getElementById('service_account_file_name').textContent = this.files[0] ? this.files[0].name : '{{ labels('admin_labels.no_file_chosen', 'No file chosen') }}';">
                        <div class="d-flex justify-content-end mt-4">
                            <button type="reset" class="btn mx-2 reset_button"
                                id="">{{ labels('admin_labels.reset', 'Reset') }}</button>
                            <button type="submit"
                                class="btn btn-primary submit_button">{{ labels('admin_labels.update_settings', 'Update Settings') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
