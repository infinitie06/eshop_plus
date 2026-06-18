@extends('admin/layout')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ labels('admin_labels.image_upload_card_header', 'Image Upload') }}</div>

                    <div class="card-body">

                        <form action="{{ route('image.upload.post') }}" method="POST" enctype="multipart/form-data">
                            @csrf

                            <div class="form-group">
                                <label for="image">{{ labels('admin_labels.select_image_label', 'Select Image') }}</label>
                                <input type="file" name="image" class="form-control" accept="image/*" required>
                            </div>

                            <button type="submit" class="btn btn-primary">{{ labels('admin_labels.upload_option', 'Upload') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
