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
  <img alt="Tests" src="https://img.shields.io/badge/tests-152%20passing-3FB950">
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

**Le seul prérequis est Docker.** Ni PHP, ni Composer, ni Node à installer.

```sh
git clone https://github.com/MaximeBlanco/support.git
cd support
docker compose up -d --build
```

C'est tout. Au premier démarrage le conteneur installe les dépendances, crée le
fichier `.env` et sa clé, migre la base, la remplit avec le jeu de démonstration
et compile les assets. **Comptez cinq à dix minutes**, le temps de construire
l'image et de télécharger les dépendances.

Pour suivre la préparation :

```sh
docker compose logs -f app
```

Quand `[support] ready — http://localhost:8080` s'affiche, l'application est prête.

| Service | Adresse |
| --- | --- |
| **Application** | **http://localhost:8080** |
| Mailpit — tous les mails envoyés | http://localhost:8025 |
| Reverb — le websocket temps réel | ws://localhost:8081 |

Le `docker compose up` démarre aussi un worker de file d'attente et Reverb : sans
eux les notifications ne partiraient pas et la liste ne se mettrait pas à jour
toute seule.

Pour tout arrêter : `docker compose down`. Pour repartir d'une base neuve :
`docker compose down -v` puis `docker compose up -d`.

### Comptes de démonstration

Tous les comptes ont le même mot de passe : **`password`**. La page de connexion
propose les trois profils en un clic, il n'y a rien à taper.

| Compte | Profil | Ce qu'il voit en se connectant |
| --- | --- | --- |
| **`manager@support.test`** | Responsable | Les 26 tickets, peut assigner, clôturer et importer |
| **`nadia@support.test`** | Technicien | Seulement les tickets qui lui sont assignés |
| **`maxime@support.test`** | Demandeur | Seulement les siens, peut en créer |

Cinq autres comptes existent pour que les tickets aient des interlocuteurs
crédibles : `sofia@` (responsable), `thomas@` et `ines@` (techniciens),
`julie@`, `karim@`, `lea@`, `antoine@` et `fatou@` (demandeurs).

> **Commencez par `manager@support.test`** : c'est le seul profil qui voit tout.
> Connectez-vous ensuite en demandeur pour constater que le périmètre se referme.

### Ce que contient la démonstration

26 tickets écrits à la main — pas de texte généré — répartis sur les cinq statuts
et les quatre priorités, avec de vraies conversations entre demandeurs et
techniciens, un historique daté pour chacun, et des dates d'ouverture étalées sur
plusieurs semaines. Certains sont volontairement **hors délai**, de quoi voir
l'escalade automatique faire son travail :

```sh
docker compose exec app php artisan tickets:escalate
```

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

## Au-delà du socle

Six extensions prolongent l'application :

| | Extension | Ce que ça fait |
| --- | --- | --- |
| **E1** | Escalade automatique | Une commande horaire relève la priorité des tickets qui ont dépassé leur délai cible et prévient les responsables. Le test de retard dépend d'une colonne, donc la détection est **une requête par priorité** — quatre, quel que soit le volume — et relancer la commande n'escalade rien deux fois. |
| **E2** | Historisation | Un trait que le modèle déclare lui-même journalise les attributs qu'il choisit de suivre. Pas d'Observer : le comportement est visible là où on le cherche. Le même trait couvre `Comment` sans une ligne dédiée. Le journal est en écriture seule et purgé au-delà de sa rétention. |
| **E3** | Notifications multi-canal | Le canal dépend de la priorité : mail pour les basses, canal d'urgence en plus pour les hautes, et alerte aux responsables pour les critiques. Aucune condition sur la priorité dans le code appelant — chaque politique déclare ce dont elle répond, et une nouvelle est **un fichier, sans rien modifier**. |
| **E4** | Import CSV | Un job déroule le fichier à travers des étapes ordonnées. Une ligne invalide est collectée avec son numéro et l'import continue ; un fichier cassé l'interrompt et l'opérateur est prévenu. Mille lignes résolvent leur demandeur **en une requête**. |
| **E5** | Temps réel | Les events du domaine partent vers le navigateur — aucun event n'a été inventé pour l'écran. Chaque destinataire est calculé en rejouant la policy et joint sur **son propre canal privé**, donc le websocket ne peut pas laisser fuiter ce que la page aurait caché. |
| **E6** | Serveur MCP | Trois outils pour un agent IA — rechercher, lire, ouvrir un ticket — plus une ressource décrivant les règles de saisie. L'agent porte un jeton d'utilisateur, donc les périmètres s'appliquent : un agent connecté en demandeur ne voit que ses tickets. Aucune règle métier n'est réécrite dans un outil. |

## Tests

```sh
docker compose exec app php artisan test
```

152 tests, 355 assertions.

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
├── Broadcasting/        TicketAudience — qui a le droit d'entendre quoi
├── Console/Commands/    l'escalade planifiée
├── Enums/               statuts, priorités, permissions, rôles
├── Events/              les events du domaine, diffusés tels quels
├── Exceptions/          IllegalTicketTransition (409), et les autres
├── Imports/             le pipeline CSV, une classe par étape
├── Jobs/                délai de résolution, import en arrière-plan
├── Listeners/           notifications
├── Livewire/            composants de page
├── Mcp/                 serveur, outils et ressource pour un agent IA
├── Models/
│   ├── Builders/        TicketBuilder, UserBuilder — périmètres et filtres
│   └── Concerns/        RecordsAttributeChanges — le journal, sans Observer
├── Notifications/
│   └── Policies/        une politique de canal par niveau d'urgence
└── Policies/
lang/fr/                 tout le texte affiché
routes/ai.php            le serveur MCP et son authentification
routes/channels.php      l'autorisation des canaux privés
```
