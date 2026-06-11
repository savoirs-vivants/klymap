# Klymap

## Plateforme de suivi des îlots de chaleur urbains — terrain, IoT et pédagogie

Klymap est une application web permettant de mesurer, cartographier et analyser les îlots de chaleur urbains (ICU). Elle combine des relevés de terrain réalisés par des participants (élèves, citoyens, équipes pédagogiques), des capteurs météo IoT (LoRa / Bluetooth) et des capteurs témoins de référence, afin de calculer un indice de chaleur urbaine fiable et de le comparer entre différentes zones d'une ville.

---

## Fonctionnalités principales

### 🌍 Carte interactive
Carte Leaflet centralisée affichant en temps réel les capteurs météo IoT, les capteurs témoins et les points de mesure des campagnes. Recherche d'adresse, filtres par type de capteur et accès au détail de chaque point depuis la carte.

### 📡 Capteurs météo IoT
Suivi des stations météo connectées (température, humidité) via synchronisation LoRa (DevEui) et Bluetooth. Historique des mesures, graphiques d'évolution et export des données par capteur.

### 🎓 Campagnes pédagogiques
Création de campagnes de mesure par les enseignants/gestionnaires, organisation en groupes, et participation via un simple code de session (sans création de compte). Chaque participant place ses points de mesure (capteur témoin associé, photo, mesures de température/humidité) directement sur la carte.

### 🔥 Analyse ICU (Indice de Chaleur Urbaine)
Calcul automatique de l'ICU par point de mesure : sélection des nuits valides (créneau 2h–8h), moyenne des trois relevés les plus bas pour le point urbain et son capteur témoin, écart-type (n-1) et détection des mesures aberrantes (taux d'humidité élevé, écarts anormaux entre sondes). Système de validation manuelle (badge) lorsque la fiabilité du résultat est incertaine.

### 📊 Statistiques et export
Export Excel (XLSX) des mesures par capteur météo, par capteur témoin et des comparaisons ICU multi-conditions, avec mise en forme prête à l'analyse (PhpSpreadsheet).

### 🛠️ Backoffice admin
Gestion des comptes utilisateurs (création, modification, suppression, attribution de rôle) avec envoi automatique des identifiants par e-mail.

---

## Stack technique

| Composant       | Technologie                          |
|-----------------|---------------------------------------|
| Framework       | Laravel 13 (PHP ^8.3)                 |
| Frontend        | Blade + JavaScript natif (ES Modules) |
| Styles          | Tailwind CSS 4                        |
| Build           | Vite 8                                |
| Cartographie    | Leaflet.js                            |
| Graphiques      | Chart.js                              |
| Export Excel    | PhpOffice/PhpSpreadsheet              |
| Documentation API | L5-Swagger (OpenAPI)                |
| Base de données | MySQL                                 |

---

## Rôles et permissions

| Rôle              | Accès                                                                 |
|-------------------|------------------------------------------------------------------------|
| **Admin**         | Accès complet : backoffice (gestion des utilisateurs), campagnes, capteurs, comparaison ICU |
| **Utilisateur (gestionnaire)** | Création et gestion de ses campagnes, capteurs météo et capteurs témoins, comparaison ICU |
| **Participant**   | Accès via code de campagne (sans compte) : placement de points de mesure et consultation des données de sa campagne |

---

## Prérequis

| Outil      | Version recommandée |
|------------|----------------------|
| PHP        | >= 8.3               |
| Composer   | >= 2.x               |
| Node.js    | >= 18.x              |
| npm        | >= 9.x               |
| MySQL      | >= 8.0               |
| XAMPP      | Apache + MySQL       |

---

## Installation

1. **Cloner le projet** dans le dossier `htdocs` de XAMPP :
   ```bash
   git clone <url-du-depot> klymap
   cd klymap
   ```

2. **Installer les dépendances PHP** :
   ```bash
   composer install
   ```

