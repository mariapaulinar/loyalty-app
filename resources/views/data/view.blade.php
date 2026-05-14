@php
    if ($settings['overrideTitle']) {
        $pageTitle = $settings['overrideTitle'];
    } else {
        $pageTitle = trans('common.view_item_', ['item' => $settings['subject_column'] ? parse_attr($form['data']->{$settings['subject_column']}) : trans('common.item')]) .
            config('default.page_title_delimiter') . $settings['title'];
    }
@endphp
@extends($settings['guard'] . '.layouts.default')
@section('page_title', $pageTitle . config('default.page_title_delimiter') . config('default.app_name'))
@section('content')
<div class="w-full max-w-7xl mx-auto px-4 md:px-6 py-6 md:py-8">
    
    {{-- Page Header --}}
    <x-ui.page-header
        :icon="$settings['icon']"
        :title="$settings['overrideTitle'] ?? $settings['title']"
        :description="!$settings['overrideTitle'] ? trans('common.view_item_', ['item' => $settings['subject_column'] ? parse_attr($form['data']->{$settings['subject_column']}) : trans('common.item')]) : null"
    >
        <x-slot name="actions">
            @if($settings['list'])
                <a href="{{ route($settings['guard'] . '.data.list', ['name' => $dataDefinition->name]) }}{{ request()->has('batch_id') ? '?batch_id='.request('batch_id') : '' }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 
                          text-sm font-medium text-secondary-700 dark:text-secondary-300 
                          bg-white dark:bg-secondary-800 
                          border border-secondary-200 dark:border-secondary-700 
                          rounded-xl shadow-sm
                          hover:bg-secondary-50 dark:hover:bg-secondary-700 
                          hover:border-secondary-300 dark:hover:border-secondary-600
                          focus:outline-none focus:ring-2 focus:ring-primary-500/20
                          transition-colors duration-200">
                    <x-ui.icon icon="arrow-left" class="w-4 h-4" />
                    <span class="hidden sm:inline">{{ trans('common.back_to_list') }}</span>
                </a>
            @endif
            @if ($settings['edit'])
                <a href="{{ route($settings['guard'] . '.data.edit', ['name' => $dataDefinition->name, 'id' => $form['data']->id]) }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 
                          text-sm font-medium text-amber-600 dark:text-amber-400 
                          bg-amber-50 dark:bg-amber-500/10 
                          border border-amber-200 dark:border-amber-500/30 
                          rounded-xl shadow-sm
                          hover:bg-amber-100 dark:hover:bg-amber-500/20 
                          hover:border-amber-300 dark:hover:border-amber-500/50
                          focus:outline-none focus:ring-2 focus:ring-amber-500/20
                          transition-all duration-200">
                    <x-ui.icon class="w-4 h-4" icon="pencil" />
                    <span class="hidden sm:inline">{{ trans('common.edit') }}</span>
                </a>
            @endif
            @if ($settings['delete'])
                <button type="button" 
                        class="inline-flex items-center gap-2 px-4 py-2.5 
                               text-sm font-medium text-red-600 dark:text-red-400 
                               bg-red-50 dark:bg-red-500/10 
                               border border-red-200 dark:border-red-500/30 
                               rounded-xl shadow-sm
                               hover:bg-red-100 dark:hover:bg-red-500/20 
                               hover:border-red-300 dark:hover:border-red-500/50
                               focus:outline-none focus:ring-2 focus:ring-red-500/20
                               transition-all duration-200"
                        @click="deleteItem('{{ $form['data']->id }}', '{{ $settings['subject_column'] ? str_replace("'", "\'", parse_attr($form['data']->{$settings['subject_column']})) : null }}')">
                    <x-ui.icon class="w-4 h-4" icon="trash" />
                    <span class="hidden sm:inline">{{ trans('common.delete') }}</span>
                </button>
            @endif
        </x-slot>
    </x-ui.page-header>

    {{-- Content Card --}}
    <div class="bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-800 shadow-sm p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @if ($form['columns'])
                @foreach ($form['columns'] as $column)
                    @if (!$column['hidden'])
                        @if($column['container_start::view'])
                            <div class="{{ $column['container_start::view'] }}">
                        @endif
                        @if($column['classes::view'])
                            <div class="{{ $column['classes::view'] }}">
                        @endif
                        
                        <div class="bg-secondary-50 dark:bg-secondary-800 rounded-xl p-4 border border-secondary-100 dark:border-secondary-700 hover:border-secondary-200 dark:hover:border-secondary-600 transition-colors duration-200">
                            <div class="mb-2 text-xs font-medium text-secondary-500 dark:text-secondary-400 uppercase tracking-wide">
                                {{ $column['text'] }}
                            </div>
                            <div class="text-secondary-900 dark:text-white font-medium break-words">
                                @if ($column['type'] == 'image' || $column['type'] == 'avatar')
                                    @if ($form['data']->{$column['name']})
                                        <script>
                                            let imgModalSrc_{{ $column['name'] }} = "{{ $form['data']->{$column['name']} }}";
                                            let imgModalDesc_{{ $column['name'] }} = "{{ parse_attr($column['text']) }}";
                                        </script>
                                        <a @click="$dispatch('img-modal', { imgModalSrc: imgModalSrc_{{ $column['name'] }}, imgModalDesc: imgModalDesc_{{ $column['name'] }} })"
                                           class="cursor-pointer block group relative overflow-hidden rounded-lg">
                                            <img src="{{ $form['data']->{$column['name']} !== null && $column['conversion'] !== null ? $form['data']->{$column['name'] . '-' . $column['conversion']} : $form['data']->{$column['name']} }}"
                                                 alt="{{ parse_attr($column['text']) }}"
                                                 class="w-full max-w-full h-auto max-h-48 object-contain {{ $column['type'] == 'avatar' ? 'rounded-full !w-20 !h-20 !max-h-20' : 'rounded-lg' }} shadow-sm">
                                        </a>
                                    @else
                                        <div class="flex items-center gap-2 text-secondary-400">
                                            <x-ui.icon icon="image-off" class="w-4 h-4" />
                                            <span class="text-sm">{{ trans('common.no_image') }}</span>
                                        </div>
                                    @endif
                                @elseif ($column['type'] == 'boolean')
                                    @php $boolValue = (bool) $form['data']->{$column['name']}; @endphp
                                    @if ($column['format'] == 'icon')
                                        @php
                                            $iconName = $boolValue ? 'check' : 'x';
                                            $colorClasses = $boolValue
                                                ? 'text-green-600 bg-green-100 dark:text-green-400 dark:bg-green-900/50'
                                                : 'text-red-600 bg-red-100 dark:text-red-400 dark:bg-red-900/50';
                                        @endphp
                                        <div class="inline-flex items-center justify-center w-6 h-6 rounded-full {{ $colorClasses }}">
                                            <x-ui.icon :icon="$iconName" class="w-4 h-4" />
                                        </div>
                                    @else
                                        {{ $boolValue ? trans('common.yes') : trans('common.no') }}
                                    @endif
                                @elseif (in_array($column['type'], ['date_time']))
                                    <span class="format-date-time text-secondary-600 dark:text-secondary-300">{!! $form['data']->{$column['name']} !!}</span>
                                @elseif (in_array($column['format'], ['datetime-local']))
                                    <span class="format-date-time-local text-secondary-600 dark:text-secondary-300">{!! $form['data']->{$column['name']} !!}</span>
                                @else
                                    @php
                                        $fieldValue = $form['data']->{$column['name']};
                                        $isNumberColumn = ($column['type'] ?? '') === 'number' || ($column['format'] ?? '') === 'number';

                                        // For number columns, check raw value to detect null converted to 0 by model cast
                                        if ($isNumberColumn && method_exists($form['data'], 'getRawOriginal')) {
                                            $rawValue = $form['data']->getRawOriginal($column['name']);
                                            if ($rawValue === null || $rawValue === '') {
                                                $fieldValue = null;
                                            }
                                        }

                                        // Treat empty-ish values as "show dash"
                                        $isEmpty = $fieldValue === null || $fieldValue === '';
                                    @endphp
                                    @if(!$isEmpty)
                                        {!! $fieldValue !!}
                                    @else
                                        <span class="text-secondary-400 dark:text-secondary-500 text-sm">—</span>
                                    @endif
                                @endif
                            </div>
                        </div>

                        @if($column['classes::view'])
                            </div>
                        @endif
                        @if($column['container_end::view'])
                            </div>
                        @endif
                    @endif
                @endforeach
            @endif
        </div>
    </div>
