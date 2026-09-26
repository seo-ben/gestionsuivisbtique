# 🚀 GESP Backend — API REST Laravel 11

Backend complet pour la plateforme **GESP** (Gestion multi-boutiques avec synchronisation hors-ligne pour vendeurs).

---

## 🛠️ Stack Technique
- **Framework** : Laravel 11 (PHP 8.4)
- **Authentification** : Laravel Sanctum (Tokens API par device)
- **Base de données** : SQLite (local) / MySQL 8+ (production)
- **Architecture** : RESTful API + Services métier (`SyncService`, `ClotureCaisseService`)
- **Tâches planifiées** : Laravel Scheduler (Clôture automatique à 23h59, mise à jour des échéances à 06h00)

---

## ⚡ Démarrage rapide en local

### 1. Démarrer le serveur de développement
```bash
cd gesp-backend
php artisan serve
```
L'API est accessible sur `http://127.0.0.1:8000/api`.

### 2. Comptes de test (déjà seedés)
| Rôle | Téléphone | Mot de passe | Accès |
|---|---|---|---|
| **Admin** | `0600000000` | `password` | Gestion complète, Dashboard, Emprunts, Clôtures |
| **Vendeur** | `0611111111` | `password` | Ventes, Réappros, Clôtures, Mes autorisations |

---

## 🧪 Lancer la suite de tests
```bash
php artisan test
```
Tests automatisés couvrant :
- Authentification & tokens Sanctum
- CRUD Boutiques & Paramètres de caisse
- CRUD Produits & Gestion du stock
- Synchronisation offline des ventes (idempotence & réconciliation automatique du stock)
- Synchronisation des réapprovisionnements (vérification de validité de l'autorisation)
- Clôture journalière & enregistrement du solde réel physique avec détection d'écarts
- Gestion des emprunts & échéancier d'amortissement
- Dashboard de consolidation admin

---

## 📋 Répertoire des Endpoints API

### 🔐 Authentification
- `POST /api/auth/login` : Connexion avec `telephone` + `password` (retourne token Sanctum)
- `POST /api/auth/logout` : Révocation du token actif
- `GET /api/auth/me` : Profil de l'utilisateur connecté avec boutiques rattachées

### 🏬 Boutiques (Admin)
- `GET /api/boutiques` : Liste des boutiques de l'admin (ou boutiques assignées si vendeur)
- `POST /api/boutiques` : Création d'une nouvelle boutique
- `GET /api/boutiques/{id}` : Détails d'une boutique avec activités et paramètres
- `PUT /api/boutiques/{id}` : Modification d'une boutique
- `DELETE /api/boutiques/{id}` : Désactivation d'une boutique
- `PUT /api/boutiques/{id}/parametres` : Configuration (fond de caisse, seuils d'écart, heure clôture)
- `POST /api/boutiques/{id}/vendeurs` : Rattacher un vendeur à une boutique
- `DELETE /api/boutiques/{id}/vendeurs/{vendeurId}` : Détacher un vendeur

### 📦 Produits & Stocks
- `GET /api/boutiques/{boutiqueId}/produits` : Liste des produits actifs
- `POST /api/boutiques/{boutiqueId}/produits` : Créer un produit avec stock initial
- `GET /api/produits/{id}` : Détail d'un produit
- `PUT /api/produits/{id}` : Mettre à jour (nom, prix, seuil alerte)
- `DELETE /api/produits/{id}` : Désactiver un produit

### 💰 Ventes & Synchronisation Offline
- `GET /api/boutiques/{boutiqueId}/ventes?date=YYYY-MM-DD` : Liste des ventes du jour
- `POST /api/ventes/sync` : Synchronisation par lot de ventes hors-ligne (idempotence par UUID, réconciliation automatique de stock, journalisation dans `sync_logs`)

### 🔑 Autorisations de Réapprovisionnement
- `GET /api/boutiques/{boutiqueId}/autorisations` : Liste des autorisations (Admin)
- `POST /api/boutiques/{boutiqueId}/autorisations` : Délivrer une autorisation ponctuelle ou permanente
- `DELETE /api/autorisations/{id}/revoquer` : Révoquer une autorisation
- `GET /api/mes-autorisations` : Autorisations actives de la vendeuse connectée

### 🚚 Réapprovisionnements
- `GET /api/boutiques/{boutiqueId}/reapprovisionnements?date=YYYY-MM-DD` : Entrées du jour
- `POST /api/reapprovisionnements/sync` : Synchronisation par lot avec vérification de validité de l'autorisation serveur

### 🔒 Clôtures de Caisse
- `GET /api/boutiques/{boutiqueId}/clotures?mois=YYYY-MM` : Historique des clôtures
- `POST /api/boutiques/{boutiqueId}/clotures` : Déclencher manuellement la clôture journalière
- `PUT /api/clotures/{id}/solde-reel` : Enregistrer le contrôle physique et calcul d'écart

### 👥 Vendeurs (Admin)
- `GET /api/vendeurs` : Liste des vendeurs
- `POST /api/vendeurs` : Créer un compte vendeur
- `PUT /api/vendeurs/{id}` : Modifier un compte vendeur
- `DELETE /api/vendeurs/{id}` : Désactiver un vendeur

### 💳 Paiements Vendeurs
- `GET /api/paiements` : Liste des salaires/primes (Admin)
- `POST /api/paiements` : Enregistrer un paiement
- `PUT /api/paiements/{id}/confirmer` : Vendeur confirme la réception
- `GET /api/mes-paiements` : Historique des paiements reçus (Vendeur)

### 🏦 Emprunts & Remboursements
- `GET /api/emprunts` : Liste des emprunts contractés (Admin)
- `POST /api/emprunts` : Enregistrer un emprunt (génère automatiquement l'échéancier)
- `GET /api/emprunts/{id}` : Détails et tableau d'amortissement
- `PUT /api/echeances/{id}/payer` : Enregistrer le règlement d'une échéance

### 📊 Rapports & Dashboard
- `GET /api/dashboard` : Vue consolidée multi-boutiques en temps-réel
- `GET /api/boutiques/{id}/rapports/journalier?date=YYYY-MM-DD` : Rapport journalier détaillé
- `GET /api/boutiques/{id}/rapports/mensuel?mois=YYYY-MM` : Rapport mensuel consolidé
- `GET /api/vendeurs/{id}/ecarts?debut=YYYY-MM-DD&fin=YYYY-MM-DD` : Suivi des écarts d'une vendeuse
