<?php

namespace Tests\Feature;

use App\Models\PriceList;
use App\Services\Pricing\PricingException;
use App\Services\Pricing\QuoteCalculator;
use Tests\TestCase;

/** Cálculo de cotizaciones con la tarifa progresiva por bloques (Tarifa v1 de la migración). */
class QuoteCalculatorTest extends TestCase
{
    private array $tariff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tariff = PriceList::current()->snapshot();
    }

    public function test_precios_de_referencia_de_la_tarifa_progresiva(): void
    {
        $expected = [
            '250' => 37500, '500' => 37500, '500.5' => 37532.50, '1000' => 70000, '1500' => 100000,
            '2000' => 127500, '3000' => 177500, '4000' => 222500, '5000' => 262500, '6000' => 297500,
        ];

        foreach ($expected as $hectares => $price) {
            $this->assertSame((int) round($price * 100), QuoteCalculator::censusCents($this->tariff, (string) $hectares), "$hectares ha");
        }
    }

    public function test_limites_de_bloque_y_hectareas_decimales(): void
    {
        // Justo en el límite se cobra todo al bloque inferior; una centésima después entra al siguiente.
        $this->assertSame(7000000, QuoteCalculator::censusCents($this->tariff, '1000'));
        $this->assertSame(7000060, QuoteCalculator::censusCents($this->tariff, '1000.01'));   // 0.01 ha × $60
        $this->assertSame(12750050, QuoteCalculator::censusCents($this->tariff, '2000.01'));  // 0.01 ha × $50
        $this->assertSame(29750035, QuoteCalculator::censusCents($this->tariff, '6000.01'));  // 0.01 ha × $35 = $0.35
        $this->assertSame(3753250, QuoteCalculator::censusCents($this->tariff, '500.50'));
    }

    public function test_tarifa_minima_cubre_hasta_500_ha(): void
    {
        foreach (['0.01', '1', '499.99', '500'] as $hectares) {
            $this->assertSame(3750000, QuoteCalculator::censusCents($this->tariff, $hectares));
        }
    }

    public function test_rechaza_superficie_cero_o_negativa(): void
    {
        foreach (['0', '-10', ''] as $hectares) {
            try {
                QuoteCalculator::calculate($this->tariff, ['hectares' => $hectares, 'one_way_km' => 0]);
                $this->fail("Aceptó $hectares ha");
            } catch (PricingException $e) {
                $this->assertSame('hectares', $e->field);
            }
        }
    }

    public function test_caso_de_aceptacion_2000_ha_200_km(): void
    {
        $r = $this->calc(['hectares' => 2000, 'one_way_km' => 200, 'apply_vat' => true]);

        $this->assertSame('127500.00', $r['service_amount']);
        $this->assertSame('200.00', $r['one_way_km']);
        $this->assertSame('400.00', $r['route_km']);
        $this->assertSame('6000.00', $r['logistics_amount']);
        $this->assertSame('133500.00', $r['subtotal']);
        $this->assertSame('21360.00', $r['vat_amount']);
        $this->assertSame('154860.00', $r['total']);
    }

    public function test_caso_de_aceptacion_con_descuento_fijo_solo_en_traslado(): void
    {
        $r = $this->calc([
            'hectares' => 2000, 'one_way_km' => 200, 'apply_vat' => true,
            'discount_type' => 'amount', 'discount_value' => 5000, 'discount_scope' => 'travel', 'discount_reason' => 'Ruta compartida',
        ]);

        $this->assertSame('127500.00', $r['service_amount']);
        $this->assertSame('6000.00', $r['logistics_amount']);
        $this->assertSame('6000.00', $r['discount_base']);
        $this->assertSame('5000.00', $r['discount_amount']);
        $this->assertSame('128500.00', $r['subtotal']);
        $this->assertSame('20560.00', $r['vat_amount']);
        $this->assertSame('149060.00', $r['total']);
    }

    public function test_viaje_redondo_distancias_decimales_y_cero(): void
    {
        $r = $this->calc(['hectares' => 1000, 'one_way_km' => '87.35']);
        $this->assertSame('174.70', $r['route_km']);
        $this->assertSame('2620.50', $r['logistics_amount']);

        $r = $this->calc(['hectares' => 1000, 'one_way_km' => 0]);
        $this->assertSame('0.00', $r['logistics_amount']);

        $this->expectException(PricingException::class);
        $this->calc(['hectares' => 1000, 'one_way_km' => -1]);
    }

    public function test_ajuste_de_kilometros_exige_motivo_y_conserva_el_calculado(): void
    {
        try {
            $this->calc(['hectares' => 1000, 'one_way_km' => 200, 'route_adjusted' => true, 'route_km' => 250]);
            $this->fail('Ajuste sin motivo');
        } catch (PricingException $e) {
            $this->assertSame('route_adjust_reason', $e->field);
        }

        $r = $this->calc(['hectares' => 1000, 'one_way_km' => 200, 'route_adjusted' => true, 'route_km' => '250.5', 'route_adjust_reason' => 'Ruta compartida']);
        $this->assertSame('200.00', $r['one_way_km']);
        $this->assertSame('400.00', $r['route_km_calculated']);
        $this->assertSame('250.50', $r['route_km']);
        $this->assertSame('3757.50', $r['logistics_amount']);
    }

    public function test_costo_por_kilometro_configurable(): void
    {
        $r = QuoteCalculator::calculate([...$this->tariff, 'km_rate' => '18.50'], ['hectares' => 1000, 'one_way_km' => 100]);
        $this->assertSame('18.50', $r['km_rate']);
        $this->assertSame('3700.00', $r['logistics_amount']);
    }

    public function test_descuentos_por_alcance_y_porcentaje(): void
    {
        $base = ['hectares' => 1000, 'one_way_km' => 100, 'travel_expenses' => 2000, 'apply_vat' => true, 'discount_reason' => 'Cliente recurrente'];

        $census = $this->calc([...$base, 'discount_type' => 'percent', 'discount_value' => 10, 'discount_scope' => 'census']);
        $this->assertSame('70000.00', $census['discount_base']);
        $this->assertSame('7000.00', $census['discount_amount']);
        $this->assertSame('68000.00', $census['subtotal']); // 70000 + 3000 + 2000 − 7000

        $subtotal = $this->calc([...$base, 'discount_type' => 'percent', 'discount_value' => '12.5', 'discount_scope' => 'subtotal']);
        $this->assertSame('75000.00', $subtotal['discount_base']);
        $this->assertSame('9375.00', $subtotal['discount_amount']);
        $this->assertSame('65625.00', $subtotal['subtotal']);
        $this->assertSame('10500.00', $subtotal['vat_amount']);
        $this->assertSame('76125.00', $subtotal['total']);
    }

    public function test_descuento_no_excede_la_base_ni_va_sin_motivo(): void
    {
        $cases = [
            [['discount_type' => 'amount', 'discount_value' => 3001, 'discount_scope' => 'travel', 'discount_reason' => 'Ruta'], 'discount_value'],
            [['discount_type' => 'percent', 'discount_value' => 101, 'discount_scope' => 'census', 'discount_reason' => 'Ruta'], 'discount_value'],
            [['discount_type' => 'amount', 'discount_value' => -5, 'discount_scope' => 'census', 'discount_reason' => 'Ruta'], 'discount_value'],
            [['discount_type' => 'amount', 'discount_value' => 100, 'discount_scope' => 'census'], 'discount_reason'],
            [['discount_type' => 'amount', 'discount_value' => 100, 'discount_scope' => 'otro', 'discount_reason' => 'Ruta'], 'discount_scope'],
        ];

        foreach ($cases as [$discount, $field]) {
            try {
                $this->calc(['hectares' => 1000, 'one_way_km' => 100, ...$discount]);
                $this->fail('Aceptó '.json_encode($discount));
            } catch (PricingException $e) {
                $this->assertSame($field, $e->field, json_encode($discount));
            }
        }
    }

    public function test_ajuste_excepcional_del_censo_conserva_el_precio_calculado(): void
    {
        $r = $this->calc(['hectares' => 2000, 'one_way_km' => 0, 'service_adjusted' => true, 'service_amount' => 120000, 'service_adjust_reason' => 'Convenio anual']);
        $this->assertSame('127500.00', $r['service_amount_calculated']);
        $this->assertSame('120000.00', $r['service_amount']);
        $this->assertTrue($r['service_adjusted']);

        $this->expectException(PricingException::class);
        $this->calc(['hectares' => 2000, 'one_way_km' => 0, 'service_adjusted' => true, 'service_amount' => 120000]);
    }

    public function test_iva_se_calcula_sobre_subtotal_y_redondea_a_centavos(): void
    {
        // 500.5 ha → 37,532.50; traslado 0.33 km ida → 0.66 km × 15 = 9.90; subtotal 37,542.40; IVA 6,006.784 → 6,006.78
        $r = $this->calc(['hectares' => '500.5', 'one_way_km' => '0.33', 'apply_vat' => true]);
        $this->assertSame('37542.40', $r['subtotal']);
        $this->assertSame('6006.78', $r['vat_amount']);
        $this->assertSame('43549.18', $r['total']);

        $sinIva = $this->calc(['hectares' => '500.5', 'one_way_km' => '0.33', 'apply_vat' => false]);
        $this->assertSame('0.00', $sinIva['vat_amount']);
        $this->assertSame('37542.40', $sinIva['total']);
    }

    private function calc(array $input): array
    {
        return QuoteCalculator::calculate($this->tariff, $input);
    }
}
