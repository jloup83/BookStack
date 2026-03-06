@extends('layouts.simple')

@section('body')
    <div class="container small">
        <div class="my-s">
            @if (isset($bookshelf))
                @include('entities.breadcrumbs', ['crumbs' => [
                    $bookshelf,
                    $bookshelf->getUrl('/create-collection') => [
                        'text' => trans('entities.collections_create'),
                        'icon' => 'add'
                    ]
                ]])
            @else
                @include('entities.breadcrumbs', ['crumbs' => [
                    '/collections' => [
                        'text' => trans('entities.collections'),
                        'icon' => 'books'
                    ],
                    '/create-collection' => [
                        'text' => trans('entities.collections_create'),
                        'icon' => 'add'
                    ]
                ]])
            @endif
        </div>

        <main class="content-wrap card">
            <h1 class="list-heading">{{ trans('entities.collections_create') }}</h1>
            <form action="{{ $bookshelf?->getUrl('/create-collection') ?? url('/collections') }}" method="POST" enctype="multipart/form-data">
                @include('collections.parts.form', [
                    'returnLocation' => $bookshelf?->getUrl() ?? url('/collections')
                ])
            </form>
        </main>
    </div>

@stop
