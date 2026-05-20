# 📚 BookShop

> Application web e-commerce de livres avec espace admin, authentification et gestion de commandes.

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
- Panier d'achat (localStorage)
- Inscription & connexion sécurisée

### 🔐 Authentification
- Connexion / Déconnexion sécurisée
- Gestion des sessions PHP
- Protection des pages par rôle (admin / utilisateur)
- Hash des mots de passe (bcrypt)

### ⚙️ Panel Admin
- Dashboard avec statistiques 
- CRUD complet des livres (+ upload image)
- Gestion des catégories
- Gestion des commandes (changement de statut)
- Gestion des utilisateurs
- Gestion des avis
---

## 📁 Structure du projet

```
BookShop/
├── Login.php                       # Connexion
├── Logout.php                      # Déconnexion
├── Register.php                    # Inscription
├── index.php                       # Accueil boutique
├── details.php                     # Fiche livre
├── panier.php                      # Panier
├── ajouter_panier.php              # Ajouter au panier (action)
├── valider.php                     # Valider commande
├── confirmation.php                # Confirmation commande
│
├── admin/
│   ├── dashboard.php               # Tableau de bord
│   ├── books.php                   # Liste des livres
│   ├── add-book.php                # Ajouter un livre
│   ├── book-detail.php             # Modifier un livre
│   ├── authors.php                 # Liste des auteurs
│   ├── author-detail.php           # Modifier un auteur
│   ├── categories.php              # Gestion catégories
│   ├── category-detail.php         # Détail catégorie
│   ├── orders.php                  # Gestion commandes
│   ├── commande_detail.php         # Détail commande
│   ├── users.php                   # Gestion utilisateurs
│   ├── review.php                  # Gestion avis
│   └── profile.php                 # Profil admin
│
├── includes/
│   ├── db.php                      # Connexion PDO
│   └── nav.php                     # Navigation admin (sidebar)
│
├── assests/
│   ├── css/
│   │   ├── admin.css               # Style interface admin
│   │   ├── auth.css                # Style login / register
│   │   ├── style.css               # Style boutique
│   │   ├── style_details.css       # Style fiche livre
│   │   ├── style_panier.css        # Style panier
│   │   └── style_suivi.css         # Style suivi commande
│   └── img/
│       └── auth-bg.jpg             # Image fond login/register
│
├── uploads/
│   ├── book-covers/                # Couvertures des livres
│   ├── authors/                    # Photos des auteurs
│   └── users/                      # Photos de profil admins
│
└── sql/
    └── bookdb.sql                  # Base de données complète
```

---

## ⚙️ Installation

### 1. Cloner le projet
```bash
git clone https://github.com/hamza/bookshop.git
cd bookshop
```

### 2. Configurer la base de données
```bash
# Importer le schéma SQL dans phpMyAdmin ou via terminal
mysql -u root -p bookshop < sql/database.sql
```

Modifier le fichier `includes/db.php` :
$host = 'localhost';
$dbname = 'bookshop';
$user = 'root';
$password = '';

### 4. Lancer avec XAMPP / WAMP
- Placer le projet dans `htdocs/` (XAMPP) ou `www/` (WAMP)

---

## 🌿 Workflow Git

|---|---|---|
| main | Les deux | Version stable — demo finale |
| feature/hamza | Hamza | Login, logout, panel admin |
| feature/eya | Eya | Catalogue, panier, commandes |
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

## 👥 Équipe

| Membre | Rôle | Branche |
|---|---|---|
| **Hamza Arfaoui** | Panel Admin & Database & Register , Login et logout | `feature/hamza` |
| **Eya Kochbati** | Côté Utilisateur & panier & catalogue| `feature/eya` |

---

## 📄 Licence

Projet académique — TIC-1 · 2025
| Branche | Responsable | Contenu |
- Accéder via : `http://localhost/BookShop`
```
```php

