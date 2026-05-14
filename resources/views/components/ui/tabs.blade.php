{{--
Premium Tabs Component
iOS-style segmented control with content panels.

Supports two tab formats:
1. String tabs (legacy): ['Profile', 'Settings'] 
2. Object tabs (new): [['title' => 'Profile', 'type' => 'standard'], ['title' => 'Privacy', 'type' => 'custom', 'view' => 'path.to.view']]
--}}
@if ($tabs != null)
@php
    // Normalize tabs to consistent format (handle both string and array tabs)
    $normalizedTabs = collect($tabs)->map(function ($tab) {
        if (is_string($tab)) {
            return ['title' => $tab, 'type' => 'standard', 'view' => null, 'icon' => null];
        }
        return [
            'title' => $tab['title'] ?? 'Tab',
            'type' => $tab['type'] ?? 'standard',
            'view' => $tab['view'] ?? null,
            'icon' => $tab['icon'] ?? null,
        ];
    })->values()->all();
@endphp
<div class="{{ $tabClass }} w-full min-w-0" 
     x-data="{ 
        activeTab: 'tab-{{ $activeTab }}',
        updateTabIndex(tab) {
            this.$refs.tabIndex.value = tab.replace('tab-', '');
        }
     }" 
     {{ $attributes }}>
    
    <div class="space-y-4">
        
        {{-- Tab Bar --}}
        <div class="inline-flex w-full p-1 gap-1 bg-secondary-100 dark:bg-secondary-800 rounded-xl">
            @foreach ($normalizedTabs as $index => $tab)
                @php $tabIndex = $index + 1; @endphp
                <button
                    type="button"
                    data-tab-index="{{ $tabIndex }}"
                    data-tab-type="{{ $tab['type'] }}"
                    @click="activeTab = 'tab-{{ $tabIndex }}'; updateTabIndex(activeTab); $dispatch('onclicktab', { tab: 'tab-{{ $tabIndex }}', type: '{{ $tab['type'] }}' })"
                    class="relative flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 
                           text-sm rounded-lg
                           transition-colors duration-200
                           focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/20"
                    :class="activeTab === 'tab-{{ $tabIndex }}' 
                        ? 'bg-white dark:bg-secondary-700 text-secondary-900 dark:text-white font-medium shadow-sm' 
                        : 'text-secondary-500 dark:text-secondary-400 hover:text-secondary-700 dark:hover:text-secondary-300'"
                >
                    @if($tab['icon'])
                        <x-ui.icon :icon="$tab['icon']" class="w-4 h-4" />
                    @endif
                    {{ $tab['title'] }}
                </button>
            @endforeach
        </div>
        
        {{-- Content Panel --}}
        <div class="bg-white dark:bg-secondary-900 rounded-xl border border-secondary-200 dark:border-secondary-800 shadow-sm">
            <div class="p-6">
                @foreach ($normalizedTabs as $index => $tab)
                    @php $tabIndex = $index + 1; @endphp
                    <div 
                        x-show="activeTab === 'tab-{{ $tabIndex }}'" 
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        class="w-full"
                    >
                        {{ ${'tab' . $tabIndex} }}
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    
    <input type="hidden" id="current_tab_index" name="current_tab_index" x-ref="tabIndex" value="{{ $activeTab }}">
</div>
@endif