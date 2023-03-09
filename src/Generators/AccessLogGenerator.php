<?php

declare(strict_types=1);

namespace FarPost\TestCase\Generators;

use DateInterval;
use Faker\Factory;
use Faker\Generator as Faker;
use Generator;

class AccessLogGenerator extends AbstractAccessLogGenerator
{
    private Faker $faker;
    private int $durationInSeconds;

    private string $placeholder = '-';
    private string $timeZoneOffset = '+1000';
    private array $requestMethods = ['POST', 'PUT'];
    private string $baseUrlPath = '/rest/v1.4/documents?zone=default&_rid=';
    private string $requestVersion = 'HTTP/1.1';
    private int $successStatusCode = 200;
    private array $failureStatusCodes = [500, 501, 502, 503, 504, 505, 506, 507, 508, 509, 510, 511];
    private int $failureRequestDurationInMs = 50;

    public function __construct(int $durationInSeconds)
    {
        $this->faker = Factory::create('ru_RU');
        $this->durationInSeconds = $durationInSeconds;
    }

    protected function getNextRowGenerator(): Generator
    {
        $initialDate = $this->faker->dateTime();
        $interval = new DateInterval('PT1S');

        for ($i = 0; $i < $this->durationInSeconds; $i++) {
            $rowsPerSecond = $this->faker->numberBetween(5000, 7500);

            for ($j = 0; $j < $rowsPerSecond; $j++) {
                $isFailure = $this->faker->boolean(5);
                $statusCode = $this->successStatusCode;
                $requestDuration = $this->faker->numberBetween(1, $this->failureRequestDurationInMs);

                if ($isFailure) {
                    if ($this->faker->boolean()) {
                        $statusCode = $this->faker->randomElement($this->failureStatusCodes);
                    } else {
                        $requestDuration = $this->faker->numberBetween($this->failureRequestDurationInMs, 100);
                    }
                }

                yield sprintf(
                        '%s %s %s [%s %s] "%s %s %s" %s %s %s "%s" "%s" %s',
                        $this->faker->ipv4(),
                        $this->placeholder,
                        $this->placeholder,
                        $initialDate->format('d/m/Y:H:i:s'),
                        $this->timeZoneOffset,
                        $this->faker->randomElement($this->requestMethods),
                        $this->baseUrlPath . substr($this->faker->sha1(), 0, 8),
                        $this->requestVersion,
                        $statusCode,
                        '2',
                        $requestDuration,
                        $this->placeholder,
                        '@list-item-updater',
                        'prio:0'
                    ) . PHP_EOL;
            }

            $initialDate->add($interval);
        }
    }
}