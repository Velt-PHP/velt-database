# Issue 02 - Ajouter Query Helper securise

## Labels

`module:1-foundations`, `area:database`, `type:feature`, `type:tests`, `priority:p1`, `status:ready`

## Objectif

Fournir un helper simple pour executer des requetes preparees sans exposer PDO partout dans l'application.

## API cible

```php
DB::select('SELECT * FROM users WHERE email = ?', [$email]);
DB::statement('UPDATE users SET name = ? WHERE id = ?', [$name, $id]);
DB::transaction(function () {
    DB::statement('...');
});
```

## Travail attendu

- Creer facade ou helper `DB`.
- Ajouter `select`, `first`, `statement`, `transaction`.
- Utiliser uniquement des requetes preparees.
- Retourner des tableaux associatifs par defaut.

## Contraintes

- Ne pas creer un query builder complet dans cette issue.
- Ne pas accepter de concatenation SQL generee automatiquement.
- Documenter clairement que les placeholders sont obligatoires pour les valeurs dynamiques.

## Criteres d'acceptation

- `select` retourne plusieurs lignes.
- `first` retourne une ligne ou null.
- `statement` retourne succes/echec ou row count.
- `transaction` commit si tout va bien, rollback en cas d'exception.

## Definition of Done

- Helper implemente.
- Tests SQLite couvrant select, first, statement, transaction.
- README mis a jour.

