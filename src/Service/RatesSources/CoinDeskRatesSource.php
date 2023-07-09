<?php

declare(strict_types=1);

namespace App\Service\RatesSources;

use App\Entity\Rate;
use App\Repository\CurrencyRepository;
use Generator;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CoinDeskRatesSource implements RatesSource
{
    /**
     * @var HttpClientInterface
     */
    private HttpClientInterface $httpClient;

    /**
     * @var Serializer
     */
    private Serializer $serializer;

    /**
     * @var CurrencyRepository
     */
    private CurrencyRepository $currencyRepository;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;


    /**
     * CoinDeskRatesSource constructor.
     * @param HttpClientInterface $httpClient
     * @param CurrencyRepository $currencyRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        HttpClientInterface $httpClient,
        CurrencyRepository $currencyRepository,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->httpClient = $httpClient;
        $this->serializer = new Serializer([new GetSetMethodNormalizer()], [new JsonEncoder()]);
        $this->currencyRepository = $currencyRepository;
    }

    public function GetRates(): Generator
    {
        $url = 'https://api.coindesk.com/v1/bpi/currentprice.json';
        $response = $this->httpClient->request('GET', $url);

        if ($response->getStatusCode() != Response::HTTP_OK) {
            throw new \Exception(sprintf(
                "Request to %s returned non-200 response. Status code %d, Response Body: %s.",
                $url,
                $response->getStatusCode(),
                $response->getContent(),
            ));
        }

        $data = $this->serializer->decode($response->getContent(), 'json', ['object' => false,]);

        if (!$this->validate($data)) {
            throw new \Exception(sprintf(
                "Request to %s returned response in unknown format. Response Body: %s.",
                $url,
                $response->getContent(),
            ));
        }

        $dateTime = $data['time']['updatedISO'];
        $currencyFrom = $this->currencyRepository->getOrCreate('BTC');

        foreach ($data['bpi'] as $item) {
            yield (new Rate())
                ->setDatetime(\DateTime::createFromFormat(DATE_ATOM, $dateTime))
                ->setCurrencyFrom($currencyFrom)
                ->setCurrencyTo($this->currencyRepository->getOrCreate($item['code']))
                ->setRate($item['rate_float']);
            yield (new Rate())
                ->setDatetime(\DateTime::createFromFormat(DATE_ATOM, $dateTime))
                ->setCurrencyFrom($this->currencyRepository->getOrCreate($item['code']))
                ->setCurrencyTo($currencyFrom)
                ->setRate(1 / $item['rate_float']);
        }
    }

    private function validate(array $data): bool
    {
        if (
            isset($data['time'])
            && isset($data['time']['updatedISO'])
            && isset($data['bpi'])
        ) {
            $bpi = $data['bpi'];

            foreach ($bpi as $item) {
                if (
                    !isset($item['code'])
                    || !isset($item['rate_float'])
                ) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }
}