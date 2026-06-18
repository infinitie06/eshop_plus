@extends('admin.layout')

@section('title')
    Edit Blog SEO - {{ $blog->title }}
@endsection

@section('content')
    <x-admin.breadcrumb title="Edit Blog SEO" subtitle="Configure SEO settings for this blog" :breadcrumbs="[
        ['label' => 'SEO', 'url' => route('admin.seo.index')],
        ['label' => 'Blog SEO', 'url' => route('admin.seo.blogs')],
        ['label' => 'Edit'],
    ]" />

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Edit SEO for: {{ $blog->title }}</h4>
                    </div>
                    <div class="card-body">

                        <form action="{{ route('admin.seo.blogs.update', $blog->id) }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            @include('admin.pages.seo.partials.seo-form', [
                                'seo' => $seo,
                                'defaultTitle' => $blog->title,
                                'defaultDescription' => strip_tags($blog->description ?? ''),
                                'backRoute' => route('admin.seo.blogs'),
                            ])
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
