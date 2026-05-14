@php
$pageTitle = $settings['overrideTitle'] ?? $settings['title'];
@endphp
@extends($settings['guard'].'.layouts.default')
@section('page_title', $pageTitle . config('default.page_title_delimiter') . config('default.app_name'))
@section('content')
    <div class="w-full max-w-7xl mx-auto px-4 md:px-6 py-6 md:py-8">
        
        {{-- Page Header --}}
        <x-ui.page-header
            :icon="$settings['icon']"
            :title="$pageTitle"
            :badge="trans('common.number_results', ['number' => $tableData['data']->total()])"
        >
            <x-slot name="actions">
                @if($settings['search'])
                    <div class="w-full sm:w-auto min-w-[280px]">
                        <form id="data-list-search-form"
                              class="flex items-center"
                              method="GET"
                              action="{{ route($settings['guard'].'.data.list', ['name' => $dataDefinition->name]) }}">
                            @foreach(request()->except(['search', 'page']) as $key => $value)
                                @if(is_array($value))
                                    @foreach($value as $subKey => $subValue)
                                        <input type="hidden" name="{{ $key }}[{{ $subKey }}]" value="{{ $subValue }}">
                                    @endforeach
                                @else
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endif
                            @endforeach
                            <label for="tableDataDefinition-search" class="sr-only">{{ trans('common.search') }}</label>
                            <div class="relative w-full group"
                                 x-data="dataListSearchAutocomplete({
                                    suggestUrl: '{{ route($settings['guard'].'.data.suggest', ['name' => $dataDefinition->name]) }}',
                                    initialQuery: @js((string) request()->get('search', '')),
                                    formId: 'data-list-search-form'
                                 })"
                                 @click.outside="close()"
                                 @keydown.escape.stop="close()">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                                    <x-ui.icon icon="search" class="w-4 h-4 text-secondary-400 group-focus-within:text-primary-500 transition-colors" />
                                </div>
                                <input type="search"
                                    x-ref="input"
                                    x-model="query"
                                    @input="onInput()"
                                    @focus="onFocus()"
                                    @keydown.down.prevent="move(1)"
                                    @keydown.up.prevent="move(-1)"
                                    @keydown.enter.prevent="submitIfReady()"
                                    @keydown.tab="close()"
                                    autocomplete="off"
                                    name="search"
                                    id="tableDataDefinition-search"
                                    class="w-full bg-white dark:bg-secondary-800 
                                           border border-secondary-200 dark:border-secondary-700 
                                           text-secondary-900 dark:text-white text-sm 
                                           rounded-xl pl-10 pr-4 py-2.5
                                           shadow-sm
                                           transition-colors duration-200 
                                           placeholder:text-secondary-400 dark:placeholder:text-secondary-500 
                                           focus:outline-none focus:bg-white dark:focus:bg-secondary-800
                                           focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 
                                           hover:border-secondary-300 dark:hover:border-secondary-600"
                                    placeholder="{{ trans('common.search') }}"
                                    value="{{ request()->get('search') }}">

                                {{-- Autocomplete dropdown --}}
                                <div x-show="open" x-cloak
                                     x-transition:enter="transition ease-out duration-150"
                                     x-transition:enter-start="opacity-0 -translate-y-1"
                                     x-transition:enter-end="opacity-100 translate-y-0"
                                     x-transition:leave="transition ease-in duration-100"
                                     x-transition:leave-start="opacity-100 translate-y-0"
                                     x-transition:leave-end="opacity-0 -translate-y-1"
                                     class="absolute z-20 mt-2 w-full min-w-[24rem] overflow-hidden rounded-xl border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 shadow-lg">
                                    <div class="px-3 py-2 border-b border-secondary-100 dark:border-secondary-800 bg-secondary-50/70 dark:bg-secondary-800/40">
                                        <div class="flex flex-col gap-1">
                                            <div class="text-xs text-secondary-500 dark:text-secondary-400">
                                                <span class="font-medium">{{ trans('common.navigate') }}</span> ↑ ↓
                                                <span class="mx-1 text-secondary-300 dark:text-secondary-600">•</span>
                                                <span class="font-medium">{{ trans('common.select') }}</span> ↵
                                                <span class="mx-1 text-secondary-300 dark:text-secondary-600">•</span>
                                                <span class="font-medium">{{ trans('common.to_clear') }}</span> ⌫
                                            </div>
                                        </div>
                                    </div>

                                    <template x-if="query.trim().length < 2">
                                        <div class="px-4 py-4 text-sm text-secondary-500 dark:text-secondary-400">
                                            {{ trans('common.type_to_search') ?? 'Type at least 2 characters to search…' }}
                                        </div>
                                    </template>

                                    <template x-if="query.trim().length >= 2 && loading">
                                        <div class="px-4 py-4 text-sm text-secondary-500 dark:text-secondary-400 flex items-center gap-2">
                                            <svg class="h-4 w-4 animate-spin text-secondary-400 dark:text-secondary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <span>{{ trans('common.loading') ?? 'Loading…' }}</span>
                                        </div>
                                    </template>

                                    <template x-if="query.trim().length >= 2 && !loading && suggestions.length === 0">
                                        <div class="px-4 py-4 text-sm text-secondary-500 dark:text-secondary-400">
                                            {{ trans('common.no_search_results') }}
                                        </div>
                                    </template>

                                    <div class="max-h-72 overflow-auto custom-scrollbar" x-show="suggestions.length > 0">
                                        <template x-for="(item, idx) in suggestions" :key="item.id">
                                            <button type="button"
                                                    class="w-full text-left px-4 py-2.5 flex items-center gap-3 transition-colors"
                                                    :class="idx === activeIndex
                                                        ? 'bg-primary-50 dark:bg-primary-500/10 text-secondary-900 dark:text-white'
                                                        : 'bg-white dark:bg-secondary-900 text-secondary-700 dark:text-secondary-200 hover:bg-secondary-50 dark:hover:bg-secondary-800/50'"
                                                    @mouseenter="activeIndex = idx"
                                                    @click="choose(item)">
                                                <span class="flex-1 min-w-0">
                                                    <span class="block text-sm font-medium leading-snug"
                                                          x-text="item.label.split(' · ')[0]"></span>
                                                    <template x-if="item.label.includes(' · ')">
                                                        <span class="block text-xs text-secondary-500 dark:text-secondary-400 leading-snug mt-0.5"
                                                              x-text="item.label.split(' · ').slice(1).join(' · ')"></span>
                                                    </template>
                                                </span>
                                                <x-ui.icon icon="arrow-right" class="w-3.5 h-3.5 shrink-0 text-secondary-400" />
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                @endif
                @if (isset($settings['customLink']) && $settings['customLink'])
                    @php
                        $linkVariant = $settings['customLink']['variant'] ?? 'default';
                    @endphp
                    <a href="{{ $settings['customLink']['url'] }}"
                        class="inline-flex items-center gap-2 px-4 py-2.5 
                               text-sm font-medium rounded-xl shadow-sm
                               focus:outline-none focus:ring-2 transition-colors duration-200
                               @if($linkVariant === 'danger')
                                   text-red-600 dark:text-red-400 
                                   bg-red-50 dark:bg-red-500/10 
                                   border border-red-200 dark:border-red-500/30 
                                   hover:bg-red-100 dark:hover:bg-red-500/20 
                                   hover:border-red-300 dark:hover:border-red-500/50
                                   focus:ring-red-500/20
                               @elseif($linkVariant === 'warning')
                                   text-amber-600 dark:text-amber-400 
                                   bg-amber-50 dark:bg-amber-500/10 
                                   border border-amber-200 dark:border-amber-500/30 
                                   hover:bg-amber-100 dark:hover:bg-amber-500/20 
                                   hover:border-amber-300 dark:hover:border-amber-500/50
                                   focus:ring-amber-500/20
                               @else
                                   text-secondary-700 dark:text-secondary-300 
                                   bg-white dark:bg-secondary-800 
                                   border border-secondary-200 dark:border-secondary-700 
                                   hover:bg-secondary-50 dark:hover:bg-secondary-700 
                                   hover:border-secondary-300 dark:hover:border-secondary-600
                                   focus:ring-primary-500/20
                               @endif">
                        <x-ui.icon :icon="$settings['customLink']['icon'] ?? 'plus'" class="w-4 h-4" />
                        <span class="hidden sm:inline">{{ $settings['customLink']['label'] }}</span>
                    </a>
                @endif
                @if ($settings['insert'])
                    <a href="{{ route($settings['guard'].'.data.insert', ['name' => $dataDefinition->name]) }}"
                        class="inline-flex items-center gap-2 px-4 py-2.5 
                               text-sm font-medium text-white 
                               bg-primary-600 hover:bg-primary-500
                               rounded-xl shadow-sm hover:shadow-md
                               focus:outline-none focus:ring-2 focus:ring-primary-500/20
                               transition-all duration-200 active:scale-[0.98]">
                        <x-ui.icon icon="plus" class="w-4 h-4" />
                        <span class="hidden sm:inline">{{ trans('common.add_new_item') }}</span>
                    </a>
                @endif
                @if ($settings['export'])
                    @php
                        $exportBaseQuery = request()->except('page');
                    @endphp
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <button type="button"
                                @click="open = !open"
                                class="inline-flex items-center gap-2 px-4 py-2.5 
                                       text-sm font-medium text-secondary-700 dark:text-secondary-300 
                                       bg-white dark:bg-secondary-800 
                                       border border-secondary-200 dark:border-secondary-700 
                                       rounded-xl shadow-sm
                                       hover:bg-secondary-50 dark:hover:bg-secondary-700 
                                       hover:border-secondary-300 dark:hover:border-secondary-600
                                       focus:outline-none focus:ring-2 focus:ring-primary-500/20
                                       transition-colors duration-200">
                            <x-ui.icon icon="download" class="w-4 h-4" />
                            <span class="hidden sm:inline">{{ trans('common.export') }}</span>
                            <x-ui.icon icon="chevron-down" class="w-4 h-4 text-secondary-400" />
                        </button>

                        <div x-show="open" x-cloak
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 -translate-y-1"
                             class="absolute right-0 mt-2 w-56 overflow-hidden rounded-xl border border-secondary-200 dark:border-secondary-700 bg-white dark:bg-secondary-900 shadow-lg z-30">
                            <div class="px-4 py-2 text-xs font-medium uppercase tracking-wide text-secondary-500 dark:text-secondary-400 bg-secondary-50 dark:bg-secondary-800/40 border-b border-secondary-100 dark:border-secondary-800">
                                {{ trans('common.export_as') ?? trans('common.export') }}
                            </div>
                            <div class="p-2">
                                <a href="{{ route($settings['guard'].'.data.export', ['name' => $dataDefinition->name]) . '?' . http_build_query(array_merge($exportBaseQuery, ['format' => 'csv'])) }}"
                                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-secondary-700 dark:text-secondary-200 hover:bg-secondary-50 dark:hover:bg-secondary-800/60 transition-colors">
                                    <x-ui.icon icon="file-text" class="w-4 h-4 text-secondary-400" />
                                    <span>{{ trans('common.export_csv') ?? 'CSV' }}</span>
                                </a>
                                <a href="{{ route($settings['guard'].'.data.export', ['name' => $dataDefinition->name]) . '?' . http_build_query(array_merge($exportBaseQuery, ['format' => 'tsv'])) }}"
                                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-secondary-700 dark:text-secondary-200 hover:bg-secondary-50 dark:hover:bg-secondary-800/60 transition-colors">
                                    <x-ui.icon icon="file" class="w-4 h-4 text-secondary-400" />
                                    <span>{{ trans('common.export_tsv') ?? 'TSV' }}</span>
                                </a>
                                <a href="{{ route($settings['guard'].'.data.export', ['name' => $dataDefinition->name]) . '?' . http_build_query(array_merge($exportBaseQuery, ['format' => 'json'])) }}"
                                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-secondary-700 dark:text-secondary-200 hover:bg-secondary-50 dark:hover:bg-secondary-800/60 transition-colors">
                                    <x-ui.icon icon="code" class="w-4 h-4 text-secondary-400" />
                                    <span>{{ trans('common.export_json') ?? 'JSON' }}</span>
                                </a>
                            </div>
                        </div>
                    </div>
                @endif
            </x-slot>
        </x-ui.page-header>

        {{-- Table Card --}}
        <div class="relative bg-white dark:bg-secondary-900 border border-secondary-200 dark:border-secondary-800 rounded-xl overflow-hidden"
            @if ($settings['multiSelect']) 
                x-data="{
                    selectAll: false,
                    selected: [],
                    toggleAllCheckboxes() {
                        event.stopPropagation();
                        this.selectAll = !this.selectAll;
                        const checkboxes = document.querySelectorAll('#tableDataDefinition input[type=checkbox]:not(#checkbox-all)');
                        checkboxes.forEach((checkbox, index) => {
                            this.selected[index] = this.selectAll;
                        });
                    },
                    anySelected() {
                        return this.selected.some(item => item);
                    }
                }" 
            @endif>

            {{-- Help Content Accordion --}}
            @if(isset($settings['helpContent']) && $settings['helpContent'])
                @php
                    $helpCookieName = 'help_dismissed_' . $dataDefinition->name;
                    $helpDismissed = request()->cookie($helpCookieName);
                @endphp
                <div x-data="{ 
                        open: {{ $helpDismissed ? 'false' : 'true' }},
                        dismissHelp() {
                            this.open = false;
                            document.cookie = '{{ $helpCookieName }}=1; path=/; max-age=' + (365 * 24 * 60 * 60);
                        },
                        showHelp() {
                            this.open = true;
                            document.cookie = '{{ $helpCookieName }}=; path=/; max-age=0';
                        }
                     }"
                     class="border-b border-secondary-200 dark:border-secondary-800">
                    
                    {{-- Collapsed state --}}
                    <div x-show="!open" x-cloak
                         class="px-6 py-2 bg-secondary-50 dark:bg-secondary-800">
                        <button @click="showHelp()" 
                                class="text-sm text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 font-medium flex items-center gap-1.5 transition-colors">
                            <x-ui.icon icon="help-circle" class="w-4 h-4" />
                            {{ $settings['helpContent']['title'] ?? trans('common.help_getting_started') }}
                        </button>
                    </div>

                    {{-- Expanded state --}}
                    <div x-show="open" 
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="px-6 py-5 bg-primary-50/50 dark:bg-primary-500/5">
                        
                        <div class="flex items-start gap-4">
                            {{-- Icon --}}
                            <div class="shrink-0 hidden sm:block">
                                <x-ui.icon :icon="$settings['helpContent']['icon'] ?? 'lightbulb'" class="w-5 h-5 text-primary-600 dark:text-primary-400 mt-0.5" />
                            </div>
                            
                            {{-- Content --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-4 mb-2">
                                    <h4 class="text-sm font-semibold text-secondary-900 dark:text-white">
                                        {{ $settings['helpContent']['title'] ?? trans('common.help_getting_started') }}
                                    </h4>
                                    <button @click="dismissHelp()" 
                                            class="shrink-0 text-secondary-400 hover:text-secondary-600 dark:hover:text-secondary-300 transition-colors"
                                            title="{{ trans('common.dismiss') }}">
                                        <x-ui.icon icon="x" class="w-4 h-4" />
                                    </button>
                                </div>
                                
                                <div class="text-sm text-secondary-600 dark:text-secondary-400 leading-relaxed">
                                    {!! $settings['helpContent']['content'] ?? '' !!}
                                </div>
                                
                                @if(isset($settings['helpContent']['steps']) && is_array($settings['helpContent']['steps']))
                                    <div class="mt-4 grid sm:grid-cols-2 lg:grid-cols-{{ min(count($settings['helpContent']['steps']), 4) }} gap-3">
                                        @foreach($settings['helpContent']['steps'] as $index => $step)
                                            <div class="flex items-start gap-2.5 p-3 rounded-lg bg-white/60 dark:bg-secondary-800/40 border border-secondary-200/50 dark:border-secondary-700/30">
                                                <span class="shrink-0 w-6 h-6 rounded-full bg-primary-100 dark:bg-primary-500/20 text-primary-700 dark:text-primary-300 text-xs font-bold flex items-center justify-center">
                                                    {{ $index + 1 }}
                                                </span>
                                                <div class="min-w-0">
                                                    <p class="text-sm font-medium text-secondary-800 dark:text-secondary-200">{{ $step['title'] ?? '' }}</p>
                                                    @if(isset($step['description']))
                                                        <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-0.5">{{ $step['description'] }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                                
                                @if(isset($settings['helpContent']['link']))
                                    <div class="mt-4">
                                        <a href="{{ $settings['helpContent']['link']['url'] }}" 
                                           class="inline-flex items-center gap-1.5 text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 transition-colors">
                                            {{ $settings['helpContent']['link']['label'] ?? trans('common.learn_more') }}
                                            <x-ui.icon icon="arrow-right" class="w-4 h-4" />
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Table --}}
            <div class="overflow-x-auto custom-scrollbar">
                <x-forms.form-open id="formDataDefinition" method="POST" />
                <table class="w-full text-sm text-left text-secondary-600 dark:text-secondary-400" id="tableDataDefinition">
                    <thead class="text-xs text-secondary-500 dark:text-secondary-400 uppercase bg-secondary-50 dark:bg-secondary-900/50 border-b border-secondary-200 dark:border-secondary-800">
                        <tr class="h-14">
                            @if ($settings['multiSelect'])
                                <th scope="col" class="p-4" @click="toggleAllCheckboxes()">
                                    <div class="flex items-center">
                                        <label class="table-checkbox">
                                            <input @click="toggleAllCheckboxes()" x-bind:checked="selectAll"
                                                autocomplete="off" id="checkbox-all" type="checkbox">
                                            <span class="checkbox-box"></span>
                                            <svg class="checkbox-check" viewBox="0 0 12 12" fill="none">
                                                <path d="M2.5 6L5 8.5L9.5 3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            <span class="checkbox-minus"></span>
                                        </label>
                                        <label for="checkbox-all" class="sr-only">checkbox</label>
                                    </div>
                                </th>
                            @endif
                            @if ($tableData['columns'])
                                @foreach ($tableData['columns'] as $column)
                                    @if (!$column['hidden'])
                                        <th scope="col"
                                            class="@if ($column['filter']) py-0 @else py-3 @endif px-4 whitespace-nowrap font-medium
                                                   @if ($column['type'] == 'avatar') text-center w-24 @endif
                                                   @if (in_array($column['type'], ['image', 'boolean', 'impersonate', 'qr'])) text-center @endif
                                                   @if ($column['type'] == 'number' || $column['format'] == 'number') text-right @endif
                                                   @if ($column['classes::list']) {{ $column['classes::list'] }} @endif">
                                            @if ($column['filter'])
                                                <div class="relative group min-w-[140px]">
                                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none z-10">
                                                        <x-ui.icon icon="filter" class="h-4 w-4 text-secondary-400 group-focus-within:text-primary-500 transition-colors" />
                                                    </div>
                                                    <select onchange="reloadWithFilter('{{ $column['name'] }}', this.value)" name="{{ $column['name'] }}" id="{{ $column['name'] }}" 
                                                            class="appearance-none w-full bg-white dark:bg-secondary-800 
                                                                   border border-secondary-200 dark:border-secondary-700 
                                                                   text-secondary-700 dark:text-secondary-300 text-sm 
                                                                   rounded-xl pl-9 pr-10 py-2
                                                                   shadow-sm
                                                                   transition-colors duration-200 cursor-pointer 
                                                                   hover:border-secondary-300 dark:hover:border-secondary-600
                                                                   focus:outline-none focus:bg-white dark:focus:bg-secondary-800
                                                                   focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                                                        <option value="0">{!! $column['text'] !!}</option>
                                                        <option disabled>───</option>
                                                        @if(isset($tableData['filters'][$column['name']]['options']))
                                                            @foreach($tableData['filters'][$column['name']]['options'] as $id => $filter)
                                                                <option @if(request()->input('filter.' . $column['name']) == $id) selected @endif value="{{ $id }}">{!! $filter !!}</option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                        <x-ui.icon icon="chevron-down" class="h-4 w-4 text-secondary-400 group-hover:text-secondary-600 dark:group-hover:text-secondary-300 transition-colors" />
                                                    </div>
                                                </div>
                                            @else
                                                @if ($column['sortable'])
                                                    @php
                                                        $sortParams = request()->except('page');
                                                        $sortParams['order'] = $column['name'];
                                                        $sortParams['orderDir'] = (request()->input('orderDir', null) == 'asc') ? 'desc' : 'asc';
                                                    @endphp
                                                    <a href="?{{ http_build_query($sortParams) }}" 
                                                       class="group inline-flex items-center hover:text-secondary-900 dark:hover:text-white transition-colors">
                                                        {!! $column['text'] !!}
                                                        <x-ui.icon icon="chevrons-up-down" class="h-4 w-4 ml-1 text-secondary-400 group-hover:text-secondary-600 dark:group-hover:text-secondary-300 transition-colors" />
                                                    </a>
                                                @else
                                                    {!! $column['text'] !!}
                                                @endif
                                            @endif
                                        </th>
                                    @endif
                                @endforeach
                            @endif
                            @if ($settings['hasActions'])
                                <th scope="col" class="px-4 py-3 text-right font-medium">
                                    {{ trans('common.actions') }}
                                </th>
                            @endif
                        </tr>
                    </thead>
                    @if ($tableData['data']->all() !== null)
                        <tbody class="divide-y divide-secondary-100 dark:divide-secondary-800/50">
                            @foreach ($tableData['data']->all() as $i => $row)
                                @php
                                    $rowClickUrl = null;
                                    if (! $settings['multiSelect']) {
                                        if ($settings['edit']) {
                                            $rowClickUrl = route($settings['guard'].'.data.edit', ['name' => $dataDefinition->name, 'id' => $row['id']]);
                                        } elseif ($settings['view']) {
                                            $rowClickUrl = route($settings['guard'].'.data.view', ['name' => $dataDefinition->name, 'id' => $row['id']]);
                                        } else {
                                            $searchValue = null;
                                            if ($settings['subject_column'] && isset($row[$settings['subject_column']])) {
                                                $searchValue = trim(strip_tags(html_entity_decode((string) $row[$settings['subject_column']])));
                                            }
                                            $searchValue = $searchValue !== '' ? $searchValue : (string) $row['id'];

                                            $rowClickUrl = route($settings['guard'].'.data.list', ['name' => $dataDefinition->name]).'?'.
                                                http_build_query(array_merge(request()->except('page'), ['search' => $searchValue]));
                                        }
                                    }
                                @endphp

                                <tr class="hover:bg-secondary-50 dark:hover:bg-secondary-800/30 transition-colors duration-150 @if (!$settings['multiSelect']) bg-white dark:bg-transparent cursor-pointer @endif"
                                    @if($rowClickUrl) onclick="dataListRowNavigate(event, @js($rowClickUrl))" tabindex="0" role="link" onkeydown="dataListRowNavigateKey(event, @js($rowClickUrl))" @endif
                                    @if ($settings['multiSelect']) :class="selected[{{ $i }}] ? 'bg-primary-50 dark:bg-primary-500/10' : 'bg-white dark:bg-transparent'" @endif>
                                    @if ($settings['multiSelect'])
                                        <td class="w-4 p-4"
                                            @click="selected[{{ $i }}] = !selected[{{ $i }}]">
                                            <div class="flex items-center">
                                                <label class="table-checkbox">
                                                    <input id="select-row-{{ $i }}"
                                                        x-model="selected[{{ $i }}]" name="id[]"
                                                        value="{{ $row['id'] }}" autocomplete="off" type="checkbox"
                                                        class="item-checkbox">
                                                    <span class="checkbox-box"></span>
                                                    <svg class="checkbox-check" viewBox="0 0 12 12" fill="none">
                                                        <path d="M2.5 6L5 8.5L9.5 3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                </label>
                                                <label for="select-row-{{ $i }}" class="sr-only">checkbox</label>
                                            </div>
                                        </td>
                                    @endif
                                    @foreach ($tableData['columns'] as $column)
                                        @if (!$column['hidden'])
                                            <td @if ($settings['multiSelect'] && !in_array($column['type'], ['impersonate', 'qr'])) @click="selected[{{ $i }}] = !selected[{{ $i }}]" @endif
                                                class="px-4 py-3 cursor-default 
                                                       @if ($column['highlight']) font-medium text-secondary-900 dark:text-white @endif
                                                       @if (in_array($column['type'], ['boolean', 'impersonate', 'qr'])) text-center align-middle @endif
                                                       @if ($column['type'] == 'number' || $column['format'] == 'number') text-right tabular-nums @endif
                                                       @if ($column['classes::list']) {{ $column['classes::list'] }} @endif
                                                       @if (!in_array($column['type'], ['image', 'avatar', 'boolean', 'impersonate', 'qr', 'number']) && !$column['highlight']) max-w-xs @endif"
                                                @if (!in_array($column['type'], ['image', 'avatar', 'boolean', 'impersonate', 'qr']) && strlen(strip_tags($row[$column['name']])) > 40)
                                                    title="{{ parse_attr(strip_tags($row[$column['name']])) }}"
                                                @endif>
                                                @if (in_array($column['type'], ['boolean', 'impersonate', 'qr'])) 
                                                    <div class="inline-block mx-auto"> 
                                                @endif
                                                @if (in_array($column['type'], ['date_time'])) 
                                                    <span class="format-date-time text-secondary-500 dark:text-secondary-400 whitespace-nowrap"> 
                                                @endif
                                                @if (in_array($column['type'], ['belongsTo', 'belongsToMany']) && strlen($row[$column['name']]) > 40)
                                                    <span class="inline-block max-w-xs truncate" title="{{ parse_attr($row[$column['name']]) }}">{{ $row[$column['name']] }}</span>
                                                @else
                                                    {!! $row[$column['name']] !!}
                                                @endif
                                                @if (in_array($column['type'], ['boolean', 'impersonate', 'qr'])) 
                                                    </div> 
                                                @endif
                                                @if (in_array($column['type'], ['date_time'])) 
                                                    </span> 
                                                @endif
                                            </td>
                                        @endif
                                    @endforeach
                                    @if ($settings['hasActions'])
                                        <td class="px-4 py-3 text-right">
                                            <div class="flex flex-nowrap justify-end gap-1">
                                                @if ($settings['view'])
                                                    <a href="{{ route($settings['guard'].'.data.view', ['name' => $dataDefinition->name, 'id' => $row['id']]) }}{{ request()->has('batch_id') ? '?batch_id='.request('batch_id') : '' }}" 
                                                       data-fb="tooltip" title="{{ trans('common.view') }}" 
                                                       class="inline-flex items-center justify-center w-8 h-8 rounded-lg
                                                              text-secondary-400 dark:text-secondary-500
                                                              hover:text-secondary-700 dark:hover:text-secondary-200
                                                              hover:bg-secondary-100 dark:hover:bg-secondary-800
                                                              transition-colors duration-150">
                                                        <x-ui.icon icon="eye" class="h-4 w-4" />
                                                    </a>
                                                @endif
                                                @if ($settings['edit'])
                                                    <a href="{{ route($settings['guard'].'.data.edit', ['name' => $dataDefinition->name, 'id' => $row['id']]) }}{{ request()->has('batch_id') ? '?batch_id='.request('batch_id') : '' }}" 
                                                       data-fb="tooltip" title="{{ trans('common.edit') }}" 
                                                       class="inline-flex items-center justify-center w-8 h-8 rounded-lg
                                                              text-secondary-400 dark:text-secondary-500
                                                              hover:text-secondary-700 dark:hover:text-secondary-200
                                                              hover:bg-secondary-100 dark:hover:bg-secondary-800
                                                              transition-colors duration-150">
                                                        <x-ui.icon icon="pencil" class="h-4 w-4" />
                                                    </a>
                                                @endif
                                                @if ($settings['delete'])
                                                    <a href="javascript:void(0);" 
                                                       data-fb="tooltip" title="{{ trans('common.delete') }}"
                                                       class="inline-flex items-center justify-center w-8 h-8 rounded-lg
                                                              text-secondary-400 dark:text-secondary-500
                                                              hover:text-red-500 dark:hover:text-red-400
                                                              hover:bg-red-50 dark:hover:bg-red-500/10
                                                              transition-colors duration-150"
                                                       @click="deleteItem('{{ $row['id'] }}', '{{ $settings['subject_column'] ? str_replace("'", "\\'", parse_attr($row[$settings['subject_column']])) : null }}')">
                                                        <x-ui.icon icon="trash" class="h-4 w-4" />
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    @endif
                    @if($tableData['data']->total() == 0)
                        <tbody>
                            <tr>
                                <td id="noResults" colspan="3" class="p-0">
                                    <div class="flex flex-col items-center justify-center py-16 px-6">
                                        @if($settings['icon'])
                                            <x-ui.icon :icon="$settings['icon']" class="w-8 h-8 text-secondary-300 dark:text-secondary-500 mb-5" />
                                        @else
                                            <x-ui.icon icon="inbox" class="w-8 h-8 text-secondary-300 dark:text-secondary-500 mb-5" />
                                        @endif
                                        
                                        <h3 class="text-base font-semibold text-secondary-900 dark:text-white mb-1">
                                            @if(request()->get('search'))
                                                {{ trans('common.no_search_results') }}
                                            @else
                                                {{ trans('common.no_items_yet', ['items' => $settings['title']]) }}
                                            @endif
                                        </h3>
                                        
                                        <p class="text-sm text-secondary-500 dark:text-secondary-400 text-center max-w-sm mb-5">
                                            @if(request()->get('search'))
                                                {{ trans('common.no_search_results_description', ['term' => request()->get('search')]) }}
                                            @elseif(isset($settings['emptyStateDescription']) && $settings['emptyStateDescription'])
                                                {{ $settings['emptyStateDescription'] }}
                                            @else
                                                {{ trans('common.no_items_yet_description') }}
                                            @endif
                                        </p>
                                        
                                        @if($settings['insert'] && !request()->get('search'))
                                            <a href="{{ route($settings['guard'].'.data.insert', ['name' => $dataDefinition->name]) }}"
                                                class="inline-flex items-center justify-center px-4 py-2.5 
                                                       text-sm font-medium text-white 
                                                       bg-primary-600 hover:bg-primary-500
                                                       rounded-xl shadow-sm hover:shadow-md
                                                       focus:outline-none focus:ring-2 focus:ring-primary-500/20
                                                       transition-all duration-200 active:scale-[0.98]">
                                                <x-ui.icon icon="plus" class="h-4 w-4 mr-2 rtl:ml-2" />
                                                {{ trans('common.add_first_item') }}
                                            </a>
                                        @elseif(request()->get('search'))
                                            <a href="{{ route($settings['guard'].'.data.list', ['name' => $dataDefinition->name]) }}"
                                                class="inline-flex items-center justify-center px-4 py-2.5 
                                                       text-sm font-medium text-secondary-700 dark:text-secondary-300 
                                                       bg-white dark:bg-secondary-800 
                                                       border border-secondary-200 dark:border-secondary-700 
                                                       rounded-xl shadow-sm
                                                       hover:bg-secondary-50 dark:hover:bg-secondary-700 
                                                       hover:border-secondary-300 dark:hover:border-secondary-600
                                                       focus:outline-none focus:ring-2 focus:ring-primary-500/20
                                                       transition-colors duration-200">
                                                <x-ui.icon icon="x" class="h-4 w-4 mr-2 rtl:ml-2" />
                                                {{ trans('common.clear_search') }}
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        <script>
                            function setColspan() {
                                const table = document.getElementById('tableDataDefinition');
                                const colspan = table.rows[0].cells.length;
                                document.getElementById('noResults').setAttribute('colspan', colspan);
                            }
                            setColspan();
                        </script>
                    @endif
                </table>
                <x-forms.form-close />
            </div>

            {{-- Footer --}}
            @if (($tableData['data']->total() > 0 && $settings['multiSelect']) || $tableData['data']->hasPages())
                <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center bg-secondary-50 dark:bg-secondary-800 border-t border-secondary-200 dark:border-secondary-800 p-4">
                    <div class="mb-4 lg:mb-0 flex flex-col sm:flex-row gap-3 sm:items-center">
                        @if ($settings['multiSelect'])
                            <div class="relative group min-w-[200px]" x-data>
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none z-10">
                                    <x-ui.icon icon="check-square" class="h-4 w-4 text-secondary-400 group-focus-within:text-primary-500 transition-colors" />
                                </div>
                                <select id="table-with-selected" x-bind:disabled="!anySelected()"
                                        class="appearance-none w-full bg-white dark:bg-secondary-800 
                                               border border-secondary-200 dark:border-secondary-700 
                                               text-secondary-700 dark:text-secondary-300 text-sm 
                                               rounded-xl pl-10 pr-10 py-3
                                               shadow-sm
                                               transition-colors duration-200 cursor-pointer 
                                               hover:border-secondary-300 dark:hover:border-secondary-600
                                               focus:outline-none focus:bg-white dark:focus:bg-secondary-800
                                               focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20
                                               disabled:opacity-50 disabled:cursor-not-allowed">
                                    <option selected value="">{{ trans('common.with_selected_') }}</option>
                                    <option value="" disabled>───</option>
                                    <option value="delete">{{ trans('common.delete') }}</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none">
                                    <x-ui.icon icon="chevron-down" class="h-4 w-4 text-secondary-400 group-hover:text-secondary-600 dark:group-hover:text-secondary-300 transition-colors" />
                                </div>
                            </div>
                        @endif

                        {{-- Per-page selector (only if there are more than 20 records) --}}
                        @if($tableData['data']->total() > 20)
                            <div class="relative group min-w-[220px]">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none z-10">
                                    <x-ui.icon icon="list" class="h-4 w-4 text-secondary-400 group-focus-within:text-primary-500 transition-colors" />
                                </div>
                                <select onchange="reloadWithPerPage(this.value)"
                                        class="appearance-none w-full bg-white dark:bg-secondary-800 
                                               border border-secondary-200 dark:border-secondary-700 
                                               text-secondary-700 dark:text-secondary-300 text-sm 
                                               rounded-xl pl-10 pr-10 py-3
                                               shadow-sm
                                               transition-colors duration-200 cursor-pointer 
                                               hover:border-secondary-300 dark:hover:border-secondary-600
                                               focus:outline-none focus:bg-white dark:focus:bg-secondary-800
                                               focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                                    @foreach(($settings['itemsPerPageOptions'] ?? [20, 50, 100]) as $opt)
                                        <option value="{{ $opt }}" @if((int) $settings['itemsPerPage'] === (int) $opt) selected @endif>
                                            {{ trans('common.rows_per_page', ['number' => $opt]) ?? ($opt . ' / page') }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none">
                                    <x-ui.icon icon="chevron-down" class="h-4 w-4 text-secondary-400 group-hover:text-secondary-600 dark:group-hover:text-secondary-300 transition-colors" />
                                </div>
                            </div>
                        @endif
                    </div>
                    @if ($tableData['data']->hasPages())
                        <div class="lg:ml-auto">
                            {{ $tableData['data']->onEachSide(5)->links('pagination.custom') }}
                        </div>
                    @endif
                </div>
            @endif

            @if ($settings['delete'])
                <script>
                    function deleteItem(id, item) {
                        if (item == null) item = "{{ trans('common.this_item') }}";
                        appConfirm('{{ trans('common.confirm_deletion') }}', _lang.delete_confirmation_text.replace(":item",
                            '<strong>' + item + '</strong>'), {
                            'btnConfirm': {
                                'click': function() {
                                    const form = document.getElementById('formDataDefinition');
                                    const baseUrl = '{{ route($settings['guard'].'.data.delete.post', ['name' => $dataDefinition->name]) }}/' + id;
                                    const batchId = '{{ request('batch_id', '') }}';
                                    form.action = baseUrl + (batchId ? '?batch_id=' + batchId : '');
                                    form.submit();
                                }
                            }
                        });
                    }
                </script>
            @endif
            @if ($settings['multiSelect'])
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        function handleWithSelected(selectedValue) {
                            @if ($settings['delete'])
                                if (selectedValue == 'delete') {
                                    appConfirm('{{ trans('common.confirm_deletion') }}',
                                        '{{ trans('common.confirm_deletion_selected_items') }}', {
                                            'btnConfirm': {
                                                'click': function() {
                                                    const form = document.getElementById('formDataDefinition');
                                                    form.action =
                                                        '{{ route($settings['guard'].'.data.delete.post', ['name' => $dataDefinition->name]) }}';
                                                    form.submit();
                                                }
                                            }
                                        });
                                }
                            @endif
                        }
                        const selectElement = document.getElementById('table-with-selected');
                        if (selectElement) {
                            selectElement.addEventListener('change', function(event) {
                                handleWithSelected(event.target.value);
                                event.target.value = '';
                            });
                        }
                    });
                </script>
            @endif
            <script>
                function reloadWithFilter(columnName, selectedValue) {
                    let url = new URL(window.location.href);
                    let filterKey = `filter[${columnName}]`;
                    let paramsToRemove = ['page'];
                    if (selectedValue == 0) {
                        paramsToRemove.push(filterKey);
                    }
                    for (let param of paramsToRemove) {
                        url.searchParams.delete(param);
                    }
                    if (selectedValue != 0) {
                        url.searchParams.set(filterKey, selectedValue);
                    }
                    window.location.href = url.href;
                }
            </script>
            <script>
                function dataListRowNavigate(event, url) {
                    let el = event.target;
                    if (!(el instanceof Element) && el && el.nodeType === Node.TEXT_NODE) {
                        el = el.parentElement;
                    }

                    const interactive = el instanceof Element ? el.closest('a,button,input,select,textarea,label') : null;
                    if (interactive) {
                        return;
                    }
                    window.location.href = url;
                }

                function dataListRowNavigateKey(event, url) {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        dataListRowNavigate(event, url);
                    }
                }
            </script>
            <script>
                function reloadWithPerPage(perPage) {
                    let url = new URL(window.location.href);
                    url.searchParams.set('perPage', perPage);
                    url.searchParams.delete('page');
                    window.location.href = url.href;
                }
            </script>
            <script>
                function dataListSearchAutocomplete(config) {
                    return {
                        suggestUrl: config.suggestUrl,
                        formId: config.formId,
                        query: config.initialQuery || '',
                        open: false,
                        loading: false,
                        suggestions: [],
                        activeIndex: 0,
                        _timer: null,
                        onFocus() {
                            if (this.query.trim().length >= 2) {
                                this.open = true;
                                this.fetchSuggestions();
                            }
                        },
                        onInput() {
                            // If cleared, return to base list without query-string noise.
                            if (this.query === '') {
                                const url = new URL(window.location.href);
                                url.searchParams.delete('search');
                                url.searchParams.delete('page');
                                window.location.href = url.href;
                                return;
                            }

                            if (this.query.trim().length < 2) {
                                this.open = true;
                                this.suggestions = [];
                                return;
                            }

                            this.open = true;
                            clearTimeout(this._timer);
                            this._timer = setTimeout(() => this.fetchSuggestions(), 180);
                        },
                        close() {
                            this.open = false;
                            this.activeIndex = 0;
                        },
                        move(delta) {
                            if (!this.open || this.suggestions.length === 0) {
                                return;
                            }
                            const next = this.activeIndex + delta;
                            this.activeIndex = Math.max(0, Math.min(next, this.suggestions.length - 1));
                        },
                        choose(item) {
                            if (item && item.url) {
                                window.location.href = item.url;
                                return;
                            }

                            this.query = item.label;
                            this.submit();
                        },
                        submitIfReady() {
                            if (this.open && this.suggestions.length > 0 && this.suggestions[this.activeIndex]) {
                                this.choose(this.suggestions[this.activeIndex]);
                                return;
                            }
                            this.submit();
                        },
                        submit() {
                            const form = document.getElementById(this.formId);
                            if (form) {
                                form.submit();
                            }
                        },
                        async fetchSuggestions() {
                            if (this.query.trim().length < 2) {
                                return;
                            }
                            this.loading = true;
                            try {
                                const url = new URL(this.suggestUrl, window.location.origin);
                                url.searchParams.set('q', this.query.trim());
                                const response = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
                                const json = await response.json();
                                this.suggestions = Array.isArray(json.data) ? json.data : [];
                                this.activeIndex = 0;
                            } catch (e) {
                                this.suggestions = [];
                            } finally {
                                this.loading = false;
                            }
                        }
                    };
                }
            </script>
        </div>
    </div>

    <style>
        .custom-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: rgb(214 211 209) rgb(250 250 249);
        }
        
        .dark .custom-scrollbar {
            scrollbar-color: rgb(71 85 105) rgb(15 23 42);
        }
        
        .custom-scrollbar::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgb(250 250 249);
            border-radius: 6px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgb(214 211 209);
            border-radius: 6px;
            border: 2px solid rgb(250 250 249);
        }
        
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgb(168 162 158);
        }
        
        .dark .custom-scrollbar::-webkit-scrollbar-track {
            background: rgb(15 23 42);
        }
        
        .dark .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgb(71 85 105);
            border: 2px solid rgb(15 23 42);
        }
        
        .dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgb(100 116 139);
        }
        
        .custom-scrollbar::-webkit-scrollbar-corner {
            background: rgb(250 250 249);
        }
        
        .dark .custom-scrollbar::-webkit-scrollbar-corner {
            background: rgb(15 23 42);
        }
    </style>

    {{-- Custom JavaScript from DataDefinition settings --}}
    @php
        $customJs = null;
        if (!empty($settings['js'])) {
            if (is_string($settings['js'])) {
                // String = applies to all views
                $customJs = $settings['js'];
            } elseif (is_array($settings['js'])) {
                // Check for exact match or comma-separated keys containing 'list'
                foreach ($settings['js'] as $views => $code) {
                    $viewList = array_map('trim', explode(',', $views));
                    if (in_array('list', $viewList) || in_array('all', $viewList)) {
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