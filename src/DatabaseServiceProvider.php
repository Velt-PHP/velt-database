<?php

declare(strict_types=1);

namespace Velt\Database;

use Velt\Database\Cache\FileDatabaseCache;
use Velt\Database\Cache\NullDatabaseCache;
use Velt\Kernel\Contracts\ConfigRepositoryInterface;
use Velt\Kernel\Contracts\ContainerInterface;
use Velt\Kernel\ServiceProvider;

final class DatabaseServiceProvider extends ServiceProvider
{
    //Enregistre le DatabaseManager dans le container. Utilise la résolution lazy: le manager n'est créé que lorsqu'il est demandé.
    
    public function register(): void
    {
        $container = $this->app->container();

        // Enregistre le DatabaseManager en tant que singleton (une seule instance)
        $container->singleton(DatabaseManager::class, function (ContainerInterface $container): DatabaseManager {
            // Récupère le repository de configuration depuis le container
            /** @var ConfigRepositoryInterface $config */
            $config = $container->get(ConfigRepositoryInterface::class);

            // Retourne une nouvelle instance du gestionnaire de base de données
            $manager = new DatabaseManager($config, new ConnectionFactory());
            DB::setManager($manager);
            DB::setCache($this->createCache($config));

            return $manager;
        });
    }

    private function createCache(ConfigRepositoryInterface $config): \Velt\Database\Cache\DatabaseCacheInterface
    {
        if ($this->app->isTesting() || $config->get('database.cache.enabled', false) !== true) {
            return new NullDatabaseCache();
        }

        $path = (string) $config->get(
            'database.cache.path',
            $this->app->basePath() . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'database-cache',
        );

        return new FileDatabaseCache($path);
    }
}
