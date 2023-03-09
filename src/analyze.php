<?php

declare(strict_types=1);

namespace FarPost\TestCase;

$appDir = __DIR__;
require_once $appDir . '/../vendor/autoload.php';

use FarPost\TestCase\Parsers\AccessLogParser;
use InvalidArgumentException;

$options = getopt('u:t:');

if (!isset($options['u']) || (float)$options['u'] === 0.0) {
    throw new InvalidArgumentException('Failure threshold not specified');
}

if (!isset($options['t']) || (int)$options['t'] === 0) {
    throw new InvalidArgumentException('Failure interval not specified');
}

$failureThreshold = (float)$options['u'];
$failureInterval = (int)$options['t'];

$parser = new AccessLogParser();

try {
    $parser->run(STDIN, $failureThreshold, $failureInterval);

    foreach ($parser->getPeriods() as $period) {
        print sprintf('%s %s %s', $period[0], $period[1], $period[2]) . PHP_EOL;
    }

    $alloc_mem = round(memory_get_usage(true) / 1024);
    $mem_peak = round(memory_get_peak_usage() / 1024);
    print sprintf('Memory usage: allocated %sKB, peak %sKB', $alloc_mem, $mem_peak) . PHP_EOL;
}
finally {
    fclose(STDIN);
}
