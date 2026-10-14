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

window.Alpine = Alpine;

registerChatWidget(Alpine);
registerNotificationsPoll(Alpine);
registerPresencePoll(Alpine);
registerThemeToggle();
registerLoadingSpinner();

Alpine.start();

// turbo:load covers both the very first page load and every subsequent
// in-app navigation (DOMContentLoaded only fires once, ever, for the
// lifetime of the tab under Turbo Drive).
document.addEventListener('turbo:load', () => {
    mountLiquidityChart();
    mountReportCharts();
});
