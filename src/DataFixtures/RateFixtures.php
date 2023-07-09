<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Currency;
use App\Entity\Rate;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class RateFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $eur = (new Currency())->setName('EUR');
        $manager->persist($eur);
        $usd = (new Currency())->setName('USD');
        $manager->persist($usd);
        $rub = (new Currency())->setName('RUB');
        $manager->persist($rub);
        $btc = (new Currency())->setName('BTC');
        $manager->persist($btc);

        $rateEURUSDOld = (new Rate())
            ->setCurrencyFrom($eur)
            ->setCurrencyTo($usd)
            ->setRate(2)
            ->setDatetime(\DateTime::createFromFormat('Y-m-d H:i:s', '2023-01-01 00:00:00'));
        $manager->persist($rateEURUSDOld);
        $rateEURUSDNew = (new Rate())
            ->setCurrencyFrom($eur)
            ->setCurrencyTo($usd)
            ->setRate(10)
            ->setDatetime(\DateTime::createFromFormat('Y-m-d H:i:s', '2023-06-01 00:00:00'));
        $manager->persist($rateEURUSDNew);

        $rateUSDBTCOld = (new Rate())
            ->setCurrencyFrom($usd)
            ->setCurrencyTo($btc)
            ->setRate(0.0001)
            ->setDatetime(\DateTime::createFromFormat('Y-m-d H:i:s', '2023-01-01 00:00:00'));
        $manager->persist($rateUSDBTCOld);
        $rateUSDBTCNew = (new Rate())
            ->setCurrencyFrom($usd)
            ->setCurrencyTo($btc)
            ->setRate(0.0002)
            ->setDatetime(\DateTime::createFromFormat('Y-m-d H:i:s', '2023-06-01 00:00:00'));
        $manager->persist($rateUSDBTCNew);

        $manager->flush();
    }
}
