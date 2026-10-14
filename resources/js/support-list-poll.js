/**
 * Keeps the admin's Soporte list in sync across multiple staff members
 * (the master admin and a dean can see the same request) — polls every
 * ~5s, and the moment anything differs from what was server-rendered at
 * page load (someone started/finished attending, or escalated it), does
 * one full Turbo-smooth page refresh so the server's already-correct
 * conditional rendering (the live thread block, which buttons show, etc.)
 * takes over instead of trying to mirror all of that in JS.
 */
export function registerSupportListPoll(Alpine) {
    Alpine.data('supportListPoll', (initialState, url) => ({
        init() {
            const ids = Object.keys(initialState);

            if (ids.length === 0) {
                return;
            }

            const intervalId = setInterval(() => this.poll(initialState, ids, url, intervalId), 5000);

            document.addEventListener('turbo:before-cache', () => clearInterval(intervalId), { once: true });
        },

        async poll(initialState, ids, url, intervalId) {
            try {
                const response = await fetch(`${url}?ids=${ids.join(',')}`, { headers: { Accept: 'application/json' } });

                if (!response.ok) {
                    return;
                }

                const data = await response.json();

                const changed = ids.some((id) => {
                    const before = initialState[id];
                    const after = data[id];

                    return after && (before.status !== after.status || before.attended_by !== after.attended_by);
                });

                if (changed) {
                    clearInterval(intervalId);

                    if (window.Turbo) {
                        window.Turbo.visit(window.location.href, { action: 'replace' });
                    } else {
                        window.location.reload();
                    }
                }
            } catch (e) {
                // A failed poll just tries again on the next interval tick.
            }
        },
    }));
}
