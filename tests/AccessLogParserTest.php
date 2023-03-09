<?php

declare(strict_types=1);

namespace FarPost\TestCase\Tests;

use DateInterval;
use DateTime;
use FarPost\TestCase\Generators\UnitTestAccessLogGenerator;
use FarPost\TestCase\Parsers\AccessLogParser;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../vendor/autoload.php';

class AccessLogParserTest extends TestCase
{
    private string $accessLogFilePath;
    private DateTime $accessLogDate;
    private AccessLogParser $parser;

    protected function setUp(): void
    {
        $this->accessLogFilePath = __DIR__ . '/../storage/unit-test-access.log';
        $this->accessLogDate = DateTime::createFromFormat('d.m.Y H:i:s', '01.03.2023 20:30:00');
        $this->parser = new AccessLogParser();

        $accessLogGenerator = new UnitTestAccessLogGenerator($this->generateTestData());
        $accessLogGenerator->run($this->accessLogFilePath);
    }

    protected function tearDown(): void
    {
        unlink($this->accessLogFilePath);
    }

    /**
     * @dataProvider logParserDataProvider
     */
    public function testLogParser(string $expectedPeriodStart, string $expectedPeriodEnd, float $expectedSuccessRate, float $failureThreshold, int $failureDuration): void
    {
        try {
            $handle = fopen($this->accessLogFilePath, 'rb');
            $this->parser->run($handle, $failureThreshold, $failureDuration);
        } finally {
            fclose($handle);
        }

        $this->assertGreaterThan(0, count($this->parser->getPeriods()));
        [$periodStart, $periodEnd, $percent] = $this->parser->getPeriods()[0];
        $this->assertEquals($expectedPeriodStart, $periodStart);
        $this->assertEquals($expectedPeriodEnd, $periodEnd);
        $this->assertEquals($expectedSuccessRate, $percent);
    }

    private function logParserDataProvider(): array
    {
        return [
            [
                '20:30:01', '20:30:01', 80, 95, 25
            ],
            [
                '20:30:01', '20:30:01', 95, 97, 31
            ],
            [
                '20:30:01', '20:30:01', 98, 99, 55
            ],
            [
                '20:30:01', '20:30:01', 97, 99, 50
            ],
            [
                '20:30:01', '20:30:01', 96, 99, 45
            ],
        ];
    }

    private function generateTestData(): array
    {
        $result = array_merge([], array_pad([], 3, $this->formatLogLine(200, 25)));
        $this->moveAccessLogDateBySecond();

        $result = array_merge($result, array_pad([], 80, $this->formatLogLine(200, 25)));
        $result = array_merge($result, array_pad([], 2, $this->formatLogLine(500, 10)));
        $result = array_merge($result, array_pad([], 1, $this->formatLogLine(200, 50)));
        $result = array_merge($result, array_pad([], 1, $this->formatLogLine(200, 45)));
        $result = array_merge($result, array_pad([], 1, $this->formatLogLine(200, 55)));
        $result = array_merge($result, array_pad([], 15, $this->formatLogLine(200, 30)));
        $this->moveAccessLogDateBySecond();

        return $result;
    }

    private function formatLogLine(int $status, float $duration): string
    {
        return sprintf('- - - %s - - - - %s - %s', $this->accessLogDate->format('d/m/Y:H:i:s'), $status, $duration) . PHP_EOL;
    }

    private function moveAccessLogDateBySecond(): void
    {
        $this->accessLogDate->add(new DateInterval('PT1S'));
    }
}