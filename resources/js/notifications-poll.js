/**
 * Polls the unread-notifications count every ~5s (InfinityFree has no
 * WebSockets, so this is the closest thing to "live" available here) and
 * plays a short beep — synthesized with the Web Audio API, no sound file
 * to upload — the first time the count goes up since the last poll.
 * Shared by the student sidebar and the admin sidebar: both bind this same
 * component to their own "Notificaciones" nav item.
 */
export function registerNotificationsPoll(Alpine) {
    Alpine.data('notificationsBadge', (url) => ({
        count: 0,

        init() {
            this.poll();

            const intervalId = setInterval(() => this.poll(), 5000);

            // Turbo Drive swaps the whole <body> on every in-app
            // navigation, which recreates this component (and its
            // interval) on the next page — without this, the old interval
            // would keep ticking and polling in the background forever.
            document.addEventListener('turbo:before-cache', () => clearInterval(intervalId), { once: true });
        },

        async poll() {
            try {
                const response = await fetch(url, { headers: { Accept: 'application/json' } });

                if (! response.ok) {
                    return;
                }

                const data = await response.json();

                if (data.count > this.count) {
                    this.playSound();
                }

                this.count = data.count;
            } catch (e) {
                // A failed poll (offline, slow shared hosting, etc.) just
                // tries again on the next interval tick — never surfaced.
            }
        },

        playSound() {
            try {
                const AudioContextClass = window.AudioContext || window.webkitAudioContext;
                const ctx = new AudioContextClass();
                const oscillator = ctx.createOscillator();
                const gain = ctx.createGain();

                oscillator.frequency.value = 880;
                oscillator.connect(gain);
                gain.connect(ctx.destination);
                gain.gain.setValueAtTime(0.15, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.3);

                oscillator.start();
                oscillator.stop(ctx.currentTime + 0.3);
            } catch (e) {
                // Autoplay-policy or unsupported-browser failures are silent
                // — the visual badge still updates either way.
            }
        },
    }));
}
