{{-- Agent Key One-Time Display Modal --}}
{{-- Renders when session('agent_key_raw') is set after key creation --}}
@if(session('agent_key_raw'))
<div id="agent-key-modal-overlay"
     role="dialog"
     aria-modal="true"
     aria-label="{{ trans('agent.agent_key_created') }}">

    <div class="agent-key-modal">

        {{-- Header --}}
        <div class="agent-key-modal__header">
            <div class="agent-key-modal__icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 13v2"/><path d="M9 13v2"/></svg>
            </div>
            <h3 class="agent-key-modal__title">{{ trans('agent.agent_key_created') }}</h3>
        </div>

        {{-- Body --}}
        <div class="agent-key-modal__body">
            {{-- Warning --}}
            <div class="agent-key-modal__warning">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <span>{!! trans('agent.key_warning') !!}</span>
            </div>

            {{-- Key display --}}
            <div class="agent-key-modal__key-row">
                <code id="agent-key-value" class="agent-key-modal__key-value">{{ session('agent_key_raw') }}</code>
                <button type="button" id="agent-key-copy-btn" class="agent-key-modal__copy-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                    <span>{{ trans('agent.copy') }}</span>
                </button>
            </div>

            {{-- Copied confirmation --}}
            <p id="agent-key-copied-msg" class="agent-key-modal__copied-msg">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                {{ trans('agent.copied_to_clipboard') }}
            </p>
        </div>

        {{-- Footer --}}
        <div class="agent-key-modal__footer">
            <button type="button" id="agent-key-close-btn" class="agent-key-modal__close-btn">{{ trans('agent.saved_key') }}</button>
        </div>
    </div>
</div>

<style>
    @keyframes agentKeyFadeIn { from { opacity: 0 } to { opacity: 1 } }
    @keyframes agentKeySlideUp { from { opacity: 0; transform: translateY(16px) scale(0.98) } to { opacity: 1; transform: translateY(0) scale(1) } }

    #agent-key-modal-overlay {
        position: fixed;
        inset: 0;
        z-index: 99999;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        animation: agentKeyFadeIn 0.2s ease-out;
    }

    .agent-key-modal {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        width: 100%;
        max-width: 600px;
        margin: 16px;
        overflow: hidden;
        animation: agentKeySlideUp 0.3s ease-out 0.05s both;
    }

    .agent-key-modal__header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 24px 24px 0;
    }

    .agent-key-modal__icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: #fff;
        flex-shrink: 0;
    }

    .agent-key-modal__title {
        font-size: 18px;
        font-weight: 600;
        margin: 0;
        color: #111827;
    }

    .agent-key-modal__body {
        padding: 16px 24px 0;
    }

    .agent-key-modal__warning {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        padding: 12px 14px;
        border-radius: 10px;
        background: #fef3c7;
        color: #92400e;
        font-size: 13px;
        line-height: 1.5;
        margin-bottom: 16px;
    }

    .agent-key-modal__key-row {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 12px 14px;
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
    }

    .agent-key-modal__key-value {
        flex: 1;
        font-family: SFMono-Regular, Menlo, Consolas, 'Liberation Mono', monospace;
        font-size: 13px;
        word-break: break-all;
        color: #111827;
        user-select: all;
        background: none;
        line-height: 1.5;
    }

    .agent-key-modal__copy-btn {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 6px 12px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: #ffffff;
        color: #374151;
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        white-space: nowrap;
        flex-shrink: 0;
        outline: none;
        transition: border-color 0.15s, color 0.15s, box-shadow 0.15s;
    }

    .agent-key-modal__copy-btn:hover {
        border-color: #9ca3af;
        background: #f3f4f6;
    }

    .agent-key-modal__copy-btn:focus-visible {
        border-color: #6366f1;
        box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.25);
    }

    .agent-key-modal__copy-btn--copied {
        border-color: #059669 !important;
        color: #059669 !important;
    }

    .agent-key-modal__copied-msg {
        display: none;
        align-items: center;
        gap: 6px;
        color: #059669;
        font-size: 13px;
        font-weight: 500;
        margin: 10px 0 0;
    }

    .agent-key-modal__footer {
        padding: 20px 24px;
    }

    .agent-key-modal__close-btn {
        display: block;
        width: 100%;
        padding: 10px 20px;
        border: none;
        border-radius: 10px;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: #fff;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        outline: none;
        transition: opacity 0.15s, box-shadow 0.15s;
    }

    .agent-key-modal__close-btn:hover {
        opacity: 0.92;
    }

    .agent-key-modal__close-btn:focus-visible {
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.35);
    }

    /* ── Dark mode ─────────────────────────────────────────── */
    [data-bs-theme="dark"] .agent-key-modal,
    .dark .agent-key-modal {
        background: #1e1e2e;
    }

    [data-bs-theme="dark"] .agent-key-modal__title,
    .dark .agent-key-modal__title {
        color: #e2e8f0;
    }

    [data-bs-theme="dark"] .agent-key-modal__key-row,
    .dark .agent-key-modal__key-row {
        background: #2a2a3e;
        border-color: #374151;
    }

    [data-bs-theme="dark"] .agent-key-modal__key-value,
    .dark .agent-key-modal__key-value {
        color: #e2e8f0;
    }

    [data-bs-theme="dark"] .agent-key-modal__copy-btn,
    .dark .agent-key-modal__copy-btn {
        background: #1e1e2e;
        border-color: #4b5563;
        color: #9ca3af;
    }

    [data-bs-theme="dark"] .agent-key-modal__copy-btn:hover,
    .dark .agent-key-modal__copy-btn:hover {
        border-color: #6b7280;
        background: #2a2a3e;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var overlay = document.getElementById('agent-key-modal-overlay');
        if (!overlay) return;

        var rawKey = document.getElementById('agent-key-value').textContent.trim();
        var copyBtn = document.getElementById('agent-key-copy-btn');

        // Copy to clipboard (with fallback for non-HTTPS / older browsers)
        copyBtn.addEventListener('click', function() {
            var copySuccess = function() {
                document.getElementById('agent-key-copied-msg').style.display = 'flex';
                copyBtn.classList.add('agent-key-modal__copy-btn--copied');
                copyBtn.querySelector('span').textContent = '{{ trans("agent.copied") }}';
            };

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(rawKey).then(copySuccess).catch(function() {
                    fallbackCopy(rawKey, copySuccess);
                });
            } else {
                fallbackCopy(rawKey, copySuccess);
            }
        });

        // Fallback copy using a temporary textarea
        function fallbackCopy(text, onSuccess) {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.style.cssText = 'position:fixed;left:-9999px;top:-9999px;opacity:0';
            document.body.appendChild(ta);
            ta.focus();
            ta.select();
            try {
                document.execCommand('copy');
                onSuccess();
            } catch (e) {
                // Select-all as last resort so user can Cmd+C
                document.getElementById('agent-key-value').focus();
                window.getSelection().selectAllChildren(document.getElementById('agent-key-value'));
            }
            document.body.removeChild(ta);
        }

        // Close modal
        var closeModal = function() {
            overlay.style.animation = 'agentKeyFadeIn 0.15s ease-in reverse';
            setTimeout(function() { overlay.remove(); }, 150);
        };

        document.getElementById('agent-key-close-btn').addEventListener('click', closeModal);
        overlay.addEventListener('click', function(e) { if (e.target === overlay) closeModal(); });
        document.addEventListener('keydown', function handler(e) {
            if (e.key === 'Escape' && document.getElementById('agent-key-modal-overlay')) {
                closeModal();
                document.removeEventListener('keydown', handler);
            }
        });
    });
</script>
@endif
