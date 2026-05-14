<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Offline - {{ config('default.app_name') }}</title>
    <style>
        * { 
            box-sizing: border-box; 
            margin: 0; 
            padding: 0; 
        }

        /* Dark mode detection from localStorage */
        html.dark {
            color-scheme: dark;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem 1rem;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            color: #111827;
        }

        html.dark body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #f1f5f9;
        }

        .header { 
            text-align: center; 
            margin-bottom: 2rem;
        }

        .icon { 
            font-size: 3rem; 
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            width: 80px;
            height: 80px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px -10px rgba(99, 102, 241, 0.5);
        }

        html.dark .icon {
            box-shadow: 0 10px 30px -10px rgba(99, 102, 241, 0.3);
        }

        h1 { 
            font-size: 1.5rem; 
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .subtitle {
            color: #6b7280;
            font-size: 0.95rem;
        }

        html.dark .subtitle {
            color: #94a3b8;
        }

        /* Cards Grid */
        .cards-container {
            width: 100%;
            max-width: 800px;
            margin-bottom: 2rem;
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.25rem;
        }

        .card {
            background: white;
            border-radius: 1rem;
            padding: 1.25rem;
            box-shadow: 0 4px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        }

        html.dark .card {
            background: #1e293b;
            box-shadow: 0 4px 15px -3px rgba(0, 0, 0, 0.3), 0 4px 6px -4px rgba(0, 0, 0, 0.2);
        }

        html.dark .card:hover {
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4), 0 8px 10px -6px rgba(0, 0, 0, 0.3);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .card-name {
            font-size: 1rem;
            font-weight: 600;
            color: #1e293b;
        }

        html.dark .card-name {
            color: #f1f5f9;
        }

        .card-badge {
            font-size: 0.7rem;
            font-weight: 500;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            background: #dbeafe;
            color: #1d4ed8;
        }

        html.dark .card-badge {
            background: #1e3a5f;
            color: #93c5fd;
        }

        .qr-wrapper {
            background: white;
            padding: 0.75rem;
            border-radius: 0.75rem;
            display: flex;
            justify-content: center;
            margin-bottom: 1rem;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        html.dark .qr-wrapper {
            background: #f8fafc;
        }

        .qr-wrapper img {
            width: 140px;
            height: 140px;
            display: block;
        }

        .card-balance {
            font-size: 0.9rem;
            font-weight: 600;
            color: #4f46e5;
            text-align: center;
            padding: 0.5rem;
            background: #f5f3ff;
            border-radius: 0.5rem;
            margin-bottom: 0.75rem;
        }

        html.dark .card-balance {
            background: #312e81;
            color: #c4b5fd;
        }

        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.75rem;
            color: #9ca3af;
            padding-top: 0.75rem;
            border-top: 1px solid #e5e7eb;
        }

        html.dark .card-footer {
            color: #64748b;
            border-top-color: #334155;
        }

        /* No Cards Message */
        .no-cards {
            text-align: center;
            padding: 3rem 2rem;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 15px -3px rgba(0, 0, 0, 0.1);
        }

        html.dark .no-cards {
            background: #1e293b;
        }

        .no-cards-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .no-cards p {
            color: #6b7280;
            line-height: 1.6;
        }

        html.dark .no-cards p {
            color: #94a3b8;
        }

        /* Button */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.875rem 1.75rem;
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            color: white;
            border-radius: 0.75rem;
            text-decoration: none;
            font-weight: 600;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            box-shadow: 0 4px 15px -3px rgba(79, 70, 229, 0.4);
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px -3px rgba(79, 70, 229, 0.5);
        }

        .btn:active { 
            transform: translateY(0);
        }

        .btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        /* Spinner */
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .spinner {
            width: 1rem;
            height: 1rem;
            border: 2px solid currentColor;
            border-right-color: transparent;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }

        /* Cards count badge */
        .cards-count {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            color: #6b7280;
            margin-bottom: 1rem;
        }

        html.dark .cards-count {
            color: #94a3b8;
        }

        .count-badge {
            background: #4f46e5;
            color: white;
            font-weight: 600;
            padding: 0.125rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="icon">📱</div>
        <h1>You're Offline</h1>
        <p class="subtitle">Your saved loyalty cards are available below</p>
    </div>

    <div class="cards-container">
        <div id="cards-count" class="cards-count" style="display: none;">
            <span class="count-badge" id="count-value">0</span>
            <span>cards saved for offline</span>
        </div>

        <div class="cards-grid" id="cards-grid"></div>

        <div class="no-cards" id="no-cards" style="display: none;">
            <div class="no-cards-icon">💳</div>
            <p>No cards saved yet.<br>Visit your loyalty cards while online to save them for offline use.</p>
        </div>
    </div>

    <button class="btn" onclick="tryReconnect()" id="retry-btn">
        <span id="btn-text">Try Reconnecting</span>
    </button>

    <script>
        'use strict';

        // Apply dark mode immediately to prevent flash
        (function() {
            if (localStorage.getItem('color-theme') === 'dark' || 
                (!('color-theme' in localStorage) && 
                 window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();

        // Format date nicely
        function formatDate(timestamp) {
            const date = new Date(timestamp);
            const now = new Date();
            const diff = now - date;
            
            // Less than 1 minute
            if (diff < 60000) return 'Just now';
            // Less than 1 hour
            if (diff < 3600000) return Math.floor(diff / 60000) + ' min ago';
            // Less than 24 hours
            if (diff < 86400000) return Math.floor(diff / 3600000) + ' hours ago';
            
            // Show date
            return date.toLocaleDateString(undefined, { 
                month: 'short', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // Load and display cached cards
        (function() {
            const grid = document.getElementById('cards-grid');
            const noCards = document.getElementById('no-cards');
            const cardsCount = document.getElementById('cards-count');
            const countValue = document.getElementById('count-value');
            
            // Try new multi-card format first
            let cards = {};
            try {
                const data = localStorage.getItem('loyalty_cached_cards');
                if (data) {
                    cards = JSON.parse(data);
                }
            } catch (e) {
                console.error('Failed to parse cached cards:', e);
            }
            
            // Legacy support: check old single-card format
            if (Object.keys(cards).length === 0) {
                const legacyQR = localStorage.getItem('loyalty_qr_svg');
                const legacyInfo = localStorage.getItem('loyalty_card_info');
                const legacyAt = localStorage.getItem('loyalty_cached_at');
                
                if (legacyQR) {
                    let info = { name: 'Your Card', balance: '' };
                    try {
                        if (legacyInfo) info = JSON.parse(legacyInfo);
                    } catch (e) {}
                    
                    cards['legacy'] = {
                        id: 'legacy',
                        name: info.name || 'Your Card',
                        balance: info.balance || '',
                        qrSvg: legacyQR,
                        cachedAt: legacyAt ? parseInt(legacyAt) : Date.now()
                    };
                }
            }
            
            const cardIds = Object.keys(cards);
            
            if (cardIds.length === 0) {
                noCards.style.display = 'block';
                return;
            }
            
            // Show count
            cardsCount.style.display = 'flex';
            countValue.textContent = cardIds.length;
            
            // Sort by most recently cached
            cardIds.sort((a, b) => (cards[b].cachedAt || 0) - (cards[a].cachedAt || 0));
            
            // Render cards
            cardIds.forEach(function(id) {
                const card = cards[id];
                const cardEl = document.createElement('div');
                cardEl.className = 'card';
                
                cardEl.innerHTML = `
                    <div class="card-header">
                        <div class="card-name">${escapeHtml(card.name)}</div>
                        <div class="card-badge">Cached</div>
                    </div>
                    <div class="qr-wrapper">
                        <img src="${card.qrSvg}" alt="QR Code for ${escapeHtml(card.name)}">
                    </div>
                    ${card.balance ? `<div class="card-balance">${escapeHtml(card.balance)}</div>` : ''}
                    <div class="card-footer">
                        <span>Saved ${formatDate(card.cachedAt)}</span>
                        <span>ID: ${id.slice(0, 8)}...</span>
                    </div>
                `;
                
                grid.appendChild(cardEl);
            });
        })();
        
        // Escape HTML to prevent XSS
        function escapeHtml(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        // Try to reconnect
        function tryReconnect() {
            const btn = document.getElementById('retry-btn');
            const btnText = document.getElementById('btn-text');
            
            btnText.textContent = 'Reconnecting...';
            btn.insertAdjacentHTML('beforeend', '<span class="spinner"></span>');
            btn.disabled = true;
            
            // Check if we're online
            if (navigator.onLine) {
                location.reload();
            } else {
                // Still offline, show feedback
                setTimeout(() => {
                    btnText.textContent = 'Still Offline';
                    const spinner = btn.querySelector('.spinner');
                    if (spinner) spinner.remove();
                    
                    setTimeout(() => {
                        btnText.textContent = 'Try Reconnecting';
                        btn.disabled = false;
                    }, 2000);
                }, 1000);
            }
        }

        // Listen for online event
        window.addEventListener('online', () => {
            location.reload();
        });
    </script>
</body>
</html>
