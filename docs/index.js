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

    // Naissances par mois
    if (dashboardData.births_by_month) {
        createChart('chart-births-month', dashboardData.births_by_month);
    }
});

// Composant Alpine.js pour les stats dynamiques
function dashboardStats() {
    return {
        loading: true,
        stats: {
            animaux_actifs: 0,
            revenus_ce_mois: 0,
            alertes: 0,
            evenements_sante: 0,
        },
        
        async loadStats() {
            try {
                const farmId = document.querySelector('[data-farm-id]')?.dataset.farmId || '';
                const response = await fetch(`/api/dashboard/admin-stats?current_farm_id=${farmId}`, {
                    headers: {
                        'Accept': 'application/json',
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.stats = data.data;
                }
            } catch (error) {
                console.error('Erreur lors du chargement des stats:', error);
            } finally {
                this.loading = false;
            }
        },
        
        formatNumber(value) {
            return new Intl.NumberFormat('fr-FR').format(value);
        }
    };
}
