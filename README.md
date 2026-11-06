# Maquette — LBC Visites

Maquette haute fidélité du site **LBC Visites**, application web de gestion des visites des visiteurs médicaux du laboratoire **LBC (Laboratoire Bio Company)**, réalisée d'après le brief *BTS SIO SLAM — AP3 « Visiteurs LBC »*.

> Objectif du projet : permettre aux visiteurs médicaux de saisir et suivre leurs visites, de générer des comptes rendus PDF et de gérer leurs activités complémentaires, avec un espace de pilotage pour les responsables de région.

---

## Ouvrir la maquette

Deux possibilités :

1. **Directement** — ouvrir `index.html` dans un navigateur moderne (Chrome, Edge, Firefox).
2. **Avec un serveur local** (recommandé pour éviter les restrictions de fichier) :

```bash
cd maquette-lbc
npx serve .
# ou : python -m http.server 8000
```

Aucune dépendance npm n'est nécessaire : le site est 100 % HTML / CSS / JavaScript natif.

### Comptes de démonstration

| Profil | E-mail | Accès |
|---|---|---|
| Visiteur médical | `camille.moreau@lbc.fr` | tableau de bord, visites, praticiens, AC, comptes rendus |
| Responsable de région | `julien.lefevre@lbc.fr` | pilotage d'équipe, échantillons, tous les comptes rendus |

Le sélecteur de rôle de l'écran de connexion préremplit l'e-mail correspondant. Le mot de passe est libre (maquette).

---

## Écrans livrés

| Fichier | Écran | Rôle |
|---|---|---|
| `index.html` | Connexion sécurisée | Tous |
| `dashboard.html` | Tableau de bord visiteur (KPI, activité, agenda) | Visiteur |
| `visites.html` | Liste des visites, filtres, statuts | Visiteur |
| `visite-nouvelle.html` | Formulaire de saisie d'une visite | Visiteur |
| `visite-detail.html` | Détail d'une visite + onglets CR / historique | Visiteur |
| `compte-rendu.html` | Compte rendu PDF imprimable | Visiteur / Responsable |
| `praticiens.html` | Portefeuille de praticiens | Visiteur |
| `activites.html` | Activités complémentaires + calendrier | Visiteur |
| `comptes-rendus.html` | Bibliothèque des comptes rendus | Visiteur |
| `responsable.html` | Pilotage de la région, performance d'équipe | Responsable |
| `echantillons.html` | Stocks, seuils et allocations d'échantillons | Responsable |
| `styleguide.html` | Design system (couleurs, typo, composants) | Annexe |

---

## Couverture du cahier des charges

| Exigence du brief | Traduction dans la maquette |
|---|---|
| Application web sécurisée, accessible depuis un navigateur | Écran de connexion, session, accès par rôle |
| Authentification préalable (responsable + chaque visiteur) | Connexion + bascule visiteur / responsable |
| Gestion des visites (date, praticien, motif, médicaments, échantillons, bilan) | `visite-nouvelle.html`, `visite-detail.html` |
| Bilan : praticien convaincu ou non, autre visite planifiée | Section « Bilan de la visite » (radios + date de suivi) |
| Saisie, visualisation, modification, suppression encadrée | Liste des visites + actions ; suppression désactivée si CR/échantillon |
| Génération d'un compte rendu PDF | Onglet « Compte rendu » + `compte-rendu.html` imprimable |
| Consultation des CR de ses praticiens (soi-même ou autres visiteurs) | `comptes-rendus.html` avec colonne « Visiteur » |
| Responsable : visualiser les CR, rechercher par visiteur / praticien | `responsable.html`, filtres de `comptes-rendus.html` |
| Responsable : saisie des échantillons | `echantillons.html` + modale d'allocation |
| Activités complémentaires (thème libre, date, lieu, co-organisation) | `activites.html` + modale de création |
| Praticien identifié par spécialité + numéro séquentiel | Format `Cardiologie · #1042` partout |
| Un praticien = une seule visite du laboratoire | Bandeaux de règle métier + contrôle de saisie |
| Visites / AC limitées à la région du visiteur | Rappels dans les formulaires et listes filtrées « Région Sud » |
| Un praticien invité uniquement par un visiteur qui le concerne | Règle affichée dans la modale de création d'AC |
| Maquettage + documentation | Cette maquette + `styleguide.html` |
| Architecture MVC / PHP-MySQL / Laravel | Hors périmètre de la maquette front (à implémenter ensuite) |

---

## Choix de design

- **Positionnement** : institutionnel et rassurant (santé, données sensibles) mais chaleureux et contemporain.
- **Palette** : bleu profond `#0A2A33` (confiance), vert médical `#0FA98F` (action), menthe `#E8F7F3` (apaisement).
- **Typographie** : Inter, avec une échelle courte et des titres très serrés (`letter-spacing: -0.035em`).
- **Composants** : cartes à grand rayon (18 px), ombres douces, tableaux aérés, badges de statut colorés.
- **Iconographie** : sprite SVG maison, tracé 1,7 px, cohérent sur tous les écrans.
- **Responsive** : sidebar rétractable, grilles fluides, tableaux scrollables (breakpoints 1240 / 1024 / 860 / 520 px).
- **Accessibilité** : contrastes élevés, focus visibles, libellés de champs, `aria-*` sur les menus et onglets, alternatives textuelles sur les icônes.

---

## Interactions de la maquette

- Connexion → redirection selon le rôle choisi.
- Menu utilisateur et menu mobile fonctionnels.
- Filtres de recherche instantanés sur les tableaux et les fiches praticiens.
- Onglets (détail de visite, liste des visites).
- Ajout / suppression de tags produits, compteur d'échantillons.
- Récapitulatif de visite mis à jour en direct.
- Modales (suppression, création d'AC, allocation d'échantillons).
- Notifications *toast* et impression du compte rendu (`Ctrl+P`).

---

## Structure du dossier

```
maquette-lbc/
├── index.html
├── dashboard.html
├── visites.html
├── visite-nouvelle.html
├── visite-detail.html
├── compte-rendu.html
├── praticiens.html
├── activites.html
├── comptes-rendus.html
├── responsable.html
├── echantillons.html
├── styleguide.html
├── README.md
└── assets/
    ├── style.css      # design system et mise en page
    ├── layout.js      # sidebar, topbar, sprite d'icônes
    └── app.js         # interactions (onglets, filtres, modales, graphiques)
```

---

## Pistes pour l'intégration (PHP / Laravel)

1. Reprendre les tokens de `style.css` dans `resources/css/app.css` (ou Tailwind config).
2. Découper la sidebar et la topbar en composants Blade (`layouts/app.blade.php`).
3. Modéliser : `users`, `regions`, `praticiens`, `visites`, `produits`, `visite_produit`, `echantillons`, `activites`, `invitations`.
4. Contrôler les règles métier côté serveur (unicité praticien/visite, portefeuille, région, suppression conditionnelle).
5. Générer les PDF avec Dompdf ou Snappy depuis les vues du compte rendu.
6. Ajouter les tests fonctionnels (Pest / PHPUnit) sur les règles de gestion et les autorisations.

---

*Maquette réalisée pour un usage pédagogique — les données (praticiens, produits, chiffres) sont fictives.*
