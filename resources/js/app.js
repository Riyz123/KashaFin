import './bootstrap';

import Alpine from 'alpinejs';
import { mountLiquidityChart } from './charts/liquidity-chart';
import { mountReportCharts } from './charts/report-charts';
import { registerChatWidget } from './chat-voice';

window.Alpine = Alpine;

registerChatWidget(Alpine);

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    mountLiquidityChart();
    mountReportCharts();
});
