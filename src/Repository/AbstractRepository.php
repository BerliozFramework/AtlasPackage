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

namespace Berlioz\Package\Atlas\Repository;

use Berlioz\Core\CoreAwareInterface;
use Berlioz\Core\CoreAwareTrait;
use Berlioz\Package\Atlas\EntityManagerAwareInterface;
use Berlioz\Package\Atlas\EntityManagerAwareTrait;

abstract class AbstractRepository implements EntityManagerAwareInterface, CoreAwareInterface, RepositoryInterface
{
    use CoreAwareTrait;
    use EntityManagerAwareTrait;
}