</div>

@if ($settings['delete'])
    <script>
        function deleteItem(id, item) {
            if (item == null) item = "{{ trans('common.this_item') }}";
            appConfirm('{{ trans('common.confirm_deletion') }}', _lang.delete_confirmation_text.replace(":item",
                '<strong>' + item + '</strong>'), {
                'btnConfirm': {
                    'click': function () {
                        const form = document.getElementById('formDataDefinition');
                        form.action =
                            '{{ route($settings['guard'] . '.data.delete.post', ['name' => $dataDefinition->name]) }}/' + id;
                        form.submit();
                    }
                }
            });
        }
    </script>
@endif

{{-- Custom JavaScript from DataDefinition settings --}}
@php
    $customJs = null;
    if (!empty($settings['js'])) {
        if (is_string($settings['js'])) {
            // String = applies to all views
            $customJs = $settings['js'];
        } elseif (is_array($settings['js'])) {
            // Check for exact match or comma-separated keys containing 'view'
            foreach ($settings['js'] as $views => $code) {
                $viewList = array_map('trim', explode(',', $views));
                if (in_array('view', $viewList) || in_array('all', $viewList)) {
                    $customJs = $code;
                    break;
                }
            }
        }
    }
@endphp
@if ($customJs)
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            {!! $customJs !!}
        });
    </script>
@endif
@stop
