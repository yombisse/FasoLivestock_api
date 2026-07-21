/* ═══════════════════════════════════════════════════════════
   FasoLivestock Admin — Dashboard Index JS
═══════════════════════════════════════════════════════ */

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

// Composant Alpine.js pour le dashboard
function dashboardIndex() {
    return {
        loading: true,
        refreshing: false,
        stats: {
            animaux_actifs: 0,
            revenus_ce_mois: 0,
            alertes: 0,
            evenements_sante: 0,
        },
        
        init() {
            // Initialiser les stats avec les données du controller
            this.stats = {
                animaux_actifs: window.dashboardData?.cheptel?.animaux_actifs || 0,
                revenus_ce_mois: window.dashboardData?.finance?.revenus_ce_mois || 0,
                alertes: window.dashboardData?.alertes?.total_alertes || 0,
                evenements_sante: window.dashboardData?.sante?.evenements_sanitaires_ce_mois || 0,
            };
            
            // Initialiser les graphiques
            this.initCharts();
            
            this.loading = false;
            
            // Rafraîchissement automatique toutes les 5 minutes
            this.startAutoRefresh();
        },
        
        initCharts() {
            // Évolution financière
            if (dashboardChartsData.financial_evolution) {
                createChart('chart-financial-evolution', dashboardChartsData.financial_evolution);
            }

            // Cheptel par espèce
            if (dashboardChartsData.herd_by_species) {
                createChart('chart-herd-species', dashboardChartsData.herd_by_species);
            }

            // Naissances par mois
            if (dashboardChartsData.births_by_month) {
                createChart('chart-births-month', dashboardChartsData.births_by_month);
            }

            // Cheptel par sexe
            if (dashboardChartsData.herd_by_sex) {
                createChart('chart-herd-sex', dashboardChartsData.herd_by_sex);
            }

            // Revenus par catégorie
            if (dashboardChartsData.revenue_by_category) {
                createChart('chart-revenue-category', dashboardChartsData.revenue_by_category);
            }

            // Charges par catégorie
            if (dashboardChartsData.expense_by_category) {
                createChart('chart-expense-category', dashboardChartsData.expense_by_category);
            }

            // Statistiques mouvements
            if (dashboardChartsData.movements_stats) {
                createChart('chart-movements-stats', dashboardChartsData.movements_stats);
            }
        },
        
        async refreshAll() {
            if (this.refreshing) return;
            
            this.refreshing = true;
            
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
                    this.animateCounters();
                }
            } catch (error) {
                console.error('Erreur lors du rafraîchissement:', error);
            } finally {
                this.refreshing = false;
            }
        },
        
        startAutoRefresh() {
            // Rafraîchir toutes les 5 minutes (300000 ms)
            setInterval(() => {
                this.refreshAll();
            }, 300000);
        },
        
        animateCounters() {
            // Animation simple des compteurs
            const counters = document.querySelectorAll('.kpi-value');
            counters.forEach(counter => {
                const target = parseInt(counter.textContent.replace(/\s/g, '')) || 0;
                this.animateValue(counter, 0, target, 500);
            });
        },
        
        animateValue(element, start, end, duration) {
            const range = end - start;
            const increment = end > start ? 1 : -1;
            const stepTime = Math.abs(Math.floor(duration / range));
            let current = start;
            
            const timer = setInterval(() => {
                current += increment;
                element.textContent = this.formatNumber(current);
                
                if (current === end) {
                    clearInterval(timer);
                }
            }, Math.max(stepTime, 10));
        },
        
        formatNumber(value) {
            return new Intl.NumberFormat('fr-FR').format(value);
        },
        
        formatCurrency(value) {
            return new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: 'EUR',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(value);
        }
    };
}

// Initialisation au chargement du DOM
document.addEventListener('DOMContentLoaded', function() {
    // Les données sont déjà injectées par le controller PHP
    // dans la variable globale dashboardChartsData
});
