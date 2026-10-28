/**
 * Refreshes each AI provider's "Tráfico en tiempo real" panel on the admin
 * Asistente IA page every ~10s, same pattern as presence-poll.js. This only
 * re-reads what our own database already has (usage logs + quota counters)
 * — it never calls out to an external provider on its own, so polling it
 * costs nothing against anyone's free-tier quota. "Probar conexión" is
 * still the only action that makes a real request.
 */
export function registerAiTrafficPoll(Alpine) {
    Alpine.data('aiTrafficPoll', (url) => ({
        providers: {},
        loaded: false,

        init() {
            this.poll();

            const intervalId = setInterval(() => this.poll(), 10000);

            document.addEventListener('turbo:before-cache', () => clearInterval(intervalId), { once: true });
        },

        async poll() {
            try {
                const response = await fetch(url, { headers: { Accept: 'application/json' } });

                if (! response.ok) {
                    return;
                }

                this.providers = await response.json();
                this.loaded = true;
            } catch (e) {
                // A failed poll just tries again on the next interval tick.
            }
        },

        trafficFor(providerId) {
            return this.providers[providerId] ?? null;
        },
    }));
}
