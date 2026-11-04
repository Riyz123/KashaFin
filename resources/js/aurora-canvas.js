import { createNoise2D } from 'simplex-noise';

/**
 * The LIGHT-mode aurora on the login/register brand panel — thin glowing
 * ribbons driven by simplex noise instead of a fixed sine wave, so they
 * drift organically and never repeat on a visible cycle. Canvas2D is
 * enough here (no bloom/lighting needed against a pale background); dark
 * mode gets the full WebGL version instead (see aurora-three.js) since
 * that's where a glowing wireframe mesh actually reads well.
 *
 * Each ribbon is a set of points sampled across the canvas width, where
 * the y-offset at point `i` and time `t` comes from
 * `noise2D(i * frequency, t)` — reading noise along a line that moves
 * forward in its own "time" dimension is the standard trick for a
 * continuously-evolving 1D wave that still looks smooth in space. Every
 * ribbon gets a different frequency/speed/seed offset so they move
 * independently instead of in lockstep.
 */
export function registerAuroraCanvas() {
    const canvas = document.getElementById('aurora-canvas-light');

    if (!canvas) {
        return;
    }

    const panel = canvas.closest('.auth-brand-panel');
    const ctx = canvas.getContext('2d');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const desktopQuery = window.matchMedia('(min-width: 1024px)');

    const ribbons = [
        { noise: createNoise2D(), baseline: 0.22, amplitude: 85, frequency: 0.007, speed: 0.00075, width: 3, points: 70 },
        { noise: createNoise2D(), baseline: 0.3, amplitude: 65, frequency: 0.01, speed: -0.0006, width: 2, points: 70 },
        { noise: createNoise2D(), baseline: 0.68, amplitude: 80, frequency: 0.008, speed: 0.0007, width: 3, points: 70 },
        { noise: createNoise2D(), baseline: 0.76, amplitude: 60, frequency: 0.012, speed: -0.00065, width: 2, points: 70 },
    ];

    const colors = ['#166534', '#15803d', '#047857', '#0f766e'];

    let width = 0;
    let height = 0;
    let dpr = Math.min(window.devicePixelRatio || 1, 2);
    let frameId = null;
    let running = false;

    function isDark() {
        return document.documentElement.classList.contains('dark');
    }

    function shouldRun() {
        return desktopQuery.matches && !isDark();
    }

    function resize() {
        const rect = panel.getBoundingClientRect();
        width = rect.width;
        height = rect.height;
        canvas.width = width * dpr;
        canvas.height = height * dpr;
        canvas.style.width = `${width}px`;
        canvas.style.height = `${height}px`;
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    }

    function drawRibbon(ribbon, color, time) {
        const step = width / ribbon.points;

        ctx.beginPath();

        for (let i = 0; i <= ribbon.points; i++) {
            const x = i * step;
            const n = ribbon.noise(i * ribbon.frequency, time * ribbon.speed);
            const y = ribbon.baseline * height + n * ribbon.amplitude;

            if (i === 0) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }
        }

        ctx.strokeStyle = color;
        ctx.globalAlpha = 0.5;
        ctx.lineWidth = ribbon.width;
        ctx.lineJoin = 'round';
        ctx.lineCap = 'round';
        ctx.shadowColor = color;
        ctx.shadowBlur = 18;
        ctx.stroke();
    }

    function draw(time) {
        ctx.clearRect(0, 0, width, height);
        ctx.globalCompositeOperation = 'multiply';

        ribbons.forEach((ribbon, i) => drawRibbon(ribbon, colors[i % colors.length], time));

        ctx.globalAlpha = 1;
        ctx.globalCompositeOperation = 'source-over';
    }

    function loop(time) {
        draw(time);
        frameId = requestAnimationFrame(loop);
    }

    function start() {
        if (running || !shouldRun()) {
            return;
        }

        running = true;
        resize();

        if (reducedMotion) {
            draw(0);

            return;
        }

        frameId = requestAnimationFrame(loop);
    }

    function stop() {
        running = false;

        if (frameId) {
            cancelAnimationFrame(frameId);
            frameId = null;
        }
    }

    function sync() {
        if (shouldRun()) {
            start();
        } else {
            stop();
        }
    }

    const handleResize = () => running && resize();
    const handleThemeChange = sync;

    desktopQuery.addEventListener('change', sync);
    window.addEventListener('resize', handleResize);
    window.addEventListener('kashafin:theme-change', handleThemeChange);

    sync();

    // Same reasoning as every other interval/rAF loop in this app (see
    // notifications-poll.js) — Turbo Drive swaps <body> on navigation
    // without a full reload. The rAF loop alone would leak like any
    // other interval, but the addEventListener calls above leak worse:
    // `document`/`window` survive the swap, so without this they'd pile
    // up one extra stale listener (holding a reference to this closure's
    // now-detached canvas) per login/register visit for the tab's life.
    document.addEventListener('turbo:before-cache', () => {
        stop();
        desktopQuery.removeEventListener('change', sync);
        window.removeEventListener('resize', handleResize);
        window.removeEventListener('kashafin:theme-change', handleThemeChange);
    }, { once: true });
}
