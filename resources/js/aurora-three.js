import * as THREE from 'three';
import { createNoise3D } from 'simplex-noise';
import { EffectComposer } from 'three/examples/jsm/postprocessing/EffectComposer.js';
import { RenderPass } from 'three/examples/jsm/postprocessing/RenderPass.js';
import { UnrealBloomPass } from 'three/examples/jsm/postprocessing/UnrealBloomPass.js';

/**
 * The DARK-mode aurora on the login/register brand panel: real WebGL
 * (three.js), not a 2D sketch — a glowing wireframe mesh rippling like a
 * sheet of fabric, plus a dust of particles, rendered with bloom
 * post-processing so the lines actually glow instead of just being thin
 * bright pixels. Light mode gets the plain Canvas2D version instead (see
 * aurora-canvas.js) since bloom against a pale background just looks
 * washed out, not like northern lights.
 *
 * Each ribbon is a plane whose vertices are displaced along Z every
 * frame by 3D simplex noise sampled at that vertex's (x, y) and the
 * current time — the standard "flag/terrain" noise-displacement trick,
 * which is what makes it ripple smoothly instead of jittering randomly.
 */
export function registerAuroraThree() {
    const canvas = document.getElementById('aurora-canvas-dark');

    if (!canvas) {
        return;
    }

    const panel = canvas.closest('.auth-brand-panel');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const desktopQuery = window.matchMedia('(min-width: 1024px)');

    let renderer = null;
    let composer = null;
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
        return desktopQuery.matches && isDark();
    }

    function buildScene() {
        scene = new THREE.Scene();
        camera = new THREE.PerspectiveCamera(50, 1, 0.1, 100);
        camera.position.set(0, 0.3, 4.6);
        camera.lookAt(0, -0.05, 0);

        renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true });
        renderer.setClearColor(0x000000, 0);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));

        composer = new EffectComposer(renderer);
        composer.addPass(new RenderPass(scene, camera));
        composer.addPass(new UnrealBloomPass(new THREE.Vector2(1, 1), 0.55, 0.4, 0.3));

        const ribbonConfigs = [
            { color: 0x34d399, rotX: -1.32, rotZ: 0.22, posY: 1.05, posZ: -1, freq: 1.4, speed: 0.00016, amp: 0.14, seed: 0 },
            { color: 0x2dd4bf, rotX: -1.28, rotZ: -0.18, posY: -1.15, posZ: -1.3, freq: 1.1, speed: -0.00013, amp: 0.12, seed: 90 },
        ];

        ribbonConfigs.forEach((cfg) => {
            const geometry = new THREE.PlaneGeometry(7, 2.3, 72, 26);
            const material = new THREE.MeshBasicMaterial({
                color: cfg.color,
                wireframe: true,
                transparent: true,
                opacity: 0.35,
            });
            const mesh = new THREE.Mesh(geometry, material);
            mesh.rotation.x = cfg.rotX;
            mesh.rotation.z = cfg.rotZ;
            mesh.position.set(0, cfg.posY, cfg.posZ);
            scene.add(mesh);
            ribbons.push({ mesh, cfg, noise: createNoise3D() });
        });

        const count = 240;
        const positions = new Float32Array(count * 3);

        for (let i = 0; i < count; i++) {
            positions[i * 3] = (Math.random() - 0.5) * 7.5;
            positions[i * 3 + 1] = (Math.random() - 0.5) * 3;
            positions[i * 3 + 2] = (Math.random() - 0.5) * 2 - 0.4;
        }

        const particleGeometry = new THREE.BufferGeometry();
        particleGeometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));

        particles = new THREE.Points(particleGeometry, new THREE.PointsMaterial({
            color: 0xffffff,
            size: 0.016,
            transparent: true,
            opacity: 0.85,
            blending: THREE.AdditiveBlending,
            depthWrite: false,
            sizeAttenuation: true,
        }));
        scene.add(particles);
    }

    function updateRibbon(ribbon, time) {
        const { mesh, cfg, noise } = ribbon;
        const position = mesh.geometry.attributes.position;

        for (let i = 0; i < position.count; i++) {
            const x = position.getX(i);
            const y = position.getY(i);
            const n = noise(x * cfg.freq + cfg.seed, y * cfg.freq, time * cfg.speed);
            position.setZ(i, n * cfg.amp);
        }

        position.needsUpdate = true;
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
                ribbon.mesh.geometry.dispose();
                ribbon.mesh.material.dispose();
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
