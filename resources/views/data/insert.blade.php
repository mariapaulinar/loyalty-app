@php
    $pageTitle = $settings['overrideTitle'] ?? trans('common.add_item') . config('default.page_title_delimiter') . $settings['title'];
@endphp
@extends($settings['guard'] . '.layouts.default')
@section('page_title', $pageTitle . config('default.page_title_delimiter') . config('default.app_name'))
@section('content')
    <div class="w-full max-w-7xl mx-auto px-4 md:px-6 py-6 md:py-8" @onclicktab="window.appSetImageUploadHeight()">
        
        {{-- Page Header --}}
        <x-ui.page-header
            :icon="$settings['icon']"
            :title="$settings['overrideTitle'] ?? $settings['title']"
            :description="!$settings['overrideTitle'] ? trans('common.add_item') : null"
        >
            <x-slot name="actions">
                @if($settings['list'])
                    <a href="{{ route($settings['guard'].'.data.list', ['name' => $dataDefinition->name]) }}"
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
            </x-slot>
        </x-ui.page-header>

        @php $hasTabs = !empty($form['tabs']); @endphp
        
        {{-- Form Content --}}
        <div class="space-y-6">
            <x-forms.messages />
            <x-forms.form-open 
                :novalidate="$hasTabs"
                action="{{ route($settings['guard'].'.data.insert.post', ['name' => $dataDefinition->name]) }}"
                enctype="multipart/form-data"
                id="formDataDefinition"
                method="POST"
                class="space-y-6"
            />
            <input type="hidden" id="meta-data-guard" value="{{ $settings['guard'] }}" />
            <input type="hidden" id="meta-data-name" value="{{ $dataDefinition->name }}" />
            <input type="hidden" id="meta-data-view" value="insert" />
            
            @if ($form['columns'])
                @if($hasTabs)
                    {{-- Tabs at Page Level (NO card wrapper!) --}}
                    <x-ui.tabs :tabs="array_values($form['tabs'])" active-tab="1">
                            @php $previousTab = null; @endphp
                            @foreach ($form['columns'] as $column)
                                @if (!$column['hidden'])
                                    @if($column['tab'] && $column['tab'] !== $previousTab)
                                        @if($previousTab !== null)
                                            </div>
                                            </x-slot>
                                        @endif
                                        <x-slot :name="$column['tab']">
                                            <div class="space-y-6">
                                    @endif
                                    @if($column['container_start::insert'])
                                        <div class="{{ $column['container_start::insert'] }}">
                                    @endif
                                    @if($column['classes::insert'])
                                        <div class="{{ $column['classes::insert'] }}">
                                    @endif
                                    @if(!empty($column['conditional']))
                                        <div class="conditional-field" 
                                             data-condition-field="{{ $column['conditional']['field'] }}" 
                                             data-condition-values="{{ implode(',', $column['conditional']['values']) }}"
                                             @if(!in_array($form['data']->{$column['conditional']['field']} ?? ($form['columns'][$column['conditional']['field']]['default'] ?? ''), $column['conditional']['values'])) style="display: none;" @endif>
                                    @endif
                                    @include('data.form', compact('form', 'column'))
                                    @if(!empty($column['conditional']))
                                        </div>
                                    @endif
                                    @if($column['classes::insert'])
                                        </div>
                                    @endif
                                    @if($column['container_end::insert'])
                                        </div>
                                    @endif
                                    @php $previousTab = $column['tab']; @endphp
                                @endif
                            @endforeach
                                </div>
                            </x-slot>
                    </x-ui.tabs>
                @else
                    {{-- Non-tabbed form: Wrap in clean card --}}
                    <div class="bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-800 shadow-sm p-6">
                        <div class="space-y-6">
                            @foreach ($form['columns'] as $column)
                                @if (!$column['hidden'])
                                    @if($column['container_start::insert'])
                                        <div class="{{ $column['container_start::insert'] }}">
                                    @endif
                                    @if($column['classes::insert'])
                                        <div class="{{ $column['classes::insert'] }}">
                                    @endif
                                    @if(!empty($column['conditional']))
                                        <div class="conditional-field" 
                                             data-condition-field="{{ $column['conditional']['field'] }}" 
                                             data-condition-values="{{ implode(',', $column['conditional']['values']) }}"
                                             @if(!in_array($form['data']->{$column['conditional']['field']} ?? ($form['columns'][$column['conditional']['field']]['default'] ?? ''), $column['conditional']['values'])) style="display: none;" @endif>
                                    @endif
                                    @include('data.form', compact('form', 'column'))
                                    @if(!empty($column['conditional']))
                                        </div>
                                    @endif
                                    @if($column['classes::insert'])
                                        </div>
                                    @endif
                                    @if($column['container_end::insert'])
                                        </div>
                                    @endif
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Form Actions --}}
                <div class="bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-800 shadow-sm p-6">
                    <div class="flex flex-col items-center justify-center gap-3 sm:flex-row">
                        <button type="submit" 
                                class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3
                                       text-sm font-medium text-white 
                                       bg-primary-600 hover:bg-primary-500
                                       rounded-xl shadow-sm hover:shadow-md
                                       focus:outline-none focus:ring-2 focus:ring-primary-500/20
                                       transition-all duration-200 active:scale-[0.98]">
                            {{ trans('common.create') }}
                            <span class="form-dirty hidden">&nbsp;•</span>
                        </button>
                        @if($settings['list'])
                            <a href="{{ route($settings['guard'].'.data.list', ['name' => $dataDefinition->name]) }}"
                               class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3
                                      text-sm font-medium text-secondary-700 dark:text-secondary-300 
                                      bg-white dark:bg-secondary-800 
                                      border border-secondary-200 dark:border-secondary-700 
                                      rounded-xl shadow-sm
                                      hover:bg-secondary-50 dark:hover:bg-secondary-700 
                                      hover:border-secondary-300 dark:hover:border-secondary-600
                                      focus:outline-none focus:ring-2 focus:ring-primary-500/20
                                      transition-colors duration-200">
                                {{ trans('common.cancel') }}
                            </a>
                    @endif
                </div>
            </div>
        @endif
        <x-forms.form-close />

        {{-- Custom JavaScript from DataDefinition settings --}}
        @php
            $customJs = null;
            if (!empty($settings['js'])) {
                if (is_string($settings['js'])) {
                    // String = applies to all views
                    $customJs = $settings['js'];
                } elseif (is_array($settings['js'])) {
                    // Check for exact match or comma-separated keys containing 'insert'
                    foreach ($settings['js'] as $views => $code) {
                        $viewList = array_map('trim', explode(',', $views));
                        if (in_array('insert', $viewList) || in_array('all', $viewList)) {
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

        @if (session('current_tab_index') && $hasTabs)
            <script>
                document.addEventListener("DOMContentLoaded", () => {
                    window.openTab({{ session('current_tab_index') }});
                });
            </script>
        @endif
        @if ($errors->any())
            <script>
                document.addEventListener("DOMContentLoaded", () => {
                    window.openTabWithInvalidElement();
                });
            </script>
        @endif
    </div>
@stop