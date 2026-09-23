import { Chart, LineController, LineElement, PointElement, LinearScale, CategoryScale, Filler, Tooltip } from 'chart.js';

Chart.register(LineController, LineElement, PointElement, LinearScale, CategoryScale, Filler, Tooltip);

export function mountLiquidityChart() {
    const canvas = document.getElementById('liquidityChart');
    const dataEl = document.getElementById('liquidity-series');

    if (!canvas || !dataEl) {
        return;
    }

    const series = JSON.parse(dataEl.textContent);
    const labels = series.map((point) => point.date.slice(5));
    const values = series.map((point) => point.balance);

    const lowestIndex = values.indexOf(Math.min(...values));
    const pointColors = values.map((_, i) => (i === lowestIndex ? '#DC2626' : '#22C55E'));
    const pointRadii = values.map((_, i) => (i === lowestIndex ? 6 : 3));

    new Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Saldo proyectado',
                    data: values,
                    borderColor: '#22C55E',
                    backgroundColor: 'rgba(34, 197, 94, 0.12)',
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: pointColors,
                    pointRadius: pointRadii,
                    pointHoverRadius: 7,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (ctx) => `S/ ${ctx.parsed.y.toFixed(2)}`,
                    },
                },
            },
            scales: {
                y: {
                    ticks: { callback: (value) => `S/ ${value}` },
                },
            },
        },
    });
}
