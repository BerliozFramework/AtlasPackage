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

use Atlas\Mapper\Record;
use Atlas\Orm\Atlas;
use Atlas\Transit\Handler\HandlerLocator;
use Atlas\Transit\Transit;
use Berlioz\Package\Atlas\Exception\RepositoryException;
use Berlioz\Package\Atlas\Repository\RepositoryInterface;
use Berlioz\ServiceContainer\ContainerAwareInterface;
use Berlioz\ServiceContainer\ContainerAwareTrait;
use Exception;

class EntityManager extends Transit implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * EntityManager constructor.
     *
     * @param Atlas $atlas
     * @param HandlerLocator $handlerLocator
     */
    public function __construct(
        Atlas $atlas,
        HandlerLocator $handlerLocator,
    ) {
        parent::__construct($atlas, $handlerLocator);
    }

    /**
     * Attach a domain object to plan.
     *
     * @param object $domain
     *
     * @return static
     * @throws \Atlas\Transit\Exception
     */
    public function attach(object $domain): EntityManager
    {
        $handler = $this->handlerLocator->get($domain);

        /** @var Record $record */
        $record = $handler->updateSource($domain, $this->plan);
        $record->getRow()->init('');

        return $this;
    }

    /**
     * Get repository.
     *
     * @param string $class
     *
     * @return RepositoryInterface
     * @throws RepositoryException
     */
    public function getRepository(string $class): RepositoryInterface
    {
        try {
            $repository = $this->container->call($class, ['entityManager' => $this]);

            if (!$repository instanceof RepositoryInterface) {
                throw new RepositoryException('Not a valid repository');
            }

            return $repository;
        } catch (RepositoryException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new RepositoryException(sprintf('Unable to instance repository class "%s"', $class), previous: $e);
        }
    }
}