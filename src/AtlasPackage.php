<?php
/**
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2018 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

declare(strict_types=1);

namespace Berlioz\Package\Atlas;

use Atlas\Mapper\MapperLocator;
use Atlas\Mapper\MapperQueryFactory;
use Atlas\Orm\Atlas;
use Atlas\Orm\Transaction\AutoCommit;
use Atlas\Pdo\Connection;
use Atlas\Pdo\ConnectionLocator;
use Atlas\Table\TableLocator;
use Berlioz\Config\ExtendedJsonConfig;
use Berlioz\Core\Core;
use Berlioz\Core\Package\AbstractPackage;
use Berlioz\ServiceContainer\Service;

class AtlasPackage extends AbstractPackage
{
    /** @var \Berlioz\Package\Atlas\Debug\Atlas */
    private static $debugSection;
    ///////////////
    /// PACKAGE ///
    ///////////////

    /**
     * @inheritdoc
     * @throws \Berlioz\Config\Exception\ConfigException
     */
    public static function config()
    {
        return new ExtendedJsonConfig(
            implode(
                DIRECTORY_SEPARATOR, [
                __DIR__,
                '..',
                'resources',
                'config.default.json',
            ]
            ), true
        );
    }

    /**
     * @inheritdoc
     * @throws \Berlioz\Core\Exception\BerliozException
     * @throws \Berlioz\ServiceContainer\Exception\ContainerException
     */
    public static function register(Core $core): void
    {
        // Create connection locator service
        $connectionLocatorService = new Service(ConnectionLocator::class);
        $connectionLocatorService->setFactory(AtlasPackage::class . '::connectionLocatorFactory');
        self::addService($core, $connectionLocatorService);

        // Create atlas service
        $atlasService = new Service(Atlas::class, 'atlas');
        $atlasService->setFactory(AtlasPackage::class . '::atlasFactory');
        self::addService($core, $atlasService);

        // Create entity manager service
        $entityManagerService = new Service(EntityManager::class, 'entityManager');
        $entityManagerService->setFactory(AtlasPackage::class . '::transitFactory');
        self::addService($core, $entityManagerService);
    }

    /**
     * @inheritdoc
     * @throws \Berlioz\Config\Exception\ConfigException
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function init(): void
    {
        if ($this->getCore()->getConfig()->get('berlioz.debug', false)) {
            $this::$debugSection = new Debug\Atlas($this->getCore());
            $this->getCore()->getDebug()->addSection($this::$debugSection);
        }
    }

    /////////////////
    /// FACTORIES ///
    /////////////////

    /**
     * ConnectionLocator factory.
     *
     * @param \Berlioz\Core\Core $core
     *
     * @return \Atlas\Pdo\ConnectionLocator
     * @throws \Berlioz\Config\Exception\ConfigException
     */
    public static function connectionLocatorFactory(Core $core): ConnectionLocator
    {
        // Get configuration
        $defaultPdoConfig = $core->getConfig()->get('atlas.pdo.connection_locator.default', []);
        $pdoArgs = self::getPdoArgs($defaultPdoConfig);

        // Create connection locator
        $connectionLocator = ConnectionLocator::new(...$pdoArgs);

        // Add additional connections
        self::addConnections($core, $connectionLocator, 'read');
        self::addConnections($core, $connectionLocator, 'write');

        return $connectionLocator;
    }

    /**
     * Init Atlas package.
     *
     * @param \Berlioz\Core\Core $core
     *
     * @return \Atlas\Orm\Atlas
     * @throws \Berlioz\Config\Exception\ConfigException
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public static function atlasFactory(Core $core): Atlas
    {
        $connectionLocator = $core->getServiceContainer()->get(ConnectionLocator::class);

        // Factory
        $container = $core->getServiceContainer();
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
            self::$debugSection->setAtlas($atlas);
        }

        return $atlas;
    }

    /**
     * Add connections to connection locator.
     *
     * @param \Berlioz\Core\Core $core
     * @param \Atlas\Pdo\ConnectionLocator $connectionLocator
     * @param string $type
     *
     * @throws \Berlioz\Config\Exception\ConfigException
     */
    private static function addConnections(Core $core, ConnectionLocator $connectionLocator, string $type)
    {
        $specs = $core->getConfig()->get(sprintf('atlas.pdo.connection_locator.%s', $type), []);
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
    private static function getPdoArgs(array $spec)
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
     * @param \Berlioz\Core\Core $core
     *
     * @return \Berlioz\Package\Atlas\EntityManager
     * @throws \Berlioz\Config\Exception\ConfigException
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public static function transitFactory(Core $core)
    {
        /** @var \Atlas\Orm\Atlas $atlas */
        $atlas = $core->getServiceContainer()->get(Atlas::class);
        $transit = EntityManager::new($atlas, (string)$core->getConfig()->get('atlas.cli.config.input.namespace'));
        $transit->setCore($core);

        return $transit;
    }
}
