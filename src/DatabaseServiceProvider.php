<?php

declare(strict_types=1);

namespace Velt\Database;

use Velt\Database\Contracts\ConfigRepositoryInterface;
use Velt\Database\Contracts\ContainerInterface;

final class DatabaseServiceProvider
{
    /**
     * Enregistre le DatabaseManager dans le container.
     * Utilise la résolution lazy: le manager n'est créé que lorsqu'il est demandé.
     */
    public function register(ContainerInterface $container): void
    {
        // Enregistre le DatabaseManager en tant que singleton (une seule instance)
        $container->singleton(DatabaseManager::class, function (ContainerInterface $container): DatabaseManager {
            // Récupère le repository de configuration depuis le container
            /** @var ConfigRepositoryInterface $config */
            $config = $container->get(ConfigRepositoryInterface::class);

            // Retourne une nouvelle instance du gestionnaire de base de données
            return new DatabaseManager($config, new ConnectionFactory());
        });
    }
}
