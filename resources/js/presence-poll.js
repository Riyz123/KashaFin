/**
 * Refreshes the "en línea"/"última vez" indicator on the admin's Usuarios
 * page every ~20s without a page reload — same polling pattern as
 * notifications-poll.js. The interval is cleared on `turbo:before-cache`
 * (fired right before Turbo Drive swaps/caches the current page) so
 * navigating away doesn't leave an orphaned timer running.
 */
export function registerPresencePoll(Alpine) {
    Alpine.data('presencePoll', (ids, url) => ({
        state: {},

        init() {
            if (ids.length === 0) {
                return;
            }

            this.poll();

            const intervalId = setInterval(() => this.poll(), 20000);

            document.addEventListener('turbo:before-cache', () => clearInterval(intervalId), { once: true });
        },

        async poll() {
            try {
                const response = await fetch(`${url}?ids=${ids.join(',')}`, { headers: { Accept: 'application/json' } });

                if (! response.ok) {
                    return;
                }

                this.state = await response.json();
            } catch (e) {
                // A failed poll just tries again on the next interval tick.
            }
        },
    }));
}
