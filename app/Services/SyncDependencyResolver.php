<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Service pour résoudre les dépendances de synchronisation
 * Construit un graphe de dépendances et trie les items en ordre topologique
 */
class SyncDependencyResolver
{
    /**
     * Définition des dépendances entre tables
     * Format: 'table_dependante' => ['table_parent1', 'table_parent2']
     */
    private const DEPENDENCY_GRAPH = [
        'evenements' => ['animals', 'type_evenements', 'farms'],
        'transactions' => ['animals', 'evenements', 'categories', 'farms'],
        'naissances' => ['animals', 'farms'],
        'lots' => ['farms'],
        'notifications' => ['animals', 'farms'],
        'animals' => ['especes', 'categories', 'lots', 'farms'],
        'categories' => ['especes'],
        'farm_user' => ['farms', 'users'],
    ];

    /**
     * Colonnes de foreign keys par table
     * Format: 'table' => ['fk_column1', 'fk_column2']
     */
    private const FOREIGN_KEY_COLUMNS = [
        'animals' => ['farm_id', 'espece_id', 'categorie_id', 'lot_id', 'mother_id'],
        'evenements' => ['farm_id', 'animal_id', 'type_evenement_id'],
        'transactions' => ['farm_id', 'animal_id', 'evenement_id', 'categorie_id'],
        'naissances' => ['farm_id', 'animal_id'],
        'lots' => ['farm_id'],
        'notifications' => ['farm_id', 'animal_id'],
        'categories' => ['espece_id'],
        'farm_user' => ['farm_id', 'user_id'],
    ];

    /**
     * Résout l'ordre de traitement pour un chunk de changements
     *
     * @param array $chunk Changements groupés par table avec created/updated/deleted
     * @return array Ordre de traitement trié topologiquement
     */
    public function resolveProcessingOrder(array $chunk): array
    {
        Log::debug('[SyncDependencyResolver] Starting dependency resolution', [
            'tables' => array_keys($chunk),
        ]);

        // Extraire tous les items du chunk avec leurs dépendances
        $allItems = $this->extractAllItems($chunk);
        
        if (empty($allItems)) {
            Log::debug('[SyncDependencyResolver] No items to process');
            return [];
        }

        // Construire le graphe de dépendances au niveau item
        $itemGraph = $this->buildItemDependencyGraph($allItems);
        
        // Effectuer le tri topologique
        $sortedItems = $this->topologicalSort($itemGraph);
        
        Log::debug('[SyncDependencyResolver] Dependency resolution completed', [
            'total_items' => count($sortedItems),
            'processing_order' => array_map(fn($item) => $item['table'], $sortedItems),
        ]);

        return $sortedItems;
    }

    /**
     * Extrait tous les items du chunk avec leurs métadonnées
     *
     * @param array $chunk
     * @return array
     */
    private function extractAllItems(array $chunk): array
    {
        $items = [];

        foreach ($chunk as $tableName => $tableData) {
            foreach (['created', 'updated', 'deleted'] as $action) {
                if (isset($tableData[$action]) && is_array($tableData[$action])) {
                    foreach ($tableData[$action] as $record) {
                        $items[] = [
                            'table' => $tableName,
                            'action' => $action,
                            'data' => $record,
                            'id' => $record['id'] ?? null,
                        ];
                    }
                }
            }
        }

        return $items;
    }

    /**
     * Construit le graphe de dépendances au niveau item
     *
     * @param array $items
     * @return array
     */
    private function buildItemDependencyGraph(array $items): array
    {
        $graph = [];
        $itemMap = [];

        // Créer une map ID -> item pour lookup rapide
        foreach ($items as $index => $item) {
            if ($item['id']) {
                $itemMap[$item['table'] . ':' . $item['id']] = $index;
            }
        }

        // Construire les arêtes du graphe
        foreach ($items as $index => $item) {
            $dependencies = $this->getItemDependencies($item, $itemMap);
            $graph[$index] = [
                'item' => $item,
                'dependencies' => $dependencies,
            ];
        }

        return $graph;
    }

