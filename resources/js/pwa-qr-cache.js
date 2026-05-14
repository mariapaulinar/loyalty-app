'use strict';

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * PWA QR Code Caching - Aggressive Pre-caching
 *
 * Purpose:
 * Aggressively caches loyalty cards, stamp cards, and vouchers for offline use.
 * Automatically caches all cards visible on any page.
 *
 * Features:
 * - Pre-caches all cards when any page loads (homepage, my-cards, etc.)
 * - Caches card QR codes in the background
 * - Stores card name, balance, and timestamp
 * - Falls back to cached version when offline
 * - Provides cache clearing function for logout
 */

import QRCode from 'qrcode';

const CARDS_STORAGE_KEY = 'loyalty_cached_cards';
const MAX_CACHED_CARDS = 50;

/**
 * Get all cached cards from localStorage
 */
function getCachedCards() {
    try {
        const data = localStorage.getItem(CARDS_STORAGE_KEY);
        return data ? JSON.parse(data) : {};
    } catch (e) {
        return {};
    }
}

/**
 * Save cards to localStorage
 */
function saveCachedCards(cards) {
    try {
        // Limit number of cards to prevent overflow
        const cardIds = Object.keys(cards);
        if (cardIds.length > MAX_CACHED_CARDS) {
            // Remove oldest cards first
            const sorted = cardIds.sort((a, b) => 
                (cards[a].cachedAt || 0) - (cards[b].cachedAt || 0)
            );
            const toRemove = sorted.slice(0, cardIds.length - MAX_CACHED_CARDS);
            toRemove.forEach(id => delete cards[id]);
        }
        
        localStorage.setItem(CARDS_STORAGE_KEY, JSON.stringify(cards));
        return true;
    } catch (e) {
        return false;
    }
}

/**
 * Generate QR code and return SVG data URL
 */
async function generateQrDataUrl(url, colorLight = '#fff', colorDark = '#000') {
    const opts = {
        errorCorrectionLevel: 'H',
        type: 'svg',
        margin: 2,
        color: {
            dark: colorDark,
            light: colorLight,
        },
        width: 512,
    };
    
    return new Promise((resolve, reject) => {
        QRCode.toString(url, opts, (error, svgString) => {
            if (error) {
                reject(error);
            } else {
                resolve('data:image/svg+xml;base64,' + btoa(svgString));
            }
        });
    });
}

/**
 * Cache a card with QR code generated in background
 */
async function cacheCardData(cardData) {
    if (!cardData.id || !cardData.qrUrl) {
        return;
    }
    
    try {
        const cards = getCachedCards();
        
        // Skip if already cached recently (within 1 hour)
        const existing = cards[cardData.id];
        if (existing && existing.cachedAt && (Date.now() - existing.cachedAt) < 3600000) {
            return;
        }
        
        // Generate QR code in background
        const qrSvg = await generateQrDataUrl(
            cardData.qrUrl, 
            cardData.qrColorLight || '#FCFCFC', 
            cardData.qrColorDark || '#1F1F1F'
        );
        
        cards[cardData.id] = {
            id: cardData.id,
            type: cardData.type || 'loyalty',
            name: cardData.name || 'Loyalty Card',
            balance: cardData.balance || '',
            qrSvg: qrSvg,
            cachedAt: Date.now()
        };
        
        saveCachedCards(cards);
    } catch (e) {
        // Silent fail
    }
}

/**
 * Scan page for cards to pre-cache
 * Looks for elements with data-pwa-cache attribute
 */
function precacheVisibleCards() {
    const cardElements = document.querySelectorAll('[data-pwa-card]');
    
    if (cardElements.length === 0) {
        return;
    }
    
    // Process each card in background
    cardElements.forEach(element => {
        const cardData = {
            id: element.dataset.pwaCardId,
            type: element.dataset.pwaCardType || 'loyalty',
            name: element.dataset.pwaCardName,
            balance: element.dataset.pwaCardBalance,
            qrUrl: element.dataset.pwaCardQr,
            qrColorLight: element.dataset.pwaQrLight || '#FCFCFC',
            qrColorDark: element.dataset.pwaQrDark || '#1F1F1F'
        };
        
        if (cardData.id && cardData.qrUrl) {
            // Use setTimeout to not block rendering
            setTimeout(() => cacheCardData(cardData), 100);
        }
    });
}

