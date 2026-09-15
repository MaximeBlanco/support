<h1 align="center">Support</h1>

<p align="center">
  Une application de ticketing interne — déposer une demande, suivre son avancement,
  et savoir qui s'en occupe.
</p>

<p align="center">
  <img alt="Laravel 13" src="https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white">
  <img alt="PHP 8.5" src="https://img.shields.io/badge/PHP-8.5-777BB4?logo=php&logoColor=white">
  <img alt="Livewire 4" src="https://img.shields.io/badge/Livewire-4-FB70A9?logo=livewire&logoColor=white">
  <img alt="Tailwind 4" src="https://img.shields.io/badge/Tailwind-4-06B6D4?logo=tailwindcss&logoColor=white">
  <img alt="Tests" src="https://img.shields.io/badge/tests-80%20passing-3FB950">
</p>

---

## Le domaine

Un **ticket** porte un objet, une description, un statut, une priorité, un demandeur et,
une fois pris en charge, un technicien. Il suit un cycle de vie strict, accumule des
commentaires, et garde la trace de chacun de ses changements d'état.

### Les statuts et leurs transitions

Toute transition absente de ce tableau est refusée **par le code**, pas seulement par
l'interface.

| Depuis | Transitions autorisées |
| --- | --- |
| `Ouvert` | `Assigné` |
| `Assigné` | `En cours`, `Ouvert` *(désassignation)* |
| `En cours` | `Résolu`, `Assigné` |
| `Résolu` | `Clôturé`, `En cours` *(réouverture)* |
| `Clôturé` | — |

### Les priorités et leur délai cible

| Priorité | Objectif de résolution |
| --- | --- |
| Basse | 72 h |
| Normale | 24 h |
| Haute | 8 h |
| Critique | 2 h |

À la résolution, un job compare le temps écoulé à l'objectif porté par la priorité et
marque le ticket comme tenu ou hors délai.

### Les trois profils

| Profil | Ce qu'il voit | Ce qu'il peut faire |
| --- | --- | --- |
| **Demandeur** | Ses propres tickets | Créer, commenter |
| **Technicien** | Les tickets qui lui sont assignés | Commenter, faire avancer le statut |
| **Responsable** | Tous les tickets | Assigner, réassigner, clôturer |

La restriction s'applique **dans la requête SQL**, pas après elle : un utilisateur ne
charge jamais une ligne qu'il n'a pas le droit de lire.

---

## Démarrer

Prérequis : Docker.

```sh
git clone https://github.com/MaximeBlanco/support.git
cd support
cp .env.example .env
```

```sh
docker compose up -d --build
```

```sh
docker compose exec laravel.test composer install
docker compose exec laravel.test php artisan key:generate
docker compose exec laravel.test php artisan migrate --seed
docker compose exec laravel.test npm install && docker compose exec laravel.test npm run build
```

| Service | Adresse |
| --- | --- |
| Application | http://localhost:8080 |
| Mailpit | http://localhost:8025 |

### Comptes de démonstration

Le mot de passe est `password` pour tous. La page de connexion propose les trois en un clic.

| Compte | Profil |
| --- | --- |
| `manager@support.test` | Responsable |
| `nadia@support.test` | Technicien |
| `maxime@support.test` | Demandeur |

---

## Ce qu'il y a dedans

- **Authentification écrite à la main** — connexion avec limitation de tentatives,
  inscription, déconnexion. Pas de starter kit.
- **Rôles et permissions maison** — trois rôles qui regroupent huit permissions. Les
  autorisations se décident toujours sur une permission, jamais sur un nom de rôle, pour
  qu'ajouter un profil demain ne demande pas de toucher au code.
- **Cycle de vie explicite** — une classe d'action par transition métier. Une transition
  illégale lève une exception nommée qui porte son code HTTP 409.
- **Historique complet** — chaque changement de statut est journalisé avec son auteur et
  restitué en timeline sur la fiche du ticket.
- **Notifications** — mail et cloche in-app à l'assignation et à la résolution.
- **Interface Livewire** — recherche temps réel, filtres, tri par colonne, pagination,
  thème clair/sombre, sans une ligne de JavaScript écrite à la main.
- **Aucun texte en dur** — tout l'affichage passe par les fichiers de langue.

---

## Tests

```sh
docker compose exec laravel.test php artisan test
```

80 tests, 185 assertions.

`tests/Unit` couvre les enums sans jamais toucher la base : la table des transitions y est
vérifiée exhaustivement, dans les deux sens. `tests/Feature` couvre le cycle de vie, les
périmètres de visibilité des trois profils, les composants Livewire et l'authentification.

Un test vérifie que la liste tient en un nombre de requêtes **constant**, quel que soit le
nombre de tickets affichés — c'est le garde-fou anti N+1.

---

## Structure

```
app/
├── Actions/Tickets/     une classe par transition métier
├── Enums/               statuts, priorités, permissions, rôles
├── Events/              TicketCreated, TicketAssigned, TicketResolved, TicketStatusChanged
├── Exceptions/          IllegalTicketTransition (409)
├── Jobs/                calcul du respect du délai cible
├── Listeners/           notifications
├── Livewire/            composants de page
├── Models/
│   └── Builders/        TicketBuilder — visibilité, filtres, tri
├── Notifications/
└── Policies/
lang/fr/                 tout le texte affiché
```
