<?php

declare(strict_types=1);

namespace App\Validation;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

class ConversionRequest
{
    #[Assert\NotBlank]
    #[Assert\Type('float')]
    #[Assert\GreaterThan(0)]
    private float $amount;

    #[Assert\NotBlank]
    #[Assert\Type('int')]
    #[Assert\GreaterThan(0)]
    private int $currencyFromId;

    #[Assert\NotBlank]
    #[Assert\Type('int')]
    #[Assert\GreaterThan(0)]
    private int $currencyToId;

    /**
     * ConversionRequest constructor.
     * @param array $requestParams
     */
    public function __construct(array $requestParams)
    {
        $this->amount = isset($requestParams['amount']) ? floatval($requestParams['amount']) : 0;
        $this->currencyFromId = isset($requestParams['currency_from_id']) ? intval($requestParams['currency_from_id']) : 0;
        $this->currencyToId = isset($requestParams['currency_to_id']) ? intval($requestParams['currency_to_id']) : 0;
    }

    /**
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * @return int
     */
    public function getCurrencyFromId(): int
    {
        return $this->currencyFromId;
    }

    /**
     * @return int
     */
    public function getCurrencyToId(): int
    {
        return $this->currencyToId;
    }


}