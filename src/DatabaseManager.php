<?php

declare(strict_types=1);

namespace Velt\Database;

use PDO;
use Velt\Database\Contracts\ConfigRepositoryInterface;
use Velt\Database\Exceptions\DatabaseConfigurationException;

final class DatabaseManager
{
    // Cache des connexions PDO ouvertes pour éviter les reconnexions inutiles
    /** @var array<string, PDO> */
    private array $connections = [];

    public function __construct(
        private readonly ConfigRepositoryInterface $config,
        private readonly ConnectionFactory $factory = new ConnectionFactory(),
    ) {
    }

    /**
     * Récupère ou crée une connexion PDO par nom.
     * Les connexions sont mises en cache pour la durée de l'exécution.
     */
    public function connection(?string $name = null): PDO
    {
        // Utilise la connexion par défaut si aucun nom n'est fourni
        $connectionName = $name ?? $this->defaultConnectionName();

        // Retourne la connexion du cache si elle existe déjà
        if (isset($this->connections[$connectionName])) {
            return $this->connections[$connectionName];
        }

        // Récupère la configuration de la connexion du repository
        $connectionConfig = $this->config->get("database.connections.{$connectionName}");

        // Valide que la configuration existe et est un tableau
        if (!is_array($connectionConfig)) {
            throw new DatabaseConfigurationException(sprintf('Database connection "%s" is not configured.', $connectionName));
        }

        // Crée la connexion PDO
        $pdo = $this->factory->create($connectionConfig);
        
        // Met en cache la connexion pour les prochains appels
        $this->connections[$connectionName] = $pdo;

        return $pdo;
    }

    /**
     * Récupère le nom de la connexion par défaut depuis la configuration.
     */
    private function defaultConnectionName(): string
    {
        // Récupère la clé 'database.default' de la configuration
        $default = $this->config->get('database.default');

        // Valide que la configuration est présente et non vide
        if (!is_string($default) || $default === '') {
            throw new DatabaseConfigurationException('Database default connection is not configured at "database.default".');
        }

        return $default;
    }
}
