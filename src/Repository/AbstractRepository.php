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

use Berlioz\Core\Core;
use Berlioz\Core\CoreAwareInterface;
use Berlioz\Core\CoreAwareTrait;
use Berlioz\Package\Atlas\EntityManager;
use Berlioz\Package\Atlas\EntityManagerAwareInterface;
use Berlioz\Package\Atlas\EntityManagerAwareTrait;

/**
 * Class AbstractRepository.
 *
 * @package Berlioz\Package\Atlas\Repository
 */
abstract class AbstractRepository implements EntityManagerAwareInterface, CoreAwareInterface, RepositoryInterface
{
    use CoreAwareTrait;
    use EntityManagerAwareTrait;

    /**
     * AbstractRepository constructor.
     *
     * @param \Berlioz\Core\Core $core
     * @param \Berlioz\Package\Atlas\EntityManager $entityManager
     */
    public function __construct(Core $core, EntityManager $entityManager)
    {
        $this->setCore($core);
        $this->setEntityManager($entityManager);
    }
}