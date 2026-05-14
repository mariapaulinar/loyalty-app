<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Icon Picker Configuration
 *
 * Curated collection of loyalty-related icons organized by business category.
 * Supports both Lucide icons (via blade-icons) and emojis.
 *
 * Design Philosophy:
 * - Category-first organization (Restaurant, Retail, Services, etc.)
 * - Commonly used loyalty icons only (no kitchen sink)
 * - Beautiful emoji fallbacks for instant visual feedback
 * - Partner-friendly naming (no dev jargon)
 */

namespace App\Support;

class IconPickerConfig
{
    /**
     * Get all available icon categories with their icons.
     *
     * @return array<string, array<string, array<string, string>>>
     */
    public static function getCategories(): array
    {
        return [
            'popular' => [
                'name' => trans('common.popular'),
                'icon' => 'star',
                'icons' => self::getPopularIcons(),
            ],
            'food' => [
                'name' => trans('common.food'),
                'icon' => 'utensils',
                'icons' => self::getFoodIcons(),
            ],
            'retail' => [
                'name' => trans('common.retail'),
                'icon' => 'shopping-bag',
                'icons' => self::getRetailIcons(),
            ],
            'services' => [
                'name' => trans('common.services'),
                'icon' => 'briefcase',
                'icons' => self::getServicesIcons(),
            ],
            'rewards' => [
                'name' => trans('common.rewards'),
                'icon' => 'trophy',
                'icons' => self::getRewardsIcons(),
            ],
            'emojis' => [
                'name' => trans('common.emojis'),
                'icon' => 'smile',
                'icons' => self::getEmojiIcons(),
            ],
        ];
    }

    /**
     * Get popular/frequently used icons.
     */
    private static function getPopularIcons(): array
    {
        return [
            'star' => '⭐ Star',
            'heart' => '❤️ Heart',
            'trophy' => '🏆 Trophy',
            'gift' => '🎁 Gift',
            'sparkles' => '✨ Sparkles',
            'medal' => '🏅 Medal',
            'crown' => '👑 Crown',
            'fire' => '🔥 Fire',
            'rocket' => '🚀 Rocket',
            'target' => '🎯 Target',
            'gem' => '💎 Diamond',
            'check' => '✅ Check',
            'coffee' => '☕ Coffee',
            'pizza' => '🍕 Pizza',
            'burger' => '🍔 Burger',
            'cake' => '🍰 Cake',
        ];
    }

    /**
     * Get food & beverage related icons.
     */
    private static function getFoodIcons(): array
    {
        return [
            'coffee' => '☕ Coffee',
            'croissant' => '🥐 Croissant',
            'pizza-slice' => '🍕 Pizza',
            'cookie' => '🍪 Cookie',
            'ice-cream' => '🍦 Ice Cream',
            'cake' => '🍰 Cake',
            'sandwich' => '🥪 Sandwich',
            'burger' => '🍔 Burger',
            'taco' => '🌮 Taco',
            'sushi' => '🍣 Sushi',
            'wine-glass' => '🍷 Wine',
            'beer' => '🍺 Beer',
            'utensils' => '🍴 Utensils',
            'chef-hat' => '👨‍🍳 Chef',
            'restaurant' => '🍽️ Restaurant',
        ];
    }

    /**
     * Get retail & shopping related icons.
     */
    private static function getRetailIcons(): array
    {
        return [
            'shopping-bag' => '🛍️ Shopping Bag',
            'shopping-cart' => '🛒 Shopping Cart',
            'shirt' => '👕 Shirt',
            'package' => '📦 Package',
            'tag' => '🏷️ Tag',
            'receipt' => '🧾 Receipt',
            'credit-card' => '💳 Credit Card',
            'wallet' => '👛 Wallet',
            'gem' => '💎 Gem',
            'watch' => '⌚ Watch',
            'glasses' => '👓 Glasses',
            'handbag' => '👜 Handbag',
            'shoe' => '👞 Shoe',
            'dress' => '👗 Dress',
            'lipstick' => '💄 Lipstick',
        ];
    }

    /**
     * Get service-related icons.
     */
    private static function getServicesIcons(): array
    {
        return [
            'scissors' => '✂️ Scissors (Salon)',
            'dumbbell' => '🏋️ Gym/Fitness',
            'car' => '🚗 Car/Auto',
            'wrench' => '🔧 Repair',
            'paint-brush' => '🎨 Beauty/Art',
            'home' => '🏠 Home Services',
            'zap' => '⚡ Electric',
            'droplet' => '💧 Plumbing',
            'leaf' => '🌿 Eco/Garden',
            'paw' => '🐾 Pet Services',
            'graduation-cap' => '🎓 Education',
            'stethoscope' => '🩺 Health',
            'plane' => '✈️ Travel',
            'hotel' => '🏨 Hotel',
            'camera' => '📷 Photography',
        ];
    }

