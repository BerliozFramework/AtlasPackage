<?php
/*
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2021 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

declare(strict_types=1);

namespace Berlioz\Package\Atlas\Debug;

use Atlas\Orm\Atlas;
use Berlioz\Core\Debug\AbstractSection;
use Berlioz\Core\Debug\DebugHandler;
use Countable;
use Doctrine\SqlFormatter\NullHighlighter;
use Doctrine\SqlFormatter\SqlFormatter;

class AtlasSection extends AbstractSection implements Countable
{
    private array $queries = [];

    /**
     * Atlas constructor.
     */
    public function __construct(private Atlas $atlas)
    {
    }

    /**
     * @inheritDoc
     */
    public function snap(DebugHandler $debug): void
    {
        $this->queries = $this->atlas?->getQueries() ?? [];
        $sqlFormatter = new SqlFormatter(new NullHighlighter());

        // Add queries to the timeline
        foreach ($this->queries as $query) {
            $activity = $debug->newActivity('Query', $this->getSectionName());
            $activity
                ->start($query['start'])
                ->end($query['finish'])
                ->setDetail($sqlFormatter->format($query['statement']));
        }
    }

    public function __toString(): string
    {
        return var_export($this, true);
    }

    /**
     * PHP serialize method.
     *
     * @return array
     */
    public function __serialize(): array
    {
        return ['queries' => $this->queries];
    }

    /**
     * PHP unserialize method.
     *
     * @param array $data
     */
    public function __unserialize(array $data): void
    {
        $this->queries = $data['queries'] ?? [];
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->queries);
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
     * Get template name.
     */
    public function getTemplateName(): string
    {
        return '@Berlioz-AtlasPackage/Twig/Debug/atlas.html.twig';
    }

    /**
     * Get queries.
     *
     * @return array
     */
    public function getQueries(): array
    {
        return $this->queries;
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

        $duration = array_reduce(
            $this->queries,
            fn($time, array $query) => $time + ($query['finish'] - $query['start'])
        );

        return floatval($duration);
    }
}