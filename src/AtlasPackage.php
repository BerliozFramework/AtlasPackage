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

use Atlas\Orm\Atlas;
use Atlas\Orm\AtlasBuilder;
use Atlas\Orm\Transaction\AutoCommit;
use Atlas\Pdo\Connection;
use Atlas\Pdo\ConnectionLocator;
use Berlioz\Core\Core;
use Berlioz\Core\Package\AbstractPackage;
use Berlioz\ServiceContainer\Service;

class AtlasPackage extends AbstractPackage
{
    /** @var \Berlioz\Package\Atlas\Debug\Atlas */
    private static $debugSection;

    /**
     * @inheritdoc
     * @throws \Berlioz\Core\Exception\BerliozException
     * @throws \Berlioz\ServiceContainer\Exception\ContainerException
     */
    public function register()
    {
        // Merge configuration
        $this->mergeConfig(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'resources', 'config.default.json']));

        // Create router service
        $atlasService = new Service(Atlas::class, 'atlas');
        $atlasService->setFactory(AtlasPackage::class . '::atlasFactory');
        $this->addService($atlasService);
    }

    /**
     * @inheritdoc
     * @throws \Berlioz\Config\Exception\ConfigException
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function init()
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
        // Get configuration
        $defaultPdoConfig = $core->getConfig()->get('atlas.pdo.connection_locator.default', []);
        $atlasBuilderArgs = self::getPdoArgs($defaultPdoConfig);

        // Instance atlas builder with default configuration of PDO
        $atlasBuilder = new AtlasBuilder(...$atlasBuilderArgs);

        // Add additional connections
        $connectionLocator = $atlasBuilder->getConnectionLocator();
        self::addConnections($core, $connectionLocator, 'read');
        self::addConnections($core, $connectionLocator, 'write');

        // Transaction class
        $transactionClass = (string) $core->getConfig()->get('atlas.orm.atlas.transaction_class', AutoCommit::class);
        $atlasBuilder->setTransactionClass($transactionClass);

        // Factory
        $container = $core->getServiceContainer();
        $factory = function ($class) use ($container) {
            if ($container->has($class)) {
                return $container->get($class);
            }

            return new $class();
        };
        $atlasBuilder->setFactory($factory);

        // Create instance of atlas
        $atlas = $atlasBuilder->newAtlas();

        // Log queries?
        $atlas->logQueries($core->getConfig()->get('atlas.orm.atlas.log_queries', false));

        // Debug activate?
        if ($core->getConfig()->get('berlioz.debug', false)) {
            self::$debugSection->setAtlas($atlas);
        }

        return $atlas;
    }

    /**
     * Add connections to connection locator.
     *
     * @param \Berlioz\Core\Core           $core
     * @param \Atlas\Pdo\ConnectionLocator $connectionLocator
     * @param string                       $type
     *
     * @throws \Berlioz\Config\Exception\ConfigException
     * @throws \Berlioz\Core\Exception\BerliozException
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
}