'use strict';

/**
 * Member WebSocket layer (Laravel Reverb + Echo)
 *
 * Listens for staff-awarded stamps and triggers the experience-rating modal.
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

function getBroadcastConfig(serverConfig) {
    if (serverConfig?.key) {
        const port = Number(serverConfig.port) || (serverConfig.scheme === 'https' ? 443 : 80);

        return {
            key: serverConfig.key,
            host: serverConfig.host || window.location.hostname,
            port,
            scheme: serverConfig.scheme || 'https',
        };
    }

    const key = import.meta.env.VITE_REVERB_APP_KEY;

    if (!key) {
        return null;
    }

    return {
        key,
        host: import.meta.env.VITE_REVERB_HOST ?? window.location.hostname,
        port: Number(import.meta.env.VITE_REVERB_PORT) || 8080,
        scheme: import.meta.env.VITE_REVERB_SCHEME ?? 'http',
    };
}

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

/**
 * Pusher-js treats HTTPS pages as TLS-only (wss://). Local Reverb on :8080 uses ws://.
 * Spoof the runtime protocol during construction so the first connection uses plain WS.
 */
function createReverbPusher(key, config) {
    const useTls = config.scheme === 'https';

    const options = {
        wsHost: config.host,
        wsPort: config.port,
        wssPort: config.port,
        forceTLS: useTls,
        enabledTransports: useTls ? ['wss'] : ['ws'],
        disableStats: true,
        cluster: '',
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                Accept: 'application/json',
            },
        },
    };

    if (useTls) {
        return new Pusher(key, options);
    }

    const runtime = Pusher.Runtime;
    const originalGetProtocol = runtime.getProtocol.bind(runtime);

    runtime.getProtocol = () => 'http:';

    try {
        return new Pusher(key, {
            ...options,
            forceTLS: false,
            enabledTransports: ['ws'],
        });
    } finally {
        runtime.getProtocol = originalGetProtocol;
    }
}

/**
 * Initialize Echo and subscribe to the member's private channel.
 *
 * @param {string} memberId
 * @param {(payload: object) => void} onStampEarned
 * @returns {import('laravel-echo').default|null}
 */
export function initMemberBroadcasting(memberId, onStampEarned, serverConfig) {
    const config = getBroadcastConfig(serverConfig);

    if (!config || !memberId || typeof onStampEarned !== 'function') {
        if (!config) {
            console.warn('[StampReview] Reverb no configurado — el modal en tiempo real no estará disponible.');
        }

        return null;
    }

    if (window.memberEcho) {
        return window.memberEcho;
    }

    const pusher = createReverbPusher(config.key, config);

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: config.key,
        client: pusher,
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                Accept: 'application/json',
            },
        },
    });

    window.memberEcho = window.Echo;

    if (pusher.connection) {
        pusher.connection.bind('connected', () => {
            const transport = config.scheme === 'https' ? 'wss' : 'ws';
            console.info('[StampReview] WebSocket conectado a Reverb (' + transport + '://' + config.host + ':' + config.port + ').');
        });

        pusher.connection.bind('error', (error) => {
            console.error('[StampReview] Error de conexión WebSocket:', error);
        });
    }

    const channel = window.Echo.private(`member.${memberId}`);

    channel.listen('.stamp.earned', (payload) => {
        onStampEarned(payload);
    });

    channel.error((error) => {
        console.error('[StampReview] Error en canal privado member.' + memberId, error);
    });

    return window.Echo;
}

window.initMemberBroadcasting = initMemberBroadcasting;
