<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        
        .header {
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .header h1 {
            color: #2c3e50;
            font-size: 18px;
            margin: 0 0 10px 0;
        }
        
        .header-info {
            font-size: 11px;
            color: #666;
        }
        
        .section-title {
            background-color: #2c3e50;
            color: white;
            padding: 8px 12px;
            font-size: 14px;
            font-weight: bold;
            margin: 20px 0 10px 0;
        }
        
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .summary-table th {
            background-color: #ecf0f1;
            border: 1px solid #bdc3c7;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            font-size: 11px;
        }
        
        .summary-table td {
            border: 1px solid #bdc3c7;
            padding: 8px;
            font-size: 10px;
        }
        
        .summary-table .number {
            text-align: right;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10px;
        }
        
        .data-table th {
            background-color: #e74c3c;
            color: white;
            border: 1px solid #c0392b;
            padding: 6px 8px;
            text-align: left;
            font-weight: bold;
        }
        
        .data-table td {
            border: 1px solid #bdc3c7;
            padding: 6px 8px;
        }
        
        .data-table .number {
            text-align: right;
        }
        
        .data-table .center {
            text-align: center;
        }
        
        .data-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .statut-en_attente {
            color: #f39c12;
            font-weight: bold;
        }
        
        .statut-realise {
            color: #27ae60;
            font-weight: bold;
        }
        
        .statut-en_retard {
            color: #e74c3c;
            font-weight: bold;
        }
        
        .alert-retard {
            background-color: #fadbd8;
            border-left: 4px solid #e74c3c;
            padding: 10px;
            margin-bottom: 15px;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #bdc3c7;
            font-size: 10px;
            color: #7f8c8d;
            text-align: center;
        }
        
        .total-row {
            background-color: #ecf0f1;
            font-weight: bold;
        }
        
        .taux-bon {
            color: #27ae60;
            font-weight: bold;
        }
        
        .taux-moyen {
            color: #f39c12;
            font-weight: bold;
        }
        
        .taux-mauvais {
            color: #e74c3c;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Rapport Sanitaire</h1>
        <div class="header-info">
            <strong>Ferme :</strong> {{ $farm->name ?? 'Non définie' }}<br>
            <strong>Date de génération :</strong> {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}<br>
            @if($date_debut && $date_fin)
            <strong>Période couverte :</strong> du {{ \Carbon\Carbon::parse($date_debut)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($date_fin)->format('d/m/Y') }}
            @else
            <strong>Période couverte :</strong> Données actuelles
            @endif
        </div>
    </div>

    {{-- Résumé --}}
    <div class="section-title">Résumé Sanitaire</div>
    
    <table class="summary-table">
        <tr>
            <th style="width: 70%;">Indicateur</th>
            <th style="width: 30%;" class="number">Valeur</th>
        </tr>
        <tr>
            <td>Total rappels</td>
            <td class="number">{{ $totalRappels }}</td>
        </tr>
        <tr>
            <td>Rappels en attente</td>
            <td class="number">{{ $rappelsEnAttente }}</td>
        </tr>
        <tr>
            <td>Rappels réalisés</td>
            <td class="number">{{ $rappelsRealises }}</td>
        </tr>
        <tr>
            <td>Rappels en retard</td>
            <td class="number">{{ $rappelsEnRetard }}</td>
        </tr>
        <tr class="total-row">
            <td>Taux de réalisation</td>
            <td class="number {{ $tauxRealisationClass }}">{{ $tauxRealisation }}%</td>
        </tr>
    </table>

    {{-- Répartition par statut --}}
    <div class="section-title">Répartition par Statut</div>
    
    <table class="summary-table">
        <tr>
            <th>Statut</th>
            <th class="number">Effectif</th>
            <th class="number">%</th>
        </tr>
        @foreach($repartitionStatut as $statut)
        <tr>
            <td class="statut-{{ strtolower($statut['statut']) }}">{{ $statut['statut'] }}</td>
            <td class="number">{{ $statut['count'] }}</td>
            <td class="number">{{ $statut['percentage'] }}%</td>
        </tr>
        @endforeach
        <tr class="total-row">
            <td><strong>Total</strong></td>
            <td class="number"><strong>{{ $totalRappels }}</strong></td>
            <td class="number"><strong>100%</strong></td>
        </tr>
    </table>

    {{-- Rappels en retard --}}
    @if(count($rappelsEnRetardList) > 0)
    <div class="section-title">Rappels en Retard ({{ count($rappelsEnRetardList) }})</div>
    
    <div class="alert-retard">
        <strong>Attention :</strong> {{ count($rappelsEnRetardList) }} rappels sont en retard et nécessitent une action immédiate.
    </div>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>Animal</th>
                <th>Type Rappel</th>
                <th>Date Prévue</th>
                <th class="number">Jours Retard</th>
                <th>Note</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rappelsEnRetardList as $rappel)
            <tr>
                <td>{{ $rappel->animal ? ($rappel->animal->nom ?? $rappel->animal->numero_identification) : 'N/A' }}</td>
                <td>{{ $rappel->type_rappel }}</td>
                <td>{{ \Carbon\Carbon::parse($rappel->date_prevue)->format('d/m/Y') }}</td>
                <td class="number">{{ \Carbon\Carbon::parse($rappel->date_prevue)->diffInDays(now(), false) }} jours</td>
                <td>{{ $rappel->note }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="section-title">Rappels en Retard</div>
    <p>Aucun rappel en retard. Excellent travail !</p>
    @endif

    {{-- Rappels à venir --}}
    @if(count($rappelsAVenir) > 0)
    <div class="section-title">Rappels à Venir (30 prochains jours)</div>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>Animal</th>
                <th>Type Rappel</th>
                <th>Date Prévue</th>
                <th class="number">Jours Restants</th>
                <th>Urgence</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rappelsAVenir as $rappel)
            <tr>
                <td>{{ $rappel['animal'] }}</td>
                <td>{{ $rappel['type_rappel'] }}</td>
                <td>{{ \Carbon\Carbon::parse($rappel['date_prevue'])->format('d/m/Y') }}</td>
                <td class="number">{{ $rappel['jours_restants'] }} jours</td>
                <td>{{ $rappel['urgence'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="section-title">Rappels à Venir</div>
    <p>Aucun rappel prévu dans les 30 prochains jours.</p>
    @endif

    <script type="text/php">
        if (isset($pdf)) {
            $text = "Page " . $pdf->get_page_number() . " sur " . $pdf->get_page_count();
            $pdf->page_text(270, 820, $text, null, 10, array(0, 0, 0));
        }
    </script>
</body>
</html>