/**
 * Enhanced QR code processing with caching support
 * This handles QR modals when they're opened
 */
window.processQrCodes = function() {
    const elements = document.querySelectorAll('[data-qr-url]');
    
    elements.forEach(function(element) {
        const url = element.getAttribute('data-qr-url');
        
        // Skip if URL is empty or invalid
        if (!url || url.trim() === '' || url === 'null' || url === 'undefined') {
            return;
        }
        
        const colorLight = element.getAttribute('data-qr-color-light') || '#fff';
        const colorDark = element.getAttribute('data-qr-color-dark') || '#000';
        const shouldCache = element.hasAttribute('data-qr-cache');
        const cardId = element.getAttribute('data-card-id');
        
        const opts = {
            errorCorrectionLevel: 'H',
            type: 'svg',
            margin: 2,
            color: {
                dark: colorDark,
                light: colorLight,
            },
            width: 512,
        };

        // Try to load cached version first if offline
        if (!navigator.onLine && shouldCache && cardId) {
            const cards = getCachedCards();
            if (cards[cardId] && cards[cardId].qrSvg) {
                element.src = cards[cardId].qrSvg;
                return;
            }
        }

        // Generate QR code
        QRCode.toString(url, opts, function(error, svgString) {
            if (error) {
                // Try cached version on error
                if (shouldCache && cardId) {
                    const cards = getCachedCards();
                    if (cards[cardId] && cards[cardId].qrSvg) {
                        element.src = cards[cardId].qrSvg;
                    }
                }
            } else {
                // Convert SVG to Data URL
                const svgDataUrl = 'data:image/svg+xml;base64,' + btoa(svgString);
                element.src = svgDataUrl;
                
                // Cache if marked for caching and has card ID
                if (shouldCache && cardId) {
                    cacheCardFromElement(element, cardId, svgDataUrl);
                }
            }
        });
    });
};

/**
 * Cache a card from QR modal element
 */
function cacheCardFromElement(qrElement, cardId, svgDataUrl) {
    try {
        const cards = getCachedCards();
        
        // Find card info from data attributes
        const container = qrElement.closest('[data-card-info]');
        const name = container?.getAttribute('data-card-name') || 'Loyalty Card';
        const balance = container?.getAttribute('data-card-balance') || '';
        
        cards[cardId] = {
            id: cardId,
            name: name,
            balance: balance,
            qrSvg: svgDataUrl,
            cachedAt: Date.now()
        };
        
        saveCachedCards(cards);
    } catch (e) {
        // Silent fail
    }
}

/**
 * Clear PWA cache - call this on logout
 */
window.clearPwaCache = function() {
    // Clear localStorage
    localStorage.removeItem(CARDS_STORAGE_KEY);
    // Legacy cleanup
    localStorage.removeItem('loyalty_qr_svg');
    localStorage.removeItem('loyalty_card_info');
    localStorage.removeItem('loyalty_cached_at');
    
    // Clear service worker caches
    if ('caches' in window) {
        caches.keys().then(function(names) {
            names.forEach(function(name) {
                caches.delete(name);
            });
        });
    }
};

/**
 * Get cached cards for display (exported for offline page)
 */
window.getCachedCards = getCachedCards;

/**
 * Force cache a specific card (for programmatic caching)
 */
window.pwaCacheCard = function(cardData) {
    return cacheCardData(cardData);
};

/**
 * Initialize on page load
 */
function initialize() {
    // Process QR codes in DOM
    if (typeof window.processQrCodes === 'function') {
        window.processQrCodes();
    }
    
    // Pre-cache all visible cards in background
    setTimeout(precacheVisibleCards, 500);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initialize);
} else {
    initialize();
}
