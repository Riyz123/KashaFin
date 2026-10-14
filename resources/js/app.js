import './bootstrap';

import Alpine from 'alpinejs';
import { mountLiquidityChart } from './charts/liquidity-chart';
import { mountReportCharts } from './charts/report-charts';
import { registerChatWidget } from './chat-voice';
import { registerNotificationsPoll } from './notifications-poll';
import { registerPresencePoll } from './presence-poll';

window.Alpine = Alpine;

registerChatWidget(Alpine);
registerNotificationsPoll(Alpine);
registerPresencePoll(Alpine);

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    mountLiquidityChart();
    mountReportCharts();
});
