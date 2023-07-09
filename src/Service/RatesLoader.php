<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Rate;
use App\Repository\RateRepository;
use App\Service\RatesSources\RatesSource;
use Psr\Log\LoggerInterface;
use Throwable;

class RatesLoader
{
    /**
     * @var RatesSource
     */
    private RatesSource $ratesSource;

    /**
     * @var RateRepository
     */
    private RateRepository $rateRepository;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * RatesLoader constructor.
     * @param RateRepository $rateRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        RateRepository $rateRepository,
        LoggerInterface $logger
    ) {
        $this->rateRepository = $rateRepository;
        $this->logger = $logger;
    }

    /**
     * @param RatesSource $ratesSource
     */
    public function setRatesSource(RatesSource $ratesSource): void
    {
        $this->ratesSource = $ratesSource;
    }

    /**
     * Method loads rates from the source and saves them to the database
     */
    public function load(): void
    {
        if ($this->ratesSource == null) {
            $this->logger->error("Load rates from source is not possible. Reason: source is not set.");
            return;
        }

        try {
            /** @var Rate $rate */
            foreach ($this->ratesSource->getRates() as $rate) {
                $this->rateRepository->save($rate);
            }

            $this->rateRepository->flush();
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                "Load rates from source has been failed. Reason: %s, Trace: %s",
                $e->getMessage(),
                $e->getTraceAsString(),
            ));
        }
    }
}
