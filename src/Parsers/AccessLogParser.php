<?php

declare(strict_types=1);

namespace FarPost\TestCase\Parsers;

use DateTime;
use Generator;

class AccessLogParser
{
    private array $periods;

    private string $accumulationDate = '';
    private int $accumulatedRowsCount = 0;
    private int $accumulatedFailuresCount = 0;

    private string $failureTimePeriodStart = '';
    private int $failureTimePeriodsCount = 0;
    private float $failureTimePeriodsSum = 0;

    public function getPeriods(): array
    {
        return $this->periods;
    }

    /** @param resource $handle */
    public function run($handle, float $failureThreshold, float $failureDuration): void
    {
        $this->periods = [];

        $isTimePeriodChanged = fn (string $date): bool => $date !== $this->accumulationDate;
        $calculateFailurePercent = fn (): float => $this->accumulatedFailuresCount / ($this->accumulatedRowsCount / 100);
        $isFailurePercentExceedsThreshold = fn (float $failurePercent): bool => (100 - $failurePercent) < $failureThreshold;
        $isFailurePeriodCurrentlyActive = fn (): bool => $this->failureTimePeriodStart !== '';

        /** @var array $parts */
        foreach ($this->parseFile($handle) as $parts) {
            [$date, $status, $duration] = $parts;

            if ($isTimePeriodChanged($date)) {
                if ($this->accumulationDate !== '') {
                    $failurePercent = $calculateFailurePercent();

                    if ($isFailurePercentExceedsThreshold($failurePercent)) {
                        if (!$isFailurePeriodCurrentlyActive()) {
                            $this->failureTimePeriodStart = $this->accumulationDate;
                        }

                        $this->failureTimePeriodsCount++;
                        $this->failureTimePeriodsSum += $failurePercent;
                    } else if ($isFailurePeriodCurrentlyActive()) {
                        $this->storeFailureTimePeriod();
                        $this->clearFailureTimePeriod();
                    }
                }

                $this->accumulationDate = $date;
                $this->accumulatedRowsCount = 0;
                $this->accumulatedFailuresCount = 0;
            }

            if ($status !== 200 || $duration > $failureDuration) {
                $this->accumulatedFailuresCount++;
            }

            $this->accumulatedRowsCount++;
        }

        if ($isFailurePeriodCurrentlyActive()) {
            $this->storeFailureTimePeriod();
        }
    }

    /** @param resource $handle */
    private function parseFile($handle): Generator
    {
        while ($line = fgets($handle)) {
            $parts = explode(' ', trim($line));

            yield [
                ltrim($parts[3], '['),
                (int)$parts[8],
                (float)$parts[10],
            ];
        }
    }

    private function storeFailureTimePeriod(): void
    {
        $this->periods[] = [
            $this->formatFailureTime($this->failureTimePeriodStart),
            $this->formatFailureTime($this->accumulationDate),
            number_format(100 - ($this->failureTimePeriodsSum / $this->failureTimePeriodsCount), 2),
        ];
    }

    private function clearFailureTimePeriod(): void
    {
        $this->failureTimePeriodStart = '';
        $this->failureTimePeriodsCount = 0;
        $this->failureTimePeriodsSum = 0;
    }

    private function formatFailureTime(string $date): string
    {
        return DateTime::createFromFormat('d/m/Y:H:i:s', $date)->format('H:i:s');
    }
}