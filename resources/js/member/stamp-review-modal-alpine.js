'use strict';

/**
 * Alpine component for the stamp experience review modal.
 *
 * Registered before Alpine.start() (via core.js) so x-data="stampReviewModal(...)"
 * is available when the DOM is initialized. WebSocket setup is deferred until
 * member.js exposes window.initMemberBroadcasting.
 */

function connectBroadcasting(component, config, attempts = 0) {
    if (typeof window.initMemberBroadcasting !== 'function') {
        if (attempts >= 100) {
            console.warn('[StampReview] initMemberBroadcasting no disponible — recarga la página.');

            return;
        }

        requestAnimationFrame(() => connectBroadcasting(component, config, attempts + 1));

        return;
    }

    window.initMemberBroadcasting(config.memberId, (payload) => {
        component.openModal(payload);
    }, config.reverbConfig);
}

window.stampReviewModal = function(config) {
    return {
        show: false,
        rating: 0,
        hoverRating: 0,
        isSubmitting: false,
        transactionId: null,
        stampCardTitle: '',
        currentStamps: 0,
        stampsRequired: 0,
        submitUrlTemplate: config.submitUrlTemplate ?? '',

        init() {
            if (!config.memberId) {
                return;
            }

            connectBroadcasting(this, config);
        },

        openModal(payload) {
            if (typeof window.confettiGentle === 'function') {
                window.confettiGentle();
            } else if (typeof window.confettiCelebrate === 'function') {
                window.confettiCelebrate();
            }

            this.transactionId = payload.transaction_id;
            this.stampCardTitle = payload.stamp_card_title ?? '';
            this.currentStamps = payload.current_stamps ?? 0;
            this.stampsRequired = payload.stamps_required ?? 0;
            this.rating = 0;
            this.hoverRating = 0;
            this.show = true;
        },

        closeModal() {
            this.show = false;
            this.rating = 0;
            this.hoverRating = 0;
            this.isSubmitting = false;
        },

        setRating(value) {
            this.rating = value;
        },

        getSubmitUrl() {
            return this.submitUrlTemplate.replace('__TRANSACTION_ID__', this.transactionId ?? '');
        },

        async submitReview() {
            if (!this.rating || !this.transactionId || this.isSubmitting) {
                return;
            }

            this.isSubmitting = true;

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

            try {
                const response = await fetch(this.getSubmitUrl(), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ rating: this.rating }),
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(data.message ?? 'Request failed');
                }

                if (typeof window.confettiStars === 'function') {
                    window.confettiStars();
                }

                this.closeModal();
            } catch (error) {
                console.error('[StampReview] Failed to submit review:', error);
                this.isSubmitting = false;
            }
        },
    };
};
