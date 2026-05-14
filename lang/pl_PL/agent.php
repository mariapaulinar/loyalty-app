<?php

/**
 * Agent API translations.
 *
 * All strings related to the Agent API, Agent Keys, and the
 * Agentic Layer are centralized here instead of polluting common.php.
 *
 * Convention: keys use snake_case, grouped by feature area.
 */

return [
    // ─────────────────────────────────────────────────────────────────
    // Navigation & Titles
    // ─────────────────────────────────────────────────────────────────
    'agent_keys' => 'Agent Keys',
    'agent_key' => 'Agent Key',
    'agent_key_created' => 'Agent Key Created',

    // ─────────────────────────────────────────────────────────────────
    // DataDefinition Field Labels
    // ─────────────────────────────────────────────────────────────────
    'key_prefix' => 'Key Prefix',
    'last_used' => 'Last Used',
    'rate_limit_rpm' => 'Rate Limit',

    // ─────────────────────────────────────────────────────────────────
    // Scope Presets (shown in create form dropdown)
    // ─────────────────────────────────────────────────────────────────
    'scope_read' => 'View Only — View analytics, members, and reports',
    'scope_pos' => 'Point of Sale — Award points, stamps, and redeem rewards',
    'scope_standard' => 'Full Management — Manage cards, rewards, stamps, vouchers, and clubs',
    'scope_admin' => 'Full Access — All operations (use with caution)',

    // ─────────────────────────────────────────────────────────────────
    // Help Text / Descriptions
    // ─────────────────────────────────────────────────────────────────
    'help_name' => 'A descriptive label for this key (e.g., "POS System", "Shopify Integration").',
    'help_scopes' => 'Choose the permission level for this key. "Point of Sale" covers most use cases.',
    'help_rate_limit' => 'How many requests the connected app or agent can make per minute. Higher values allow faster operations but use more resources. The default works well for most integrations.',
    'help_expires_at' => 'Leave empty for a non-expiring key.',
    'help_is_active' => 'Deactivate to immediately revoke access without deleting the key.',

    // ─────────────────────────────────────────────────────────────────
    // One-Time Key Display Modal
    // ─────────────────────────────────────────────────────────────────
    'key_warning' => 'Copy this key now. It will <strong>never be shown again</strong>.',
    'copy' => 'Copy',
    'copied' => 'Copied!',
    'copied_to_clipboard' => 'Copied to clipboard',
    'saved_key' => "I've saved this key",

    // ─────────────────────────────────────────────────────────────────
    // Limit Messages
    // ─────────────────────────────────────────────────────────────────
    'limit_reached' => 'Agent key limit reached (:limit). Contact your admin to increase the limit.',

    // ─────────────────────────────────────────────────────────────────
    // Admin — Partner Permission Management
    // ─────────────────────────────────────────────────────────────────
    'help_agent_api_permission' => 'Allow this partner to create and manage Agent API keys for integrations.',
    'help_agent_keys_limit' => 'Maximum number of Agent Keys this partner can create. Default: 5. Use -1 for unlimited.',

    // ─────────────────────────────────────────────────────────────────
    // Member — Key Management
    // ─────────────────────────────────────────────────────────────────
    'help_member_key_name' => 'A label for this key (e.g., "My Wallet App", "Webshop Connection").',
    'member_scope_read' => 'View Only — View your balance, cards, and rewards',
    'member_scope_wallet' => 'Full Access — View balance, claim rewards, save cards, and update profile',
    'help_member_scopes' => 'Choose what this key can do. "View Only" is safest for external apps.',
    'help_member_expires_at' => 'Keys expire after 90 days by default. You can set a custom date.',
    'member_limit_reached' => 'You can have a maximum of :limit agent keys.',

    // ─────────────────────────────────────────────────────────────────
    // Admin — Key Management
    // ─────────────────────────────────────────────────────────────────
    'help_admin_key_name' => 'A label for this key (e.g., "CRM Integration", "Billing Dashboard", "Monitoring Agent").',
    'admin_scope_read' => 'View Only — View partners, members, and analytics',
    'admin_scope_standard' => 'Standard — View + manage partner settings',
    'admin_scope_admin' => 'Full Access — All admin operations (use with caution)',
    'help_admin_scopes' => 'Choose the permission level. "View Only" is safest for monitoring and dashboards.',
    'admin_limit_reached' => 'Platform agent key limit reached (:limit). Delete unused keys to create new ones.',

    // ─────────────────────────────────────────────────────────────────
    // helpContent — Partner Agent Keys Page
    // ─────────────────────────────────────────────────────────────────
    'partner_help_title' => 'What are Agent Keys?',
    'partner_help_content' => 'Agent Keys let external apps and AI assistants interact with your loyalty program on your behalf. For example, a POS system can automatically award points when a customer pays, or a chatbot can check a member\'s balance. Each key has its own permissions, so you stay in control of what it can access.',
    'partner_help_step1_title' => 'Create a key',
    'partner_help_step1_desc' => 'Give the key a name (e.g., "POS System") and choose what it\'s allowed to do.',
    'partner_help_step2_title' => 'Copy the key',
    'partner_help_step2_desc' => 'The key is shown only once after creation. Copy it and store it securely.',
    'partner_help_step3_title' => 'Connect your app',
    'partner_help_step3_desc' => 'Paste the key into your integration or give it to your developer. The app can now interact with your loyalty program.',

    // ─────────────────────────────────────────────────────────────────
    // helpContent — Member Agent Keys Page
    // ─────────────────────────────────────────────────────────────────
    'member_help_title' => 'What are Agent Keys?',
    'member_help_content' => 'Agent Keys let apps and AI assistants access your loyalty account. For example, a wallet app can show your points balance, or a smart assistant can claim rewards for you. You control what each key can do, and you can deactivate a key at any time.',
    'member_help_step1_title' => 'Create a key',
    'member_help_step1_desc' => 'Choose a name and permission level. "View Only" is the safest option.',
    'member_help_step2_title' => 'Copy the key',
    'member_help_step2_desc' => 'The key is shown only once. Copy it and paste it into the app you want to connect.',
    'member_help_step3_title' => 'Stay in control',
    'member_help_step3_desc' => 'You can deactivate or delete a key at any time from this page.',

    // ─────────────────────────────────────────────────────────────────
    // helpContent — Admin Agent Keys Page
    // ─────────────────────────────────────────────────────────────────
    'admin_help_title' => 'Platform Agent Keys',
    'admin_help_content' => 'Agent Keys give external systems secure API access to the platform. Use them for CRM integrations, monitoring dashboards, or automated management tools. Each key is scoped to specific permissions and can be revoked instantly.',
    'admin_help_step1_title' => 'Create a key',
    'admin_help_step1_desc' => 'Name it after the system it connects to (e.g., "CRM Sync"). Choose the narrowest permission level that works.',
    'admin_help_step2_title' => 'Copy and secure it',
    'admin_help_step2_desc' => 'The full key is shown only once. Store it in a password manager or secrets vault.',
    'admin_help_step3_title' => 'Monitor usage',
    'admin_help_step3_desc' => 'The "Last Used" column shows when each key was last active. Deactivate unused keys.',
];
