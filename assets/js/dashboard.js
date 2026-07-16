// Priority Chart
const ctxPriority = document.getElementById('priorityChart')?.getContext('2d');
if (ctxPriority) {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const tc = isDark ? '#9ca3af' : '#6b7280';
    new Chart(ctxPriority, {
        type: 'doughnut',
        data: {
            labels: ['Niski', 'Średni', 'Wysoki', 'Krytyczny'],
            datasets: [{
                data: window.dashboardData.priorities,
                backgroundColor: ['#10b981','#06b6d4','#f59e0b','#ef4444'],
                borderWidth: 0,
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            cutout: '68%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: tc, padding: 14, font: { size: 12, weight: '600' } }
                }
            }
        }
    });
}

// Trend Chart
let trendChart = null;
async function loadTrend() {
    try {
        const data = await apiGet('/api/stats.php');
        if (!data?.trend) return;
        const labels = data.trend.map(d => d.day);
        const values = data.trend.map(d => d.count);
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const gridColor = isDark ? 'rgba(255,255,255,.06)' : 'rgba(0,0,0,.06)';
        const textColor = isDark ? '#9ca3af' : '#6b7280';
        const ctx = document.getElementById('trendChart')?.getContext('2d');
        if (!ctx) return;
        if (trendChart) {
            trendChart.data.labels = labels;
            trendChart.data.datasets[0].data = values;
            trendChart.update('active');
            return;
        }
        const grad = ctx.createLinearGradient(0,0,0,280);
        grad.addColorStop(0,'rgba(99,102,241,.25)');
        grad.addColorStop(1,'rgba(99,102,241,.02)');
        trendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Zadania ukończone',
                    data: values,
                    borderColor: '#6366f1',
                    backgroundColor: grad,
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointBackgroundColor: '#6366f1',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: textColor } },
                    y: { grid: { color: gridColor }, ticks: { color: textColor, precision: 0 }, beginAtZero: true }
                }
            }
        });
    } catch(e) { console.error('Trend chart error:', e); }
}
document.addEventListener("DOMContentLoaded", () => { loadTrend(); setInterval(loadTrend, 60000); });

document.querySelectorAll('[data-counter]').forEach(el => {
    const target = parseInt(el.dataset.counter);
    if (!isNaN(target) && typeof animateCounter === 'function') animateCounter(el, target);
});