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

/**
 * Class EntityManagerAwareTrait.
 *
 * Describes a entity manager-aware instance.
 *
 * @package Berlioz\Package\Atlas
 */
trait EntityManagerAwareTrait
{
    /** @var EntityManager Entity manager */
    protected $entityManager;

    /**
     * Get entity manager.
     *
     * @return EntityManager|null
     */
    public function getEntityManager(): ?EntityManager
    {
        return $this->entityManager;
    }

    /**
     * Set entity manager.
     *
     * @param EntityManager $entityManager
     *
     * @return static
     */
    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;

        return $this;
    }

    /**
     * Has entity manager?
     *
     * @return bool
     */
    public function hasEntityManager(): bool
    {
        return !is_null($this->entityManager);
    }
}