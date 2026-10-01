<?php

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

if (! function_exists('money')) {
    /** Formato de moneda: $75,000.00 */
    function money(int|float|string|null $amount, bool $withCurrency = false): string
    {
        $formatted = '$'.number_format((float) $amount, 2, '.', ',');

        return $withCurrency ? $formatted.' MXN' : $formatted;
    }
}

if (! function_exists('fecha')) {
    /** Formato de fecha DD/MM/YYYY. */
    function fecha(CarbonInterface|string|null $date, string $empty = '—'): string
    {
        if (blank($date)) {
            return $empty;
        }

        return ($date instanceof CarbonInterface ? $date : Carbon::parse($date))->format('d/m/Y');
    }
}

if (! function_exists('fecha_hora')) {
    function fecha_hora(CarbonInterface|string|null $date, string $empty = '—'): string
    {
        if (blank($date)) {
            return $empty;
        }

        return ($date instanceof CarbonInterface ? $date : Carbon::parse($date))->format('d/m/Y H:i');
    }
}

if (! function_exists('hectareas')) {
    function hectareas(int|float|string|null $value, bool $suffix = true): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        $number = (float) $value;
        $formatted = number_format($number, fmod($number, 1.0) == 0.0 ? 0 : 2, '.', ',');

        return $suffix ? $formatted.' ha' : $formatted;
    }
}

if (! function_exists('fecha_relativa')) {
    /** "Hoy", "Mañana", "Hace 3 días", "En 5 días". */
    function fecha_relativa(CarbonInterface|string|null $date): string
    {
        if (blank($date)) {
            return '';
        }

        $date = ($date instanceof CarbonInterface ? $date : Carbon::parse($date))->copy()->startOfDay();
        $days = (int) today()->diffInDays($date, false);

        return match (true) {
            $days === 0 => 'Hoy',
            $days === 1 => 'Mañana',
            $days === -1 => 'Ayer',
            $days < 0 => 'Hace '.abs($days).' días',
            default => 'En '.$days.' días',
        };
    }
}
