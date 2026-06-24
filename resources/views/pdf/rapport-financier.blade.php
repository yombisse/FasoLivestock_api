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
            background-color: #27ae60;
            color: white;
            border: 1px solid #229954;
            padding: 6px 8px;
            text-align: left;
            font-weight: bold;
        }
        
        .data-table th.depenses {
            background-color: #e74c3c;
            border-color: #c0392b;
        }
        
        .data-table td {
            border: 1px solid #bdc3c7;
            padding: 6px 8px;
        }
        
        .data-table .number {
            text-align: right;
        }
        
        .data-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .montant-pos {
            color: #27ae60;
            font-weight: bold;
        }
        
        .montant-neg {
            color: #e74c3c;
            font-weight: bold;
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
        
        .benefice-positif {
            color: #27ae60;
            font-weight: bold;
        }
        
        .benefice-negatif {
            color: #e74c3c;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Rapport Financier</h1>
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

    {{-- Résumé financier --}}
    <div class="section-title">Résumé Financier</div>
    
    <table class="summary-table">
        <tr>
            <th style="width: 70%;">Indicateur</th>
            <th style="width: 30%;" class="number">Montant (FCFA)</th>
        </tr>
        <tr>
            <td>Total recettes</td>
            <td class="number montant-pos">{{ number_format($totalRecettes, 0, '', ' ') }}</td>
        </tr>
        <tr>
            <td>Total dépenses</td>
            <td class="number montant-neg">{{ number_format($totalDepenses, 0, '', ' ') }}</td>
        </tr>
        <tr class="total-row">
            <td>Bénéfice net</td>
            <td class="number {{ $beneficeNet >= 0 ? 'benefice-positif' : 'benefice-negatif' }}">{{ number_format($beneficeNet, 0, '', ' ') }}</td>
        </tr>
        <tr>
            <td>Marge bénéficiaire</td>
            <td class="number">{{ $margeBeneficiaire }}%</td>
        </tr>
    </table>

    {{-- Recettes par catégorie --}}
    <div class="section-title">Recettes par Catégorie</div>
    
    @if(count($recettesParCategorie) > 0)
    <table class="data-table">
        <thead>
            <tr>
                <th>Catégorie</th>
                <th class="number">Montant (FCFA)</th>
                <th class="number">%</th>
                <th class="number">Nombre</th>
            </tr>
        </thead>
        <tbody>
            @foreach($recettesParCategorie as $categorie)
            <tr>
                <td>{{ $categorie['categorie_nom'] }}</td>
                <td class="number montant-pos">{{ number_format($categorie['total'], 0, '', ' ') }}</td>
                <td class="number">{{ $categorie['percentage'] }}%</td>
                <td class="number">{{ $categorie['nombre'] }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td><strong>Total</strong></td>
                <td class="number"><strong>{{ number_format($totalRecettes, 0, '', ' ') }}</strong></td>
                <td class="number"><strong>100%</strong></td>
                <td class="number"><strong>{{ $recettesParCategorie->sum('nombre') }}</strong></td>
            </tr>
        </tbody>
    </table>
    @else
    <p>Aucune recette enregistrée sur cette période.</p>
    @endif

    {{-- Dépenses par catégorie --}}
    <div class="section-title">Dépenses par Catégorie</div>
    
    @if(count($depensesParCategorie) > 0)
    <table class="data-table">
        <thead>
            <tr>
                <th class="depenses">Catégorie</th>
                <th class="depenses number">Montant (FCFA)</th>
                <th class="depenses number">%</th>
                <th class="depenses number">Nombre</th>
            </tr>
        </thead>
        <tbody>
            @foreach($depensesParCategorie as $categorie)
            <tr>
                <td>{{ $categorie['categorie_nom'] }}</td>
                <td class="number montant-neg">{{ number_format($categorie['total'], 0, '', ' ') }}</td>
                <td class="number">{{ $categorie['percentage'] }}%</td>
                <td class="number">{{ $categorie['nombre'] }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td><strong>Total</strong></td>
                <td class="number"><strong>{{ number_format($totalDepenses, 0, '', ' ') }}</strong></td>
                <td class="number"><strong>100%</strong></td>
                <td class="number"><strong>{{ $depensesParCategorie->sum('nombre') }}</strong></td>
            </tr>
        </tbody>
    </table>
    @else
    <p>Aucune dépense enregistrée sur cette période.</p>
    @endif

    {{-- Évolution mensuelle --}}
    @if(count($evolutionMensuelle) > 1)
    <div class="section-title">Évolution Mensuelle</div>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>Mois</th>
                <th class="number">Recettes (FCFA)</th>
                <th class="number">Dépenses (FCFA)</th>
                <th class="number">Bénéfice (FCFA)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($evolutionMensuelle as $mois)
            <tr>
                <td>{{ $mois['mois'] }}</td>
                <td class="number montant-pos">{{ number_format($mois['recettes'], 0, '', ' ') }}</td>
                <td class="number montant-neg">{{ number_format($mois['depenses'], 0, '', ' ') }}</td>
                <td class="number {{ $mois['benefice'] >= 0 ? 'benefice-positif' : 'benefice-negatif' }}">{{ number_format($mois['benefice'], 0, '', ' ') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <script type="text/php">
        if (isset($pdf)) {
            $text = "Page " . $pdf->get_page_number() . " sur " . $pdf->get_page_count();
            $pdf->page_text(270, 820, $text, null, 10, array(0, 0, 0));
        }
    </script>
</body>
</html>
