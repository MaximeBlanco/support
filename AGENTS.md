# Support — notes pour un agent

Application de ticketing interne. Laravel 13 · PHP 8.5 · Livewire 4 · Tailwind 4 · MySQL,
le tout sous Docker via Laravel Sail. Aucun package tiers au-delà de Livewire : les rôles,
les permissions et le cycle de vie sont écrits à la main.

## Lancer le projet

```sh
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate:fresh --seed
./vendor/bin/sail npm run build
```

L'application répond sur http://localhost:8080, les mails sur http://localhost:8025.

Il n'y a ni PHP ni Composer sur l'hôte : **toute** commande passe par le conteneur
(`sail artisan …`, `sail composer …`, `sail npm …`, `sail php vendor/bin/…`).

## Où vit quoi

| Dossier | Contenu |
| --- | --- |
| `app/Enums` | `TicketStatus` (table des transitions), `TicketPriority` (délais cibles), `Permission`, `RoleName` |
| `app/Actions/Tickets` | Une classe par transition métier. Toute écriture de statut passe par là |
| `app/Models/Builders` | `TicketBuilder` — périmètres de visibilité, filtres, tri |
| `app/Policies` | `TicketPolicy` — décide sur des permissions, jamais sur un nom de rôle |
| `app/Livewire` | Composants de page. Ils appellent les actions, ils ne rejouent pas les règles |
| `lang/fr` | Tout le texte affiché. Rien de visible n'est écrit en dur dans le code |

## Règles que le code respecte

- **Le statut ne change que par une action.** `TransitionTicketStatus` valide la transition
  contre l'enum, écrit l'historique et diffuse l'event. Une transition illégale lève
  `IllegalTicketTransition`, qui porte son code HTTP 409.
- **Les modèles restent fins** : `fillable`, `casts`, relations. Aucune logique métier.
- **La visibilité est appliquée dans la requête** (`Ticket::query()->visibleTo($user)`),
  jamais en filtrant une collection déjà chargée.
- **Les accès se décident par permission**, pas par rôle. Le rôle ne fait que regrouper.
- **Aucun texte utilisateur en dur** : tout passe par `lang/fr`.
- **Pas de N+1** : la liste tient en 4 requêtes quel que soit le nombre de lignes, et un
  test le vérifie. `Model::preventLazyLoading()` est actif en local.
- Pas d'Observer : on réagit par events et listeners, enregistrés dans `AppServiceProvider`.

## Tests

```sh
./vendor/bin/sail artisan test
```

`tests/Unit` ne touche jamais la base (enums purs). `tests/Feature` couvre le cycle de vie,
les périmètres, les composants Livewire et l'authentification. Le trait
`Tests\Concerns\CreatesUsers` fournit `requester()`, `technician()` et `manager()` après un
`seedRoles()`.
