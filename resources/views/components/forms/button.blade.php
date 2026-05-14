{{--
Premium Form Button Component
Clean button with subtle depth and smooth interactions.
--}}
<div {!! $class ? 'class="' . $class . '"' : '' !!}>
    <button type="{{ $type }}"
        class="group relative w-full inline-flex items-center justify-center gap-2 px-6 py-3
               bg-primary-600 hover:bg-primary-500
               text-white font-semibold text-sm rounded-xl
               shadow-sm hover:shadow-md
               transition-all duration-200
               focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:ring-offset-0
               active:scale-[0.98]
               disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100
               cursor-pointer {{ $buttonClass }}">
        {!! $label !!}
        <span class="form-dirty hidden text-primary-200">&nbsp;•</span>
    </button>
    
    @if ($back)
        <a href="{{ $backUrl }}" 
           class="block w-full mt-3 text-center text-sm font-medium text-secondary-500 dark:text-secondary-400 hover:text-secondary-700 dark:hover:text-secondary-200 transition-colors">
            {{ $backText }}
        </a>
    @endif
</div>