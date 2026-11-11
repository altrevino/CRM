<?php

namespace App\Services\Pricing;

use App\Support\Money;

/**
 * Cálculo único de cotizaciones. Lo usan el formulario (vista previa), el guardado
 * y el PDF, por lo que interfaz y documento siempre muestran las mismas cifras.
 *
 * Tarifa del censo: progresiva por bloques. La tarifa mínima cubre las primeras
 * `minimum_hectares`; cada bloque cobra su precio solo por la porción de superficie
 * que cae dentro de él (no se multiplica toda la superficie por el último precio).
 */
class QuoteCalculator
{
    public const DISCOUNT_SCOPES = ['census' => 'Servicio de censo', 'travel' => 'Traslado', 'subtotal' => 'Subtotal completo'];

    public const DISCOUNT_TYPES = ['amount' => 'Importe fijo', 'percent' => 'Porcentaje'];

    /**
     * Precio del censo para una superficie, en centavos.
     *
     * @param  array{minimum_hectares: mixed, minimum_price: mixed, blocks: list<array{up_to: mixed, price: mixed}>}  $tariff
     */
    public static function censusCents(array $tariff, int|float|string $hectares): int
    {
        $area = Money::toHundredths($hectares);
        if ($area <= 0) {
            throw new PricingException('hectares', 'La superficie debe ser mayor a 0 hectáreas.');
        }

        $lower = Money::toHundredths($tariff['minimum_hectares']);
        $numerator = 0;

        foreach ($tariff['blocks'] as $block) {
            if ($area <= $lower) {
                break;
            }
            $upper = $block['up_to'] === null || $block['up_to'] === '' ? PHP_INT_MAX : Money::toHundredths($block['up_to']);
            $portion = min($area, $upper) - $lower;
            $numerator += $portion * Money::toCents($block['price']);
            $lower = $upper;
        }

        return Money::toCents($tariff['minimum_price']) + Money::divRound($numerator, 100);
    }

