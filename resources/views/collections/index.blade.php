@extends('layouts.tri')

@section('body')
    <main class="content-wrap mt-m card">

        <div class="grid half v-center">
            <h1 class="list-heading">{{ trans('entities.collections') }}</h1>
            <div class="text-right">
                @include('common.sort', $listOptions->getSortControlData())
            </div>
        </div>

        @if(count($collections) > 0)
            @if($view === 'list')
                <div class="entity-list">
                    @foreach($collections as $index => $collection)
                        @if ($index !== 0)
                            <hr class="my-m">
                        @endif
                        @include('entities.list-item', ['entity' => $collection])
                    @endforeach
                </div>
            @else
                <div class="grid third">
                    @foreach($collections as $collection)
                        @include('entities.grid-item', ['entity' => $collection])
                    @endforeach
                </div>
            @endif
            <div>
                {!! $collections->render() !!}
            </div>
        @else
            <p class="text-muted">{{ trans('entities.collections_empty_contents') }}</p>
            @if(userCan(\BookStack\Permissions\Permission::CollectionCreateAll))
                <div class="icon-list block inline">
                    <a href="{{ url("/create-collection") }}"
                       class="icon-list-item text-book">
                        <span>@icon('add')</span>
                        <span>{{ trans('entities.create_now') }}</span>
                    </a>
                </div>
            @endif
        @endif

    </main>
@stop

@section('right')
    <div class="actions mb-xl">
        <h5>{{ trans('common.actions') }}</h5>
        <div class="icon-list text-link">
            @if(userCan(\BookStack\Permissions\Permission::CollectionCreateAll))
                <a href="{{ url("/create-collection") }}" data-shortcut="new" class="icon-list-item">
                    <span>@icon('add')</span>
                    <span>{{ trans('entities.collections_new_action') }}</span>
                </a>
            @endif

            @include('entities.view-toggle', ['view' => $view, 'type' => 'collections'])
        </div>
    </div>
@stop
