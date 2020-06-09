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

namespace Berlioz\Package\Atlas\Debug;

use Berlioz\Core\Core;
use Berlioz\Core\CoreAwareInterface;
use Berlioz\Core\CoreAwareTrait;
use Berlioz\Core\Debug\AbstractSection;
use Berlioz\Core\Debug\Activity;
use Berlioz\Core\Debug\Section;
use Berlioz\Core\Exception\BerliozException;
use Countable;

class Atlas extends AbstractSection implements Section, Countable, CoreAwareInterface
{
    use CoreAwareTrait;

    /** @var \Atlas\Orm\Atlas|null Atlas ORM */
    private $atlas;
    /** @var array Queries */
    private $queries;

    /**
     * Atlas constructor.
     *
     * @param Core $core
     */
    public function __construct(Core $core)
    {
        $this->setCore($core);
    }

    /**
     * Set atlas.
     *
     * @param \Atlas\Orm\Atlas $atlas
     *
     * @return Atlas
     */
    public function setAtlas(\Atlas\Orm\Atlas $atlas): Atlas
    {
        $this->atlas = $atlas;

        return $this;
    }

    /////////////////////////
    /// SECTION INTERFACE ///
    /////////////////////////

    /**
     * @inheritdoc
     */
    public function __toString(): string
    {
        return var_export($this, true);
    }

    /**
     * @inheritdoc
     * @throws BerliozException
     */
    public function saveReport()
    {
        $debug = $this->getCore()->getDebug();

        if (!is_null($this->atlas)) {
            $this->queries = $this->atlas->getQueries();

            // Add queries to the timeline
            foreach ($this->queries as $query) {
                $activity =
                    (new Activity('Query', $this->getSectionName()))
                        ->start($query['start'])
                        ->end($query['finish'])
                        ->setDetail($query['statement']);
                $debug->getTimeLine()->addActivity($activity);
            }
        }
    }

    /**
     * Get section name.
     *
     * @return string
     */
    public function getSectionName(): string
    {
        return 'Atlas ORM';
    }

    /**
     * @inheritdoc
     */
    public function getTemplateName(): string
    {
        return '@Berlioz-AtlasPackage/Twig/Debug/atlas.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return serialize(['queries' => $this->queries]);
    }

    /**
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        $unserialized = unserialize($serialized);

        $this->queries = $unserialized['queries'] ?? [];
    }

    ///////////////////////////
    /// COUNTABLE INTERFACE ///
    ///////////////////////////

    /**
     * @inheritdoc
     */
    public function count()
    {
        return count($this->getQueries());
    }

    ////////////////////
    /// USER DEFINED ///
    ////////////////////

    /**
     * Get queries.
     *
     * @return array
     */
    public function getQueries(): array
    {
        return $this->queries ?? [];
    }

    /**
     * Get total duration.
     *
     * @return float
     */
    public function getDuration(): float
    {
        if (empty($this->queries)) {
            return 0;
        }

        $duration =
            array_reduce(
                $this->queries,
                function ($time, $query) {
                    return $time + $query['duration'];
                }
            );

        return floatval($duration);
    }
}