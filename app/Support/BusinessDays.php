<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Días hábiles: lunes a viernes, sin los días de descanso obligatorio del artículo 74
 * de la Ley Federal del Trabajo (incluye el 1 de octubre de cada sexenio).
 */
class BusinessDays
{
    /** Fecha que resulta de sumar $days días hábiles a $from (sin contar $from). */
    public static function add(CarbonInterface|string $from, int $days): Carbon
    {
        $date = Carbon::parse($from)->startOfDay();
        while ($days > 0) {
            $date->addDay();
            if (self::isBusinessDay($date)) {
                $days--;
            }
        }

        return $date;
    }

    /** Días hábiles desde hoy (sin contarlo) hasta $until (incluido). Hoy mismo hábil y vigente cuenta como 0. */
    public static function remaining(CarbonInterface|string $until, CarbonInterface|string|null $from = null): int
    {
        $date = Carbon::parse($from ?? today())->startOfDay();
        $until = Carbon::parse($until)->startOfDay();
        $count = 0;
        while ($date->lt($until)) {
            $date->addDay();
            if (self::isBusinessDay($date)) {
                $count++;
            }
        }

        return $count;
    }

    public static function isBusinessDay(CarbonInterface $date): bool
    {
        return ! $date->isWeekend() && ! in_array($date->format('m-d'), self::holidays($date->year), true);
    }

    /** @return list<string> Días feriados del año en formato m-d. */
    public static function holidays(int $year): array
    {
        $days = [
            '01-01',
            self::nthMonday($year, 2, 1),   // Constitución: primer lunes de febrero
            self::nthMonday($year, 3, 3),   // Natalicio de Benito Juárez: tercer lunes de marzo
            '05-01',
            '09-16',
            self::nthMonday($year, 11, 3),  // Revolución: tercer lunes de noviembre
            '12-25',
        ];

        // Transmisión del Poder Ejecutivo Federal (2024, 2030, 2036…).
        if ($year >= 2024 && ($year - 2024) % 6 === 0) {
            $days[] = '10-01';
        }

        return $days;
    }

    private static function nthMonday(int $year, int $month, int $nth): string
    {
        $date = Carbon::create($year, $month, 1)->startOfDay();
        while (! $date->isMonday()) {
            $date->addDay();
        }

        return $date->addWeeks($nth - 1)->format('m-d');
    }
}
