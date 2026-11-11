<?php

namespace App\Services\Pricing;

use RuntimeException;

/** Error de captura en el cálculo (se muestra al usuario asociado a un campo). */
class PricingException extends RuntimeException
{
    public function __construct(public readonly string $field, string $message)
    {
        parent::__construct($message);
    }
}
