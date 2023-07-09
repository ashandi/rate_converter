<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\RatesLoader;
use App\Service\RatesSources\RatesSource;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Throwable;

#[AsCommand(
    name: 'app:load-rates-from-source',
    description: 'Loads currency conversion rates from a specified sources.',
)]
class LoadRatesFromSourceCommand extends Command
{
    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * @var RatesLoader
     */
    private RatesLoader $ratesLoader;

    /**
     * LoadRateCommand constructor.
     * @param ContainerInterface $container
     * @param RatesLoader $ratesLoader
     */
    public function __construct(ContainerInterface $container, RatesLoader $ratesLoader)
    {
        $this->container = $container;
        $this->ratesLoader = $ratesLoader;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('source', InputArgument::REQUIRED, 'Alias of the source of rates')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $sourceAlias = $input->getArgument('source');

        try {
            /** @var RatesSource $source */
            $source = $this->container->get($sourceAlias);
            $this->ratesLoader->setRatesSource($source);
            $this->ratesLoader->load();
        } catch (Throwable $e) {
            $io->error(sprintf(
                "Loading rates from the source %s has been failed. Reason: %s, Trace: %s",
                $sourceAlias,
                $e->getMessage(),
                $e->getTraceAsString(),
            ));
        }

        $io->success("Rates from the source {$sourceAlias} has been loaded.");

        return Command::SUCCESS;
    }
}
