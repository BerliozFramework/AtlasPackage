<?php

declare(strict_types=1);

namespace Berlioz\Package\Atlas\Container;

use Atlas\Mapper\MapperLocator;
use Atlas\Mapper\MapperQueryFactory;
use Atlas\Orm\Atlas;
use Atlas\Orm\Transaction\AutoCommit;
use Atlas\Pdo\Connection;
use Atlas\Pdo\ConnectionLocator;
use Atlas\Table\TableLocator;
use Berlioz\Config\Config;
use Berlioz\Config\Exception\ConfigException;
use Berlioz\Core\Core;
use Berlioz\Package\Atlas\Debug\AtlasSection;
use Berlioz\Package\Atlas\EntityManager;
use Berlioz\Package\Atlas\EntityManagerAwareInterface;
use Berlioz\ServiceContainer\Container;
use Berlioz\ServiceContainer\Inflector\Inflector;
use Berlioz\ServiceContainer\Provider\AbstractServiceProvider;
use Berlioz\ServiceContainer\Service\Service;

class ServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [
        ConnectionLocator::class,
        Atlas::class,
        EntityManager::class,
        'atlas',
        'entityManager',
    ];

    /**
     * @inheritDoc
     */
    public function register(Container $container): void
    {
        // Create connection locator service
        $connectionLocatorService = new Service(
                     ConnectionLocator::class,
            factory: static::class . '::connectionLocatorFactory'
        );
        $container->addService($connectionLocatorService);

        // Create atlas service
        $atlasService = new Service(Atlas::class, 'atlas', factory: static::class . '::atlasFactory');
        $container->addService($atlasService);

        // Create entity manager service
        $entityManagerService = new Service(
            class:   EntityManager::class,
            alias:   'entityManager',
            factory: static::class . '::transitFactory'
        );
        $container->addService($entityManagerService);
    }

    /**
     * @inheritDoc
     */
    public function boot(Container $container): void
    {
        $container->addInflector(
            new Inflector(
                EntityManagerAwareInterface::class,
                'setEntityManager',
                ['entityManager' => '@entityManager']
            )
        );
    }

    /////////////////
    /// FACTORIES ///
    /////////////////

    /**
     * ConnectionLocator factory.
     *
     * @param Core $core
     *
     * @return ConnectionLocator
     * @throws ConfigException
     */
    public static function connectionLocatorFactory(Core $core): ConnectionLocator
    {
        // Get configuration
        $defaultPdoConfig = $core->getConfig()->get('atlas.pdo.connection_locator.default', []);
        $pdoArgs = self::getPdoArgs($defaultPdoConfig);

        // Create connection locator
        $connectionLocator = ConnectionLocator::new(...$pdoArgs);

        // Add additional connections
        self::addConnections($core->getConfig(), $connectionLocator, 'read');
        self::addConnections($core->getConfig(), $connectionLocator, 'write');

        return $connectionLocator;
    }

    /**
     * Init Atlas package.
     *
     * @param Core $core
     *
     * @return Atlas
     * @throws ConfigException
     */
    public static function atlasFactory(Core $core): Atlas
    {
        $container = $core->getContainer();
        $connectionLocator = $container->get(ConnectionLocator::class);

        // Factory
        $factory = function ($class) use ($container) {
            if ($container->has($class)) {
                return $container->get($class);
            }

            return new $class();
        };

        // Table locator
        $tableLocator = new TableLocator(
            $connectionLocator,
            new MapperQueryFactory(),
            $factory
        );

        // Transaction class
        $transactionClass = (string)$core->getConfig()->get('atlas.orm.atlas.transaction_class', AutoCommit::class);

        // Create Atlas object
        $atlas = new Atlas(
            new MapperLocator($tableLocator, $factory),
            new $transactionClass($connectionLocator)
        );

        // Log queries?
        $logQueries = $core->getConfig()->get('atlas.orm.atlas.log_queries', false);
        if (null === filter_var($logQueries, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)) {
            throw new ConfigException("'atlas.orm.atlas.log_queries' must be a boolean");
        }

        $atlas->logQueries((bool)$logQueries);

        // Debug activate?
        if ($core->getConfig()->get('berlioz.debug.enable', false)) {
            $core->getDebug()->addSection(new AtlasSection($atlas));
        }

        return $atlas;
    }

    /**
     * Add connections to connection locator.
     *
     * @param Config $config
     * @param ConnectionLocator $connectionLocator
     * @param string $type
     *
     * @throws ConfigException
     */
    private static function addConnections(Config $config, ConnectionLocator $connectionLocator, string $type)
    {
        $specs = $config->get(sprintf('atlas.pdo.connection_locator.%s', $type), []);
        $method = 'set' . ucfirst($type) . 'Factory';

        foreach ($specs as $name => $spec) {
            $connectionArgs = self::getPdoArgs($spec);
            $connectionLocator->$method($name, Connection::factory(...$connectionArgs));
        }
    }

    /**
     * Get PDO args.
     *
     * @param array $spec
     *
     * @return array
     */
    private static function getPdoArgs(array $spec): array
    {
        return [
            $spec['dsn'] ?? null,
            $spec['username'] ?? null,
            $spec['password'] ?? null,
            $spec['options'] ?? [],
        ];
    }

    /**
     * Atlas Transit factory.
     *
     * @param Core $core
     *
     * @return EntityManager
     * @throws ConfigException
     */
    public static function transitFactory(Core $core): EntityManager
    {
        /** @var Atlas $atlas */
        $atlas = $core->getContainer()->get(Atlas::class);

        return EntityManager::new($atlas, (string)$core->getConfig()->get('atlas.cli.config.input.namespace'));
    }
}