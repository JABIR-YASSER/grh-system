
# 🚀 GRH-System  
### Système Intégré de Gestion des Ressources Humaines (ERP)

**Projet de Fin d'Études**  
Développé par **Yasser Jabir** – Développement Digital Full Stack (OFPPT)

---

## 📖 Présentation

**GRH-System** est une application web ERP moderne conçue pour automatiser et centraliser la gestion des ressources humaines.

Elle permet de gérer efficacement :
- Les employés  
- Les présences (mode industriel via import)  
- Les congés  
- La paie  

L’application se distingue par sa capacité à traiter des **données réelles issues de machines de pointage**, offrant ainsi un niveau de réalisme professionnel adapté au monde de l’entreprise.

---

## 🎯 Objectifs du Projet

- Digitaliser les processus RH manuels  
- Centraliser les données des employés  
- Sécuriser l’accès aux informations sensibles  
- Automatiser la génération des bulletins de paie  
- Fournir un dashboard administratif intelligent  

---

## ✨ Fonctionnalités

### 📊 Tableaux de Bord Intelligents
- Dashboard Admin (présence, masse salariale, effectifs)  
- Dashboard Employé personnalisé  
- Bannière d’alerte critique pour les demandes urgentes  

---

### 🔐 Gestion des Rôles & Permissions (RBAC)
- Rôles : **Administrateur / Employé**  
- Gestion via Spatie Laravel Permission  
- Isolation stricte des données par utilisateur  

---

### 👥 Gestion du Personnel
- CRUD complet des employés  
- Gestion des départements et postes  
- Fiches complètes (CNSS, RIB, contacts d’urgence)  
- Workflow : Actifs / En congé / Archivés  

---

### ⏱️ Gestion des Présences (Mode Industriel)
- Import automatique depuis machines de pointage  
- Synchronisation avec les employés  
- Horodatage sécurisé côté serveur  
- Export Excel (.xlsx)  

---

### 🏖️ Gestion des Congés
- Demandes soumises par les employés  
- Validation / refus par l’administration  
- Suivi automatique des soldes  
- Statut en temps réel  

---

### 💰 Gestion de la Paie
- Calcul automatisé des salaires  
- Génération PDF des fiches de paie  
- Archivage sécurisé  

---

## 🏗️ Architecture & Conception

- Architecture **MVC (Model-View-Controller)**  
- Backend : Laravel 11  
- Admin Panel : FilamentPHP v3  
- Frontend dynamique : Livewire + Alpine.js  
- Styling : Tailwind CSS  
- Séparation claire des responsabilités  

---

## 🔒 Sécurité

- Hashage des mots de passe (bcrypt)  
- Protection CSRF  
- Middleware d’authentification  
- RBAC (rôles & permissions)  
- Isolation des données par utilisateur  
- Protection contre suppression auto admin  
- Validation stricte des imports  

---

## 🛠️ Stack Technique

### Backend
- PHP 8.2+  
- Laravel 11  

### Frontend / Admin
- FilamentPHP v3  
- Livewire v3  
- Alpine.js  
- Tailwind CSS  

### Base de données
- MySQL  

### Packages principaux
- spatie/laravel-permission  
- pxlrbt/filament-excel  
- barryvdh/laravel-dompdf  

---

## 🚀 Installation en Local

### 1️⃣ Prérequis
- PHP ≥ 8.2  
- Composer  
- Node.js & NPM  
- MySQL  
- Laragon / XAMPP recommandé  

---

### 2️⃣ Cloner le projet
```bash
git clone https://github.com/YasserJabir/grh-system.git
cd grh-system
````

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

Configurer `.env` :

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

Accès :

```
http://127.0.0.1:8000/app/login
```

---

## 🔑 Comptes de Test

| Rôle           | Email                                     | Mot de passe |
| -------------- | ----------------------------------------- | ------------ |
| Administrateur | [admin@grh.com](mailto:admin@grh.com)     | password     |
| Employé        | [employe@grh.com](mailto:employe@grh.com) | password     |

---

## 📈 Améliorations Futures

* Module d’évaluation des performances
* Dashboard analytique avancé
* Notifications en temps réel
* Déploiement Docker / VPS
* API pour intégration externe

---

## 👨‍💻 Auteur

**Yasser Jabir**
Développeur Full Stack
Projet réalisé dans le cadre du cursus OFPPT

---

## 📄 Licence

Licence MIT


