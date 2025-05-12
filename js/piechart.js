document.addEventListener("DOMContentLoaded", function () {
    Chart.register(ChartDataLabels);

    const legendContainer = document.getElementById("customLegend");

    const filtered = chartData.labels
        .map((label, i) => ({
            label,
            value: parseFloat(chartData.data[i]) || 0
        }))
        .filter(item => item.value > 0);

    const filteredLabels = filtered.map(item => item.label);
    const filteredData = filtered.map(item => item.value);

    if (filtered.length > 0) {
        const ctx = document.getElementById("expensesPieChart");

        const chart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: filteredLabels,
                datasets: [{
                    data: filteredData,
                    backgroundColor: [
                        '#f87171', '#4ade80', '#a7f3d0', '#fde68a', '#fdba74', '#93c5fd'
                    ],
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#fff',
                        borderColor: '#e5e7eb',
                        borderWidth: 1,
                        bodyColor: '#111827',
                        titleColor: '#111827',
                        callbacks: {
                            label: function (tooltipItem) {
                                const value = parseFloat(tooltipItem.raw) || 0;
                                const label = tooltipItem.label || 'Unknown';
                                const userName = chart.options.userName || 'You';
                                return `${label}: NRS ${value.toFixed(2)} (by ${userName})`;
                            }
                        }
                    },
                    datalabels: {
                        color: '#fff',
                        formatter: function (value, context) {
                            const label = context.chart.data.labels[context.dataIndex];
                            const num = parseFloat(value.value) || 0;
                            return `${label}\nNRS ${num.toFixed(2)}`;
                        },
                        font: {
                            weight: 'bold',
                            size: 12
                        }
                    }
                },
                userName: chartData.userName || 'You'
            },
            plugins: [ChartDataLabels]
        });

        if (legendContainer) {
            legendContainer.innerHTML = filtered.map((item, i) => {
                const color = chart.data.datasets[0].backgroundColor[i];
                return `
                    <div class="legend-item">
                        <span class="legend-color" style="background-color:${color};"></span>
                        <span class="legend-label">${item.label}</span>
                    </div>
                `;
            }).join('');
        }
    } else {
        if (legendContainer) legendContainer.innerHTML = '<em>No data available</em>';
    }
});
