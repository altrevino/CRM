<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Aritmética monetaria en centavos (enteros) para evitar errores de punto flotante.
 * Toda conversión a centavos redondea a dos decimales (mitad hacia arriba).
 */
class Money
{
    /** "1234.5" | 1234.5 → 123450 */
    public static function toCents(int|float|string|null $amount): int
    {
        if ($amount === null || $amount === '') {
            return 0;
        }
        if (! is_numeric($amount)) {
            throw new InvalidArgumentException("Importe inválido: {$amount}");
        }

        return self::scale((string) $amount, 2);
    }

    /** Cantidad con hasta dos decimales (hectáreas, kilómetros) → centésimas. */
    public static function toHundredths(int|float|string|null $quantity): int
    {
        return self::toCents($quantity);
    }

    public static function fromCents(int $cents): float
    {
        return round($cents / 100, 2);
    }

    /** Formato para guardar en columnas decimal(…,2). */
    public static function decimal(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $cents = abs($cents);

        return $sign.intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    /** cantidad (centésimas) × precio (centavos) → centavos, con un solo redondeo. */
    public static function multiply(int $quantityHundredths, int $priceCents): int
    {
        return self::divRound($quantityHundredths * $priceCents, 100);
    }

    /** base × porcentaje (p. ej. 16 o 12.5) → centavos redondeados. */
    public static function percent(int $baseCents, int|float|string $percent): int
    {
        $basisPoints = self::scale((string) $percent, 2); // 16 → 1600

        return self::divRound($baseCents * $basisPoints, 10000);
    }

    /** División entera con redondeo mitad hacia arriba (lejos de cero). */
    public static function divRound(int $numerator, int $denominator): int
    {
        $quotient = intdiv($numerator, $denominator);
        $remainder = $numerator % $denominator;

        if (abs($remainder) * 2 >= abs($denominator)) {
            $quotient += ($numerator < 0) !== ($denominator < 0) ? -1 : 1;
        }

        return $quotient;
    }

    /** Convierte un número decimal en texto a entero escalado (sin flotantes cuando es posible). */
    private static function scale(string $value, int $decimals): int
    {
        $value = trim($value);
        if (preg_match('/^(-)?(\d*)(?:\.(\d*))?$/', $value, $m)) {
            $negative = $m[1] === '-';
            $int = $m[2] === '' ? '0' : $m[2];
            $frac = $m[3] ?? '';
            $kept = str_pad(substr($frac, 0, $decimals), $decimals, '0');
            $result = (int) $int * (10 ** $decimals) + (int) $kept;
            // Redondeo con el siguiente dígito.
            if (strlen($frac) > $decimals && (int) $frac[$decimals] >= 5) {
                $result++;
            }

            return $negative ? -$result : $result;
        }

        // Notación científica u otros formatos numéricos.
        return (int) round((float) $value * (10 ** $decimals));
    }
}
