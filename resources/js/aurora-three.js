import * as THREE from 'three';
import { createNoise3D } from 'simplex-noise';
import { EffectComposer } from 'three/examples/jsm/postprocessing/EffectComposer.js';
import { RenderPass } from 'three/examples/jsm/postprocessing/RenderPass.js';
import { UnrealBloomPass } from 'three/examples/jsm/postprocessing/UnrealBloomPass.js';

/**
 * The aurora on the login/register brand panel — real WebGL (three.js),
 * not a flat line drawing. Modeled on the reference video: each ribbon
 * is a translucent, glossy fill (a gradient from a dark base color up to
 * a bright highlight at the wave crests, via per-vertex colors — that's
 * the "satin sheen" the video has) with a second, fainter wireframe mesh
 * laid over the SAME geometry for the fine woven-grid texture on top of
 * it. Bloom post-processing on top of both is what turns the highlight
 * into an actual glow instead of just a lighter patch of color.
 *
 * Runs in both themes (unlike earlier drafts of this file, which only
 * ran in dark and left light mode on a much plainer Canvas2D sketch) —
 * only the color palette and blend mode change: dark uses additive
 * blending (overlapping light brightens, like real light on a dark
 * background), light uses normal blending (additive on white just
 * bleaches everything to white).
 *
 * Each ribbon is a plane whose vertices are displaced along Z every
 * frame by 3D simplex noise sampled at that vertex's (x, y) and the
 * current time — the standard "flag/terrain" noise-displacement trick,
 * which is what makes it ripple smoothly instead of jittering randomly.
 */
