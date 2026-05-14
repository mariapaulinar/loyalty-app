'use strict';

/**
 * Reward Loyalty — Member Session Manager
 *
 * Purpose:
 * Manages anonymous member identity for the PWA/web app.
 * Implements the "Brawl Stars" model: play first, register later.
 *
 * How it works:
 * - Generates a device UUID and stores it in localStorage
 * - Registers the device with the backend to get a short code (e.g., "4K7X")
 * - The code can be used to switch devices or shown to staff
 * - Email registration is optional, for notifications and cross-device sync
 *
 * Architecture:
 * - Device UUID: Generated client-side with crypto.randomUUID()
 * - Member Code: Short configurable-length code from API (for QR/staff lookup)
 * - Both stored in localStorage for persistence
 *
 * @copyright 2026 NowSquare
 * @see App\Services\Member\AnonymousMemberService
 */

const MEMBER_SESSION_STORAGE_KEYS = {
    deviceUuid: 'member_device_uuid',
    memberCode: 'member_code',
    memberId: 'member_id',
    displayName: 'member_display_name',
    isAnonymous: 'member_is_anonymous',
    uniqueIdentifier: 'member_unique_identifier',
    lastSync: 'member_last_sync'
};

// How often to re-sync with server (24 hours)
const MEMBER_SYNC_INTERVAL_MS = 24 * 60 * 60 * 1000;

/**
 * Get current locale from HTML lang attribute
 * @returns {string}
 */
function getMemberSessionLocale() {
    const htmlLang = document.documentElement.lang || 'en-us';
    return htmlLang.toLowerCase();
}

/**
 * Generate a RFC 4122 v4 UUID
 * @returns {string}
 */
function generateMemberUUID() {
    if (typeof crypto !== 'undefined' && crypto.randomUUID) {
        return crypto.randomUUID();
    }

    // Fallback for older browsers
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        const r = Math.random() * 16 | 0;
        const v = c === 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}

/**
 * Get stored member session data
 * @returns {{deviceUuid: string|null, memberCode: string|null, memberId: string|null, displayName: string|null, isAnonymous: boolean, uniqueIdentifier: string|null, lastSync: number}}
 */
function getMemberStoredSession() {
    try {
        return {
            deviceUuid: localStorage.getItem(MEMBER_SESSION_STORAGE_KEYS.deviceUuid),
            memberCode: localStorage.getItem(MEMBER_SESSION_STORAGE_KEYS.memberCode),
            memberId: localStorage.getItem(MEMBER_SESSION_STORAGE_KEYS.memberId),
            displayName: localStorage.getItem(MEMBER_SESSION_STORAGE_KEYS.displayName),
            isAnonymous: localStorage.getItem(MEMBER_SESSION_STORAGE_KEYS.isAnonymous) !== 'false',
            uniqueIdentifier: localStorage.getItem(MEMBER_SESSION_STORAGE_KEYS.uniqueIdentifier),
            lastSync: parseInt(localStorage.getItem(MEMBER_SESSION_STORAGE_KEYS.lastSync) || '0', 10)
        };
    } catch (e) {
        console.warn('[MemberSession] Could not read localStorage:', e);
        return {
            deviceUuid: null,
            memberCode: null,
            memberId: null,
            displayName: null,
            isAnonymous: true,
            uniqueIdentifier: null,
            lastSync: 0
        };
    }
}

/**
 * Store session data in localStorage and set cookie for SSR
 * @param {Object} data - Session data to store
 * @returns {boolean}
 */
function storeMemberSession(data) {
    try {
        if (data.deviceUuid) {
            localStorage.setItem(MEMBER_SESSION_STORAGE_KEYS.deviceUuid, data.deviceUuid);
            // Also set as cookie so server can read it for SSR
            document.cookie = `member_device_uuid=${data.deviceUuid}; path=/; max-age=31536000; SameSite=Lax`;
        }

        // Set timezone cookie for server-side use
        try {
            const timeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;
            if (timeZone) {
                document.cookie = `member_time_zone=${timeZone}; path=/; max-age=31536000; SameSite=Lax`;
            }
        } catch (e) {
            // Intl API not available in older browsers
        }
        if (data.memberCode) {
            localStorage.setItem(MEMBER_SESSION_STORAGE_KEYS.memberCode, data.memberCode);
        }
        if (data.memberId) {
            localStorage.setItem(MEMBER_SESSION_STORAGE_KEYS.memberId, data.memberId);
        }
        if (data.displayName) {
            localStorage.setItem(MEMBER_SESSION_STORAGE_KEYS.displayName, data.displayName);
        }
        if (typeof data.isAnonymous !== 'undefined') {
            localStorage.setItem(MEMBER_SESSION_STORAGE_KEYS.isAnonymous, data.isAnonymous ? 'true' : 'false');
        }
        if (data.uniqueIdentifier) {
            localStorage.setItem(MEMBER_SESSION_STORAGE_KEYS.uniqueIdentifier, data.uniqueIdentifier);
        }
        localStorage.setItem(MEMBER_SESSION_STORAGE_KEYS.lastSync, Date.now().toString());
        return true;
    } catch (e) {
        console.warn('[MemberSession] Could not store session data:', e);
        return false;
    }
}

