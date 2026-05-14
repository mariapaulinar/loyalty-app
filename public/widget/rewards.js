'use strict';

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Shopify Rewards Widget
 *
 * A premium loyalty widget for Shopify storefronts. Inspired by iOS, Stripe, and Revolut
 * design systems - clean, fast, and delightful.
 *
 * Features:
 * - Guest mode: Shows earn rate + reward preview without API calls
 * - Member mode: Shows balance, full rewards catalog, and redemption flow
 * - Seamless redemption: Discount code → Apply & Checkout redirect
 *
 * Usage (in Shopify theme):
 * <script>
 *   window.RewardLoyaltyConfig = {
 *     integrationId: 'uuid',
 *     apiKey: 'rl_pub_xxx',
 *     apiBase: 'https://app.example.com/api/widget',
 *     customerId: {{ customer.id | default: 'null' }},
 *     customerEmail: '{{ customer.email | default: "" }}'
 *   };
 * </script>
 * <script src="https://app.example.com/widget/rewards.js" defer></script>
 * <link rel="stylesheet" href="https://app.example.com/widget/rewards.css">
 */

(function () {
  // ─────────────────────────────────────────────────────────────────────────
  // CONFIGURATION
  // ─────────────────────────────────────────────────────────────────────────

  const CONFIG = window.RewardLoyaltyConfig || {};

  const INTEGRATION_ID = CONFIG.integrationId || '';
  const API_KEY = CONFIG.apiKey || '';
  const API_BASE = CONFIG.apiBase || '';
  const CUSTOMER_ID = CONFIG.customerId || null;
  const CUSTOMER_EMAIL = CONFIG.customerEmail || '';

  // ─────────────────────────────────────────────────────────────────────────
  // STATE
  // ─────────────────────────────────────────────────────────────────────────

  let state = {
    isGuest: true,
    isOpen: false,
    isLoading: false,
    isRedeeming: false,
    config: null,
    error: null,
    activeTab: 'rewards',
    selectedReward: null,
    redeemResult: null,
  };

  // ─────────────────────────────────────────────────────────────────────────
  // UTILITIES
  // ─────────────────────────────────────────────────────────────────────────

  function isGuest() {
    return !CUSTOMER_ID && !CUSTOMER_EMAIL;
  }

  function getMemberIdentifier() {
    return CUSTOMER_EMAIL || CUSTOMER_ID || null;
  }

  async function apiRequest(endpoint, options = {}) {
    const url = `${API_BASE}/${INTEGRATION_ID}${endpoint}`;
    const headers = {
      'Content-Type': 'application/json',
      'X-API-Key': API_KEY,
      ...options.headers,
    };

    const response = await fetch(url, {
      ...options,
      headers,
    });

    if (!response.ok) {
      const errorData = await response.json().catch(() => ({}));
      throw new Error(errorData.error || errorData.message || `Request failed: ${response.status}`);
    }

    return response.json();
  }

  function formatPoints(points) {
    return new Intl.NumberFormat().format(points);
  }

  function formatCurrency(value, currency = 'USD') {
    const amount = typeof value === 'number' ? value / 100 : parseFloat(value) || 0;
    return new Intl.NumberFormat(undefined, {
      style: 'currency',
      currency: currency,
      minimumFractionDigits: 0,
      maximumFractionDigits: 2,
    }).format(amount);
  }

  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  // ─────────────────────────────────────────────────────────────────────────
  // API CALLS
  // ─────────────────────────────────────────────────────────────────────────

  async function fetchConfig() {
    const memberIdentifier = getMemberIdentifier();
    const query = memberIdentifier ? `?member_identifier=${encodeURIComponent(memberIdentifier)}` : '';
    const data = await apiRequest(`/config${query}`);
    return data.config;
  }

  async function redeemReward(integrationRewardId, memberIdentifier) {
    return apiRequest('/redeem', {
      method: 'POST',
      body: JSON.stringify({
        integration_reward_id: integrationRewardId,
        member_identifier: memberIdentifier,
      }),
    });
  }

  // ─────────────────────────────────────────────────────────────────────────
  // RENDERING
  // ─────────────────────────────────────────────────────────────────────────

  function render() {
    const container = document.getElementById('rl-widget');
    if (!container) return;

    const primaryColor = state.config?.branding?.primary_color || '#F59E0B';
    container.style.setProperty('--rl-primary', primaryColor);
    container.style.setProperty('--rl-primary-light', primaryColor + '20');

    container.innerHTML = `
      ${renderLauncher()}
      ${state.isOpen ? renderPanel() : ''}
    `;

    attachEventListeners();
  }

  function renderLauncher() {
    const programName = state.config?.branding?.program_name || 'Rewards';
    const balance = state.config?.member?.balance;
    const showBadge = !state.isGuest && typeof balance === 'number';

    return `
      <button class="rl-launcher" aria-label="Open ${escapeHtml(programName)}" data-action="toggle">
        <svg class="rl-launcher-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/>
        </svg>
        ${showBadge ? `<span class="rl-launcher-badge">${formatPoints(balance)}</span>` : ''}
      </button>
    `;
  }

  function renderPanel() {
    const programName = state.config?.branding?.program_name || 'Rewards';

    return `
      <div class="rl-panel ${state.isLoading ? 'rl-panel--loading' : ''}" role="dialog" aria-label="${escapeHtml(programName)}">
        <div class="rl-panel-header">
          <h2 class="rl-panel-title">${escapeHtml(programName)}</h2>
          <button class="rl-panel-close" aria-label="Close" data-action="close">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 6L6 18M6 6l12 12"/>
            </svg>
          </button>
        </div>
        <div class="rl-panel-body">
          ${state.error ? renderError() : ''}
          ${state.redeemResult ? renderRedeemResult() : ''}
          ${!state.error && !state.redeemResult ? renderContent() : ''}
        </div>
      </div>
      <div class="rl-backdrop" data-action="close"></div>
    `;
  }

  function renderContent() {
    if (state.isLoading) {
      return renderLoading();
    }

    if (state.isGuest) {
      return renderGuestState();
    }

    return renderMemberState();
  }

  function renderLoading() {
    return `
      <div class="rl-loading">
        <div class="rl-spinner"></div>
        <p>Loading your rewards...</p>
      </div>
    `;
  }

  function renderError() {
    return `
      <div class="rl-error">
        <svg class="rl-error-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"/>
          <path d="M12 8v4M12 16h.01"/>
        </svg>
        <p class="rl-error-text">${escapeHtml(state.error)}</p>
        <button class="rl-btn rl-btn--secondary" data-action="retry">Try Again</button>
      </div>
    `;
  }

  function renderGuestState() {
    const earnRate = state.config?.earn_rate;
    const rewards = state.config?.rewards || [];
    const previewRewards = rewards.slice(0, 3);

    return `
      <div class="rl-guest">
        ${renderEarnRateCard(earnRate)}
        
        <div class="rl-guest-cta">
          <div class="rl-guest-cta-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
              <circle cx="12" cy="7" r="4"/>
            </svg>
          </div>
          <p class="rl-guest-cta-text">Log in to see your points</p>
        </div>

        ${previewRewards.length > 0 ? `
          <div class="rl-section">
            <h3 class="rl-section-title">Rewards Preview</h3>
            <div class="rl-rewards-list">
              ${previewRewards.map(r => renderRewardCard(r, true)).join('')}
            </div>
            ${rewards.length > 3 ? `
              <p class="rl-rewards-more">+${rewards.length - 3} more rewards</p>
            ` : ''}
          </div>
        ` : ''}
      </div>
    `;
  }

  function renderMemberState() {
    const member = state.config?.member;
    const earnRate = state.config?.earn_rate;
    const rewards = state.config?.rewards || [];

    if (state.selectedReward) {
      return renderRedeemConfirm();
    }

    return `
      <div class="rl-member">
        ${renderBalanceCard(member)}
        ${renderEarnRateCard(earnRate)}

        <div class="rl-tabs">
          <button class="rl-tab ${state.activeTab === 'rewards' ? 'rl-tab--active' : ''}" data-action="tab" data-tab="rewards">
            Rewards
          </button>
        </div>

        <div class="rl-tab-content">
          ${state.activeTab === 'rewards' ? renderRewardsTab(rewards) : ''}
        </div>
      </div>
    `;
  }

  function renderBalanceCard(member) {
    if (!member) return '';

    return `
      <div class="rl-balance-card">
        <div class="rl-balance-label">Your Points</div>
        <div class="rl-balance-value">${formatPoints(member.balance)}</div>
        ${member.name ? `<div class="rl-balance-name">${escapeHtml(member.name)}</div>` : ''}
      </div>
    `;
  }

  function renderEarnRateCard(earnRate) {
    if (!earnRate) return '';

    return `
      <div class="rl-earn-card">
        <div class="rl-earn-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
          </svg>
        </div>
        <div class="rl-earn-content">
          <div class="rl-earn-rate">${formatPoints(earnRate.points_per_currency)} pts / ${earnRate.currency}</div>
          <div class="rl-earn-desc">Earn points on every purchase</div>
        </div>
      </div>
    `;
  }

  function renderRewardsTab(rewards) {
    if (rewards.length === 0) {
      return `
        <div class="rl-empty">
          <p>No rewards available yet.</p>
        </div>
      `;
    }

    return `
      <div class="rl-rewards-list">
        ${rewards.map(r => renderRewardCard(r, state.isGuest)).join('')}
      </div>
    `;
  }

  function renderRewardCard(reward, isPreview = false) {
    const canAfford = reward.can_afford;
    const discountLabel = formatDiscount(reward.discount_type, reward.discount_value, state.config?.earn_rate?.currency);

    return `
      <div class="rl-reward-card ${!canAfford && !isPreview ? 'rl-reward-card--locked' : ''}">
        ${reward.image ? `
          <div class="rl-reward-image">
            <img src="${escapeHtml(reward.image)}" alt="" loading="lazy">
          </div>
        ` : ''}
        <div class="rl-reward-content">
          <div class="rl-reward-title">${escapeHtml(reward.title)}</div>
          <div class="rl-reward-discount">${escapeHtml(discountLabel)}</div>
          <div class="rl-reward-points">${formatPoints(reward.points_required)} pts</div>
        </div>
        ${!isPreview ? `
          <button 
            class="rl-btn rl-btn--small ${canAfford ? 'rl-btn--primary' : 'rl-btn--disabled'}"
            ${canAfford ? `data-action="select-reward" data-reward-id="${escapeHtml(reward.integration_reward_id)}"` : 'disabled'}
          >
            ${canAfford ? 'Redeem' : 'Locked'}
          </button>
        ` : ''}
      </div>
    `;
  }

  function renderRedeemConfirm() {
    const reward = state.selectedReward;
    const member = state.config?.member;
    const discountLabel = formatDiscount(reward.discount_type, reward.discount_value, state.config?.earn_rate?.currency);

    return `
      <div class="rl-confirm">
        <button class="rl-back" data-action="back" aria-label="Back">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5M12 19l-7-7 7-7"/>
          </svg>
        </button>

        <div class="rl-confirm-card">
          <div class="rl-confirm-title">${escapeHtml(reward.title)}</div>
          <div class="rl-confirm-discount">${escapeHtml(discountLabel)}</div>
          
          <div class="rl-confirm-details">
            <div class="rl-confirm-row">
              <span>Points Required</span>
              <span class="rl-confirm-value">${formatPoints(reward.points_required)}</span>
            </div>
            <div class="rl-confirm-row">
              <span>Your Balance</span>
              <span class="rl-confirm-value">${formatPoints(member?.balance || 0)}</span>
            </div>
            <div class="rl-confirm-divider"></div>
            <div class="rl-confirm-row rl-confirm-row--highlight">
              <span>After Redemption</span>
              <span class="rl-confirm-value">${formatPoints((member?.balance || 0) - reward.points_required)}</span>
            </div>
          </div>
        </div>

        <button 
          class="rl-btn rl-btn--primary rl-btn--full ${state.isRedeeming ? 'rl-btn--loading' : ''}"
          data-action="confirm-redeem"
          ${state.isRedeeming ? 'disabled' : ''}
        >
          ${state.isRedeeming ? 'Processing...' : 'Confirm & Get Discount'}
        </button>
      </div>
    `;
  }

  function renderRedeemResult() {
    const result = state.redeemResult;

    if (!result.success) {
      return `
        <div class="rl-result rl-result--error">
          <div class="rl-result-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/>
              <path d="M15 9l-6 6M9 9l6 6"/>
            </svg>
          </div>
          <h3 class="rl-result-title">Redemption Failed</h3>
          <p class="rl-result-text">${escapeHtml(result.error || 'Something went wrong')}</p>
          <button class="rl-btn rl-btn--secondary" data-action="dismiss-result">Try Again</button>
        </div>
      `;
    }

    const discount = result.discount;
    const isCodeBased = discount?.kind === 'code' && discount?.code;

    if (isCodeBased) {
      return `
        <div class="rl-result rl-result--success">
          <div class="rl-result-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
              <path d="M22 4L12 14.01l-3-3"/>
            </svg>
          </div>
          <h3 class="rl-result-title">Reward Unlocked!</h3>
          
          <div class="rl-code-box">
            <div class="rl-code-label">Your Discount Code</div>
            <div class="rl-code-value">${escapeHtml(discount.code)}</div>
            <button class="rl-code-copy" data-action="copy-code" data-code="${escapeHtml(discount.code)}" aria-label="Copy code">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
              </svg>
            </button>
          </div>

          <a href="${escapeHtml(discount.apply_url)}" class="rl-btn rl-btn--primary rl-btn--full">
            Apply & Checkout
          </a>
          
          <p class="rl-result-balance">New balance: ${formatPoints(result.new_balance)} pts</p>
        </div>
      `;
    }

    // Automatic discount
    return `
      <div class="rl-result rl-result--success">
        <div class="rl-result-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
            <path d="M22 4L12 14.01l-3-3"/>
          </svg>
        </div>
        <h3 class="rl-result-title">Discount Applied!</h3>
        <p class="rl-result-text">Your discount will automatically apply at checkout.</p>
        
        <a href="${escapeHtml(discount?.apply_url || '/checkout')}" class="rl-btn rl-btn--primary rl-btn--full">
          Go to Checkout
        </a>
        
        <p class="rl-result-balance">New balance: ${formatPoints(result.new_balance)} pts</p>
      </div>
    `;
  }

  function formatDiscount(type, value, currency = 'USD') {
    if (type === 'percentage') {
      return `${value}% Off`;
    }
    if (type === 'fixed_amount') {
      return `${formatCurrency(value, currency)} Off`;
    }
    if (type === 'free_shipping') {
      return 'Free Shipping';
    }
    return 'Discount';
  }

  // ─────────────────────────────────────────────────────────────────────────
  // EVENT HANDLING
  // ─────────────────────────────────────────────────────────────────────────

  function attachEventListeners() {
    const container = document.getElementById('rl-widget');
    if (!container) return;

    container.addEventListener('click', handleClick);
  }

  function handleClick(event) {
    const action = event.target.closest('[data-action]')?.dataset.action;
    if (!action) return;

    event.preventDefault();

    switch (action) {
      case 'toggle':
        togglePanel();
        break;
      case 'close':
        closePanel();
        break;
      case 'retry':
        retryLoad();
        break;
      case 'tab':
        switchTab(event.target.closest('[data-tab]').dataset.tab);
        break;
      case 'select-reward':
        selectReward(event.target.closest('[data-reward-id]').dataset.rewardId);
        break;
      case 'back':
        clearSelection();
        break;
      case 'confirm-redeem':
        confirmRedeem();
        break;
      case 'dismiss-result':
        dismissResult();
        break;
      case 'copy-code':
        copyCode(event.target.closest('[data-code]').dataset.code);
        break;
    }
  }

  function togglePanel() {
    if (state.isOpen) {
      closePanel();
    } else {
      openPanel();
    }
  }

  function openPanel() {
    state.isOpen = true;
    render();

    if (!state.config && !state.isGuest) {
      loadConfig();
    } else if (!state.config && state.isGuest) {
      // For guests, still load config to get earn rate and rewards preview
      loadConfig();
    }
  }

  function closePanel() {
    state.isOpen = false;
    state.selectedReward = null;
    state.redeemResult = null;
    state.error = null;
    render();
  }

  async function loadConfig() {
    state.isLoading = true;
    state.error = null;
    render();

    try {
      state.config = await fetchConfig();
      state.isGuest = isGuest();
    } catch (err) {
      state.error = err.message || 'Failed to load rewards';
    } finally {
      state.isLoading = false;
      render();
    }
  }

  function retryLoad() {
    state.error = null;
    loadConfig();
  }

  function switchTab(tab) {
    state.activeTab = tab;
    render();
  }

  function selectReward(rewardId) {
    const reward = state.config?.rewards?.find(r => r.integration_reward_id === rewardId);
    if (reward) {
      state.selectedReward = reward;
      render();
    }
  }

  function clearSelection() {
    state.selectedReward = null;
    render();
  }

  async function confirmRedeem() {
    if (state.isRedeeming || !state.selectedReward) return;

    const memberIdentifier = getMemberIdentifier();
    if (!memberIdentifier) {
      state.redeemResult = { success: false, error: 'Please log in to redeem rewards' };
      render();
      return;
    }

    state.isRedeeming = true;
    render();

    try {
      const result = await redeemReward(state.selectedReward.integration_reward_id, memberIdentifier);
      state.redeemResult = result;
      state.selectedReward = null;

      // Update member balance in config
      if (result.success && typeof result.new_balance === 'number' && state.config?.member) {
        state.config.member.balance = result.new_balance;
      }
    } catch (err) {
      state.redeemResult = { success: false, error: err.message || 'Redemption failed' };
    } finally {
      state.isRedeeming = false;
      render();
    }
  }

  function dismissResult() {
    state.redeemResult = null;
    render();
  }

  function copyCode(code) {
    if (navigator.clipboard) {
      navigator.clipboard.writeText(code).then(() => {
        const copyBtn = document.querySelector('[data-action="copy-code"]');
        if (copyBtn) {
          copyBtn.classList.add('rl-code-copy--success');
          setTimeout(() => copyBtn.classList.remove('rl-code-copy--success'), 2000);
        }
      });
    }
  }

  // ─────────────────────────────────────────────────────────────────────────
  // INITIALIZATION
  // ─────────────────────────────────────────────────────────────────────────

  function init() {
    if (!INTEGRATION_ID || !API_KEY || !API_BASE) {
      console.error('[RewardLoyalty] Missing configuration. Ensure integrationId, apiKey, and apiBase are set.');
      return;
    }

    // Determine guest state immediately (no API call needed)
    state.isGuest = isGuest();

    // Create container
    let container = document.getElementById('rl-widget');
    if (!container) {
      container = document.createElement('div');
      container.id = 'rl-widget';
      container.className = `rl-widget rl-widget--${state.config?.branding?.position || 'bottom-right'}`;
      document.body.appendChild(container);
    }

    render();
  }

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
