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
use Berlioz\CliCore\Command\AbstractCommand;
use Berlioz\Config\Exception\ConfigException;
use Berlioz\Core\App\AppAwareInterface;
use Berlioz\Core\App\AppAwareTrait;
use Berlioz\Core\Core;
use Berlioz\Core\Exception\BerliozException;
use GetOpt\GetOpt;

class SkeletonCommand extends AbstractCommand implements AppAwareInterface
{
    use AppAwareTrait;

    /** @var Skeleton Skeleton */
    private $skeleton;

    /**
     * SkeletonCommand constructor.
     *
     * @param Core $core
     *
     * @throws Exception
     * @throws ConfigException
     * @throws BerliozException
     */
    public function __construct(Core $core)
    {
        $config = $core->getConfig()->get('atlas.cli.config.input');
        $config['pdo'] = array_values($config['pdo']);

        $this->skeleton = new Skeleton(
            new Config($config),
            new Fsio(),
            new Logger()
        );
    }

    /**
     * @inheritdoc
     */
    public static function getShortDescription(): ?string
    {
        return 'Atlas ORM skeleton generation';
    }

    /**
     * @inheritdoc
     */
    public function run(GetOpt $getOpt)
    {
        print ($this->skeleton)();
    }
}