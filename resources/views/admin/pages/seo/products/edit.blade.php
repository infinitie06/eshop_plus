@extends('admin.layout')

@section('title')
    Edit Product SEO - {{ $product->name }}
@endsection

@section('content')
    <x-admin.breadcrumb title="Edit Product SEO" subtitle="Configure SEO settings for this product" :breadcrumbs="[
        ['label' => 'SEO', 'url' => route('admin.seo.index')],
        ['label' => 'Product SEO', 'url' => route('admin.seo.products')],
        ['label' => 'Edit'],
    ]" />

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Edit SEO for: {{ $product->name }}</h4>
                    </div>
                    <div class="card-body">

                        <form action="{{ route('admin.seo.products.update', $product->id) }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            @include('admin.pages.seo.partials.seo-form', [
                                'seo' => $seo,
                                'defaultTitle' => $product->name,
                                'defaultDescription' => strip_tags($product->description ?? ''),
                                'backRoute' => route('admin.seo.products'),
                            ])
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
