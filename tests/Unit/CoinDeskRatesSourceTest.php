<?php

namespace App\Tests\Unit;

use App\Entity\Rate;
use App\Repository\CurrencyRepository;
use App\Service\RatesSources\CoinDeskRatesSource;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class CoinDeskRatesSourceTest extends KernelTestCase
{
    public function testCoinDeskRatesSource(): void
    {
        $output = '{"time":{"updatedISO":"2023-01-01T12:13:00+00:00"},"bpi":{"USD":{"code":"CHY","rate_float":3020000.908},"GBP":{"code":"GBP","rate_float":25235.6372}}}';
        $mockHttpClient = new MockHttpClient(new MockResponse($output));

        $container = static::getContainer();
        $currencyRepo = static::getContainer()->get(CurrencyRepository::class);

        $coinDeskRatesSource = new CoinDeskRatesSource(
            $mockHttpClient,
            $currencyRepo,
            $container->get(LoggerInterface::class)
        );

        /** @var Rate $rate */
        foreach ($coinDeskRatesSource->GetRates() as $rate) {
            if ($rate->getCurrencyTo()->getName() == 'CHY') {
                $this->assertEquals(3020000.908, $rate->getRate());
                $this->assertEquals('2023-01-01 12:13:00', $rate->getDatetime()->format('Y-m-d H:i:s'));
            }
            if ($rate->getCurrencyTo()->getName() == 'GBP') {
                $this->assertEquals(25235.6372, $rate->getRate());
            }
            if ($rate->getCurrencyFrom()->getName() == 'CHY' && $rate->getCurrencyTo()->getName() == 'BTC') {
                $this->assertEquals(1 / 3020000.908, $rate->getRate());
            }
        }

        $chy = $currencyRepo->findOneBy(['name' => 'CHY']);
        $this->assertNotNull($chy);
    }

    public function testCoinDeskRatesSource_IncorrectFormat(): void
    {
        $output = '{"time":{"incorrectFormat":"2023-01-01T12:13:00+00:00"},"bpi":{"USD":{"currency":"CHY","rate":3020000.908},"GBP":{"code":"GBP","rate_float":25235.6372}}}';
        $mockHttpClient = new MockHttpClient(new MockResponse($output));

        $container = static::getContainer();
        $currencyRepo = static::getContainer()->get(CurrencyRepository::class);

        $coinDeskRatesSource = new CoinDeskRatesSource(
            $mockHttpClient,
            $currencyRepo,
            $container->get(LoggerInterface::class)
        );

        $this->expectException(\Exception::class);

        /** @var Rate $rate */
        foreach ($coinDeskRatesSource->GetRates() as $rate) {
            ;
        }
    }
}