    /**
     * Desglose completo a partir de la tarifa y de lo capturado.
     *
     * @param  array  $tariff  Copia de la versión de tarifa (PriceList::snapshot()).
     * @param  array  $input  hectares, one_way_km, route_adjusted, route_km, route_adjust_reason,
     *                        travel_expenses, service_adjusted, service_amount, service_adjust_reason,
     *                        discount_type, discount_value, discount_scope, discount_reason, apply_vat
     * @return array<string, mixed> Importes en texto decimal ("127500.00") listos para guardar.
     */
    public static function calculate(array $tariff, array $input): array
    {
        // Servicio de censo.
        $censusCalculated = self::censusCents($tariff, $input['hectares'] ?? 0);
        $serviceAdjusted = (bool) ($input['service_adjusted'] ?? false);
        $census = $censusCalculated;
        if ($serviceAdjusted) {
            self::requireNumber($input['service_amount'] ?? null, 'service_amount', 'Captura el precio ajustado del censo.');
            $census = Money::toCents($input['service_amount']);
            if ($census < 0) {
                throw new PricingException('service_amount', 'El precio ajustado no puede ser negativo.');
            }
            self::requireReason($input['service_adjust_reason'] ?? null, 'service_adjust_reason', 'Indica el motivo del ajuste de precio.');
        }

        // Traslado: distancia de ida × 2 × tarifa por km.
        $oneWay = Money::toHundredths($input['one_way_km'] ?? 0);
        if ($oneWay < 0) {
            throw new PricingException('one_way_km', 'La distancia no puede ser negativa.');
        }
        $routeCalculated = $oneWay * 2;
        $routeAdjusted = (bool) ($input['route_adjusted'] ?? false);
        $route = $routeCalculated;
        if ($routeAdjusted) {
            self::requireNumber($input['route_km'] ?? null, 'route_km', 'Captura los kilómetros totales a cotizar.');
            $route = Money::toHundredths($input['route_km']);
            if ($route < 0) {
                throw new PricingException('route_km', 'Los kilómetros no pueden ser negativos.');
            }
            self::requireReason($input['route_adjust_reason'] ?? null, 'route_adjust_reason', 'Indica el motivo del ajuste del recorrido.');
        }
        $kmRate = Money::toCents($tariff['km_rate']);
        $travel = Money::multiply($route, $kmRate);

        // Viáticos.
        $expenses = Money::toCents($input['travel_expenses'] ?? 0);
        if ($expenses < 0) {
            throw new PricingException('travel_expenses', 'Los viáticos no pueden ser negativos.');
        }

        // Descuento único con alcance explícito.
        $discount = self::discount($input, $census, $travel, $census + $travel + $expenses);

        $applyVat = filter_var($input['apply_vat'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $vatRate = $applyVat ? (string) $tariff['vat_rate'] : '0';
        $totals = self::totals($census, $travel, $expenses, $discount['amount'], $applyVat, $vatRate);

        return [
            'hectares' => Money::decimal(Money::toHundredths($input['hectares'])),
            'service_amount_calculated' => Money::decimal($censusCalculated),
            'service_amount' => Money::decimal($census),
            'service_adjusted' => $serviceAdjusted,
            'one_way_km' => Money::decimal($oneWay),
            'route_km_calculated' => Money::decimal($routeCalculated),
            'route_km' => Money::decimal($route),
            'route_adjusted' => $routeAdjusted,
            'km_rate' => Money::decimal($kmRate),
            'logistics_amount' => Money::decimal($travel),
            'travel_expenses' => Money::decimal($expenses),
            'gross' => Money::decimal($census + $travel + $expenses),
            'discount_type' => $discount['type'],
            'discount_value' => $discount['value'] === null ? null : Money::decimal($discount['value']),
            'discount_scope' => $discount['scope'],
            'discount_base' => $discount['type'] ? Money::decimal($discount['base']) : null,
            'discount_amount' => Money::decimal($discount['amount']),
            'subtotal' => Money::decimal($totals['subtotal']),
            'apply_vat' => $applyVat,
            'vat_rate' => $applyVat ? Money::decimal(Money::toCents($vatRate)) : '0.00',
            'vat_amount' => Money::decimal($totals['vat']),
            'total' => Money::decimal($totals['total']),
        ];
    }

    /**
     * Subtotal, IVA y total. También lo usa Quote::recalculate() para que no exista
     * una segunda fórmula. El IVA se calcula sobre el subtotal después del descuento.
     *
     * @return array{subtotal: int, vat: int, total: int}
     */
    public static function totals(int $census, int $travel, int $expenses, int $discount, bool $applyVat, int|float|string $vatRate): array
    {
        $subtotal = $census + $travel + $expenses - $discount;
        $vat = $applyVat ? Money::percent($subtotal, $vatRate) : 0;

        return ['subtotal' => $subtotal, 'vat' => $vat, 'total' => $subtotal + $vat];
    }

    /** @return array{type: ?string, value: ?int, scope: ?string, base: int, amount: int} */
    private static function discount(array $input, int $census, int $travel, int $gross): array
    {
        $type = $input['discount_type'] ?? null;
        $none = ['type' => null, 'value' => null, 'scope' => null, 'base' => 0, 'amount' => 0];

        if (blank($type) || ! is_numeric($input['discount_value'] ?? null) || Money::toCents($input['discount_value']) == 0) {
            return $none;
        }
        if (! array_key_exists($type, self::DISCOUNT_TYPES)) {
            throw new PricingException('discount_type', 'Tipo de descuento inválido.');
        }

        $scope = $input['discount_scope'] ?? null;
        if (! array_key_exists((string) $scope, self::DISCOUNT_SCOPES)) {
            throw new PricingException('discount_scope', 'Elige a qué se aplica el descuento.');
        }
        self::requireReason($input['discount_reason'] ?? null, 'discount_reason', 'Indica el motivo del descuento.');

        $value = Money::toCents($input['discount_value']);
        if ($value < 0) {
            throw new PricingException('discount_value', 'El descuento no puede ser negativo.');
        }

        $base = match ($scope) {
            'census' => $census,
            'travel' => $travel,
            'subtotal' => $gross,
        };

        if ($type === 'percent') {
            if ($value > 10000) {
                throw new PricingException('discount_value', 'El porcentaje no puede ser mayor a 100%.');
            }
            $amount = Money::percent($base, Money::fromCents($value));
        } else {
            $amount = $value;
        }

        if ($amount > $base) {
            throw new PricingException('discount_value', 'El descuento ('.money(Money::fromCents($amount)).') supera la base sobre la que se aplica ('.money(Money::fromCents($base)).').');
        }

        return ['type' => $type, 'value' => $value, 'scope' => $scope, 'base' => $base, 'amount' => $amount];
    }

    private static function requireReason(mixed $reason, string $field, string $message): void
    {
        if (mb_strlen(trim((string) $reason)) < 3) {
            throw new PricingException($field, $message);
        }
    }

    private static function requireNumber(mixed $value, string $field, string $message): void
    {
        if (! is_numeric($value)) {
            throw new PricingException($field, $message);
        }
    }
}
