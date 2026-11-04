/**
 * Keeps the Préstamos list in sync with the OTHER side of a loan — when
 * the other person accepts/rejects an amortization you sent, confirms a
 * payment, or sends you a new amortization request, your own paid/remaining
 * amounts and the "Amortizaciones por confirmar" section change without you
 * doing anything on your end. Polls every ~5s and does one Turbo-smooth
 * page refresh the moment anything differs from what was server-rendered
 * at load — same pattern as the admin Soporte list (support-list-poll.js).
 */
export function registerLoanStatusPoll(Alpine) {
    Alpine.data('loanStatusPoll', (initialLoans, initialRequestsCount, url) => ({
        init() {
            const ids = Object.keys(initialLoans);

            if (ids.length === 0) {
                return;
            }

            const intervalId = setInterval(() => this.poll(initialLoans, initialRequestsCount, ids, url, intervalId), 5000);

            document.addEventListener('turbo:before-cache', () => clearInterval(intervalId), { once: true });
        },

        async poll(initialLoans, initialRequestsCount, ids, url, intervalId) {
            try {
                const response = await fetch(`${url}?ids=${ids.join(',')}`, { headers: { Accept: 'application/json' } });

                if (!response.ok) {
                    return;
                }

                const data = await response.json();

                const loansChanged = ids.some((id) => {
                    const before = initialLoans[id];
                    const after = data.loans[id];

                    return after && (before.status !== after.status || before.paid_amount !== after.paid_amount);
                });

                const requestsChanged = data.amortization_requests_count !== initialRequestsCount;

                if (loansChanged || requestsChanged) {
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
