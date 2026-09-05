/**
 * Charts JavaScript using Chart.js
 */

let charts = {};

function initCharts() {
    if (typeof Chart === 'undefined') return;
    
    initAssetDistributionChart();
    initMonthlyTrendChart();
    initWeaponTypeChart();
    initAuditVarianceChart();
}

function initAssetDistributionChart() {
    const ctx = document.getElementById('assetDistributionChart');
    if (!ctx) return;
    
    if (typeof Chart.getChart === 'function') {
        const existing = Chart.getChart(ctx);
        if (existing) existing.destroy();
    }
    
    charts.assetDistribution = new Chart(ctx.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Land', 'Buildings', 'Vehicles', 'Weapons', 'ICT', 'Other'],
            datasets: [{
                data: [30, 25, 20, 15, 8, 2],
                backgroundColor: [
                    '#207027',
                    '#3498db',
                    '#e74c3c',
                    '#f39c12',
                    '#9b59b6',
                    '#95a5a6'
                ],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value * 100) / total);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            },
            cutout: '60%'
        }
    });
}

function initMonthlyTrendChart() {
    const ctx = document.getElementById('monthlyTrendChart');
    if (!ctx) return;
    
    if (typeof Chart.getChart === 'function') {
        const existing = Chart.getChart(ctx);
        if (existing) existing.destroy();
    }
    
    charts.monthlyTrend = new Chart(ctx.getContext('2d'), {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [
                {
                    label: 'Weapons',
                    data: [65, 59, 80, 81, 56, 55, 70, 75, 82, 90, 85, 95],
                    borderColor: '#e74c3c',
                    backgroundColor: 'rgba(231, 76, 60, 0.1)',
                    tension: 0.4,
                    fill: false
                },
                {
                    label: 'Ammunition',
                    data: [28, 48, 40, 19, 86, 27, 45, 55, 60, 70, 65, 80],
                    borderColor: '#f39c12',
                    backgroundColor: 'rgba(243, 156, 18, 0.1)',
                    tension: 0.4,
                    fill: false
                },
                {
                    label: 'Vehicles',
                    data: [15, 25, 20, 35, 30, 45, 40, 50, 45, 55, 60, 65],
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    tension: 0.4,
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Items'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Month'
                    }
                }
            }
        }
    });
}

function initWeaponTypeChart() {
    const ctx = document.getElementById('weaponTypeChart');
    if (!ctx) return;
    
    if (typeof Chart.getChart === 'function') {
        const existing = Chart.getChart(ctx);
        if (existing) existing.destroy();
    }
    
    charts.weaponType = new Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: ['G3 Rifle', 'AK-47', 'SMG', 'Pistol', 'Shotgun', 'Sniper'],
            datasets: [{
                label: 'Quantity',
                data: [120, 85, 60, 45, 30, 15],
                backgroundColor: '#207027',
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Quantity'
                    }
                }
            }
        }
    });
}

function initAuditVarianceChart() {
    const ctx = document.getElementById('auditVarianceChart');
    if (!ctx) return;
    
    charts.auditVariance = new Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: ['Q1', 'Q2', 'Q3', 'Q4'],
            datasets: [
                {
                    label: 'Weapons Variance',
                    data: [5, 3, 8, 4],
                    backgroundColor: '#e74c3c'
                },
                {
                    label: 'Ammunition Variance',
                    data: [12, 8, 15, 7],
                    backgroundColor: '#f39c12'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Variances'
                    }
                }
            }
        }
    });
}

function updateChartData(chartName, labels, data) {
    if (charts[chartName]) {
        charts[chartName].data.labels = labels;
        charts[chartName].data.datasets.forEach((dataset, index) => {
            if (data[index]) {
                dataset.data = data[index];
            }
        });
        charts[chartName].update();
    }
}

function addChartData(chartName, label, value, datasetIndex = 0) {
    if (charts[chartName]) {
        charts[chartName].data.labels.push(label);
        charts[chartName].data.datasets[datasetIndex].data.push(value);
        charts[chartName].update();
    }
}

function removeChartData(chartName, index) {
    if (charts[chartName]) {
        charts[chartName].data.labels.splice(index, 1);
        charts[chartName].data.datasets.forEach(dataset => {
            dataset.data.splice(index, 1);
        });
        charts[chartName].update();
    }
}