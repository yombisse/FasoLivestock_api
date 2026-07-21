# Guide d'Utilisation - Endpoint Sync Initial

## Contexte

L'endpoint `/sync/initial` est utilisé pour la synchronisation initiale de l'application mobile. Il résout le problème "chicken-and-egg" : le frontend a besoin d'obtenir la liste des fermes accessibles avant de pouvoir synchroniser les données spécifiques à une ferme.

**Quand utiliser cet endpoint** :
- **Premier lancement** de l'application mobile (aucune donnée locale)
- **Réinitialisation** de l'application mobile
- **Erreur 403/404** lors du sync pull (ID de ferme invalide ou désynchronisé)
- **Changement de compte utilisateur** (connexion avec un autre utilisateur)

---

## Endpoint

**URL** : `POST /api/sync/initial`

**Middleware** : `auth:sanctum` (authentification requise, PAS de `farm.context`)

**Permission** : Aucune permission spécifique requise (accessible à tous les utilisateurs authentifiés)

---

## Requête

### Headers

```http
Authorization: Bearer {sanctum_token}
Content-Type: application/json
```

### Body

**Aucun paramètre requis**. L'endpoint utilise l'utilisateur authentifié pour déterminer les fermes accessibles.

```json
{}
```

---

## Réponse

### Succès (200 OK)

```json
{
  "success": true,
  "message": "Initial sync réussi",
  "data": {
    "farms": [
      {
        "id": "8a37xRyxA5J73VFABbaw",
        "name": "Ferme Espoir",
        "owner_id": "PbuNRCuMUq4WLIEn",
        "created_at": "2026-01-15 10:00:00",
        "updated_at": "2026-07-15 10:00:00",
        "deleted_at": null,
        "owner": {
          "id": "PbuNRCuMUq4WLIEn",
          "name": "Jean Dupont",
          "email": "jean@example.com"
        },
        "users": [
          {
            "id": "user_id_2",
            "name": "Marie Martin",
            "email": "marie@example.com",
            "pivot": {
              "farm_id": "8a37xRyxA5J73VFABbaw",
              "user_id": "user_id_2",
              "role": "member"
            }
          }
        ]
      },
      {
        "id": "pMlXD77IgEIpuUXldQed",
        "name": "Ferme Test Reproduction",
        "owner_id": "PbuNRCuMUq4WLIEn",
        "created_at": "2026-02-01 10:00:00",
        "updated_at": "2026-07-15 10:00:00",
        "deleted_at": null,
        "owner": {
          "id": "PbuNRCuMUq4WLIEn",
          "name": "Jean Dupont",
          "email": "jean@example.com"
        },
        "users": []
      }
    ],
    "farm_user": [
      {
        "farm_id": "8a37xRyxA5J73VFABbaw",
        "user_id": "PbuNRCuMUq4WLIEn",
        "role": "owner",
        "created_at": "2026-01-15 10:00:00",
        "updated_at": "2026-01-15 10:00:00"
      },
      {
        "farm_id": "8a37xRyxA5J73VFABbaw",
        "user_id": "user_id_2",
        "role": "member",
        "created_at": "2026-01-20 10:00:00",
        "updated_at": "2026-01-20 10:00:00"
      },
      {
        "farm_id": "pMlXD77IgEIpuUXldQed",
        "user_id": "PbuNRCuMUq4WLIEn",
        "role": "owner",
        "created_at": "2026-02-01 10:00:00",
        "updated_at": "2026-02-01 10:00:00"
      }
    ],
    "especes": [
      {
        "id": "espece_id_1",
        "nom": "Bovin",
        "description": "Espèce bovine",
        "age_reproduction_mois": 24,
        "duree_gestation_jours": 285,
        "intervalle_vaccin_jours": 365,
        "created_at": "2026-01-01 10:00:00",
        "updated_at": "2026-01-01 10:00:00"
      },
      {
        "id": "espece_id_2",
        "nom": "Ovin",
        "description": "Espèce ovine",
        "age_reproduction_mois": 18,
        "duree_gestation_jours": 150,
        "intervalle_vaccin_jours": 365,
        "created_at": "2026-01-01 10:00:00",
        "updated_at": "2026-01-01 10:00:00"
      }
    ],
    "categories": [
      {
        "id": "cat_id_1",
        "nom_categorie": "Vente d'animaux",
        "type": "REVENU",
        "description": "Revenus générés par la vente d'animaux",
        "created_at": "2026-01-01 10:00:00",
        "updated_at": "2026-01-01 10:00:00"
      },
      {
        "id": "cat_id_2",
        "nom_categorie": "Achat d'animaux",
        "type": "DEPENSE",
        "description": "Dépenses pour l'achat d'animaux",
        "created_at": "2026-01-01 10:00:00",
        "updated_at": "2026-01-01 10:00:00"
      }
    ],
    "type_evenements": [
      {
        "id": "type_id_1",
        "nom_type": "Vaccination",
        "categorie": "SANITAIRE",
        "is_system": true,
        "created_at": "2026-01-01 10:00:00",
        "updated_at": "2026-01-01 10:00:00"
      },
      {
        "id": "type_id_2",
        "nom_type": "Saillie",
        "categorie": "REPRODUCTION",
        "is_system": true,
        "created_at": "2026-01-01 10:00:00",
        "updated_at": "2026-01-01 10:00:00"
      }
    ],
    "synced_at": "2026-07-15T10:00:00.000000Z"
  }
}
```

