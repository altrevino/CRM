<?php

namespace Tests\Feature;

use App\Support\BusinessDays;
use Tests\TestCase;

class BusinessDaysTest extends TestCase
{
    public function test_salta_fines_de_semana_y_feriados(): void
    {
        // Viernes 18/12/2026 + 5 hábiles: 21, 22, 23, 24 (25 feriado) y 28.
        $this->assertSame('2026-12-28', BusinessDays::add('2026-12-18', 5)->toDateString());
        // Viernes 13/11/2026 + 1: el lunes 16 es feriado (tercer lunes de noviembre).
        $this->assertSame('2026-11-17', BusinessDays::add('2026-11-13', 1)->toDateString());
        // 15 hábiles desde el martes 17/11/2026.
        $this->assertSame('2026-12-08', BusinessDays::add('2026-11-17', 15)->toDateString());
    }

    public function test_feriados_moviles_y_de_sexenio(): void
    {
        $this->assertContains('02-01', BusinessDays::holidays(2027));  // primer lunes de febrero
        $this->assertContains('03-15', BusinessDays::holidays(2027));  // tercer lunes de marzo
        $this->assertContains('11-15', BusinessDays::holidays(2027));  // tercer lunes de noviembre
        $this->assertContains('10-01', BusinessDays::holidays(2030));
        $this->assertNotContains('10-01', BusinessDays::holidays(2027));
    }

    public function test_dias_restantes(): void
    {
        $this->assertSame(5, BusinessDays::remaining('2026-12-28', '2026-12-18'));
        $this->assertSame(0, BusinessDays::remaining('2026-12-18', '2026-12-18'));
    }
}
