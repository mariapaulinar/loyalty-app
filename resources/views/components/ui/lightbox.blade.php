{{--
Premium Lightbox Component

Modern image lightbox with smooth animations and blur backdrop.
--}}

<div x-data="{ imgModal: false, imgModalSrc: '', imgModalDesc: '' }">
    <div 
        @img-modal.window="imgModal = true; imgModalSrc = $event.detail.imgModalSrc; imgModalDesc = $event.detail.imgModalDesc;"
        x-show="imgModal" 
        x-cloak 
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" 
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" 
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" 
        @click="imgModal = false"
        @keydown.escape.window="imgModal = false"
        class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/90 backdrop-blur-sm"
    >
        {{-- Close Button --}}
        <button 
            @click="imgModal = false" 
            class="absolute top-4 right-4 z-10 w-12 h-12 rounded-full bg-white/10 backdrop-blur-sm flex items-center justify-center text-white hover:bg-white/20 transition-all duration-200 hover:scale-110"
        >
            <x-ui.icon icon="x" class="w-6 h-6" />
        </button>
        
        {{-- Image Container --}}
        <div 
            @click.stop
            x-show="imgModal"
            x-transition:enter="transition ease-out duration-300 delay-100"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative max-w-5xl max-h-[90vh] flex flex-col"
        >
            <img 
                :src="imgModalSrc" 
                :alt="imgModalDesc" 
                class="max-w-full max-h-[80vh] object-contain rounded-2xl shadow-2xl"
            >
            
            {{-- Caption --}}
            <p 
                x-show="imgModalDesc" 
                x-text="imgModalDesc" 
                class="mt-4 text-center text-white/80 text-sm font-medium"
            ></p>
        </div>
    </div>
</div>