### Erreur (400 Bad Request)

```json
{
  "success": false,
  "message": "Erreur lors de l'initial sync",
  "error": "Message d'erreur détaillé"
}
```

---

## Données Retournées

### 1. `farms` (Array)

Liste des fermes accessibles à l'utilisateur :
- Fermes dont l'utilisateur est le propriétaire (`owner_id = user_id`)
- Fermes dont l'utilisateur est membre via la table `farm_user`

**Champs inclus** :
- `id` : UUID de la ferme
- `name` : Nom de la ferme
- `owner_id` : ID du propriétaire
- `created_at`, `updated_at`, `deleted_at` : Timestamps
- `owner` : Détails du propriétaire (relation)
- `users` : Liste des membres avec leurs rôles (relation)

### 2. `farm_user` (Array)

Table pivot contenant les memberships de l'utilisateur :
- `farm_id` : ID de la ferme
- `user_id` : ID de l'utilisateur
- `role` : `owner` ou `member`
- `created_at`, `updated_at` : Timestamps

### 3. `especes` (Array)

Table de référence des espèces (globale, non scopée par ferme) :
- `id` : UUID de l'espèce
- `nom` : Nom de l'espèce
- `description` : Description
- `age_reproduction_mois` : Âge minimum de reproduction (mois)
- `duree_gestation_jours` : Durée de gestation (jours)
- `intervalle_vaccin_jours` : Intervalle de vaccination (jours)
- `created_at`, `updated_at` : Timestamps

### 4. `categories` (Array)

Table de référence des catégories de transactions (globale) :
- `id` : UUID de la catégorie
- `nom_categorie` : Nom de la catégorie
- `type` : `REVENU` ou `DEPENSE`
- `description` : Description
- `created_at`, `updated_at` : Timestamps

### 5. `type_evenements` (Array)

Table de référence des types d'événements (globale) :
- `id` : UUID du type d'événement
- `nom_type` : Nom du type d'événement
- `categorie` : `SANITAIRE`, `REPRODUCTION`, `MOUVEMENT`, etc.
- `is_system` : `true` si type système (non modifiable)
- `created_at`, `updated_at` : Timestamps

### 6. `synced_at` (String)

Timestamp de la synchronisation (ISO 8601).

---

## Stratégie Frontend

### 1. Premier Lancement

