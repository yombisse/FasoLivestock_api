<div class="farm-statistics">
    
    {{-- Alertes actives --}}
    @if(isset($dashboard['alertes']['total_alertes']) && $dashboard['alertes']['total_alertes'] > 0)
    <div class="alert alert-warning d-flex align-items-center gap-3 mb-4">
        <i class="bi bi-exclamation-triangle-fill fs-4"></i>
        <div>
            <strong>Alertes actives</strong>
            <div class="small">
                {{ $dashboard['alertes']['total_alertes'] }} alerte(s) -
                {{ $dashboard['alertes']['critiques'] ?? 0 }} critique(s),
                {{ $dashboard['alertes']['moyennes'] ?? 0 }} moyenne(s),
                {{ $dashboard['alertes']['faibles'] ?? 0 }} faible(s)
            </div>
        </div>
    </div>
    @endif

    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card green">
                <div class="stat-icon">
                    <i class="bi bi-box2-heart"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value">{{ $dashboard['cheptel']['animaux_actifs'] ?? 0 }}</div>
                    <div class="stat-label">Animaux actifs</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card orange">
                <div class="stat-icon">
                    <i class="bi bi-baby"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value">{{ $dashboard['mouvements']['naissances_ce_mois'] ?? 0 }}</div>
                    <div class="stat-label">Naissances ce mois</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card red">
                <div class="stat-icon">
                    <i class="bi bi-heart-pulse"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value">{{ $dashboard['sante']['evenements_sanitaires_ce_mois'] ?? 0 }}</div>
                    <div class="stat-label">Événements santé</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card blue">
                <div class="stat-icon">
                    <i class="bi bi-currency-dollar"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value">{{ number_format($dashboard['finance']['solde'] ?? 0, 0, ',', ' ') }}</div>
                    <div class="stat-label">Solde (XOF)</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Graphiques --}}
    <div class="row g-4">
        
        {{-- Répartition par espèce --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-pie-chart"></i> Répartition par espèce
                </div>
                <div class="card-body">
                    <div style="height: 250px;">
                        <canvas id="chart-herd-species"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Répartition par sexe --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-pie-chart"></i> Répartition par sexe
                </div>
                <div class="card-body">
                    <div style="height: 250px;">
                        <canvas id="chart-herd-sex"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Mouvements du mois --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-bar-chart"></i> Mouvements du mois
                </div>
                <div class="card-body">
                    <div style="height: 250px;">
                        <canvas id="chart-movements"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Évolution financière --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-line-chart"></i> Évolution financière
                </div>
                <div class="card-body">
                    <div style="height: 250px;">
                        <canvas id="chart-financial-evolution"></canvas>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        const farmChartsData = @json($charts);

        Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
        Chart.defaults.color = '#64748b';

        function createFarmChart(canvasId, config) {
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

        document.addEventListener('DOMContentLoaded', function() {
            if (farmChartsData.herd_by_species) {
                createFarmChart('chart-herd-species', farmChartsData.herd_by_species);
            }
            if (farmChartsData.herd_by_sex) {
                createFarmChart('chart-herd-sex', farmChartsData.herd_by_sex);
            }
            if (farmChartsData.movements_stats) {
                createFarmChart('chart-movements', farmChartsData.movements_stats);
            }
            if (farmChartsData.financial_evolution) {
                createFarmChart('chart-financial-evolution', farmChartsData.financial_evolution);
            }
        });
    </script>
@endpush
