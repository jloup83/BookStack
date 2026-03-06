@extends('layouts.simple')

@section('body')

    <div class="container small">

        <div class="my-s">
            @include('entities.breadcrumbs', ['crumbs' => [
                $collection,
                $collection->getUrl('/edit') => [
                    'text' => trans('entities.collections_edit'),
                    'icon' => 'edit',
                ]
            ]])
        </div>

        <main class="card content-wrap">
            <h1 class="list-heading">{{ trans('entities.collections_edit') }}</h1>
            <form action="{{ $collection->getUrl() }}" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="_method" value="PUT">
                @include('collections.parts.form', ['model' => $collection])
            </form>
        </main>
    </div>

@stop
