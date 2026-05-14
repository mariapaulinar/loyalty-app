{{--
    Staff Card Component
    
    A premium staff info card with avatar, name and email.
    Matches the member-card design language.
--}}

<div {{ $attributes->except('class') }} class="group relative overflow-hidden bg-white/80 dark:bg-secondary-800/80 backdrop-blur-xl border border-secondary-200/50 dark:border-secondary-700/50 rounded-2xl shadow-lg shadow-secondary-900/5 dark:shadow-secondary-900/50 transition-all duration-300 hover:shadow-xl hover:border-secondary-300/50 dark:hover:border-secondary-600/50 {{ $attributes->get('class') }}">
    {{-- Subtle gradient overlay --}}
    <div class="absolute inset-0 bg-gradient-to-br from-violet-500/5 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
    
    <div class="relative p-4 sm:p-5">
        <div class="flex items-center gap-4">
            {{-- Avatar --}}
            <div class="relative flex-shrink-0">
                @if ($avatar)
                    <img class="w-12 h-12 rounded-xl object-cover ring-2 ring-white dark:ring-secondary-700 shadow-md" 
                         src="{{ $avatar }}" 
                         alt="{{ $transaction ? parse_attr($transaction->staff_name) : parse_attr($staff->name) }}">
                @else
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center shadow-md shadow-violet-500/20">
                        <x-ui.icon icon="user" class="w-6 h-6 text-white" />
                    </div>
                @endif
            </div>
            
            {{-- Info --}}
            <div class="flex-1 min-w-0">
                <h4 class="text-base font-semibold text-secondary-900 dark:text-white truncate group-hover:text-violet-600 dark:group-hover:text-violet-400 transition-colors">
                    {{ $transaction ? $transaction->staff_name : $staff->name }}
                </h4>
                <p class="text-sm text-secondary-500 dark:text-secondary-400 truncate">
                    {{ $transaction ? $transaction->staff_email : $staff->email }}
                </p>
            </div>
        </div>
    </div>
</div>
