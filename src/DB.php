<?php

declare(strict_types=1);

namespace Velt\Database;

use Closure;
use LogicException;
use PDO;
use Throwable;
use Velt\Database\Cache\DatabaseCacheInterface;
use Velt\Database\Cache\NullDatabaseCache;
use Velt\Database\Query\QueryBuilder;

final class DB
{
    // Manager statique pour accès global au gestionnaire de base de données
    private static ?DatabaseManager $manager = null;

    private static ?DatabaseCacheInterface $cache = null;

    //Enregistre le manager de base de données pour utilisation via la façade DB.
     
    public static function setManager(DatabaseManager $manager): void
    {
        self::$manager = $manager;
    }

    // Réinitialise le manager (utile pour les tests).
    
    public static function clearManager(): void
    {
        self::$manager = null;
    }

    public static function setCache(DatabaseCacheInterface $cache): void
    {
        self::$cache = $cache;
    }

    public static function clearCache(): void
    {
        self::$cache = null;
    }

    public static function cache(): DatabaseCacheInterface
    {
        return self::$cache ??= new NullDatabaseCache();
    }

    public static function table(string $table, ?string $connection = null): QueryBuilder
    {
        return new QueryBuilder($table, $connection);
    }

    /**
     * /Exécute une requête SELECT et retourne tous les résultats.
     * 
     * @param array<int, mixed> $bindings Valeurs à binder aux placeholders (?)
     * @return array<int, array<string, mixed>> Liste des lignes trouvées
     */
    public static function select(string $sql, array $bindings = [], ?string $connection = null): array
    {
        // Prépare et exécute la requête
        $statement = self::prepareAndExecute($sql, $bindings, $connection);
        
        // Récupère tous les résultats
        $rows = $statement->fetchAll();

        // Retourne un tableau vide si aucun résultat
        return is_array($rows) ? $rows : [];
    }

    /**
     * Exécute une requête SELECT et retourne la première ligne ou null.
     * 
     * @param array<int, mixed> $bindings Valeurs à binder aux placeholders (?)
     * @return array<string, mixed>|null La première ligne ou null
     */
    public static function first(string $sql, array $bindings = [], ?string $connection = null): ?array
    {
        // Prépare et exécute la requête
        $statement = self::prepareAndExecute($sql, $bindings, $connection);
        
        // Récupère une seule ligne
        $row = $statement->fetch();

        // Retourne null si pas de résultat
        return is_array($row) ? $row : null;
    }

    /**
     * Exécute une requête INSERT, UPDATE ou DELETE.
     * 
     * @param array<int, mixed> $bindings Valeurs à binder aux placeholders (?)
     * @return int Nombre de lignes affectées
     */
    public static function statement(string $sql, array $bindings = [], ?string $connection = null): int
    {
        // Prépare et exécute la requête
        $statement = self::prepareAndExecute($sql, $bindings, $connection);

        // Retourne le nombre de lignes affectées
        return $statement->rowCount();
    }

    /**
     * Exécute un bloc de code dans une transaction.
     * La transaction commite si le callback réussit, sinon elle fait un rollback.
     */
    public static function transaction(callable $callback, ?string $connection = null): mixed
    {
        // Récupère la connexion PDO
        $pdo = self::pdo($connection);

        // Si une transaction est déjà active, exécute directement le callback
        if ($pdo->inTransaction()) {
            return $callback();
        }

        // Démarre une nouvelle transaction
        $pdo->beginTransaction();

        try {
            // Exécute le callback
            $result = $callback();
            
            // Valide la transaction si tout s'est bien passé
            $pdo->commit();

            return $result;
        } catch (Throwable $e) {
            // Annule la transaction en cas d'erreur
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * @param array<int, mixed> $bindings
     */
    private static function prepareAndExecute(string $sql, array $bindings, ?string $connection): \PDOStatement
    {
        $statement = self::pdo($connection)->prepare($sql);
        $statement->execute($bindings);

        return $statement;
    }

    public static function connection(?string $connection = null): PDO
    {
        if (self::$manager === null) {
            throw new LogicException('DB manager is not configured. Call DB::setManager() first.');
        }

        return self::$manager->connection($connection);
    }

    private static function pdo(?string $connection): PDO
    {
        return self::connection($connection);
    }
}
