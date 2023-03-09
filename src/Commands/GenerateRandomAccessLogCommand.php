<?php

declare(strict_types=1);

namespace FarPost\TestCase\Commands;

use FarPost\TestCase\Generators\AccessLogGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class GenerateRandomAccessLogCommand extends Command
{
    private const INPUT_ARGUMENT_DURATION = 'durationInSeconds';
    protected static $defaultName = 'access-log:generate';

    protected function configure()
    {
        $this->addArgument(self::INPUT_ARGUMENT_DURATION, InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Generating random access-log');

        $accessLogGenerator = new AccessLogGenerator((int)$input->getArgument(self::INPUT_ARGUMENT_DURATION));
        $accessLogGenerator->run(__DIR__ . '/../../storage/dummy-access.log');

        $output->writeln('Generation complete');

        return Command::SUCCESS;
    }
}