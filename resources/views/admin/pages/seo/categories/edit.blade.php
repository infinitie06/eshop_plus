@extends('admin.layout')

@section('title')
    Edit Category SEO - {{ $category->name }}
@endsection

@section('content')
    <x-admin.breadcrumb title="Edit Category SEO" subtitle="Configure SEO settings for this category" :breadcrumbs="[
        ['label' => 'SEO', 'url' => route('admin.seo.index')],
        ['label' => 'Category SEO', 'url' => route('admin.seo.categories')],
        ['label' => 'Edit'],
    ]" />

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Edit SEO for: {{ $category->name }}</h4>
                    </div>
                    <div class="card-body">

                        <form action="{{ route('admin.seo.categories.update', $category->id) }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            @include('admin.pages.seo.partials.seo-form', [
                                'seo' => $seo,
                                'defaultTitle' => $category->name,
                                'defaultDescription' => strip_tags($category->description ?? ''),
                                'backRoute' => route('admin.seo.categories'),
                            ])
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
