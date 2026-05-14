{{--
Reward Loyalty - Proprietary Software
Copyright (c) 2026 NowSquare. All rights reserved.
See LICENSE file for terms.

Content Page Animations — Shared Animation Styles

Purpose:
Shared CSS animations for member content pages (about, contact, faq, privacy, terms).
Extracted to eliminate duplication of identical @keyframes across 5 files.

Usage:
@include('member.content.partials.animations')
--}}
<style>
    /* Ambient float — decorative hero blobs */
    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(2deg); }
    }
    @keyframes float-delayed {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-15px) rotate(-2deg); }
    }
    .animate-float { animation: float 6s ease-in-out infinite; }
    .animate-float-delayed { animation: float-delayed 8s ease-in-out infinite; animation-delay: 2s; }

    /* Entry animations — page load only */
    .animate-fade-in {
        animation: fade-in 0.4s ease-out forwards;
    }
    .animate-fade-in-up {
        animation: fade-in-up 0.6s ease-out forwards;
        opacity: 0;
    }
    @keyframes fade-in {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    @keyframes fade-in-up {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
