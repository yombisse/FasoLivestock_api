# Schéma Base de Données Backend

## Tables Principales (Business)

### animals
- id (string, 20) PRIMARY KEY
- farm_id (string, 20) FOREIGN KEY → farms.id cascadeOnDelete
- nom (string nullable)
- race (string nullable)
- sexe (enum: 'male', 'femelle' nullable)
- date_naissance (date nullable)
- poids (decimal, 10,2 nullable)
- statut (enum: 'SAIN', 'MALADE', 'EN_TRAITEMENT', 'VENDU', 'MORT', 'PERDU') DEFAULT 'SAIN'
- origine (enum: 'enregistrement', 'achat', 'naissance') DEFAULT 'enregistrement'
- numero_identification (string nullable) UNIQUE avec farm_id
- photo (string nullable)
- espece_id (string, 20) FOREIGN KEY → especes.id
- lot_id (string, 20 nullable) FOREIGN KEY → lots.id nullOnDelete
- mother_id (string, 20 nullable) FOREIGN KEY → animals.id
- naissance_id (string, 20 nullable) FOREIGN KEY → naissances.id nullOnDelete
- farm_source_id (string, 20 nullable) FOREIGN KEY → farms.id nullOnDelete
- sync_status (enum: 'pending', 'synced', 'conflict') DEFAULT 'synced'
- last_modified_by (string, 20 nullable) FOREIGN KEY → users.id nullOnDelete
- version (integer) DEFAULT 1
- created_at (timestamp)
- updated_at (timestamp)
- deleted_at (timestamp nullable) SOFT DELETE

### evenements
- id (string, 20) PRIMARY KEY
- farm_id (string, 20) FOREIGN KEY → farms.id cascadeOnDelete
- type_evenement_id (string, 20) FOREIGN KEY → type_evenements.id cascadeOnDelete
- categorie (enum: 'MOUVEMENT', 'REPRODUCTION', 'SANITAIRE', 'AUTRE' nullable)
- animal_id (string, 20) FOREIGN KEY → animals.id cascadeOnDelete
- date_evenement (date)
- description (string nullable)
- metadonnees (json nullable)
- cout (decimal, 10,2 nullable)
- farm_destination_id (string, 20 nullable) FOREIGN KEY → farms.id nullOnDelete
- statut_avant (enum: 'SAIN', 'VENDU', 'MORT', 'PERDU' nullable)
- statut_apres (enum: 'SAIN', 'VENDU', 'MORT', 'PERDU' nullable)
- transaction_id (string, 20 nullable) FOREIGN KEY → transactions.id nullOnDelete
- sync_status (enum: 'pending', 'synced', 'conflict') DEFAULT 'synced'
- last_modified_by (string, 20 nullable) FOREIGN KEY → users.id nullOnDelete
- version (integer) DEFAULT 1
- created_at (timestamp)
- updated_at (timestamp)
- deleted_at (timestamp nullable) SOFT DELETE

### transactions
- id (string, 20) PRIMARY KEY
- numero_transaction (string nullable) UNIQUE
- farm_id (string, 20) FOREIGN KEY → farms.id cascadeOnDelete
- type_transaction (enum: 'ENTREE', 'SORTIE', 'TRANSFERT', 'AJUSTEMENT') DEFAULT 'ENTREE'
- montant (decimal, 12,2 unsigned)
- date_transaction (date)
- user_id (string, 20) FOREIGN KEY → users.id cascadeOnDelete
- animal_id (string, 20 nullable) FOREIGN KEY → animals.id nullOnDelete
- categorie_id (string, 20) FOREIGN KEY → categories.id cascadeOnDelete
- description (string nullable)
- tiers (string nullable)
- evenement_id (string, 20 nullable) FOREIGN KEY → evenements.id nullOnDelete
- sync_status (enum: 'pending', 'synced', 'conflict') DEFAULT 'synced'
- last_modified_by (string, 20 nullable) FOREIGN KEY → users.id nullOnDelete
- version (integer) DEFAULT 1
- created_at (timestamp)
- updated_at (timestamp)
- deleted_at (timestamp nullable) SOFT DELETE

### naissances
- id (string, 20) PRIMARY KEY
- farm_id (string, 20) FOREIGN KEY → farms.id cascadeOnDelete
- mother_id (string, 20) FOREIGN KEY → animals.id cascadeOnDelete
- date_naissance (date)
- nombre_petits (integer unsigned) DEFAULT 0
- poids_naissance (decimal, 10,2 nullable)
- observation (text nullable)
- evenement_id (string, 20 nullable) FOREIGN KEY → evenements.id nullOnDelete
- sync_status (enum: 'pending', 'synced', 'conflict') DEFAULT 'synced'
- last_modified_by (string, 20 nullable) FOREIGN KEY → users.id nullOnDelete
- version (integer) DEFAULT 1
- created_at (timestamp)
- updated_at (timestamp)
- deleted_at (timestamp nullable) SOFT DELETE

