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

use Atlas\Orm\AtlasBuilder;
use Atlas\Orm\Transaction\AutoCommit;
use Atlas\Pdo\Connection;
use Atlas\Pdo\ConnectionLocator;
use Berlioz\Core\Package\AbstractPackage;

class AtlasPackage extends AbstractPackage
{
    /** @var \Atlas\Orm\AtlasBuilder Atlas builder */
    private $atlasBuilder;
    /** @var \Atlas\Orm\Atlas Atlas */
    private $atlas;

    /**
     * Init Atlas package.
     *
     * @throws \Berlioz\Config\Exception\ConfigException
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function init()
    {
        // Register template path
        $this->registerTemplatePath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'resources']), 'Berlioz-AtlasPackage');

        // Get configuration
        $defaultPdoConfig = $this->getApp()->getConfig()->get('atlas.pdo.connection_locator.default', []);
        $atlasBuilderArgs = $this->getPdoArgs($defaultPdoConfig);

        // Instance atlas builder with default configuration of PDO
        $this->atlasBuilder = new AtlasBuilder(...$atlasBuilderArgs);

        // Add additional connections
        $connectionLocator = $this->atlasBuilder->getConnectionLocator();
        $this->addConnections($connectionLocator, 'read');
        $this->addConnections($connectionLocator, 'write');

        // Transaction class
        $transactionClass = (string) $this->getApp()->getConfig()->get('atlas.orm.atlas.transaction_class', AutoCommit::class);
        $this->atlasBuilder->setTransactionClass($transactionClass);

        // Factory
        $container = $this->getApp()->getServiceContainer();
        $factory = function ($class) use ($container) {
            if ($container->has($class)) {
                return $container->get($class);
            }

            return new $class();
        };
        $this->atlasBuilder->setFactory($factory);

        // Create instance of atlas and add to the service container
        $this->atlas = $this->atlasBuilder->newAtlas();
        $this->getApp()->getServiceContainer()->register('atlas', $this->atlas);

        // Log queries?
        $this->atlas->logQueries($this->getApp()->getConfig()->get('atlas.orm.atlas.log_queries', false));

        // Debug activate?
        if ($this->getApp()->getConfig()->get('berlioz.debug', false)) {
            $debugSection = new Debug\Atlas($this->getApp(), $this->atlas);
            $this->getApp()->getDebug()->addSection($debugSection);
        }
    }

    /**
     * @inheritdoc
     */
    public function getDefaultConfigFilename(): ?string
    {
        return implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'resources', 'config.default.json']);
    }

    /**
     * Add connections to connection locator.
     *
     * @param \Atlas\Pdo\ConnectionLocator $connectionLocator
     * @param string                       $type
     *
     * @throws \Berlioz\Config\Exception\ConfigException
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    private function addConnections(ConnectionLocator $connectionLocator, string $type)
    {
        $specs = $this->getApp()->getConfig()->get(sprintf('atlas.pdo.connection_locator.%s', $type), []);
        $method = 'set' . ucfirst($type) . 'Factory';

        foreach ($specs as $name => $spec) {
            $connectionArgs = $this->getPdoArgs($spec);
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
    private function getPdoArgs(array $spec)
    {
        return [
            $spec['dsn'] ?? null,
            $spec['username'] ?? null,
            $spec['password'] ?? null,
            $spec['options'] ?? [],
        ];
    }
}