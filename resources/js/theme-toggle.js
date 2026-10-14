/**
 * Animates the light/dark switch as a circle expanding from the button
 * that was clicked, using the View Transitions API — the same API the
 * `view-transition` meta tag already uses for Turbo's page-to-page fade,
 * but with its own custom clip-path animation instead of the default
 * crossfade (scoped via the .theme-transition class so it doesn't affect
 * that page fade). Falls back to an instant switch with no animation if
 * the browser doesn't support it, or the user asked for reduced motion.
 */
export function registerThemeToggle() {
    window.toggleThemeAnimated = function (dark, url, token, event) {
        const apply = () => {
            document.documentElement.classList.toggle('dark', dark);
        };

        fetch(url, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify({ theme: dark ? 'dark' : 'light' }),
        });

        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        if (! document.startViewTransition || reducedMotion) {
            apply();

            return;
        }

        const x = event.clientX;
        const y = event.clientY;
        const endRadius = Math.hypot(
            Math.max(x, window.innerWidth - x),
            Math.max(y, window.innerHeight - y)
        );

        document.documentElement.classList.add('theme-transition');

        const transition = document.startViewTransition(apply);

        transition.ready.then(() => {
            document.documentElement.animate(
                { clipPath: [`circle(0px at ${x}px ${y}px)`, `circle(${endRadius}px at ${x}px ${y}px)`] },
                { duration: 400, easing: 'ease-in-out', pseudoElement: '::view-transition-new(root)' }
            );
        });

        transition.finished.finally(() => {
            document.documentElement.classList.remove('theme-transition');
        });
    };
}
