# 📚 BookShop

> Application web e-commerce de livres avec espace admin (architecture MVC), authentification et gestion de commandes.

🌐 **Live Demo:** [bookshop-js.free.nf](https://bookshop-js.free.nf/login.php)

> **Admin access** — Email: `hamza@admin.com` · Password: `123456`

---

## 👥 Équipe

| Membre | Rôle | Branche |
|---|---|---|
| **Hamza Arfaoui** | Panel Admin (MVC) · Base de données · Authentification (Login, Logout, Register) · Uploads · Includes · Style Admin | `feature/hamza` |
| **Eya Kochbati** | Côté Utilisateur · Catalogue · Panier · Commandes · Style boutique | `feature/eya` |

---

## 🛠️ Technologies

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=flat&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.x-4479A1?style=flat&logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-ES6-F7DF1E?style=flat&logo=javascript&logoColor=black)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=flat&logo=css3&logoColor=white)

---

## 📋 Fonctionnalités

### 👤 Côté Utilisateur
- Catalogue de livres avec recherche
- Fiche détaillée de chaque livre
- Panier d'achat
- Inscription & connexion sécurisée

### 🔐 Authentification
- Connexion / Déconnexion sécurisée
- Gestion des sessions PHP
- Protection des pages par rôle (admin / utilisateur)
- Hash des mots de passe (bcrypt)

### ⚙️ Panel Admin
- Dashboard avec statistiques
- CRUD complet des livres (+ upload image)
- Gestion des catégories & auteurs
- Gestion des commandes (changement de statut)
- Gestion des utilisateurs
- Gestion des avis

---

## 🏗️ Architecture

Le panel admin suit une architecture **MVC (Model-View-Controller)** :

- **Models** — Accès aux données via PDO (`admin/models/`)
- **Controllers** — Logique métier et routing (`admin/controllers/`)
- **Views** — Rendu HTML par entité (`admin/views/`)

---

## 📁 Structure du projet

Hamza = Hamza Arfaoui · Eya = Eya Kochbati

```
BookShop/
├── Login.php                        # Connexion (Hamza)
├── Logout.php                       # Déconnexion (Hamza)
├── Register.php                     # Inscription (Hamza)
├── index.php                        # Accueil boutique (Eya)
├── details.php                      # Fiche livre (Eya)
├── panier.php                       # Panier (Eya)
├── ajouter_panier.php               # Ajouter au panier (Eya)
├── valider.php                      # Valider commande (Eya)
├── confirmation.php                 # Confirmation commande (Eya)
│
├── admin/                           # Panel admin (Hamza)
│   ├── dashboard.php                # Tableau de bord (Hamza)
│   ├── books.php                    # Liste des livres (Hamza)
│   ├── add-book.php                 # Ajouter un livre (Hamza)
│   ├── book-detail.php              # Modifier un livre (Hamza)
│   ├── authors.php                  # Liste des auteurs (Hamza)
│   ├── author-detail.php            # Modifier un auteur (Hamza)
│   ├── categories.php               # Gestion catégories (Hamza)
│   ├── category-detail.php          # Détail catégorie (Hamza)
│   ├── orders.php                   # Gestion commandes (Hamza)
│   ├── commande_detail.php          # Détail commande (Hamza)
│   ├── users.php                    # Gestion utilisateurs (Hamza)
│   ├── review.php                   # Gestion avis legacy (Hamza)
│   ├── reviews.php                  # Gestion avis (Hamza)
│   ├── profile.php                  # Profil admin (Hamza)
│   │
│   ├── controllers/                 # Logique métier MVC (Hamza)
│   │   ├── AuthorController.php
│   │   ├── BookController.php
│   │   ├── CategoryController.php
│   │   ├── DashboardController.php
│   │   ├── OrderController.php
│   │   ├── ReviewController.php
│   │   └── UserController.php
│   │
│   ├── models/                      # Accès aux données MVC (Hamza)
│   │   ├── AuthorModel.php
│   │   ├── BookModel.php
│   │   ├── CategoryModel.php
│   │   ├── DashboardModel.php
│   │   ├── OrderModel.php
│   │   ├── ReviewModel.php
│   │   └── UserModel.php
│   │
│   └── views/                       # Rendu HTML MVC (Hamza)
│       ├── authors/
│       │   ├── index.php
│       │   └── detail.php
│       ├── books/
│       │   ├── index.php
│       │   └── detail.php
│       ├── categories/
│       │   ├── index.php
│       │   └── detail.php
│       ├── dashboard/
│       │   └── index.php
│       ├── orders/
│       │   ├── index.php
│       │   └── detail.php
│       ├── reviews/
│       │   └── index.php
│       └── users/
│           └── index.php
│
├── includes/                        # (Hamza)
│   ├── db.php                       # Connexion PDO (Hamza)
│   └── nav.php                      # Navigation admin sidebar (Hamza)
│
├── assests/
│   ├── css/
│   │   ├── admin.css                # Style interface admin (Hamza)
│   │   ├── auth.css                 # Style login / register (Hamza)
│   │   ├── style.css                # Style boutique (Eya)
│   │   ├── style_details.css        # Style fiche livre (Eya)
│   │   ├── style_panier.css         # Style panier (Eya)
│   │   └── style_suivi.css          # Style suivi commande (Eya)
│   └── img/                         # (Hamza)
│       ├── auth-bg.jpg              # Image fond login/register (Hamza)
│       └── logo.webp                # Logo du site (Hamza)
│
├── uploads/                         # (Hamza)
│   ├── book-covers/                 # Couvertures des livres (Hamza)
│   ├── authors/                     # Photos des auteurs (Hamza)
│   └── users/                       # Photos de profil admins (Hamza)
│
└── sql/
    └── bookdb.sql                   # Base de données complète (Hamza)
```

---

## ⚙️ Installation

### 1. Cloner le projet
```bash
git clone https://github.com/hamza05-dot/bookshop.git
cd bookshop
```

### 2. Configurer la base de données
```bash
mysql -u root -p bookshop < sql/bookdb.sql
```

Modifier le fichier `includes/db.php` :
```php
$host = 'localhost';
$dbname = 'bookdb';
$user = 'root';
$password = '';
```

### 3. Lancer avec XAMPP / WAMP
- Placer le projet dans `htdocs/` (XAMPP) ou `www/` (WAMP)
- Accéder à `http://localhost/bookshop`

---

## 🌿 Workflow Git

| Branche | Membre | Description |
|---|---|---|
| `main` | Les deux | Version stable — demo finale |
| `feature/hamza` | Hamza | Login, logout, panel admin |
| `feature/eya` | Eya | Catalogue, panier, commandes |

### Routine quotidienne
```bash
git pull origin main          # récupérer les mises à jour
# ... coder ...
git add .
git commit -m "feat: description de ce que tu as fait"
```

### Convention des commits
| Préfixe | Exemple |
|---|---|
| `feat:` | `feat: ajout page login avec validation` |
| `fix:` | `fix: correction erreur session admin` |
| `admin:` | `admin: CRUD livres + upload image` |
| `style:` | `style: mise en page dashboard` |
| `db:` | `db: ajout table orders` |

---
