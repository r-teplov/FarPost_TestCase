<?php

declare(strict_types=1);

namespace FarPost\TestCase\Generators;

use Generator;

abstract class AbstractAccessLogGenerator
{
    abstract protected function getNextRowGenerator(): Generator;

    public function run(string $pathToFile): void
    {
        $handle = fopen($pathToFile, 'wb+');

        try {
            foreach ($this->getNextRowGenerator() as $row) {
                fwrite($handle, $row);
            }
        } finally {
            fclose($handle);
        }
    }
}