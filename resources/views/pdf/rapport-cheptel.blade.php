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
            background-color: #3498db;
            color: white;
            border: 1px solid #2980b9;
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
        
        .statut-actif {
            color: #27ae60;
            font-weight: bold;
        }
        
        .statut-inactif {
            color: #e74c3c;
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
    </style>
</head>
<body>
    <div class="header">
        <h1>Rapport Cheptel</h1>
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

    {{-- Résumé chiffré --}}
    <div class="section-title">Résumé Chiffré</div>
    
    <table class="summary-table">
        <tr>
            <th style="width: 70%;">Indicateur</th>
            <th style="width: 30%;" class="number">Valeur</th>
        </tr>
        <tr>
            <td>Total animaux</td>
            <td class="number">{{ $totalAnimaux }}</td>
        </tr>
        <tr>
            <td>Animaux actifs</td>
            <td class="number">{{ $animauxActifs }}</td>
        </tr>
        <tr>
            <td>Animaux inactifs</td>
            <td class="number">{{ $animauxInactifs }}</td>
        </tr>
        <tr class="total-row">
            <td>Taux d'activité</td>
            <td class="number">{{ $tauxActivite }}%</td>
        </tr>
    </table>

    {{-- Répartition par espèce --}}
    <div class="section-title">Répartition par Espèce</div>
    
    <table class="summary-table">
        <tr>
            <th>Espèce</th>
            <th class="number">Effectif</th>
            <th class="number">%</th>
        </tr>
        @foreach($repartitionEspece as $espece)
        <tr>
            <td>{{ $espece['nom'] }}</td>
            <td class="number">{{ $espece['count'] }}</td>
            <td class="number">{{ $espece['percentage'] }}%</td>
        </tr>
        @endforeach
        <tr class="total-row">
            <td><strong>Total</strong></td>
            <td class="number"><strong>{{ $totalAnimaux }}</strong></td>
            <td class="number"><strong>100%</strong></td>
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
            <td>{{ $statut['statut'] }}</td>
            <td class="number">{{ $statut['count'] }}</td>
            <td class="number">{{ $statut['percentage'] }}%</td>
        </tr>
        @endforeach
        <tr class="total-row">
            <td><strong>Total</strong></td>
            <td class="number"><strong>{{ $totalAnimaux }}</strong></td>
            <td class="number"><strong>100%</strong></td>
        </tr>
    </table>

    {{-- Liste des animaux actifs --}}
    <div class="section-title">Liste des Animaux Actifs</div>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>N° Identification</th>
                <th>Nom</th>
                <th>Espèce</th>
                <th>Race</th>
                <th class="center">Sexe</th>
                <th>Âge</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            @foreach($animaux as $animal)
            <tr>
                <td>{{ $animal->numero_identification }}</td>
                <td>{{ $animal->nom }}</td>
                <td>{{ $animal->espece->nom ?? 'N/A' }}</td>
                <td>{{ $animal->race }}</td>
                <td class="center">{{ $animal->sexe }}</td>
                <td>{{ $animal->date_naissance ? \Carbon\Carbon::parse($animal->date_naissance)->diffForHumans() : 'N/A' }}</td>
                <td class="{{ $animal->statut === 'ACTIF' ? 'statut-actif' : 'statut-inactif' }}">{{ $animal->statut }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <script type="text/php">
        if (isset($pdf)) {
            $text = "Page " . $pdf->get_page_number() . " sur " . $pdf->get_page_count();
            $pdf->page_text(270, 820, $text, null, 10, array(0, 0, 0));
        }
    </script>
</body>
</html>
