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
- Catalogue de livres avec recherche et filtres par catégorie
- Fiche détaillée de chaque livre
- Panier d'achat (localStorage)
- Inscription & connexion sécurisée
- Espace compte & historique des commandes

### 🔐 Authentification
- Connexion / Déconnexion sécurisée
- Gestion des sessions PHP
- Protection des pages par rôle (admin / utilisateur)
- Hash des mots de passe (bcrypt)

### ⚙️ Panel Admin
- Dashboard avec statistiques et graphiques (Chart.js)
- CRUD complet des livres (+ upload image)
- Gestion des catégories
- Gestion des commandes (changement de statut)
- Gestion des utilisateurs

---

## 📁 Structure du projet

```
bookshop/
├── index.php               # Accueil
├── catalog.php             # Catalogue livres
├── product.php             # Fiche livre
├── login.php               # Connexion
├── logout.php              # Déconnexion
├── register.php            # Inscription
├── cart.php                # Panier
├── checkout.php            # Commande
├── account.php             # Mon compte
│
├── admin/
│   ├── dashboard.php       # Tableau de bord
│   ├── books.php           # Gestion livres
│   ├── categories.php      # Gestion catégories
│   ├── orders.php          # Gestion commandes
│   └── users.php           # Gestion utilisateurs
│
├── includes/
│   ├── db.php              # Connexion PDO
│   └── auth.php            # Vérification session/rôle
│
├── assets/
│   ├── css/style.css
│   └── js/main.js
│
├── uploads/                # Images livres & auteurs
│
└── sql/
    └── database.sql        # Schéma de la base de données
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
| `main` | Les deux | Version stable — demo finale |
| `feature/hamza-auth-admin` | Hamza | Login, logout, panel admin |
| `feature/eya-user` | Eya | Catalogue, panier, commandes |
### Routine quotidienne
```bash
git checkout feature/hamza-auth-admin
git pull origin main          # récupérer les mises à jour
# ... coder ...
git add .
git commit -m "feat: description de ce que tu as fait"
git push origin feature/hamza-auth-admin
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
| **Hamza Arfaoui** | Auth (login/logout) + Panel Admin | `feature/hamza-auth-admin` |
| **Eya Kochbati** | Côté Utilisateur + Base de données | `feature/eya-user` |

---

## 📄 Licence

Projet académique — TIC-1 · 2025
| Branche | Responsable | Contenu |
- Accéder via : `http://localhost/bookshop`
```
```php

