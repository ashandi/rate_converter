<?php

namespace App\Tests\Unit;

use App\Entity\Rate;
use App\Repository\CurrencyRepository;
use App\Service\RatesSources\EcbRatesSource;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class EcbRatesSourceTest extends KernelTestCase
{
    public function testEcbRatesSource(): void
    {
        $output = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<gesmes:Envelope xmlns:gesmes="http://www.gesmes.org/xml/2002-08-01" xmlns="http://www.ecb.int/vocabulary/2002-08-01/eurofxref">
	<gesmes:subject>Reference rates</gesmes:subject>
	<gesmes:Sender>
		<gesmes:name>European Central Bank</gesmes:name>
	</gesmes:Sender>
	<Cube>
		<Cube time='2023-01-01'>
			<Cube currency='USD' rate='1.0888'/>
			<Cube currency='GBP' rate='156.01'/>
		</Cube>
	</Cube>
</gesmes:Envelope>
XML;

        $mockHttpClient = new MockHttpClient(new MockResponse($output));

        $container = static::getContainer();
        $currencyRepo = static::getContainer()->get(CurrencyRepository::class);

        $ecbRatesSource = new EcbRatesSource(
            $mockHttpClient,
            $currencyRepo,
            $container->get(LoggerInterface::class)
        );

        /** @var Rate $rate */
        foreach ($ecbRatesSource->GetRates() as $rate) {
            if ($rate->getCurrencyTo()->getName() == 'USD') {
                $this->assertEquals(1.0888, $rate->getRate());
                $this->assertEquals('2023-01-01 00:00:00', $rate->getDatetime()->format('Y-m-d H:i:s'));
            }
            if ($rate->getCurrencyTo()->getName() == 'GBP') {
                $this->assertEquals(156.01, $rate->getRate());
            }
            if ($rate->getCurrencyFrom()->getName() == 'GBP' && $rate->getCurrencyTo()->getName() == 'EUR') {
                $this->assertEquals(1 / 156.01, $rate->getRate());
            }
        }

        $gbp = $currencyRepo->findOneBy(['name' => 'GBP']);
        $this->assertNotNull($gbp);
    }

    public function testEcbRatesSource_IncorrectFormat(): void
    {
        $output = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<gesmes:Envelope xmlns:gesmes="http://www.gesmes.org/xml/2002-08-01" xmlns="http://www.ecb.int/vocabulary/2002-08-01/eurofxref">
	<gesmes:subject>Reference rates</gesmes:subject>
	<gesmes:Sender>
		<gesmes:name>European Central Bank</gesmes:name>
	</gesmes:Sender>
	<Cube incorrect_format="true">
		<Cube>
            <Cube>
                <Cube rate='1.0888'/>
                <Cube currency='GBP'/>
            </Cube>
		</Cube>
	</Cube>
</gesmes:Envelope>
XML;

        $mockHttpClient = new MockHttpClient(new MockResponse($output));

        $container = static::getContainer();
        $currencyRepo = static::getContainer()->get(CurrencyRepository::class);

        $ecbRatesSource = new EcbRatesSource(
            $mockHttpClient,
            $currencyRepo,
            $container->get(LoggerInterface::class)
        );

        $this->expectException(\Exception::class);

        /** @var Rate $rate */
        foreach ($ecbRatesSource->GetRates() as $rate) {
            ;
        }
    }
}
