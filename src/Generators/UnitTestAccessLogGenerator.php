<?php

declare(strict_types=1);

namespace FarPost\TestCase\Generators;

use Generator;

class UnitTestAccessLogGenerator extends AbstractAccessLogGenerator
{
    private array $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    protected function getNextRowGenerator(): Generator
    {
        foreach ($this->rows as $row) {
            yield $row;
        }
    }
}