```typescript
async function initialSetup() {
  try {
    // 1. Appeler /sync/initial
    const response = await api.post('/sync/initial');
    const { farms, farm_user, especes, categories, type_evenements } = response.data.data;

    // 2. Stocker les données dans WatermelonDB
    await database.write(async () => {
      // Nettoyer les anciennes données (si réinitialisation)
      await database.get('farms').query().destroyAllPermanently();
      await database.get('farm_user').query().destroyAllPermanently();
      await database.get('especes').query().destroyAllPermanently();
      await database.get('categories').query().destroyAllPermanently();
      await database.get('type_evenements').query().destroyAllPermanently();

      // Insérer les fermes
      for (const farm of farms) {
        await database.get('farms').create(f => {
          f._raw = sanitizeRaw(farm);
        });
      }

      // Insérer les farm_user
      for (const membership of farm_user) {
        await database.get('farm_user').create(f => {
          f._raw = sanitizeRaw(membership);
        });
      }

      // Insérer les espèces
      for (const espece of especes) {
        await database.get('especes').create(f => {
          f._raw = sanitizeRaw(espece);
        });
      }

      // Insérer les catégories
      for (const categorie of categories) {
        await database.get('categories').create(f => {
          f._raw = sanitizeRaw(categorie);
        });
      }

      // Insérer les types d'événements
      for (const typeEvenement of type_evenements) {
        await database.get('type_evenements').create(f => {
          f._raw = sanitizeRaw(typeEvenement);
        });
      }
    });

    // 3. Sélectionner la ferme active
    if (farms.length === 0) {
      throw new Error('Aucune ferme accessible. Veuillez contacter l\'administrateur.');
    }

    const activeFarm = farms[0]; // Ou afficher un écran de sélection
    await AsyncStorage.setItem('activeFarmId', activeFarm.id);
    await AsyncStorage.setItem('activeFarmName', activeFarm.name);

    // 4. Déclencher le sync pull pour cette ferme
    await syncPull(activeFarm.id);

    return { success: true, activeFarm };
  } catch (error) {
    console.error('Initial setup failed:', error);
    throw error;
  }
}
```

### 2. Récupération après Erreur 403/404

```typescript
async function syncPullWithFallback(farmId: string) {
  try {
    // Tenter le sync pull normal
    const response = await api.post('/sync/pull', {
      farm_id: farmId,
      last_pulled_at: await AsyncStorage.getItem('lastPulledAt'),
    });

    return response.data;
  } catch (error) {
    // Si erreur 403 (accès refusé) ou 404 (ferme introuvable)
    if (error.response?.status === 403 || error.response?.status === 404) {
      console.log('Farm ID invalid, triggering initial sync...');
      
      // Afficher un message à l'utilisateur
      Alert.alert(
        'Synchronisation requise',
        'Vos données locales sont désynchronisées. Une synchronisation initiale va être effectuée.',
        [
          { text: 'Annuler', style: 'cancel' },
          { text: 'OK', onPress: async () => {
            try {
              await initialSetup();
            } catch (setupError) {
              Alert.alert('Erreur', 'La synchronisation a échoué. Veuillez réessayer.');
            }
          }}
        ]
      );
      
      throw new Error('Initial sync required');
    } else {
      throw error;
    }
  }
}
```

### 3. Écran de Sélection de Ferme

```typescript
function FarmSelectionScreen() {
  const [farms, setFarms] = useState<Farm[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadFarms();
  }, []);

  async function loadFarms() {
    try {
      const localFarms = await database.get('farms').query().fetch();
      
      if (localFarms.length === 0) {
        // Aucune ferme locale, appeler /sync/initial
        const response = await api.post('/sync/initial');
        const { farms: serverFarms } = response.data.data;
        
        await database.write(async () => {
          for (const farm of serverFarms) {
            await database.get('farms').create(f => {
              f._raw = sanitizeRaw(farm);
            });
          }
        });
        
        setFarms(serverFarms);
      } else {
        setFarms(localFarms.map(f => f._raw));
      }
    } catch (error) {
      console.error('Error loading farms:', error);
    } finally {
      setLoading(false);
    }
  }

  async function selectFarm(farm: Farm) {
    await AsyncStorage.setItem('activeFarmId', farm.id);
    await AsyncStorage.setItem('activeFarmName', farm.name);
    
    // Naviguer vers l'écran principal
    navigation.navigate('MainTabs');
  }

  if (loading) {
    return <LoadingSpinner />;
  }

  return (
    <View>
      <Text>Sélectionnez une ferme</Text>
      {farms.map(farm => (
        <TouchableOpacity key={farm.id} onPress={() => selectFarm(farm)}>
          <Text>{farm.name}</Text>
          <Text>Propriétaire: {farm.owner?.name}</Text>
        </TouchableOpacity>
      ))}
    </View>
  );
}
```

