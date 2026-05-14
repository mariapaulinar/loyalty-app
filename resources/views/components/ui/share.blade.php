{{--
Premium Collapsible Social Share Component

Modern social sharing with collapsible button and inline expansion.
Clean icon-only design that expands horizontally to show share options.

@props
- url: string - URL to share (default: current URL)
- text: string - Text to share (default: app name)
- class: string - Additional classes for wrapper
--}}

@props([
    'url' => url()->current(),
    'text' => config('default.app_name'),
    'size' => 'md',
])

@php
    $sizes = [
        'md' => [
            'trigger' => 'w-11 h-11',
            'icon' => 'w-[18px] h-[18px]',
            'items' => 'w-10 h-10',
            'itemIcon' => 'w-[18px] h-[18px]',
        ],
        'lg' => [
            'trigger' => 'w-14 h-14',
            'icon' => 'w-6 h-6',
            'items' => 'w-12 h-12',
            'itemIcon' => 'w-5 h-5',
        ],
    ];
    $s = $sizes[$size] ?? $sizes['md'];
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center']) }} x-data="{ expanded: false }">
    {{-- Share Trigger Button --}}
    <button @click="expanded = !expanded" x-show="!expanded"
        :class="expanded ? 'bg-primary-50 dark:bg-primary-900/30 border-primary-200 dark:border-primary-800 text-primary-600 dark:text-primary-400' : 'bg-white dark:bg-secondary-800 text-secondary-600 dark:text-secondary-400 border-secondary-200 dark:border-secondary-700 hover:bg-secondary-50 dark:hover:bg-secondary-700 hover:border-secondary-300 dark:hover:border-secondary-600 hover:text-secondary-900 dark:hover:text-white hover:shadow-md'"
        class="inline-flex items-center justify-center {{ $s['trigger'] }} border rounded-2xl shadow-sm transition-all duration-200"
        aria-label="{{ trans('common.share') }}">
        <x-ui.icon x-show="!expanded" icon="share" class="{{ $s['icon'] }}" />
        <x-ui.icon x-show="expanded" x-cloak icon="x" class="{{ $s['icon'] }}" />
    </button>
    
    {{-- Inline Share Icons --}}
    <div x-show="expanded" 
         x-transition:enter="transition-all ease-out duration-300"
         x-transition:enter-start="opacity-0 w-0"
         x-transition:enter-end="opacity-100 w-auto"
         x-cloak
         class="flex items-center gap-2 ml-2">
        @php
            // Use rawurlencode for proper RFC 3986 encoding (spaces become %20, not +)
            $encodedText = rawurlencode($text);
            $encodedUrl = rawurlencode($url);
            
            $shareLinks = [
                ['url' => "https://wa.me/?text=" . $encodedText . "%20" . $encodedUrl, 'color' => '#25d366', 'icon' => asset('assets/img/share/whatsapp.svg'), 'label' => 'WhatsApp', 'delay' => '0ms'],
                ['url' => "https://t.me/share/url?text=" . $encodedText . "&url=" . $encodedUrl, 'color' => '#0088cc', 'icon' => asset('assets/img/share/telegram.svg'), 'label' => 'Telegram', 'delay' => '30ms'],
                ['url' => "https://x.com/intent/tweet?url=" . $encodedUrl . "&text=" . $encodedText, 'color' => '#000', 'icon' => asset('assets/img/share/x.svg'), 'label' => 'X', 'delay' => '60ms'],
                ['url' => "https://facebook.com/sharer/sharer.php?u=" . $encodedUrl, 'color' => '#1877f2', 'icon' => asset('assets/img/share/facebook.svg'), 'label' => 'Facebook', 'delay' => '90ms'],
                // ['url' => "https://www.linkedin.com/shareArticle?mini=true&url=" . $encodedUrl . "&title=" . $encodedText, 'color' => '#0a66c2', 'icon' => asset('assets/img/share/linkedin.svg'), 'label' => 'LinkedIn', 'delay' => '120ms'],
                ['url' => "mailto:?subject=" . $encodedText . "&body=" . $encodedUrl, 'color' => '#EA4335', 'icon' => asset('assets/img/share/mail.svg'), 'label' => 'Email', 'delay' => '150ms'],
                ['copy' => true, 'delay' => '180ms'],
            ];
        @endphp
        
        @foreach($shareLinks as $link)
            @if(isset($link['copy']))
                <button x-data="{ 
                        copied: false,
                        async copyLink() {
                            const url = '{{ $url }}';
                            if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                                try {
                                    await navigator.clipboard.writeText(url);
                                    this.copied = true;
                                    setTimeout(() => this.copied = false, 1500);
                                    return;
                                } catch (err) {}
                            }
                            const textarea = document.createElement('textarea');
                            textarea.value = url;
                            textarea.style.cssText = 'position:fixed;left:-9999px;opacity:0';
                            document.body.appendChild(textarea);
                            textarea.select();
                            try {
                                if (document.execCommand('copy')) {
                                    this.copied = true;
                                    setTimeout(() => this.copied = false, 1500);
                                }
                            } catch (err) {}
                            document.body.removeChild(textarea);
                        }
                    }"
                    @click="copyLink()"
                    :class="copied ? 'bg-emerald-500 border-emerald-500 scale-110' : 'bg-white dark:bg-secondary-800 border-secondary-200 dark:border-secondary-700 hover:bg-primary-500 hover:border-primary-500 hover:shadow-lg'"
                    class="share-link group {{ $s['items'] }} rounded-xl border flex items-center justify-center transition-all duration-200 flex-shrink-0"
                    style="animation: sharePopIn 0.3s ease-out forwards; animation-delay: {{ $link['delay'] }}"
                    data-color="#6366f1"
                    aria-label="{{ trans('common.copy_link') }}">
                    <x-ui.icon x-show="!copied" icon="link" class="{{ $s['itemIcon'] }} text-secondary-500 dark:text-secondary-400 group-hover:text-white transition-colors" />
                    <x-ui.icon x-show="copied" x-cloak icon="check" class="{{ $s['itemIcon'] }} text-white" />
                </button>
            @else
                <a href="{{ $link['url'] }}" 
                   @if(!str_starts_with($link['url'], 'mailto:'))
                   target="_blank" 
                   rel="noopener noreferrer"
                   @endif
                   class="share-link group {{ $s['items'] }} rounded-xl bg-white dark:bg-secondary-800 border border-secondary-200 dark:border-secondary-700 flex items-center justify-center transition-all duration-200 hover:scale-110 hover:shadow-lg flex-shrink-0"
                   style="animation: sharePopIn 0.3s ease-out forwards; animation-delay: {{ $link['delay'] }}"
                   data-color="{{ $link['color'] }}"
                   aria-label="{{ $link['label'] }}">
                    <img src="{{ $link['icon'] }}" 
                         alt="{{ $link['label'] }}" 
                         class="share-icon {{ $s['itemIcon'] }} transition-all duration-200"
                         style="filter: brightness(0) saturate(100%) invert(45%) sepia(8%) saturate(600%) hue-rotate(180deg) brightness(95%) contrast(90%);">
                </a>
            @endif
        @endforeach
    </div>
</div>

<style>
    @keyframes sharePopIn {
        from { opacity: 0; transform: scale(0.8); }
        to { opacity: 1; transform: scale(1); }
    }
    .share-link:hover { background-color: var(--hover-bg) !important; border-color: var(--hover-bg) !important; }
    .share-link:hover .share-icon { filter: brightness(0) invert(1) !important; }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.share-link').forEach(link => {
            const color = link.dataset.color;
            link.style.setProperty('--hover-bg', color);
        });
    });
</script>