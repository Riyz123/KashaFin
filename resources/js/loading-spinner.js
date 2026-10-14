/**
 * Shows a small spinner (fixed top-right) while Turbo is fetching the next
 * page, so a navigation that takes a moment (a heavier page like Reportes)
 * gives visual feedback instead of feeling frozen. The spinner div itself
 * lives in each layout's HTML (not created here) so it's naturally present
 * again after every Turbo page swap — these listeners just toggle its
 * class, looking it up fresh each time rather than caching a reference
 * that would go stale once Turbo replaces <body>.
 */
export function registerLoadingSpinner() {
    document.addEventListener('turbo:visit', () => {
        document.getElementById('turbo-spinner')?.classList.add('is-visible');
    });

    document.addEventListener('turbo:load', () => {
        document.getElementById('turbo-spinner')?.classList.remove('is-visible');
    });
}
