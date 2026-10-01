<?php

namespace App\Support;

/**
 * Normalización de teléfonos. México se guarda como 10 dígitos;
 * otros países como "+<código><número>".
 */
class Phone
{
    public static function normalize(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $raw = trim($raw);
        $digits = preg_replace('/\D+/', '', $raw);
        $international = str_starts_with($raw, '+') || str_starts_with($digits, '00');

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (! $international && strlen($digits) === 10) {
            return $digits;
        }

        if (strlen($digits) === 12 && str_starts_with($digits, '52')) {
            return substr($digits, 2);
        }

        if (strlen($digits) === 13 && str_starts_with($digits, '521')) {
            return substr($digits, 3);
        }

        if ($international && strlen($digits) >= 8 && strlen($digits) <= 15) {
            return '+'.$digits;
        }

        return null;
    }

    public static function isValid(?string $raw): bool
    {
        return self::normalize($raw) !== null;
    }

    public static function whatsappUrl(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $number = str_starts_with($phone, '+') ? substr($phone, 1) : '52'.$phone;

        return 'https://wa.me/'.$number;
    }

    public static function telUrl(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        return 'tel:'.(str_starts_with($phone, '+') ? $phone : '+52'.$phone);
    }

    public static function format(?string $phone): string
    {
        if (! $phone) {
            return '';
        }

        if (strlen($phone) !== 10 || ! ctype_digit($phone)) {
            return $phone;
        }

        // Monterrey, CDMX y Guadalajara usan lada de 2 dígitos.
        if (in_array(substr($phone, 0, 2), ['81', '55', '33'], true)) {
            return substr($phone, 0, 2).' '.substr($phone, 2, 4).' '.substr($phone, 6);
        }

        return substr($phone, 0, 3).' '.substr($phone, 3, 3).' '.substr($phone, 6);
    }
}
