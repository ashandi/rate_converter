<?php

declare(strict_types=1);

namespace App\Service\RatesSources;

use App\Entity\Rate;
use App\Repository\CurrencyRepository;
use Generator;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class EcbRatesSource implements RatesSource
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
     * EcbRatesSource constructor.
     * @param HttpClientInterface $httpClient
     * @param CurrencyRepository $currencyRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        HttpClientInterface $httpClient,
        CurrencyRepository $currencyRepository,
        LoggerInterface $logger
    )
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->serializer = new Serializer([new GetSetMethodNormalizer()], [new XmlEncoder()]);
        $this->currencyRepository = $currencyRepository;
    }

    /**
     * @return Generator
     * @throws Throwable
     */
    public function GetRates(): Generator
    {
        $url = 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml';
        $response = $this->httpClient->request('GET', $url);

        if ($response->getStatusCode() != Response::HTTP_OK) {
            throw new \Exception(sprintf(
                "Request to %s returned non-200 response. Status code %d, Response Body: %s.",
                $url,
                $response->getStatusCode(),
                $response->getContent(),
            ));
        }

        $data = $this->serializer->decode($response->getContent(), 'xml', ['object' => false,]);

        if (!$this->validate($data)) {
            throw new \Exception(sprintf(
                "Request to %s returned response in unknown format. Response Body: %s.",
                $url,
                $response->getContent(),
            ));
        }

        $dateTime = $data['Cube']['Cube']['@time'];
        $currencyFrom = $this->currencyRepository->getOrCreate('EUR');

        foreach ($data['Cube']['Cube']['Cube'] as $item) {
            yield (new Rate())
                ->setDatetime(\DateTime::createFromFormat('Y-m-d', $dateTime)->setTime(0, 0, 0))
                ->setCurrencyFrom($currencyFrom)
                ->setCurrencyTo($this->currencyRepository->getOrCreate($item['@currency']))
                ->setRate($item['@rate']);
            yield (new Rate())
                ->setDatetime(\DateTime::createFromFormat('Y-m-d', $dateTime)->setTime(0, 0, 0))
                ->setCurrencyFrom($this->currencyRepository->getOrCreate($item['@currency']))
                ->setCurrencyTo($currencyFrom)
                ->setRate(1 / $item['@rate']);
        }
    }

    private function validate(array $data): bool
    {
        if (
            isset($data['Cube'])
            && isset($data['Cube']['Cube'])
            && isset($data['Cube']['Cube']['Cube'])
            && isset($data['Cube']['Cube']['@time'])
        ) {
            $cubes = $data['Cube']['Cube']['Cube'];

            foreach ($cubes as $cube) {
                if (
                    !isset($cube['@currency'])
                    || !isset($cube['@rate'])
                ) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }
}