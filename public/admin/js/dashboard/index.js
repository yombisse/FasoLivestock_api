// Données des graphiques passées depuis le contrôleur
const dashboardData = @json($charts);

// Configuration commune Chart.js
Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
Chart.defaults.color = '#64748b';

// Fonction pour créer un graphique
function createChart(canvasId, config) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    return new Chart(ctx, {
        type: config.type,
        data: config.data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: config.options?.plugins?.legend || {
                    display: config.type === 'pie' || config.type === 'doughnut',
                    position: 'bottom',
                },
            },
            scales: config.type === 'bar' || config.type === 'line' ? {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#e2e8f0',
                    },
                },
                x: {
                    grid: {
                        display: false,
                    },
                },
            } : {},
        },
    });
}

// Initialisation des graphiques au chargement
document.addEventListener('DOMContentLoaded', function() {
    // Évolution financière
    if (dashboardData.financial_evolution) {
        createChart('chart-financial-evolution', dashboardData.financial_evolution);
    }

    // Cheptel par espèce
    if (dashboardData.herd_by_species) {
        createChart('chart-herd-species', dashboardData.herd_by_species);
    }

    // Cheptel par sexe
    if (dashboardData.herd_by_sex) {
        createChart('chart-herd-sex', dashboardData.herd_by_sex);
    }

    // Événements sanitaires
    if (dashboardData.health_events_evolution) {
        createChart('chart-health-events', dashboardData.health_events_evolution);
    }

    // Mouvements
    if (dashboardData.movements_stats) {
        createChart('chart-movements', dashboardData.movements_stats);
    }

    // Reproduction
    if (dashboardData.reproduction_stats) {
        createChart('chart-reproduction', dashboardData.reproduction_stats);
    }

    // Naissances par mois
    if (dashboardData.births_by_month) {
        createChart('chart-births-month', dashboardData.births_by_month);
    }
});