    /**
     * Get reward & achievement icons.
     */
    private static function getRewardsIcons(): array
    {
        return [
            'trophy' => '🏆 Trophy',
            'medal' => '🏅 Medal',
            'award' => '🎖️ Award',
            'crown' => '👑 Crown',
            'gem' => '💎 Gem',
            'sparkles' => '✨ Sparkles',
            'fire' => '🔥 Fire',
            'rocket' => '🚀 Rocket',
            'target' => '🎯 Target',
            'check-circle' => '✅ Check',
            'thumbs-up' => '👍 Thumbs Up',
            'party-popper' => '🎉 Party',
            'confetti' => '🎊 Confetti',
            'balloon' => '🎈 Balloon',
        ];
    }

    /**
     * Get emoji-only icons (comprehensive collection).
     */
    private static function getEmojiIcons(): array
    {
        return [
            // Smilies & Emotions
            '😀' => '😀 Grinning',
            '😃' => '😃 Smiley',
            '😄' => '😄 Smile',
            '😁' => '😁 Grin',
            '😊' => '😊 Blush',
            '😇' => '😇 Innocent',
            '🙂' => '🙂 Slightly Smiling',
            '🙃' => '🙃 Upside Down',
            '😉' => '😉 Wink',
            '😌' => '😌 Relieved',
            '😍' => '😍 Heart Eyes',
            '🥰' => '🥰 Smiling Face With Hearts',
            '😘' => '😘 Kissing Heart',
            '😗' => '😗 Kissing',
            '😎' => '😎 Cool',
            '🤩' => '🤩 Star Struck',
            '🥳' => '🥳 Partying',
            '😏' => '😏 Smirk',
            '😴' => '😴 Sleeping',
            '😅' => '😅 Sweat Smile',
            '😂' => '😂 Joy',
            '🤣' => '🤣 Rolling On Floor',
            '😭' => '😭 Crying',
            '😢' => '😢 Sad',
            '🤔' => '🤔 Thinking',
            '🤨' => '🤨 Raised Eyebrow',
            '😮' => '😮 Surprised',
            '😲' => '😲 Astonished',
            '🤯' => '🤯 Mind Blown',
            '😳' => '😳 Flushed',

            // Stars & Sparkles
            '⭐' => '⭐ Star',
            '🌟' => '🌟 Glowing Star',
            '✨' => '✨ Sparkles',
            '💫' => '💫 Dizzy',
            '⚡' => '⚡ Lightning',
            '🔥' => '🔥 Fire',

            // Hearts
            '❤️' => '❤️ Red Heart',
            '💙' => '💙 Blue Heart',
            '💚' => '💚 Green Heart',
            '💛' => '💛 Yellow Heart',
            '🧡' => '🧡 Orange Heart',
            '💜' => '💜 Purple Heart',
            '🖤' => '🖤 Black Heart',
            '🤍' => '🤍 White Heart',
            '💗' => '💗 Growing Heart',
            '💖' => '💖 Sparkling Heart',
            '💝' => '💝 Heart With Ribbon',
            '💞' => '💞 Revolving Hearts',

            // People & Hands
            '👍' => '👍 Thumbs Up',
            '👎' => '👎 Thumbs Down',
            '👏' => '👏 Clapping',
            '🙌' => '🙌 Raising Hands',
            '👐' => '👐 Open Hands',
            '🤝' => '🤝 Handshake',
            '🙏' => '🙏 Praying',
            '✌️' => '✌️ Victory',
            '🤞' => '🤞 Crossed Fingers',
            '🤟' => '🤟 Love You',
            '🤘' => '🤘 Rock',
            '👌' => '👌 OK',
            '🤌' => '🤌 Pinched Fingers',
            '✊' => '✊ Fist',
            '💪' => '💪 Muscle',
            '👋' => '👋 Wave',
            '🤚' => '🤚 Raised Back Of Hand',
            '✋' => '✋ Raised Hand',
            '👊' => '👊 Fist Bump',
            '🫶' => '🫶 Heart Hands',

            // Awards & Achievements
            '🏆' => '🏆 Trophy',
            '🥇' => '🥇 Gold Medal',
            '🥈' => '🥈 Silver Medal',
            '🥉' => '🥉 Bronze Medal',
            '🏅' => '🏅 Medal',
            '🎖️' => '🎖️ Military Medal',
            '👑' => '👑 Crown',
            '💎' => '💎 Diamond',
            '💍' => '💍 Ring',

            // Celebrations
            '🎉' => '🎉 Party Popper',
            '🎊' => '🎊 Confetti Ball',
            '🎈' => '🎈 Balloon',
            '🎁' => '🎁 Gift',
            '🎀' => '🎀 Ribbon',
            '🎂' => '🎂 Birthday Cake',
            '🎆' => '🎆 Fireworks',
            '🎇' => '🎇 Sparkler',

            // Symbols
            '✅' => '✅ Check Mark',
            '✔️' => '✔️ Check',
            '❌' => '❌ Cross Mark',
            '❗' => '❗ Exclamation',
            '❓' => '❓ Question',
            '💯' => '💯 Hundred Points',
            '🔔' => '🔔 Bell',
            '⚠️' => '⚠️ Warning',

            // Objects
            '🚀' => '🚀 Rocket',
            '🎯' => '🎯 Target',
            '📍' => '📍 Pin',
            '⏰' => '⏰ Alarm',
            '⏱️' => '⏱️ Stopwatch',
            '📱' => '📱 Phone',
            '💻' => '💻 Laptop',
            '🎮' => '🎮 Game',
            '🎵' => '🎵 Music',
            '📸' => '📸 Camera',
            '📚' => '📚 Books',
            '✏️' => '✏️ Pencil',
            '📝' => '📝 Memo',
            '💼' => '💼 Briefcase',

            // Food & Drink
            '☕' => '☕ Coffee',
            '🍕' => '🍕 Pizza',
            '🍔' => '🍔 Burger',
            '🍰' => '🍰 Cake',
            '🍦' => '🍦 Ice Cream',
            '🍩' => '🍩 Donut',
            '🍪' => '🍪 Cookie',
            '🥐' => '🥐 Croissant',
            '🌮' => '🌮 Taco',
            '🍣' => '🍣 Sushi',
            '🍺' => '🍺 Beer',
            '🍷' => '🍷 Wine',
            '🥂' => '🥂 Champagne',
            '🍉' => '🍉 Watermelon',
            '🍎' => '🍎 Apple',
            '🍌' => '🍌 Banana',
            '🍓' => '🍓 Strawberry',
            '🥗' => '🥗 Salad',
            '🍱' => '🍱 Bento',

            // Nature & Weather
            '🌈' => '🌈 Rainbow',
            '🌸' => '🌸 Blossom',
            '🌺' => '🌺 Hibiscus',
            '🌻' => '🌻 Sunflower',
            '🌹' => '🌹 Rose',
            '🌷' => '🌷 Tulip',
            '🍀' => '🍀 Clover',
            '🌿' => '🌿 Herb',
            '☀️' => '☀️ Sun',
            '🌙' => '🌙 Moon',
            '⭐' => '⭐ Star',
            '🌤️' => '🌤️ Sun Behind Cloud',
            '⛅' => '⛅ Sun Behind Cloud',
            '🌦️' => '🌦️ Sun Behind Rain Cloud',
            '❄️' => '❄️ Snowflake',

            // Animals
            '🐶' => '🐶 Dog',
            '🐱' => '🐱 Cat',
            '🐭' => '🐭 Mouse',
            '🐰' => '🐰 Rabbit',
            '🦊' => '🦊 Fox',
            '🐻' => '🐻 Bear',
            '🐼' => '🐼 Panda',
            '🐨' => '🐨 Koala',
            '🐯' => '🐯 Tiger',
            '🦁' => '🦁 Lion',
            '🐮' => '🐮 Cow',
            '🐷' => '🐷 Pig',
            '🐸' => '🐸 Frog',
            '🐵' => '🐵 Monkey',
            '🐔' => '🐔 Chicken',
            '🐧' => '🐧 Penguin',
            '🐦' => '🐦 Bird',
            '🦋' => '🦋 Butterfly',
            '🐝' => '🐝 Bee',
            '🐢' => '🐢 Turtle',

            // Retail & Shopping
            '🛍️' => '🛍️ Shopping Bags',
            '🛒' => '🛒 Cart',
            '💳' => '💳 Card',
            '💰' => '💰 Money Bag',
            '💵' => '💵 Dollar',
            '💴' => '💴 Yen',
            '💶' => '💶 Euro',
            '💷' => '💷 Pound',
            '🪙' => '🪙 Coin',
            '🏷️' => '🏷️ Tag',
            '🎫' => '🎫 Ticket',
            '💎' => '💎 Gem Stone',
        ];
    }

    /**
     * Get all icons as a flat array.
     */
    public static function getAllIcons(): array
    {
        $categories = self::getCategories();
        $allIcons = [];

        foreach ($categories as $category) {
            $allIcons = array_merge($allIcons, $category['icons']);
        }

        return $allIcons;
    }

    /**
     * Check if an icon is an emoji (contains non-ASCII characters).
     *
     * @param  string|null  $icon  The icon string to check (null-safe for defensive coding)
     */
    public static function isEmoji(?string $icon): bool
    {
        if ($icon === null || $icon === '') {
            return false;
        }

        return preg_match('/[^\x00-\x7F]/', $icon) === 1;
    }

    /**
     * Get icon display name.
     */
    public static function getIconName(string $icon): string
    {
        $allIcons = self::getAllIcons();

        return $allIcons[$icon] ?? $icon;
    }
}
