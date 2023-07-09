<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Throwable;

#[AsCommand(
name:
'app:load-rates',
    description: 'Loads currency conversion rates from different sources.',
)]
class LoadRatesCommand extends Command
{

    private const CONSOLE_COMMAND = 'app:load-rates-from-source';

    protected function configure(): void
    {
        $this
            ->addArgument('sourceAliases', InputArgument::IS_ARRAY, 'Alias of all rates sources');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->info('Rates loading: start.');

        $sourceAliases = $input->getArgument('sourceAliases');

        if (empty($sourceAliases)) {
            $io->error('You need to set at least one rates source.');
            return Command::INVALID;
        }

        try {
            $this->loadRates($sourceAliases);
        } catch (Throwable $e) {
            $io->error(sprintf(
                "Rates loading has been failed. Reason: %s, Trace: %s",
                $e->getMessage(),
                $e->getTraceAsString(),
            ));
            return Command::FAILURE;
        }

        $io->success('Rates loading: finish.');

        return Command::SUCCESS;
    }

    /**
     * @param array $sourceAliases
     * @throw Throwable
     */
    private function loadRates(array $sourceAliases)
    {
        $processes = [];

        for ($i = 0; $i < count($sourceAliases); $i++) {
            $process = new Process(['php', 'bin/console', self::CONSOLE_COMMAND, $sourceAliases[$i]]);
            $process->start(function ($type, $buffer): void {
                if (Process::ERR === $type) {
                    echo 'ERR > ' . $buffer;
                } else {
                    echo 'OUT > ' . $buffer;
                }
            });
            $processes[$i] = $process;
        }

        $this->waitForAllProcessesDone($processes);
    }

    private function waitForAllProcessesDone(array $processes)
    {
        while (count($processes) > 0) {
            $process = $processes[0];
            $process->wait();
            array_splice($processes, 0, 1);
        }
    }
}