3. **Installer les dépendances JavaScript** :
   ```bash
   npm install
   ```

4. **Configurer le fichier d'environnement** :
   ```bash
   cp .env.example .env
   ```
   Renseigner les informations de connexion à la base de données :
   ```env
   APP_NAME=Klymap
   APP_URL=http://localhost/klymap/public

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=klymap
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. **Générer la clé d'application** :
   ```bash
   php artisan key:generate
   ```

6. **Créer la base de données** `klymap` via phpMyAdmin (ou tout autre client MySQL).

7. **Exécuter les migrations** :
   ```bash
   php artisan migrate
   ```

8. **Lier le stockage public** (pour les images des capteurs témoins et des points de mesure) :
   ```bash
   php artisan storage:link
   ```

9. **Compiler les assets** :
   - En développement :
     ```bash
     npm run dev
     ```
   - En production :
     ```bash
     npm run build
     ```

---

## Lancer l'application

### Avec XAMPP

Démarrer Apache et MySQL depuis le panneau de contrôle XAMPP, puis accéder à :
```
http://localhost/klymap/public
```

### Avec le serveur intégré Laravel

```bash
php artisan serve
```
Puis accéder à :
```
http://localhost:8000
```

---

## Configuration de l'envoi d'e-mails

Klymap envoie automatiquement les identifiants de connexion lors de la création d'un compte (depuis le backoffice). Configurer les variables suivantes dans `.env` selon votre fournisseur SMTP :

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=votre-adresse@example.com
MAIL_PASSWORD=votre-mot-de-passe
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="votre-adresse@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

En environnement de développement, `MAIL_MAILER=log` permet de consulter les e-mails envoyés dans `storage/logs/laravel.log`.

---

## Créer le premier compte administrateur

Aucun compte n'est créé automatiquement lors de l'installation. Pour créer le premier administrateur, utiliser Tinker :

```bash
php artisan tinker
```

```php
\App\Models\User::create([
    'firstname' => 'Admin',
    'name'      => 'Klymap',
    'email'     => 'admin@klymap.fr',
    'password'  => \Illuminate\Support\Facades\Hash::make('mot-de-passe-securise'),
    'role'      => 'admin',
]);
```

---

## Structure du projet

```
app/
├── Console/Commands/        # Commandes Artisan (recalcul des valeurs ICU, etc.)
├── Http/
│   ├── Controllers/          # Campagnes, capteurs météo, capteurs témoins, points de mesure,
│   │                          # comparaison ICU, données de campagnes, backoffice, participants...
│   └── Middleware/            # AdminMiddleware, AuthOrParticipant
├── Mail/                      # Notifications par e-mail (création de compte)
├── Models/                     # Campagne, CapteurMeteo, CapteurTemoin, CapteurPoint, Mesure, User...
└── View/Composers/             # Données partagées avec les vues (campagne active, etc.)

resources/
├── js/                          # Carte (map.js), points de mesure (points.js), graphiques (capteur-chart.js),
│                                  # gestion des campagnes, Bluetooth, etc.
├── css/                          # Styles Tailwind CSS
└── views/
    ├── welcome.blade.php          # Carte principale
    ├── campagnes/                  # Gestion et données des campagnes
    ├── capteurs/                    # Gestion des capteurs météo
    ├── backoffice/                   # Gestion des utilisateurs
    ├── participant/                   # Espace participant (code de session)
    ├── comparaison-icu.blade.php       # Comparaison multi-conditions de l'ICU
    └── components/                      # En-tête, layouts, éléments réutilisables

database/
└── migrations/                  # Toutes les migrations dans l'ordre chronologique :
                                   # utilisateurs et rôles, capteurs témoins et leurs mesures,
                                   # campagnes et participants, points de mesure et leurs mesures,
                                   # capteurs météo, et évolutions successives (images, dates,
                                   # rattachement aux campagnes, indicateurs de fiabilité ICU)
```
