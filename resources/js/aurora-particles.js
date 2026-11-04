import { tsParticles } from '@tsparticles/engine';
import { loadSlim } from '@tsparticles/slim';

/**
 * The subtle "data flow" dust on the login/register brand panel — dots
 * drifting slowly upward, with faint connecting lines between nearby
 * ones. tsParticles (not a hand-rolled canvas loop) because this is
 * exactly the well-trodden case it's built for: it already handles
 * resize, device pixel ratio, and reduced-motion-friendly tuning without
 * custom code for each. Loaded with loadSlim (not the full tsparticles
 * package) — slim has everything this config uses (move, links,
 * opacity) at a fraction of the size of bundling every shape/effect
 * tsParticles supports.
 */
export async function registerAuroraParticles() {
    const container = document.getElementById('aurora-particles');

    if (!container) {
        return;
    }

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    await loadSlim(tsParticles);

    const instance = await tsParticles.load({
        id: 'aurora-particles',
        options: {
            fullScreen: { enable: false },
            fpsLimit: 60,
            detectRetina: true,
            background: { color: { value: 'transparent' } },
            particles: {
                number: { value: 55, density: { enable: true, area: 900 } },
                color: { value: ['#ffffff', '#6ee7b7', '#99f6e4'] },
                opacity: { value: { min: 0.15, max: 0.7 } },
                size: { value: { min: 1, max: 2.5 } },
                links: {
                    enable: true,
                    distance: 110,
                    color: '#6ee7b7',
                    opacity: 0.15,
                    width: 1,
                },
                move: {
                    enable: !reducedMotion,
                    direction: 'top',
                    speed: { min: 0.3, max: 0.9 },
                    straight: false,
                    random: true,
                    outModes: { default: 'out', top: 'out', bottom: 'none' },
                },
            },
            interactivity: {
                events: { onHover: { enable: false }, onClick: { enable: false } },
            },
        },
    });

    // The panel (and this container) only exists visually above `lg` on
    // the old desktop-only layout, but now the brand panel also shows,
    // shorter, stacked on mobile — either way tsParticles already reads
    // the container's own size via ResizeObserver internally, so no
    // manual breakpoint-watching is needed here unlike the old
    // canvas-based attempts at this effect.
    document.addEventListener('turbo:before-cache', () => {
        instance?.destroy();
    }, { once: true });
}
