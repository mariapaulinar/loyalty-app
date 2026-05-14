{{--
Installation Sidebar - Premium enterprise design
Uses subtle gradient background with refined stepper and clean typography.
--}}
<div class="flex flex-col h-full" x-data="{ darkMode: localStorage.getItem('color-theme') === 'dark' }">
    {{-- Logo --}}
    <div class="mb-12">
        <a href="{{ route('redir.locale') }}" class="flex items-center gap-3 group">
            <div class="w-8 h-8 rounded-lg bg-white/15 backdrop-blur-sm flex items-center justify-center text-white font-bold text-sm shadow-lg shadow-black/10 group-hover:bg-white/20 transition-colors" x-text="(appName || 'R').charAt(0).toUpperCase()">
            </div>
            <span class="font-bold text-base text-white tracking-tight" x-text="appName || '{{ config('default.app_name') }}'"></span>
        </a>
    </div>

    {{-- Stepper --}}
    <nav class="flex-1">
        <div class="space-y-0">
            @php
                $steps = [
                    ['num' => 1, 'label' => 'Requirements', 'desc' => 'Server compatibility'],
                    ['num' => 2, 'label' => 'Configuration', 'desc' => 'Database & email'],
                    ['num' => 3, 'label' => 'Install', 'desc' => 'Deploy platform'],
                ];
            @endphp

            @foreach ($steps as $step)
                <div class="flex items-start gap-3.5">
                    <div class="flex flex-col items-center">
                        <button type="button"
                            @click="tab >= {{ $step['num'] }} ? tab = {{ $step['num'] }} : null"
                            :disabled="tab < {{ $step['num'] }}"
                            class="w-7 h-7 rounded-full flex items-center justify-center text-[11px] font-bold transition-all duration-300 shrink-0 ring-2 ring-transparent"
                            :class="{
                                'bg-white text-secondary-900 shadow-md shadow-white/20 ring-white/30': tab === {{ $step['num'] }},
                                'bg-emerald-500 text-white ring-emerald-400/30': tab > {{ $step['num'] }},
                                'bg-white/10 text-white/40 border border-white/10': tab < {{ $step['num'] }}
                            }">
                            <span x-show="tab <= {{ $step['num'] }}">{{ $step['num'] }}</span>
                            <x-ui.icon icon="check" class="w-3.5 h-3.5" x-show="tab > {{ $step['num'] }}" x-cloak />
                        </button>
                        @if (!$loop->last)
                            <div class="w-px h-8 transition-colors duration-500"
                                :class="tab > {{ $step['num'] }} ? 'bg-emerald-400/50' : 'bg-white/10'"></div>
                        @endif
                    </div>
                    <button type="button"
                        @click="tab >= {{ $step['num'] }} ? tab = {{ $step['num'] }} : null"
                        :disabled="tab < {{ $step['num'] }}"
                        class="text-left pt-0.5 transition-all duration-200">
                        <span class="block text-sm font-semibold leading-tight"
                            :class="tab === {{ $step['num'] }} ? 'text-white' : (tab > {{ $step['num'] }} ? 'text-white/60' : 'text-white/30')">{{ $step['label'] }}</span>
                        <span class="block text-[11px] mt-0.5"
                            :class="tab === {{ $step['num'] }} ? 'text-white/60' : 'text-white/20'">{{ $step['desc'] }}</span>
                    </button>
                </div>
            @endforeach
        </div>
    </nav>

    {{-- Footer --}}
    <div class="mt-auto pt-6 border-t border-white/10 flex items-center justify-between">
        <button type="button" @click="toggleTheme(); darkMode = !darkMode"
            class="flex items-center justify-center w-8 h-8 rounded-full bg-white/10 text-white/50 hover:text-white hover:bg-white/20 transition-all cursor-pointer">
            <x-ui.icon icon="sun" class="w-4 h-4" x-show="darkMode" x-cloak />
            <x-ui.icon icon="moon" class="w-4 h-4" x-show="!darkMode" />
        </button>
        <span class="text-[11px] text-white/25 font-mono">v{{ config('version.current') }}</span>
    </div>
</div>