### 4. Vérification de Validité de Farm ID

```typescript
async function validateFarmId(farmId: string): Promise<boolean> {
  try {
    // Vérifier si la ferme existe localement
    const localFarm = await database.get('farms').find(farmId);
    return !!localFarm;
  } catch (error) {
    // Si pas trouvée localement, vérifier via API
    try {
      const response = await api.post('/sync/initial');
      const { farms } = response.data.data;
      const farmExists = farms.some(f => f.id === farmId);
      
      if (!farmExists) {
        // Mettre à jour les fermes locales
        await database.write(async () => {
          await database.get('farms').query().destroyAllPermanently();
          for (const farm of farms) {
            await database.get('farms').create(f => {
              f._raw = sanitizeRaw(farm);
            });
          }
        });
      }
      
      return farmExists;
    } catch (apiError) {
      console.error('Error validating farm ID:', apiError);
      return false;
    }
  }
}
```

---

## Checklist d'Intégration Frontend

### Initial Setup
- [ ] Appeler `/sync/initial` lors du premier lancement
- [ ] Stocker les fermes dans WatermelonDB
- [ ] Stocker les tables de référence (especes, categories, type_evenements)
- [ ] Stocker les memberships farm_user
- [ ] Sélectionner une ferme active
- [ ] Déclencher le sync pull après l'initial sync

### Gestion des Erreurs
- [ ] Détecter les erreurs 403/404 lors du sync pull
- [ ] Déclencher automatiquement l'initial sync en cas d'erreur
- [ ] Informer l'utilisateur de la nécessité de resynchroniser
- [ ] Permettre à l'utilisateur de réinitialiser ses données locales

### Sélection de Ferme
- [ ] Implémenter un écran de sélection de ferme
- [ ] Afficher la liste des fermes accessibles
- [ ] Permettre le changement de ferme active
- [ ] Mettre à jour le storage lors du changement de ferme

### Validation
- [ ] Valider l'ID de ferme avant chaque sync pull
- [ ] Rafraîchir la liste des fermes périodiquement
- [ ] Gérer le cas où l'utilisateur n'a accès à aucune ferme

---

## Notes Importantes

1. **Pas de middleware farm.context** : Cet endpoint ne nécessite pas de `farm_id` dans la requête, ce qui permet de résoudre le problème "chicken-and-egg".

2. **Tables de référence globales** : Les espèces, catégories et types d'événements sont envoyés en entier (pas de filtrage par ferme) car ce sont des données globales partagées.

3. **Fermes accessibles** : Seules les fermes dont l'utilisateur est propriétaire ou membre sont retournées.

4. **Timestamp synced_at** : Utilisez ce timestamp pour le prochain sync pull.

5. **Réinitialisation** : En cas de réinitialisation de l'application, appelez `/sync/initial` pour repeupler les données de base.

6. **Changement d'utilisateur** : Lors de la connexion avec un autre utilisateur, appelez `/sync/initial` pour récupérer les fermes accessibles à ce nouvel utilisateur.

7. **Désynchronisation** : Si l'ID de ferme local ne correspond à aucune ferme sur le serveur, l'erreur 403/404 indique qu'il faut appeler `/sync/initial` pour resynchroniser.
