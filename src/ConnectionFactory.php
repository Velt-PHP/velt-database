<?php

declare(strict_types=1);

namespace Velt\Database;

use PDO;
use Velt\Database\Exceptions\DatabaseConfigurationException;
use Velt\Database\Exceptions\UnknownDatabaseDriverException;

final class ConnectionFactory
{
    /**
     * Crée une connexion PDO configurée avec les options appropriées.
     * 
     * @param array<string, mixed> $connection Configuration de la connexion (driver, host, database, etc.)
     */
    public function create(array $connection): PDO
    {
        // Récupère et valide le driver de base de données
        $driver = $this->readDriver($connection);
        
        // Construit le DSN selon le driver
        $dsn = $this->buildDsn($connection);

        // Pour SQLite, pas d'authentification; pour les autres drivers, récupère les credentials
        $username = $driver === 'sqlite' ? null : (string) ($connection['username'] ?? '');
        $password = $driver === 'sqlite' ? null : (string) ($connection['password'] ?? '');

        // Retourne une instance PDO configurée pour lever des exceptions en cas d'erreur
        return new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    /**
     * Construit le DSN (Data Source Name) pour PDO selon le driver.
     * 
     * @param array<string, mixed> $connection Configuration de la connexion
     */
    public function buildDsn(array $connection): string
    {
        // Récupère le driver spécifié
        $driver = $this->readDriver($connection);

        // Route vers la bonne méthode de construction selon le driver
        return match ($driver) {
            'sqlite' => $this->buildSqliteDsn($connection),
            'mysql' => $this->buildMysqlDsn($connection),
            'pgsql' => $this->buildPgsqlDsn($connection),
            default => throw new UnknownDatabaseDriverException(sprintf('Unsupported database driver "%s".', $driver)),
        };
    }

    /**
     * Récupère et valide la clé 'driver' de la configuration.
     * 
     * @param array<string, mixed> $connection Configuration de la connexion
     */
    private function readDriver(array $connection): string
    {
        // Récupère le driver, par défaut une chaîne vide
        $driver = (string) ($connection['driver'] ?? '');

        // Valide que le driver est défini
        if ($driver === '') {
            throw new DatabaseConfigurationException('Missing required database key "driver".');
        }

        return $driver;
    }

    /**
     * Construit le DSN SQLite.
     * Supporte ':memory:' pour les tests et les fichiers locaux.
     * 
     * @param array<string, mixed> $connection Configuration de la connexion
     */
    private function buildSqliteDsn(array $connection): string
    {
        // Gère le cas spécial de la base de données en mémoire
        if (($connection['database'] ?? null) === ':memory:') {
            return 'sqlite::memory:';
        }

        // Récupère le chemin du fichier de base de données
        $database = (string) ($connection['database'] ?? '');

        // Valide que le fichier est spécifié
        if ($database === '') {
            throw new DatabaseConfigurationException('Missing required database key "database" for sqlite connection.');
        }

        return sprintf('sqlite:%s', $database);
    }

    /**
     * @param array<string, mixed> $connection
     */
    private function buildMysqlDsn(array $connection): string
    {
        $host = (string) ($connection['host'] ?? '127.0.0.1');
        $port = (string) ($connection['port'] ?? '3306');
        $database = (string) ($connection['database'] ?? '');
        $charset = (string) ($connection['charset'] ?? 'utf8mb4');

        if ($database === '') {
            throw new DatabaseConfigurationException('Missing required database key "database" for mysql connection.');
        }

        return sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $host, $port, $database, $charset);
    }

    /**
     * @param array<string, mixed> $connection
     */
    private function buildPgsqlDsn(array $connection): string
    {
        $host = (string) ($connection['host'] ?? '127.0.0.1');
        $port = (string) ($connection['port'] ?? '5432');
        $database = (string) ($connection['database'] ?? '');

        if ($database === '') {
            throw new DatabaseConfigurationException('Missing required database key "database" for pgsql connection.');
        }

        return sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $database);
    }
}
