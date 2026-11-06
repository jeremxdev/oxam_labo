# OXAM LABO — Gestion des visites médicales

Application web PHP/MySQL de suivi des visites de visiteurs médicaux, de leur portefeuille de praticiens et des activités complémentaires (AC).

## Fonctionnalités

- Création d'un profil visiteur et affectation à une région.
- Connexion et déconnexion d'un visiteur.
- Consultation de la flotte des visiteurs.
- Création et consultation des comptes rendus de visite.
- Association de praticiens, de médicaments présentés et d'échantillons à une visite.
- Consultation et création d'activités complémentaires.
- Affichage d'un compte rendu de visite dans une page imprimable (action « PDF » dans l'interface).

> **Authentification actuelle :** la connexion se fait uniquement avec l'adresse e-mail, sans mot de passe. Cette application est un projet de démonstration et ne doit pas être exposée en production sans renforcer l'authentification et la sécurité.

## Technologies et prérequis

- PHP 8.x avec les extensions PDO et PDO MySQL (`pdo_mysql`).
- MySQL ou MariaDB.
- Apache (par exemple via Laragon).
- Un navigateur web récent.

Aucun gestionnaire de dépendances ni aucune bibliothèque externe n'est requis par le code actuellement fourni.

## Installation locale avec Laragon

1. Placez le projet dans le dossier web de Laragon, par exemple `C:\laragon\www\oxam_labo`.
2. Démarrez Apache et MySQL depuis Laragon.
3. Créez et initialisez la base de données en important [`schema.sql`](schema.sql) dans MySQL, par exemple avec HeidiSQL. Ce script crée la base `oxam_labo`, ses tables et des données de démonstration. Il est destiné à une installation neuve : ne le réimportez pas dans une base déjà initialisée.
4. Vérifiez les paramètres de connexion dans [`config/database.php`](config/database.php). Les valeurs par défaut sont `localhost`, la base `oxam_labo`, l'utilisateur `root` et un mot de passe vide. Adaptez-les à votre installation MySQL.
5. Si la base existait déjà avant que `telephone_vis` soit défini comme texte, exécutez une fois [`migrations/001_telephone_vis_varchar.sql`](migrations/001_telephone_vis_varchar.sql). Cette migration convertit la colonne en `VARCHAR(20)` afin de conserver les zéros initiaux et d'accepter les numéros internationaux. Faites une sauvegarde de la base avant toute migration.
6. Ouvrez l'application dans votre navigateur :
   - `http://oxam_labo.test/` si les hôtes virtuels automatiques de Laragon sont activés ;
   - sinon `http://localhost/oxam_labo/`.

## Première utilisation

Le script d'initialisation ajoute notamment les adresses de démonstration suivantes :

- `jean.dupont@oxam-labo.fr`
- `sophie.martin@oxam-labo.fr`

La connexion de démonstration ne demande que l'adresse e-mail. Pour créer un autre profil, utilisez **Créer un compte**, renseignez les coordonnées et choisissez une région. Une inscription réussie ajoute le visiteur dans `Visiteur` et son affectation dans `Travailler`.

## Navigation

Les pages sont routées par le paramètre `action` de `index.php` :

| Action | Description |
| --- | --- |
| `login` | Connexion |
| `register` | Inscription d'un visiteur |
| `logout` | Déconnexion |
| `visites` | Liste des visites du visiteur connecté |
| `visite_create` | Saisie d'une visite |
| `visite_delete&id=<id>` | Suppression d'une visite lorsque les règles métier le permettent |
| `visite_pdf&id=<id>` | Affichage du compte rendu imprimable |
| `visiteurs` | Liste des visiteurs |
| `visiteur_create` | Création d'un visiteur connecté |
| `ac` | Liste des activités complémentaires |
| `ac_create` | Création d'une activité complémentaire |

Les pages de visites, d'activités et de gestion des visiteurs nécessitent une session ouverte.

## Base de données

Le schéma de [`schema.sql`](schema.sql) comprend notamment les tables `Visiteur`, `Region`, `Travailler`, `Practicien`, `Specialite`, `Visite`, `Medicament`, `Presenter`, `Offrir` et `AC`, ainsi que les tables de liaison utilisées par le modèle.

Les régions et exemples de visiteurs, praticiens et médicaments sont insérés par le script d'initialisation. Les listes proposées dans l'application proviennent des données présentes dans la base.

Pour une base existante, la migration du téléphone peut être exécutée dans HeidiSQL en ouvrant `migrations/001_telephone_vis_varchar.sql` dans une fenêtre de requête et en exécutant le script sur le serveur MySQL.

## Structure du projet

```text
config/       Connexion à la base de données
controllers/  Contrôleurs des pages et des actions
migrations/   Migrations destinées aux bases existantes
models/       Accès aux données et opérations métier
public/       Feuilles de style et scripts JavaScript
views/        Pages PHP et gabarits HTML
index.php     Point d'entrée et routage
schema.sql    Création du schéma et données de démonstration
```

## Dépannage

- **Erreur de connexion à la base** : vérifiez que MySQL est démarré, que la base `oxam_labo` existe et que les identifiants de [`config/database.php`](config/database.php) sont corrects.
- **Aucune région dans le formulaire** : vérifiez que les données initiales de `Region` ont été importées dans la base utilisée par l'application.
- **Erreur à la création d'un compte avec un numéro de téléphone** : sur une base antérieure, exécutez la migration `001_telephone_vis_varchar.sql`, puis vérifiez que `telephone_vis` est de type `VARCHAR(20)`.
- **Erreur SQL persistante** : consultez le journal d'erreurs PHP configuré par votre installation PHP/Laragon. Les erreurs de création de visiteur sont journalisées côté serveur.
