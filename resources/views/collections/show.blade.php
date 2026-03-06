@extends('layouts.tri')

@push('social-meta')
    <meta property="og:description" content="{{ Str::limit($collection->description, 100, '...') }}">
    @if($collection->coverInfo()->exists())
        <meta property="og:image" content="{{ $collection->coverInfo()->getUrl() }}">
    @endif
@endpush

@include('entities.body-tag-classes', ['entity' => $collection])

@section('body')

    <div class="mb-s print-hidden">
        @include('entities.breadcrumbs', ['crumbs' => [
            $collection,
        ]])
    </div>

    <main class="card content-wrap">

        <div class="flex-container-row wrap v-center">
            <h1 class="flex fit-content break-text">{{ $collection->name }}</h1>
            <div class="flex"></div>
            <div class="flex fit-content text-m-right my-m ml-m">
                @include('common.sort', $listOptions->getSortControlData())
            </div>
        </div>

        <div class="book-content">
            <div class="text-muted break-text">{!! $collection->descriptionInfo()->getHtml() !!}</div>
            @if(count($sortedVisibleBooks) > 0)
                @if($view === 'list')
                    <div class="entity-list">
                        @foreach($sortedVisibleBooks as $book)
                            @include('books.parts.list-item', ['book' => $book])
                        @endforeach
                    </div>
                @else
                    <div class="grid third">
                        @foreach($sortedVisibleBooks as $book)
                            @include('entities.grid-item', ['entity' => $book])
                        @endforeach
                    </div>
                @endif
            @else
                <div class="mt-xl">
                    <hr>
                    <p class="text-muted italic mt-xl mb-m">{{ trans('entities.collections_empty_contents') }}</p>
                    <div class="icon-list inline block">
                        @if(userCan(\BookStack\Permissions\Permission::CollectionUpdate, $collection))
                            <a href="{{ $collection->getUrl('/edit') }}" class="icon-list-item text-book">
                                <span class="icon">@icon('edit')</span>
                                <span>{{ trans('entities.collections_edit') }}</span>
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </main>

@stop

@section('left')
    @include('shelves.parts.show-sidebar-section-tags', ['shelf' => $collection])
    @include('shelves.parts.show-sidebar-section-details', ['shelf' => $collection])
@stop

@section('right')
    <div id="actions" class="actions mb-xl">
        <h5>{{ trans('common.actions') }}</h5>
        <div class="icon-list text-link">

            @include('entities.view-toggle', ['view' => $view, 'type' => 'collection'])

            <hr class="primary-background">

            @if(userCan(\BookStack\Permissions\Permission::CollectionUpdate, $collection))
                <a href="{{ $collection->getUrl('/edit') }}" data-shortcut="edit" class="icon-list-item">
                    <span>@icon('edit')</span>
                    <span>{{ trans('common.edit') }}</span>
                </a>
            @endif

            @if(userCan(\BookStack\Permissions\Permission::CollectionDelete, $collection))
                <a href="{{ $collection->getUrl('/delete') }}" data-shortcut="delete" class="icon-list-item">
                    <span>@icon('delete')</span>
                    <span>{{ trans('common.delete') }}</span>
                </a>
            @endif

            @if(!user()->isGuest())
                <hr class="primary-background">
                @include('entities.favourite-action', ['entity' => $collection])
            @endif

        </div>
    </div>
@stop
