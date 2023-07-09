<?php

declare(strict_types=1);

namespace App\Service\RatesSources;

use Generator;
use Throwable;

interface RatesSource
{
    /**
     * @return Generator
     * @throws Throwable
     */
    public function GetRates(): Generator;
}