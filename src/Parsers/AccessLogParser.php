<?php

declare(strict_types=1);

namespace FarPost\TestCase\Parsers;

use Generator;

class AccessLogParser
{
    private array $periods;

    private string $accumulationDate = '';
    private int $accumulatedRowsCount = 0;
    private int $accumulatedFailuresCount = 0;

    public function getPeriods(): array
    {
        return $this->periods;
    }

    /** @param resource $handle */
    public function run($handle, float $failureThreshold, float $failureDuration): void
    {
        $this->periods = [];

        $failureTimePeriodStart = '';
        $failureTimePeriodsCount = 0;
        $failureTimePeriodSum = 0;

        $isTimePeriodChanged = fn (string $date): bool => $date !== $this->accumulationDate;
        $calculateFailurePercent = fn (): float => $this->accumulatedFailuresCount / ($this->accumulatedRowsCount / 100);
        $isFailurePercentExceedsThreshold = fn (float $failurePercent): bool => (100 - $failurePercent) > $failureThreshold;
        $isFailurePeriodCurrentlyActive = fn (string $periodStart): bool => $periodStart !== '';

        /** @var array $parts */
        foreach ($this->parseFile($handle) as $parts) {
            [$date, $status, $duration] = $parts;

            if ($isTimePeriodChanged($date)) {
                if ($this->accumulationDate !== '') {
                    $failurePercent = $calculateFailurePercent();


                    if ($isFailurePercentExceedsThreshold($failurePercent)) {
                        if (!$isFailurePeriodCurrentlyActive($failureTimePeriodStart)) {
                            $failureTimePeriodStart = $this->accumulationDate;
                        }

                        $failureTimePeriodsCount++;
                        $failureTimePeriodSum += $failurePercent;
                    } else if ($isFailurePeriodCurrentlyActive($failureTimePeriodStart)) {
                        $this->periods[] = [
                            $failureTimePeriodStart,
                            $this->accumulationDate,
                            number_format($failureTimePeriodSum / $failureTimePeriodsCount, 2),
                        ];

                        $failureTimePeriodStart = '';
                        $failureTimePeriodsCount = 0;
                        $failureTimePeriodSum = 0;
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
}