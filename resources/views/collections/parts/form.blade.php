{{ csrf_field() }}
<div class="form-group title-input">
    <label for="name">{{ trans('common.name') }}</label>
    @include('form.text', ['name' => 'name', 'autofocus' => true])
</div>

<div class="form-group description-input">
    <label for="description_html">{{ trans('common.description') }}</label>
    @include('form.description-html-input')
</div>

<div class="form-group collapsible" component="collapsible" id="logo-control">
    <button refs="collapsible@trigger" type="button" class="collapse-title text-link" aria-expanded="false">
        <label>{{ trans('common.cover_image') }}</label>
    </button>
    <div refs="collapsible@content" class="collapse-content">
        <p class="small">{{ trans('common.cover_image_description') }}</p>

        @include('form.image-picker', [
            'defaultImage' => url('/book_default_cover.png'),
            'currentImage' => url('/book_default_cover.png'),
            'name' => 'image',
            'imageClass' => 'cover'
        ])
    </div>
</div>

<div class="form-group collapsible" component="collapsible" id="tags-control">
    <button refs="collapsible@trigger" type="button" class="collapse-title text-link" aria-expanded="false">
        <label for="tag-manager">{{ trans('entities.collections_tags') }}</label>
    </button>
    <div refs="collapsible@content" class="collapse-content">
        @include('entities.tag-manager', ['entity' => null])
    </div>
</div>

<div class="form-group text-right">
    <a href="{{ $returnLocation }}" class="button outline">{{ trans('common.cancel') }}</a>
    <a href="{{ $returnLocation }}" class="button">{{ trans('entities.collections_save') }}</a>
</div>
