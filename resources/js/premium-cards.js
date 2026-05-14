'use strict';

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Premium Card Interactions
 *
 * Purpose:
 * - Provide subtle mouse-based 3D tilt without fighting CSS hover/active states.
 * - Ensure press feedback works even when cards use an overlay link.
 *
 * Implementation:
 * - JS only updates CSS variables (--tilt-dx/--tilt-dy) rather than inline transform.
 * - On pointer press, sets data-pressed="true" on the card (CSS handles press state).
 */

document.addEventListener('DOMContentLoaded', () => {
    // Select all premium cards
    const premiumCards = document.querySelectorAll('.premium-card');

    premiumCards.forEach((card) => {
        let ticking = false;

        const clearTilt = () => {
            card.style.removeProperty('--tilt-dx');
            card.style.removeProperty('--tilt-dy');
        };

        const setPressed = (pressed) => {
            if (pressed) {
                card.dataset.pressed = 'true';
                clearTilt();
                return;
            }

            delete card.dataset.pressed;
        };

        // Mouse move handler - creates 3D tilt effect
        card.addEventListener('mousemove', (e) => {
            if (card.dataset.pressed === 'true') {
                return;
            }

            if (!ticking) {
                window.requestAnimationFrame(() => {
                    const rect = card.getBoundingClientRect();
                    const x = e.clientX - rect.left; // Mouse X position relative to card
                    const y = e.clientY - rect.top;  // Mouse Y position relative to card

                    const centerX = rect.width / 2;
                    const centerY = rect.height / 2;

                    // Calculate rotation based on distance from center
                    // Divide by 30 for subtle effect (max ~3 degrees)
                    const rotateX = ((y - centerY) / 30) * -1; // Inverted for natural feel
                    const rotateY = (x - centerX) / 30;

                    // Let CSS own transform. We only provide rotation deltas.
                    card.style.setProperty('--tilt-dx', `${rotateX}deg`);
                    card.style.setProperty('--tilt-dy', `${rotateY}deg`);

                    ticking = false;
                });

                ticking = true;
            }
        });

        // Mouse leave handler - reset to default hover state
        card.addEventListener('mouseleave', () => {
            clearTilt();
            setPressed(false);
        });

        // Pointer press handling (overlay link receives :active, so we mirror state on card)
        card.addEventListener('pointerdown', (e) => {
            const target = e.target;

            if (!target || !(target instanceof Element)) {
                return;
            }

            const hit = target.closest('.card-hit');

            if (!hit) {
                return;
            }

            setPressed(true);

            try {
                hit.setPointerCapture(e.pointerId);
            } catch {
                // Ignore capture failures (non-fatal)
            }

            const release = () => {
                setPressed(false);
            };

            hit.addEventListener('pointerup', release, { once: true });
            hit.addEventListener('pointercancel', release, { once: true });
            hit.addEventListener('lostpointercapture', release, { once: true });
        });
    });
});

// Export for potential manual initialization
export function initPremiumCards() {
    // Re-run initialization if needed
    const event = new Event('DOMContentLoaded');
    document.dispatchEvent(event);
}
