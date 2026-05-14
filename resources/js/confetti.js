'use strict';

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Confetti Celebration Utility
 *
 * Purpose:
 * Reusable confetti animations for celebrating user achievements.
 * Disney-level magic moments throughout the app.
 *
 * Usage:
 * import { celebrate, confettiBurst, confettiFireworks } from './confetti';
 * celebrate(); // Quick celebration
 * confettiFireworks(); // Full Disney castle moment
 */

import confetti from 'canvas-confetti';

/**
 * Brand color palette for confetti
 */
const BRAND_COLORS = [
    '#8B5CF6', // primary-600
    '#10B981', // emerald-600
    '#F59E0B', // amber-600
    '#EC4899', // pink-600
    '#2563EB', // blue-600
    '#A78BFA', // primary-400
];

/**
 * Quick celebration - single burst from center
 * Perfect for: button clicks, form submissions, quick wins
 */
export function celebrate() {
    confetti({
        particleCount: 100,
        spread: 70,
        origin: { y: 0.6 },
        colors: BRAND_COLORS,
    });
}

/**
 * Single confetti burst with custom options
 * 
 * @param {Object} options - Custom confetti options
 * @returns {Promise} Resolves when confetti animation completes
 */
export function confettiBurst(options = {}) {
    return confetti({
        particleCount: 100,
        spread: 70,
        origin: { y: 0.6 },
        colors: BRAND_COLORS,
        ...options,
    });
}

/**
 * The Disney Castle Moment - Multi-burst fireworks celebration
 * Perfect for: major achievements, voucher claims, level ups
 * 
 * Creates a sequence of confetti bursts:
 * 1. Center burst (main celebration)
 * 2. Side bursts from left and right (building excitement)
 * 3. Final top burst (grand finale)
 * 
 * @returns {Promise} Resolves when all animations complete
 */
export function confettiFireworks() {
    return new Promise((resolve) => {
        // Burst 1: Center explosion
        confetti({
            particleCount: 100,
            spread: 70,
            origin: { y: 0.4 },
            colors: BRAND_COLORS,
        });

        // Burst 2 & 3: Side explosions (250ms delay)
        setTimeout(() => {
            confetti({
                particleCount: 50,
                angle: 60,
                spread: 55,
                origin: { x: 0, y: 0.6 },
                colors: ['#8B5CF6', '#10B981'],
            });
            confetti({
                particleCount: 50,
                angle: 120,
                spread: 55,
                origin: { x: 1, y: 0.6 },
                colors: ['#EC4899', '#F59E0B'],
            });
        }, 250);

        // Burst 4: Final top burst (500ms delay)
        setTimeout(() => {
            confetti({
                particleCount: 80,
                spread: 100,
                origin: { y: 0.3 },
                colors: ['#8B5CF6', '#2563EB', '#10B981'],
            });
            
            // Resolve after final burst completes
            setTimeout(resolve, 500);
        }, 500);
    });
}

/**
 * Continuous confetti rain
 * Perfect for: special events, major announcements
 * 
 * @param {number} duration - Duration in milliseconds (default: 3000)
 * @returns {Function} Stop function to end the rain early
 */
export function confettiRain(duration = 3000) {
    const end = Date.now() + duration;
    
    const frame = () => {
        confetti({
            particleCount: 2,
            angle: 60,
            spread: 55,
            origin: { x: 0, y: 0 },
            colors: BRAND_COLORS,
        });
        confetti({
            particleCount: 2,
            angle: 120,
            spread: 55,
            origin: { x: 1, y: 0 },
            colors: BRAND_COLORS,
        });

        if (Date.now() < end) {
            requestAnimationFrame(frame);
        }
    };

    frame();
    
    // Return stop function
    return () => {
        // Clear by setting end to past
        return Date.now() > end;
    };
}

/**
 * School pride confetti cannon
 * Perfect for: achievements, completions, perfect scores
 */
export function confettiCannon() {
    const count = 200;
    const defaults = {
        origin: { y: 0.7 },
        colors: BRAND_COLORS,
    };

    function fire(particleRatio, opts) {
        confetti({
            ...defaults,
            ...opts,
            particleCount: Math.floor(count * particleRatio),
        });
    }

    fire(0.25, {
        spread: 26,
        startVelocity: 55,
    });
    fire(0.2, {
        spread: 60,
    });
    fire(0.35, {
        spread: 100,
        decay: 0.91,
        scalar: 0.8,
    });
    fire(0.1, {
        spread: 120,
        startVelocity: 25,
        decay: 0.92,
        scalar: 1.2,
    });
    fire(0.1, {
        spread: 120,
        startVelocity: 45,
    });
}

/**
 * Gentle celebration for subtle moments
 * Perfect for: card following, points earned, small wins
 */
export function confettiGentle() {
    confetti({
        particleCount: 50,
        spread: 50,
        origin: { y: 0.6 },
        colors: BRAND_COLORS,
        ticks: 100, // Shorter animation
    });
}

/**
 * Hearts confetti for special moments
 * Perfect for: favorites, likes, love reactions
 */
export function confettiHearts() {
    const scalar = 2;
    const heart = confetti.shapeFromText({ text: '💜', scalar });

    confetti({
        particleCount: 30,
        spread: 80,
        origin: { y: 0.6 },
        shapes: [heart],
        scalar,
    });
}

/**
 * Stars confetti for achievements
 * Perfect for: level ups, tier upgrades, milestones
 */
export function confettiStars() {
    const scalar = 2;
    const star = confetti.shapeFromText({ text: '⭐', scalar });

    confetti({
        particleCount: 40,
        spread: 70,
        origin: { y: 0.6 },
        shapes: [star],
        scalar,
    });
}

/**
 * Make confetti available globally on window object
 * For use in Alpine.js components and inline scripts
 */
window.confettiCelebrate = celebrate;
window.confettiBurst = confettiBurst;
window.confettiFireworks = confettiFireworks;
window.confettiRain = confettiRain;
window.confettiCannon = confettiCannon;
window.confettiGentle = confettiGentle;
window.confettiHearts = confettiHearts;
window.confettiStars = confettiStars;

// Export for module imports
export default {
    celebrate,
    confettiBurst,
    confettiFireworks,
    confettiRain,
    confettiCannon,
    confettiGentle,
    confettiHearts,
    confettiStars,
};