    /**
     * Identifie les dépendances d'un item spécifique
     *
     * @param array $item
     * @param array $itemMap
     * @return array
     */
    private function getItemDependencies(array $item, array $itemMap): array
    {
        $dependencies = [];
        $tableName = $item['table'];
        $data = $item['data'];

        // Récupérer les colonnes FK pour cette table
        $fkColumns = self::FOREIGN_KEY_COLUMNS[$tableName] ?? [];

        foreach ($fkColumns as $fkColumn) {
            if (isset($data[$fkColumn]) && $data[$fkColumn]) {
                // Déterminer la table parente basée sur la colonne FK
                $parentTable = $this->getParentTableFromFkColumn($fkColumn);
                
                if ($parentTable) {
                    $parentKey = $parentTable . ':' . $data[$fkColumn];
                    if (isset($itemMap[$parentKey])) {
                        $dependencies[] = $itemMap[$parentKey];
                    }
                }
            }
        }

        return $dependencies;
    }

    /**
     * Détermine la table parente à partir d'une colonne FK
     *
     * @param string $fkColumn
     * @return string|null
     */
    private function getParentTableFromFkColumn(string $fkColumn): ?string
    {
        $columnToTable = [
            'animal_id' => 'animals',
            'evenement_id' => 'evenements',
            'farm_id' => 'farms',
            'mother_id' => 'animals',
            'espece_id' => 'especes',
            'categorie_id' => 'categories',
            'lot_id' => 'lots',
            'type_evenement_id' => 'type_evenements',
            'user_id' => 'users',
        ];

        return $columnToTable[$fkColumn] ?? null;
    }

    /**
     * Effectue un tri topologique sur le graphe de dépendances
     * Utilise l'algorithme de Kahn pour détecter les cycles
     *
     * @param array $graph
     * @return array
     */
    private function topologicalSort(array $graph): array
    {
        $sorted = [];
        $inDegree = array_fill(0, count($graph), 0);
        $queue = [];

        // Calculer les degrés d'entrée
        foreach ($graph as $index => $node) {
            foreach ($node['dependencies'] as $depIndex) {
                $inDegree[$depIndex]++;
            }
        }

        // Initialiser la queue avec les nœuds sans dépendances
        foreach ($inDegree as $index => $degree) {
            if ($degree === 0) {
                $queue[] = $index;
            }
        }

        // Traitement des nœuds
        while (!empty($queue)) {
            $currentIndex = array_shift($queue);
            $sorted[] = $graph[$currentIndex]['item'];

            // Réduire les degrés d'entrée des dépendants
            foreach ($graph as $index => $node) {
                if (in_array($currentIndex, $node['dependencies'])) {
                    $inDegree[$index]--;
                    if ($inDegree[$index] === 0) {
                        $queue[] = $index;
                    }
                }
            }
        }

        // Vérifier s'il y a un cycle (tous les nœuds ne sont pas triés)
        if (count($sorted) !== count($graph)) {
            Log::warning('[SyncDependencyResolver] Cycle detected in dependencies, using fallback order', [
                'total_items' => count($graph),
                'sorted_items' => count($sorted),
            ]);
            
            // Fallback: retourner les items dans l'ordre original
            return array_map(fn($node) => $node['item'], $graph);
        }

        return $sorted;
    }

    /**
     * Trie les tables par ordre de priorité de dépendance (fallback)
     *
     * @param array $tables
     * @return array
     */
    public function sortTablesByPriority(array $tables): array
    {
        $priority = [
            'farms' => 0,
            'users' => 1,
            'especes' => 2,
            'type_evenements' => 3,
            'categories' => 4,
            'lots' => 5,
            'animals' => 6,
            'evenements' => 7,
            'naissances' => 8,
            'transactions' => 9,
            'notifications' => 10,
            'farm_user' => 11,
        ];

        usort($tables, function ($a, $b) use ($priority) {
            $priorityA = $priority[$a] ?? 999;
            $priorityB = $priority[$b] ?? 999;
            return $priorityA - $priorityB;
        });

        return $tables;
    }
}