### lots
- id (string, 20) PRIMARY KEY
- farm_id (string, 20) FOREIGN KEY → farms.id cascadeOnDelete
- nom_lot (string)
- nombre (integer unsigned) DEFAULT 0
- sync_status (enum: 'pending', 'synced', 'conflict') DEFAULT 'synced'
- last_modified_by (string, 20 nullable) FOREIGN KEY → users.id nullOnDelete
- version (integer) DEFAULT 1
- created_at (timestamp)
- updated_at (timestamp)
- deleted_at (timestamp nullable) SOFT DELETE

### notifications
- id (string, 20) PRIMARY KEY
- farm_id (string, 20 nullable) FOREIGN KEY → farms.id nullOnDelete
- animal_id (string, 20 nullable) FOREIGN KEY → animals.id nullOnDelete
- titre (string nullable)
- message (text)
- sent_at (timestamp nullable)
- evenement_id (string, 20 nullable) FOREIGN KEY → evenements.id nullOnDelete
- type (enum: 'VACCINATION', 'TRAITEMENT', 'NAISSANCE', 'MOUVEMENT', 'ALERTE', 'INFO') DEFAULT 'INFO'
- sync_status (enum: 'pending', 'synced', 'conflict') DEFAULT 'synced'
- last_modified_by (string, 20 nullable) FOREIGN KEY → users.id nullOnDelete
- version (integer) DEFAULT 1
- created_at (timestamp)
- updated_at (timestamp)
- deleted_at (timestamp nullable) SOFT DELETE

## Tables de Référence (Reference)

### farms
- id (string, 20) PRIMARY KEY
- name (string)
- location (string nullable)
- description (text nullable)
- type_elevage (enum: 'bovin', 'ovin', 'caprin', 'porcin', 'volaille', 'cunicole', 'autre' nullable)
- owner_id (string, 20) FOREIGN KEY → users.id cascadeOnDelete
- sync_status (enum: 'pending', 'synced', 'conflict') DEFAULT 'synced'
- last_modified_by (string, 20 nullable) FOREIGN KEY → users.id nullOnDelete
- version (integer) DEFAULT 1
- created_at (timestamp)
- updated_at (timestamp)
- deleted_at (timestamp nullable) SOFT DELETE

### especes
- id (string, 20) PRIMARY KEY
- nom (string) UNIQUE
- description (string nullable)
- sync_status (enum: 'pending', 'synced', 'conflict') DEFAULT 'synced'
- last_modified_by (string, 20 nullable) FOREIGN KEY → users.id nullOnDelete
- version (integer) DEFAULT 1
- created_at (timestamp)
- updated_at (timestamp)
- deleted_at (timestamp nullable) SOFT DELETE

### categories
- id (string, 20) PRIMARY KEY
- nom_categorie (string) UNIQUE
- type (string nullable)
- description (text nullable)
- is_system (boolean) DEFAULT false
- farm_id (string, 20 nullable) FOREIGN KEY → farms.id nullOnDelete
- sync_status (enum: 'pending', 'synced', 'conflict') DEFAULT 'synced'
- last_modified_by (string, 20 nullable) FOREIGN KEY → users.id nullOnDelete
- version (integer) DEFAULT 1
- created_at (timestamp)
- updated_at (timestamp)
- deleted_at (timestamp nullable) SOFT DELETE

### type_evenements
- id (string, 20) PRIMARY KEY
- nom_type (string) UNIQUE
- description (string nullable)
- categorie (enum: 'MOUVEMENT', 'REPRODUCTION', 'SANITAIRE') DEFAULT 'SANITAIRE'
- is_system (boolean) DEFAULT false
- farm_id (string, 20 nullable) FOREIGN KEY → farms.id nullOnDelete
- sync_status (enum: 'pending', 'synced', 'conflict') DEFAULT 'synced'
- last_modified_by (string, 20 nullable) FOREIGN KEY → users.id nullOnDelete
- version (integer) DEFAULT 1
- created_at (timestamp)
- updated_at (timestamp)
- deleted_at (timestamp nullable) SOFT DELETE

## Tables d'Association (Association)

### farm_user
- id (string, 20) PRIMARY KEY
- farm_id (string, 20) FOREIGN KEY → farms.id cascadeOnDelete
- user_id (string, 20) FOREIGN KEY → users.id cascadeOnDelete
- role (enum: 'owner', 'manager', 'vet', 'worker') DEFAULT 'worker'
- sync_status (enum: 'pending', 'synced', 'conflict') DEFAULT 'synced'
- last_modified_by (string, 20 nullable) FOREIGN KEY → users.id nullOnDelete
- version (integer) DEFAULT 1
- created_at (timestamp)
- updated_at (timestamp)
