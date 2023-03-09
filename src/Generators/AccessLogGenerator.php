<?php

declare(strict_types=1);

namespace FarPost\TestCase\Generators;

use DateInterval;
use Faker\Factory;
use Faker\Generator;

class AccessLogGenerator
{
    private Generator $faker;

    private string $placeholder = '-';
    private string $timeZoneOffset = '+1000';
    private array $requestMethods = ['POST', 'PUT'];
    private string $baseUrlPath = '/rest/v1.4/documents?zone=default&_rid=';
    private string $requestVersion = 'HTTP/1.1';
    private int $successStatusCode = 200;
    private array $failureStatusCodes = [500, 501, 502, 503, 504, 505, 506, 507, 508, 509, 510, 511];

    public function __construct()
    {
        $this->faker = Factory::create('ru_RU');
    }

    public function run(string $pathToFile, int $durationInSeconds): void
    {
        $initialDate = $this->faker->dateTime();
        $interval = new DateInterval('PT1S');
        $handle = fopen($pathToFile, 'wb+');

        try {
            for ($i = 0; $i < $durationInSeconds; $i++) {
                $rowsPerSecond = $this->faker->numberBetween(5000, 7500);

                for ($j = 0; $j < $rowsPerSecond; $j++) {
                    $entry = sprintf(
                            '%s %s %s [%s %s] "%s %s %s" %s %s %s "%s" "%s" %s',
                            $this->faker->ipv4(),
                            $this->placeholder,
                            $this->placeholder,
                            $initialDate->format('d/m/Y:H:i:s'),
                            $this->timeZoneOffset,
                            $this->faker->randomElement($this->requestMethods),
                            $this->baseUrlPath . substr($this->faker->sha1(), 0, 8),
                            $this->requestVersion,
                            $this->faker->boolean(95) ? $this->successStatusCode : $this->faker->randomElement($this->failureStatusCodes),
                            '2',
                            $this->faker->numberBetween(10000000, 100000000) / 1000000,
                            $this->placeholder,
                            '@list-item-updater',
                            'prio:0'
                        ) . PHP_EOL;

                    fwrite($handle, $entry);
                }

                $initialDate->add($interval);
            }
        } finally {
            fclose($handle);
        }
    }
}