/**
 * Clear all session data from localStorage and cookies
 * @returns {boolean}
 */
function clearMemberSession() {
    try {
        Object.values(MEMBER_SESSION_STORAGE_KEYS).forEach(key => localStorage.removeItem(key));
        document.cookie = 'member_device_uuid=; path=/; max-age=0';
        console.log('[MemberSession] Session cleared');
        return true;
    } catch (e) {
        console.warn('[MemberSession] Could not clear session:', e);
        return false;
    }
}

/**
 * Register device with backend API
 * @param {string} deviceUuid - The device UUID
 * @returns {Promise<{id: string, code: string, displayName: string, isAnonymous: boolean, uniqueIdentifier: string}|null>}
 */
async function registerMemberDevice(deviceUuid) {
    const locale = getMemberSessionLocale();
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const response = await fetch(`/${locale}/api/v1/member/init`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({ device_uuid: deviceUuid })
    });

    if (!response.ok) {
        const error = await response.json().catch(() => ({}));
        if (error.requires_login) {
            console.log('[MemberSession] Anonymous mode disabled, login required');
            return null;
        }
        throw new Error(`Server returned ${response.status}`);
    }

    const data = await response.json();

    if (!data.success || !data.member) {
        throw new Error('Invalid response from server');
    }

    console.log('[MemberSession] Registered with server:', {
        code: data.member.code,
        isNew: data.is_new
    });

    return {
        id: data.member.id,
        code: data.member.code,
        displayName: data.member.display_name,
        isAnonymous: data.member.is_anonymous,
        uniqueIdentifier: data.member.unique_identifier
    };
}

/**
 * Initialize member session
 * Creates device UUID if needed, syncs with server if online
 *
 * @returns {Promise<{deviceUuid: string, memberCode: string|null, memberId: string|null, displayName: string|null, isAnonymous: boolean, uniqueIdentifier: string|null}>}
 */
async function initMemberSession() {
    let session = getMemberStoredSession();

    // Ensure we have a device UUID
    if (!session.deviceUuid) {
        session.deviceUuid = generateMemberUUID();
        storeMemberSession({ deviceUuid: session.deviceUuid });
        console.log('[MemberSession] 🔑 New device UUID generated');
    } else {
        // Ensure cookie is set for existing sessions
        document.cookie = `member_device_uuid=${session.deviceUuid}; path=/; max-age=31536000; SameSite=Lax`;
    }

    // Always set timezone cookie (browser timezone can change)
    try {
        const timeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        if (timeZone) {
            document.cookie = `member_time_zone=${timeZone}; path=/; max-age=31536000; SameSite=Lax`;
        }
    } catch (e) {
        // Intl API not available
    }

    // Check if we need to sync with server
    const needsSync = !session.memberCode ||
        (Date.now() - session.lastSync > MEMBER_SYNC_INTERVAL_MS);

    // Register with server if online and needs sync
    if (navigator.onLine && needsSync) {
        try {
            const serverData = await registerMemberDevice(session.deviceUuid);
            if (serverData) {
                session.memberCode = serverData.code;
                session.memberId = serverData.id;
                session.displayName = serverData.displayName;
                session.isAnonymous = serverData.isAnonymous;
                session.uniqueIdentifier = serverData.uniqueIdentifier;
                storeMemberSession(session);
            }
        } catch (e) {
            console.warn('[MemberSession] Could not sync with server:', e.message);
        }
    }

    return session;
}

/**
 * Switch to a different member account using their device code
 * @param {string} code - The device code (e.g., "4K7X")
 * @returns {Promise<{success: boolean, message?: string, member?: Object}>}
 */
