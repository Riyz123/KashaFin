import { Chart, DoughnutController, ArcElement, BarController, BarElement, LinearScale, CategoryScale, Tooltip, Legend } from 'chart.js';

Chart.register(DoughnutController, ArcElement, BarController, BarElement, LinearScale, CategoryScale, Tooltip, Legend);

const PALETTE = ['#22C55E', '#16A34A', '#6EE7B7', '#14532D', '#86EFAC', '#BBF7D0', '#F59E0B', '#EF4444'];

export function mountReportCharts() {
    mountCategoryChart();
    mountComparisonChart();
}

function mountCategoryChart() {
    const canvas = document.getElementById('categoryChart');
    const dataEl = document.getElementById('category-series');

    if (!canvas || !dataEl) {
        return;
    }

    const rows = JSON.parse(dataEl.textContent);

    new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: rows.map((r) => r.category),
            datasets: [
                {
                    data: rows.map((r) => r.total),
                    backgroundColor: rows.map((_, i) => PALETTE[i % PALETTE.length]),
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } },
        },
    });
}

function mountComparisonChart() {
    const canvas = document.getElementById('comparisonChart');
    const dataEl = document.getElementById('comparison-series');

    if (!canvas || !dataEl) {
        return;
    }

    const summary = JSON.parse(dataEl.textContent);

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: ['Ingresos', 'Gastos'],
            datasets: [
                {
                    data: [summary.income, summary.expense],
                    backgroundColor: ['#22C55E', '#EF4444'],
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
        },
    });
}
