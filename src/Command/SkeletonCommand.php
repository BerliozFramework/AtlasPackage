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
use Atlas\Cli\Fsio;
use Atlas\Cli\Logger;
use Atlas\Cli\Skeleton;
use Berlioz\CliCore\App\CliArgs;
use Berlioz\CliCore\Command\AbstractCommand;
use Berlioz\Core\App\AppAwareInterface;
use Berlioz\Core\App\AppAwareTrait;
use Berlioz\Core\Core;

class SkeletonCommand extends AbstractCommand implements AppAwareInterface
{
    use AppAwareTrait;
    /** @var \Atlas\Cli\Skeleton Skeleton */
    private $skeleton;

    /**
     * SkeletonCommand constructor.
     *
     * @param \Berlioz\Core\Core $core
     *
     * @throws \Atlas\Cli\Exception
     * @throws \Berlioz\Config\Exception\ConfigException
     * @throws \Berlioz\Core\Exception\BerliozException
     */
    public function __construct(Core $core)
    {
        $config = $core->getConfig()->get('atlas.cli.config.input');
        $config['pdo'] = array_values($config['pdo']);

        $this->skeleton = new Skeleton(new Config($config),
                                       new Fsio(),
                                       new Logger());
    }

    /**
     * Run command.
     *
     * @param \Berlioz\CliCore\App\CliArgs $args
     *
     * @return void
     */
    public function run(CliArgs $args)
    {
        print ($this->skeleton)();
    }
}