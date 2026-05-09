# Issue 03 - Creer BaseModel MVP

## Labels

`module:1-foundations`, `area:database`, `type:feature`, `priority:p2`, `status:ready`

## Objectif

Creer un model de base tres simple pour aider les features MVP sans promettre un ORM complet.

## API cible

```php
class User extends Model
{
    protected static string $table = 'users';
}

User::find(1);
User::all();
User::create(['name' => 'Ada']);
```

## Travail attendu

- Creer `Model`.
- Supporter `find`, `all`, `create`.
- Permettre de definir `$table`.
- Utiliser le helper `DB` en interne.

## Contraintes

- Pas de relations dans le Module 1.
- Pas de dirty checking.
- Pas de scopes.
- Pas de mass assignment avance.

## Criteres d'acceptation

- Un model enfant peut definir sa table.
- `find` utilise une requete preparee.
- `create` insere les champs fournis.
- Les limites du model MVP sont documentees.

## Definition of Done

- BaseModel implemente.
- Tests SQLite.
- Documentation avec avertissement : ce n'est pas Eloquent.

