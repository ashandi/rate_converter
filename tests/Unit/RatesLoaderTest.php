<?php

namespace App\Tests\Unit;

use App\Entity\Rate;
use App\Repository\CurrencyRepository;
use App\Repository\RateRepository;
use App\Service\RatesLoader;
use App\Service\RatesSources\RatesSource;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RatesLoaderTest extends KernelTestCase
{
    public function testRatesLoading(): void
    {
        $ratesLoader = static::getContainer()->get(RatesLoader::class);

        $currencyRepo = static::getContainer()->get(CurrencyRepository::class);
        $eur = $currencyRepo->findOneBy(['name' => 'EUR']);
        $usd = $currencyRepo->findOneBy(['name' => 'USD']);

        $rate = 1.456;

        $source = $this->createMock(RatesSource::class);
        $source->expects(self::once())
            ->method('GetRates')
            ->will($this->generate([
                (new Rate())->setCurrencyFrom($usd)->setCurrencyTo($eur)->setRate($rate)->setDatetime(new \DateTime()),
            ]));

        $ratesLoader->setRatesSource($source);

        $ratesLoader->load();

        $rateRepo = static::getContainer()->get(RateRepository::class);
        $rateObject = $rateRepo->findOneBy(['currencyFrom' => $usd, 'currencyTo' => $eur], ['datetime' => 'DESC']);

        $this->assertNotNull($rateObject);
        $this->assertEquals($rate, $rateObject->getRate());
    }

    private function generate(array $yieldValues)
    {
        return $this->returnCallback(function() use ($yieldValues) {
            foreach ($yieldValues as $value) {
                yield $value;
            }
        });
    }
}
