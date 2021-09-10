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

namespace Berlioz\Package\Atlas\Command;

use Atlas\Cli\Config;
use Atlas\Cli\Exception;
use Atlas\Cli\Fsio;
use Atlas\Cli\Logger;
use Atlas\Cli\Skeleton;
use Berlioz\Cli\Core\Command\AbstractCommand;
use Berlioz\Cli\Core\Console\Environment;
use Berlioz\Config\Exception\ConfigException;

class SkeletonCommand extends AbstractCommand
{
    /**
     * @inheritdoc
     */
    public static function getDescription(): ?string
    {
        return 'Atlas ORM skeleton generation';
    }

    /**
     * @inheritdoc
     * @throws Exception
     * @throws ConfigException
     */
    public function run(Environment $env): int
    {
        $config = $this->getApp()->getConfig()->get('atlas.cli.config.input');
        $config['pdo'] = array_values($config['pdo']);

        $this->generateSkeleton($config);

        return 0;
    }

    /**
     * Generate ATLAS skeleton.
     *
     * @param array $config
     *
     * @throws Exception
     */
    public function generateSkeleton(array $config): void
    {
        $skeleton = new Skeleton(
            new Config($config),
            new Fsio(),
            new Logger()
        );

        print ($skeleton)();
    }
}