export function registerAuroraThree() {
    const canvas = document.getElementById('aurora-canvas');

    if (!canvas) {
        return;
    }

    const panel = canvas.closest('.auth-brand-panel');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const desktopQuery = window.matchMedia('(min-width: 1024px)');

    const palettes = {
        dark: {
            clear: 0x02100a,
            blending: THREE.AdditiveBlending,
            bloom: { strength: 0.55, radius: 0.45, threshold: 0.25 },
            particleColor: 0xffffff,
            particleOpacity: 0.85,
            ribbons: [
                { base: 0x064e3b, highlight: 0x6ee7b7, grid: 0x34d399, fillOpacity: 0.4, gridOpacity: 0.3 },
                { base: 0x134e4a, highlight: 0x99f6e4, grid: 0x2dd4bf, fillOpacity: 0.35, gridOpacity: 0.28 },
            ],
        },
        light: {
            // Bloom brightens whatever's already there — against a clear
            // color this light, almost any amount instantly clips the
            // whole scene to white (no headroom left before 1.0). Light
            // mode's "shine" comes from the vertex-color gradient alone;
            // bloom here is just enough to take the hard edge off it.
            clear: 0xcdf0dd,
            blending: THREE.NormalBlending,
            bloom: { strength: 0.05, radius: 0.2, threshold: 0.9 },
            particleColor: 0x14532d,
            particleOpacity: 0.5,
            ribbons: [
                { base: 0xbbf7d0, highlight: 0x15803d, grid: 0x166534, fillOpacity: 0.55, gridOpacity: 0.35 },
                { base: 0xa7f3d0, highlight: 0x0f766e, grid: 0x0f766e, fillOpacity: 0.5, gridOpacity: 0.3 },
            ],
        },
    };

    let renderer = null;
    let composer = null;
    let bloomPass = null;
    let scene = null;
    let camera = null;
    let particles = null;
    let frameId = null;
    let running = false;
    const ribbons = [];

    function isDark() {
        return document.documentElement.classList.contains('dark');
    }

    function shouldRun() {
        return desktopQuery.matches;
    }

    function palette() {
        return isDark() ? palettes.dark : palettes.light;
    }

    function applyPalette() {
        const p = palette();

        // EffectComposer/UnrealBloomPass don't carry the alpha channel
        // through their render targets — a transparent clear color here
        // still comes out as opaque black once it's gone through the
        // bloom pass, no matter what the renderer itself is configured
        // with. Simplest fix: don't rely on transparency at all, just
        // clear to an opaque color close to each theme's panel gradient
        // (the canvas covers the panel edge to edge anyway, so the DOM
        // gradient behind it was never actually going to show through
        // except at the clip-path edge, which nothing here touches).
        if (renderer) {
            renderer.setClearColor(p.clear, 1);
        }

        if (bloomPass) {
            bloomPass.strength = p.bloom.strength;
            bloomPass.radius = p.bloom.radius;
            bloomPass.threshold = p.bloom.threshold;
        }

        ribbons.forEach((ribbon, i) => {
            const cfg = p.ribbons[i % p.ribbons.length];
            ribbon.base.set(cfg.base);
            ribbon.highlight.set(cfg.highlight);
            ribbon.fillMesh.material.blending = p.blending;
            ribbon.fillMesh.material.opacity = cfg.fillOpacity;
            ribbon.gridMesh.material.color.set(cfg.grid);
            ribbon.gridMesh.material.opacity = cfg.gridOpacity;
        });

        if (particles) {
            particles.material.color.set(p.particleColor);
            particles.material.opacity = p.particleOpacity;
            particles.material.blending = p.blending;
        }
    }

    function buildScene() {
        scene = new THREE.Scene();
        camera = new THREE.PerspectiveCamera(50, 1, 0.1, 100);
        camera.position.set(0, 0.3, 4.6);
        camera.lookAt(0, -0.05, 0);

        renderer = new THREE.WebGLRenderer({ canvas, antialias: true });
        renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));

        composer = new EffectComposer(renderer);
        composer.addPass(new RenderPass(scene, camera));
        bloomPass = new UnrealBloomPass(new THREE.Vector2(1, 1), 0.55, 0.45, 0.25);
        composer.addPass(bloomPass);

        const ribbonLayout = [
            { rotX: -1.3, rotZ: 0.2, posY: 1.05, posZ: -1, freq: 0.9, speed: 0.00014, amp: 0.16, seed: 0 },
            { rotX: -1.26, rotZ: -0.16, posY: -1.15, posZ: -1.3, freq: 0.75, speed: -0.00012, amp: 0.14, seed: 90 },
        ];

        ribbonLayout.forEach((cfg) => {
            const geometry = new THREE.PlaneGeometry(7.2, 2.4, 48, 20);
            const count = geometry.attributes.position.count;
            geometry.setAttribute('color', new THREE.BufferAttribute(new Float32Array(count * 3), 3));

            const base = new THREE.Color();
            const highlight = new THREE.Color();

            const fillMesh = new THREE.Mesh(geometry, new THREE.MeshBasicMaterial({
                vertexColors: true,
                transparent: true,
                side: THREE.DoubleSide,
                depthWrite: false,
            }));

            // Shares the SAME geometry instance as fillMesh — updating its
            // vertex positions once per frame moves both meshes together,
            // no need to duplicate the noise displacement work.
            const gridMesh = new THREE.Mesh(geometry, new THREE.MeshBasicMaterial({
                wireframe: true,
                transparent: true,
                depthWrite: false,
            }));

            [fillMesh, gridMesh].forEach((mesh) => {
                mesh.rotation.x = cfg.rotX;
                mesh.rotation.z = cfg.rotZ;
                mesh.position.set(0, cfg.posY, cfg.posZ);
                scene.add(mesh);
            });

            ribbons.push({ fillMesh, gridMesh, cfg, base, highlight, noise: createNoise3D() });
        });

        const count = 220;
        const positions = new Float32Array(count * 3);

        for (let i = 0; i < count; i++) {
            positions[i * 3] = (Math.random() - 0.5) * 7.5;
            positions[i * 3 + 1] = (Math.random() - 0.5) * 3;
            positions[i * 3 + 2] = (Math.random() - 0.5) * 2 - 0.4;
        }

        const particleGeometry = new THREE.BufferGeometry();
        particleGeometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));

        particles = new THREE.Points(particleGeometry, new THREE.PointsMaterial({
            size: 0.016,
            transparent: true,
            depthWrite: false,
            sizeAttenuation: true,
        }));
        scene.add(particles);

        applyPalette();
    }

    function updateRibbon(ribbon, time) {
        const { fillMesh, cfg, noise, base, highlight } = ribbon;
        const position = fillMesh.geometry.attributes.position;
        const color = fillMesh.geometry.attributes.color;
        const tmp = new THREE.Color();

        for (let i = 0; i < position.count; i++) {
            const x = position.getX(i);
            const y = position.getY(i);
            const n = noise(x * cfg.freq + cfg.seed, y * cfg.freq, time * cfg.speed);
            position.setZ(i, n * cfg.amp);

            // Crests (n near 1) read as the bright satin highlight running
            // along the ribbon; troughs fall back to the darker base tone
            // — squaring the mix keeps the highlight a narrow bright band
            // instead of half the ribbon looking equally lit.
            const t = Math.pow(Math.max(n, 0), 2);
            tmp.copy(base).lerp(highlight, t);
            color.setXYZ(i, tmp.r, tmp.g, tmp.b);
        }

        position.needsUpdate = true;
        color.needsUpdate = true;
    }

    function draw(time) {
        ribbons.forEach((ribbon) => updateRibbon(ribbon, time));

        if (particles) {
            particles.rotation.y = time * 0.00002;
        }

        composer.render();
    }

    function resize() {
        const rect = panel.getBoundingClientRect();
        const width = rect.width;
        const height = rect.height;

        if (!renderer || width === 0 || height === 0) {
            return;
        }

        camera.aspect = width / height;
        camera.updateProjectionMatrix();
        renderer.setSize(width, height, false);
        composer.setSize(width, height);
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

        if (!renderer) {
            buildScene();
        }

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

        applyPalette();
    }

    const handleResize = () => running && resize();

    desktopQuery.addEventListener('change', sync);
    window.addEventListener('resize', handleResize);
    window.addEventListener('kashafin:theme-change', sync);

    sync();

    // Same reasoning as every other interval/rAF loop in this app (see
    // notifications-poll.js), plus disposing the WebGL resources this one
    // specifically allocates — a GPU context and its buffers don't get
    // freed just because the <canvas> scrolled out of the DOM on a Turbo
    // navigation, they need an explicit .dispose() or they leak for the
    // rest of the tab's life.
    document.addEventListener('turbo:before-cache', () => {
        stop();
        desktopQuery.removeEventListener('change', sync);
        window.removeEventListener('resize', handleResize);
        window.removeEventListener('kashafin:theme-change', sync);

        if (renderer) {
            ribbons.forEach((ribbon) => {
                ribbon.fillMesh.geometry.dispose();
                ribbon.fillMesh.material.dispose();
                ribbon.gridMesh.material.dispose();
            });

            if (particles) {
                particles.geometry.dispose();
                particles.material.dispose();
            }

            composer.dispose();
            renderer.dispose();
        }
    }, { once: true });
}
