<div align="center">

# 🚀 GRH-System  
### Système Intégré de Gestion des Ressources Humaines (ERP)

![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![Livewire](https://img.shields.io/badge/Livewire-v3-4E56A6?style=for-the-badge)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Status](https://img.shields.io/badge/Status-Academic_Project-blue?style=for-the-badge)
![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)

**Projet**  
Développé par **Yasser Jabir** – Développement Digital Full Stack (OFPPT)

</div>

---

## 📖 Présentation

**GRH-System** est une application web ERP dédiée à la gestion centralisée des ressources humaines.  
Elle permet d’automatiser les processus administratifs liés au personnel : gestion des employés, suivi du temps de travail, gestion des congés et génération de la paie.

Développée avec une architecture moderne basée sur Laravel et l’écosystème TALL, l’application garantit sécurité, performance et maintenabilité.

---

## 🎯 Objectifs du Projet

- Digitaliser les processus RH manuels
- Centraliser les données des employés
- Sécuriser l’accès aux informations sensibles
- Automatiser le calcul et la génération des bulletins de paie
- Fournir un panel d’administration professionnel

---

## ✨ Fonctionnalités

### 🔐 Gestion des Rôles & Permissions (RBAC)
- Deux rôles principaux : **Administrateur** et **Employé**
- Gestion des permissions via Spatie Laravel Permission
- Isolation stricte des données par utilisateur

### 👥 Gestion du Personnel
- CRUD complet des employés
- Gestion des départements et postes
- Suivi des contrats

### ⏱️ Système de Pointage
- Enregistrement d’arrivée et départ en temps réel
- Horodatage sécurisé côté serveur
- Export des présences au format Excel (.xlsx)

### 🏖️ Gestion des Congés
- Soumission de demandes par les employés
- Validation / Refus par l’administration
- Suivi automatique des soldes

### 💰 Gestion de la Paie
- Calcul automatisé des salaires
- Génération de bulletins de paie en PDF
- Archivage des fiches de paie

---

## 🏗️ Architecture & Conception

- Architecture **MVC (Model-View-Controller)**
- Backend structuré avec Laravel 11
- Panel d’administration basé sur FilamentPHP v3
- Composants dynamiques via Livewire
- Séparation logique des responsabilités
- Sécurisation des routes via Middleware & Policies

---

## 🔒 Sécurité

- Hashage sécurisé des mots de passe (bcrypt)
- Protection CSRF intégrée
- Middleware d’authentification
- Gestion des accès basée sur rôles & permissions
- Horodatage serveur pour éviter la fraude au pointage

---

## 🛠️ Stack Technique

**Backend**
- PHP 8.2+
- Laravel 11.x

**Frontend / Admin Panel**
- FilamentPHP v3
- Livewire v3
- Alpine.js
- Tailwind CSS

**Base de données**
- MySQL

**Packages principaux**
- spatie/laravel-permission
- pxlrbt/filament-excel
- barryvdh/laravel-dompdf

---

## 📸 Aperçu de l’Application

*(Ajoutez ici vos captures d’écran)*

- Dashboard Administrateur
- Gestion des employés
- Module de congés
- Génération PDF des fiches de paie

---

## 🚀 Installation en Local

### 1️⃣ Prérequis

- PHP >= 8.2
- Composer
- Node.js & NPM
- MySQL
- Environnement local recommandé : Laragon ou XAMPP

---

### 2️⃣ Cloner le projet

```bash
git clone https://github.com/VOTRE_NOM_UTILISATEUR/grh-system.git
cd grh-system
```

---

### 3️⃣ Installer les dépendances

```bash
composer install
npm install
npm run build
```

---

### 4️⃣ Configuration

```bash
cp .env.example .env
php artisan key:generate
```

Modifier le fichier `.env` :

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=grh_system
DB_USERNAME=root
DB_PASSWORD=
```

---

### 5️⃣ Migration & Seeder

Créer la base de données `grh_system`, puis exécuter :

```bash
php artisan migrate:fresh --seed
```

---

### 6️⃣ Lier le stockage

```bash
php artisan storage:link
```

---

### 7️⃣ Lancer le serveur

```bash
php artisan serve
```

Accès au panel :

```
http://127.0.0.1:8000/app/login
```

---

## 🔑 Comptes de Test

| Rôle | Email | Mot de passe |
|------|-------|--------------|
| Administrateur | admin@grh.com | password123 |

---

## 📈 Améliorations Futures

- Module d’évaluation des performances
- Statistiques avancées RH
- Tableau de bord analytique
- Notifications en temps réel
- Déploiement cloud (Docker / VPS)

---

## 👨‍💻 Auteur

**Yasser Jabir**  
Développeur Full Stack  
Projet réalisé dans le cadre du cursus OFPPT – Développement Digital

---

## 📄 Licence

Ce projet est distribué sous licence MIT.
