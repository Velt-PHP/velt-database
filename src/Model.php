<?php

declare(strict_types=1);

namespace Velt\Database;

use InvalidArgumentException;
use LogicException;

abstract class Model
{
    // Définir le nom de la table dans les classes enfants
    protected static string $table = '';

    /**
     * Trouve un enregistrement par ID.
     * 
     * @return array<string, mixed>|null La ligne trouvée ou null
     */
    public static function find(int|string $id): ?array
    {
        // Récupère le nom de la table
        $table = static::tableName();

        // Exécute une requête SELECT avec WHERE id = ?
        return DB::first(sprintf('SELECT * FROM %s WHERE id = ? LIMIT 1', $table), [$id]);
    }

    /**
     * Récupère tous les enregistrements de la table.
     * 
     * @return array<int, array<string, mixed>> Liste de tous les enregistrements
     */
    public static function all(): array
    {
        // Récupère le nom de la table
        $table = static::tableName();

        // Exécute une requête SELECT * sans condition
        return DB::select(sprintf('SELECT * FROM %s', $table));
    }

    /**
     * Crée un nouvel enregistrement avec les attributs fournis.
     * 
     * @param array<string, mixed> $attributes Couples clé-valeur à insérer
     * @return int Nombre de lignes insérées (normalement 1)
     */
    public static function create(array $attributes): int
    {
        // Valide qu'au moins un attribut est fourni
        if ($attributes === []) {
            throw new InvalidArgumentException('Model::create() requires at least one attribute.');
        }

        // Récupère le nom de la table
        $table = static::tableName();
        
        // Récupère les noms des colonnes
        $columns = array_keys($attributes);
        
        // Crée les placeholders (?) pour les valeurs
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        
        // Construit la liste des colonnes
        $columnList = implode(', ', $columns);

        // Exécute l'INSERT avec les valeurs en order
        return DB::statement(
            sprintf('INSERT INTO %s (%s) VALUES (%s)', $table, $columnList, $placeholders),
            array_values($attributes),
        );
    }

    /**
     * Récupère et valide le nom de la table.
     */
    private static function tableName(): string
    {
        // Valide que la classe enfant a défini le nom de la table
        if (static::$table === '') {
            throw new LogicException(sprintf('%s must define a non-empty static $table property.', static::class));
        }

        return static::$table;
    }
}
