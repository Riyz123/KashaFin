import './bootstrap';

// Importing this starts Turbo Drive immediately: it intercepts normal
// <a> clicks and <form> submits site-wide and does them via fetch instead
// of a full browser navigation, swapping <body> with the response — no
// controller/route changes needed. See resources/views/layouts/*.blade.php
// for the data-turbo="false" exemptions on file-download links.
import '@hotwired/turbo';

import Alpine from 'alpinejs';
import { mountLiquidityChart } from './charts/liquidity-chart';
import { mountReportCharts } from './charts/report-charts';
import { registerChatWidget } from './chat-voice';
import { registerNotificationsPoll } from './notifications-poll';
import { registerPresencePoll } from './presence-poll';
import { registerThemeToggle } from './theme-toggle';
import { registerLoadingSpinner } from './loading-spinner';
import { registerAdminSupportPoll } from './admin-support-poll';
import { registerSupportListPoll } from './support-list-poll';
import { registerAiTrafficPoll } from './ai-traffic-poll';
import { registerLoanStatusPoll } from './loan-status-poll';

window.Alpine = Alpine;

registerChatWidget(Alpine);
registerNotificationsPoll(Alpine);
registerPresencePoll(Alpine);
registerThemeToggle();
registerLoadingSpinner();
registerAdminSupportPoll(Alpine);
registerSupportListPoll(Alpine);
registerAiTrafficPoll(Alpine);
registerLoanStatusPoll(Alpine);

Alpine.start();

// turbo:load covers both the very first page load and every subsequent
// in-app navigation (DOMContentLoaded only fires once, ever, for the
// lifetime of the tab under Turbo Drive).
document.addEventListener('turbo:load', () => {
    mountLiquidityChart();
    mountReportCharts();

    // tsParticles is only needed on login/register — dynamic import()
    // makes Vite split it into its own chunk, fetched only when that
    // container is actually on the page, never anywhere else.
    if (document.getElementById('aurora-particles')) {
        import('./aurora-particles').then(({ registerAuroraParticles }) => registerAuroraParticles());
    }
});