async function switchMemberSession(code) {
    const session = getMemberStoredSession();
    if (!session.deviceUuid) {
        return { success: false, message: 'No session found' };
    }

    const locale = getMemberSessionLocale();
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    try {
        const response = await fetch(`/${locale}/api/v1/member/session/switch`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                code: code,
                device_uuid: session.deviceUuid
            })
        });

        const data = await response.json();

        if (!data.success) {
            return { success: false, message: data.message || 'Switch failed' };
        }

        // Update local storage with new member data
        storeMemberSession({
            deviceUuid: session.deviceUuid,
            memberCode: data.member.code,
            memberId: data.member.id,
            displayName: data.member.display_name,
            isAnonymous: data.member.is_anonymous,
            uniqueIdentifier: data.member.unique_identifier
        });

        return { success: true, member: data.member };
    } catch (e) {
        console.error('[MemberSession] Switch failed:', e);
        return { success: false, message: 'Network error' };
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// ALPINE.JS INTEGRATION
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Alpine.js component for member session management
 * Usage: x-data="memberSession()"
 */
window.memberSession = function() {
    return {
        // State
        deviceUuid: null,
        memberCode: null,
        memberId: null,
        displayName: null,
        uniqueIdentifier: null,
        isAnonymous: true,
        isLoading: true,
        isOnline: navigator.onLine,

        // Lifecycle
        async init() {
            // Listen for online/offline events
            window.addEventListener('online', () => {
                this.isOnline = true;
                this.syncSession();
            });
            window.addEventListener('offline', () => {
                this.isOnline = false;
            });

            await this.initSession();
        },

        // Initialize session
        async initSession() {
            this.isLoading = true;

            try {
                const session = await initMemberSession();
                this.deviceUuid = session.deviceUuid;
                this.memberCode = session.memberCode;
                this.memberId = session.memberId;
                this.displayName = session.displayName;
                this.isAnonymous = session.isAnonymous;
                this.uniqueIdentifier = session.uniqueIdentifier;
            } catch (e) {
                console.error('[MemberSession] Init failed:', e);
            }

            this.isLoading = false;
        },

        // Sync with server
        async syncSession() {
            if (!this.isOnline || !this.deviceUuid) return;

            try {
                const serverData = await registerMemberDevice(this.deviceUuid);
                if (serverData) {
                    this.memberCode = serverData.code;
                    this.memberId = serverData.id;
                    this.displayName = serverData.displayName;
                    this.isAnonymous = serverData.isAnonymous;
                    this.uniqueIdentifier = serverData.uniqueIdentifier;
                    storeMemberSession({
                        deviceUuid: this.deviceUuid,
                        memberCode: this.memberCode,
                        memberId: this.memberId,
                        displayName: this.displayName,
                        isAnonymous: this.isAnonymous,
                        uniqueIdentifier: this.uniqueIdentifier
                    });
                }
            } catch (e) {
                console.warn('[MemberSession] Sync failed:', e);
            }
        },

        // Switch to different account
        async switchDevice(code) {
            const result = await switchMemberSession(code);
            if (result.success) {
                // Reload to apply changes
                window.location.reload();
            }
            return result;
        },

        // Clear session (forget device)
        forgetMe() {
            clearMemberSession();
            this.deviceUuid = null;
            this.memberCode = null;
            this.memberId = null;
            this.displayName = null;
            this.isAnonymous = true;
            this.uniqueIdentifier = null;
        }
    };
};

// ═══════════════════════════════════════════════════════════════════════════
// GLOBAL API
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Global MemberSession API for non-Alpine usage
 */
window.MemberSession = {
    init: initMemberSession,
    getSession: getMemberStoredSession,
    clear: clearMemberSession,
    switch: switchMemberSession
};

/**
 * Immediately set essential cookies on module load.
 * This runs synchronously before anything else, ensuring the server
 * has access to browser timezone on the very next request.
 * 
 * FIRST VISIT HANDLING:
 * If this is the user's first visit (no timezone cookie exists), we set
 * the cookie and reload. The server-rendered loading screen (in the Blade
 * layout) is already visible, so no flash occurs.
 */
(function setEssentialCookies() {
    // Check if timezone cookie already exists
    const existingTimeZone = document.cookie.split('; ').find(row => row.startsWith('member_time_zone='));
    
    // Set timezone cookie immediately
    try {
        const timeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        if (timeZone) {
            document.cookie = `member_time_zone=${timeZone}; path=/; max-age=31536000; SameSite=Lax`;
            
            // FIRST VISIT: If cookie didn't exist before, reload immediately
            // Server already renders a loading screen, so no need for JS loader
            if (!existingTimeZone) {
                window.location.reload();
                return;
            }
        }
    } catch (e) {
        // Intl API not available in very old browsers
    }

    // Ensure device UUID cookie is synchronized with localStorage
    try {
        const storedUuid = localStorage.getItem(MEMBER_SESSION_STORAGE_KEYS.deviceUuid);
        if (storedUuid) {
            document.cookie = `member_device_uuid=${storedUuid}; path=/; max-age=31536000; SameSite=Lax`;
        }
    } catch (e) {
        // localStorage not available
    }
})();