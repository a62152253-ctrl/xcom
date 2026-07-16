const chartColor = getComputedStyle(document.documentElement).getPropertyValue('--text-primary').trim();
const gridColor = getComputedStyle(document.documentElement).getPropertyValue('--border-color').trim();

// Line chart
new Chart(document.getElementById('lineChart').getContext('2d'), {
    type: 'line',
    data: {
        labels: window.reportsData.daysLabels,
        datasets: [{
            label: 'Ukończone zadania',
            data: window.reportsData.daysValues,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59,130,246,.12)',
            borderWidth: 2,
            tension: .4,
            fill: true,
            pointBackgroundColor: '#3b82f6',
            pointRadius: 4
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { color: gridColor }, ticks: { color: chartColor } },
            y: { grid: { color: gridColor }, ticks: { color: chartColor, stepSize: 1, precision: 0 } }
        }
    }
});

// Doughnut chart
new Chart(document.getElementById('donutChart').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: ['Do zrobienia', 'W trakcie', 'Review', 'Ukończone'],
        datasets: [{
            data: [...window.reportsData.statuses],
            backgroundColor: ['#64748b','#f59e0b','#06b6d4','#10b981'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        cutout: '65%',
        plugins: {
            legend: { position: 'bottom', labels: { color: chartColor, padding: 16 } }
        }
    }
});