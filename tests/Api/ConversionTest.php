<?php

namespace App\Tests\Api;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\Repository\CurrencyRepository;
use App\Repository\RateRepository;
use Symfony\Component\HttpFoundation\Response;

class ConversionTest extends ApiTestCase
{
    private const URL = '/api/conversion';

    public function testWrongMethod(): void
    {
        $response = static::createClient()->request('GET', self::URL);

        $this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }

    public function testEmptyRequest(): void
    {
        $response = static::createClient()->request('POST', self::URL);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['success' => false]);
    }

    public function testInvalidAmount_NotFloat(): void
    {
        $response = static::createClient()->request(
            'POST',
            self::URL,
            [
                'json' => [
                    'amount' => 'incorrect_value',
                    'currency_from_id' => '1',
                    'currency_to_id' => '2',
                ],
            ],
        );

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['success' => false]);
    }

    public function testInvalidAmount_NegativeValue(): void
    {
        $response = static::createClient()->request(
            'POST',
            self::URL,
            [
                'json' => [
                    'amount' => '-1',
                    'currency_from_id' => '1',
                    'currency_to_id' => '2',
                ],
            ],
        );

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['success' => false]);
    }

    public function testInvalidCurrencyFrom_NotInteger(): void
    {
        $response = static::createClient()->request(
            'POST',
            self::URL,
            [
                'json' => [
                    'amount' => '100',
                    'currency_from_id' => 'incorrect_value',
                    'currency_to_id' => '2',
                ],
            ],
        );

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['success' => false]);
    }

    public function testInvalidCurrencyFrom_NegativeValue(): void
    {
        $response = static::createClient()->request(
            'POST',
            self::URL,
            [
                'json' => [
                    'amount' => '100',
                    'currency_from_id' => '-1',
                    'currency_to_id' => '2',
                ],
            ],
        );

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['success' => false]);
    }

    public function testInvalidCurrencyTo_NotInteger(): void
    {
        $response = static::createClient()->request(
            'POST',
            self::URL,
            [
                'json' => [
                    'amount' => '100',
                    'currency_from_id' => '1',
                    'currency_to_id' => 'incorrect_value',
                ],
            ],
        );

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['success' => false]);
    }

    public function testInvalidCurrencyTo_NegativeValue(): void
    {
        $response = static::createClient()->request(
            'POST',
            self::URL,
            [
                'json' => [
                    'amount' => '100',
                    'currency_from_id' => '1',
                    'currency_to_id' => '-1',
                ],
            ],
        );

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['success' => false]);
    }

    public function testSameCurrency(): void
    {
        $response = static::createClient()->request(
            'POST',
            self::URL,
            [
                'json' => [
                    'amount' => '100',
                    'currency_from_id' => '1',
                    'currency_to_id' => '1',
                ],
            ],
        );

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['success' => true, 'result' => 100]);
    }

    public function testDirectConversion(): void
    {
        $currencyRepo = static::getContainer()->get(CurrencyRepository::class);
        $eur = $currencyRepo->findOneBy(['name' => 'EUR']);
        $usd = $currencyRepo->findOneBy(['name' => 'USD']);
        $rateRepo = static::getContainer()->get(RateRepository::class);
        $rate = $rateRepo->findOneBy(['currencyFrom' => $eur, 'currencyTo' => $usd], ['datetime' => 'DESC']);

        $amount = 100;

        $response = static::createClient()->request(
            'POST',
            self::URL,
            [
                'json' => [
                    'amount' => $amount,
                    'currency_from_id' => $eur->getId(),
                    'currency_to_id' => $usd->getId(),
                ],
            ],
        );

        $this->assertResponseIsSuccessful();

        $responseBody = $response->toArray();
        $this->assertTrue($responseBody['success']);
        $this->assertEquals($amount * $rate->getRate(), $responseBody['result']);
    }

    public function testDirectConversion_NoRate(): void
    {
        $currencyRepo = static::getContainer()->get(CurrencyRepository::class);
        $eur = $currencyRepo->findOneBy(['name' => 'EUR']);
        $rub = $currencyRepo->findOneBy(['name' => 'RUB']);

        $amount = 100;

        $response = static::createClient()->request(
            'POST',
            self::URL,
            [
                'json' => [
                    'amount' => $amount,
                    'currency_from_id' => $eur->getId(),
                    'currency_to_id' => $rub->getId(),
                ],
            ],
        );

        $this->assertResponseIsSuccessful();

        $responseBody = $response->toArray();
        $this->assertFalse($responseBody['success']);
    }

    public function testConversionThroughThirdCurrency(): void
    {
        $currencyRepo = static::getContainer()->get(CurrencyRepository::class);
        $eur = $currencyRepo->findOneBy(['name' => 'EUR']);
        $usd = $currencyRepo->findOneBy(['name' => 'USD']);
        $btc = $currencyRepo->findOneBy(['name' => 'BTC']);
        $rateRepo = static::getContainer()->get(RateRepository::class);
        $rate1 = $rateRepo->findOneBy(['currencyFrom' => $eur, 'currencyTo' => $usd], ['datetime' => 'DESC']);
        $rate2 = $rateRepo->findOneBy(['currencyFrom' => $usd, 'currencyTo' => $btc], ['datetime' => 'DESC']);

        $amount = 100;

        $response = static::createClient()->request(
            'POST',
            self::URL,
            [
                'json' => [
                    'amount' => $amount,
                    'currency_from_id' => $eur->getId(),
                    'currency_to_id' => $btc->getId(),
                ],
            ],
        );

        $this->assertResponseIsSuccessful();

        $responseBody = $response->toArray();
        $this->assertTrue($responseBody['success']);
        $this->assertEquals($amount * $rate1->getRate() * $rate2->getRate(), $responseBody['result']);
    }
}
