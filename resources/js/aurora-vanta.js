import * as THREE from 'three';
import FOG from 'vanta/dist/vanta.fog.min';

/**
 * The animated "aureola" background on the login/register brand panel —
 * Vanta.js's FOG effect (built on three.js under the hood, passed in
 * explicitly via the `THREE` option rather than the global `window.THREE`
 * Vanta also accepts, so it uses the exact bundled copy instead of
 * expecting one to already be on the page). Installed via npm and
 * imported as a module, not the usual two CDN <script> tags for Vanta +
 * three.js — this project bundles everything through Vite, so loading it
 * that way would be the only piece of the app depending on an external
 * CDN being up, and it would fetch its own separate copy of three.js
 * from there too.
 *
 * Very dark base with the fog in emerald/mint, speed and blurFactor both
 * pushed well past Vanta's defaults so the light reads as moving liquid
 * motion rather than a static haze.
 */
export function registerAuroraVanta() {
    const el = document.getElementById('vanta-bg');

    if (!el) {
        return;
    }

    // Vanta has no built-in "reduced motion" mode — the only real
    // equivalent is to not start its render loop at all, leaving the
    // container's own dark CSS background (set in the markup) as a
    // still fallback instead of a frozen mid-animation frame.
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    const effect = FOG({
        el,
        THREE,
        mouseControls: false,
        touchControls: false,
        gyroControls: false,
        minHeight: 200,
        minWidth: 200,
        scale: 1,
        scaleMobile: 1,
        highlightColor: 0x34d399,
        midtoneColor: 0x10b981,
        lowlightColor: 0x020a07,
        baseColor: 0x020a07,
        blurFactor: 0.9,
        speed: 3,
        zoom: 1,
    });

    // Same reasoning as every other interval/rAF-driven effect in this
    // app (see notifications-poll.js) — Turbo Drive swaps <body> on
    // navigation without a full reload, so Vanta's own internal render
    // loop and WebGL context need to be torn down explicitly via
    // .destroy() or they keep running against a detached canvas for the
    // rest of the tab's life.
    document.addEventListener('turbo:before-cache', () => {
        effect?.destroy();
    }, { once: true });
}
