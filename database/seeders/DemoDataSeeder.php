<?php

namespace Database\Seeders;

use App\Enums\PipelineStage;
use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Comment;
use App\Models\MexicanState;
use App\Models\Opportunity;
use App\Models\PaymentMethod;
use App\Models\Quote;
use App\Models\Ranch;
use App\Models\Species;
use App\Models\Task;
use App\Models\User;
use App\Services\OpportunityService;
use App\Services\PaymentService;
use App\Services\QuoteService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

/**
 * Datos ficticios realistas para evaluar el sistema.
 * Las fechas se calculan respecto a hoy para que siempre haya censos próximos,
 * seguimientos vencidos y cotizaciones sin seguimiento.
 */
class DemoDataSeeder extends Seeder
{
    private QuoteService $quotes;

    private PaymentService $payments;

    private OpportunityService $stages;

    public function run(): void
    {
        $this->quotes = app(QuoteService::class);
        $this->payments = app(PaymentService::class);
        $this->stages = app(OpportunityService::class);

        $admin = User::updateOrCreate(['email' => 'admin@espectro.local'], [
            'name' => 'Administrador Espectro',
            'password' => 'password',
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);
        $ops = User::updateOrCreate(['email' => 'operaciones@espectro.local'], [
            'name' => 'Laura Operaciones',
            'password' => 'password',
            'role' => UserRole::User,
            'is_active' => true,
        ]);

        Auth::setUser($admin);

        $nl = MexicanState::where('name', 'Nuevo León')->value('id');
        $coah = MexicanState::where('name', 'Coahuila')->value('id');
        $tamps = MexicanState::where('name', 'Tamaulipas')->value('id');

        // ------------------------------------------------------------ Clientes y ranchos
        $juan = Client::create(['name' => 'Juan Pérez Garza', 'phone' => '8112345678', 'notes' => 'Prefiere WhatsApp por la mañana.']);
        $roberto = Client::create(['name' => 'Roberto Garza Treviño', 'phone' => '8187654321']);
        $mauricio = Client::create(['name' => 'Mauricio Cantú Elizondo', 'phone' => '8441239876', 'notes' => 'Contacto referido por la UMA El Salado.']);
        $alejandro = Client::create(['name' => 'Alejandro Villarreal Salinas', 'phone' => '8999876543']);
        $eduardo = Client::create(['name' => 'Eduardo de la Garza Sada', 'phone' => '8183456789']);

        $venado = $this->ranch($juan, 'Rancho El Venado', 'Anáhuac', $nl, 2800, 'alta', 440, 'https://maps.app.goo.gl/ElVenadoAnahuac', 'Acceso por carretera Colombia, 12 km de terracería.');
        $palomas = $this->ranch($juan, 'Rancho Las Palomas', 'Lampazos de Naranjo', $nl, 1200, 'baja', 330, 'https://maps.app.goo.gl/LasPalomasLampazos');
        $sanIsidro = $this->ranch($roberto, 'Rancho San Isidro', 'Hidalgo', $coah, 4500, 'alta', 520, 'https://maps.app.goo.gl/SanIsidroHidalgo', 'Pista de aterrizaje disponible.');
        $escondida = $this->ranch($roberto, 'Rancho La Escondida', 'Guerrero', $coah, 3200, 'alta', 640, null);
        $encinos = $this->ranch($mauricio, 'Rancho Los Encinos', 'Galeana', $nl, 1800, 'baja', 380, 'https://maps.app.goo.gl/LosEncinosGaleana');
        $mezquital = $this->ranch($alejandro, 'Rancho El Mezquital', 'San Fernando', $tamps, 6000, 'alta', 560, 'https://maps.app.goo.gl/MezquitalSanFernando');
        $santaElena = $this->ranch($alejandro, 'Rancho Santa Elena', 'China', $nl, 950, 'baja', 230, null);
        $herradura = $this->ranch($eduardo, 'Rancho La Herradura', 'Sabinas Hidalgo', $nl, 2100, 'alta', 220, 'https://maps.app.goo.gl/LaHerraduraSabinas');

        $species = Species::pluck('id', 'name');
        $deer = [$species['Venado cola blanca'], $species['Pecarí de collar'], $species['Guajolote silvestre']];
        $transfer = PaymentMethod::where('name', 'Transferencia')->value('id');
        $cash = PaymentMethod::where('name', 'Efectivo')->value('id');

        $year = today()->year;

        // ------------------------------------------------------------ Servicios
        // 1. Censo realizado el año pasado, liquidado.
        $o1 = $this->opportunity($venado, 'completo', 2800, $deer, $admin, PipelineStage::CensusDone, census: today()->subMonths(10)->toDateString());
        $q = $this->quote($o1, 2800, 126000, 18000, true, issuedDaysAgo: 340, sentDaysAgo: 339, acceptedDaysAgo: 330);
        $this->pay($o1, 83520, $transfer, 320, 'SPEI 4589', 'Anticipo 50%');
        $this->pay($o1, 83520, $transfer, 300, 'SPEI 6120', 'Liquidación');
        Task::create(['title' => 'Entregar reporte de censo 2025', 'due_date' => today()->subMonths(9), 'opportunity_id' => $o1->id, 'assigned_to' => $ops->id])->complete();

        // 2. Censo confirmado en 5 días con saldo pendiente (V1 reemplazada por V2).
        $o2 = $this->opportunity($venado, 'completo', 2800, [...$deer, $species['Jabalí europeo']], $admin, PipelineStage::Prospect, tentative: today()->addDays(5)->toDateString());
        $v1 = $this->quote($o2, 2800, 140000, 20000, true, issuedDaysAgo: 60, sentDaysAgo: 59);
        $v2 = $this->quotes->createVersion($v1, ['issued_at' => today()->subDays(50), 'hectares' => 2800, 'service_amount' => 133000, 'logistics_amount' => 20000, 'apply_vat' => true, 'notes' => 'Descuento por cliente recurrente.']);
        $this->quotes->markSent($v2);
        $this->quotes->accept($v2);
        $this->pay($o2, 88740, $transfer, 30, 'SPEI 7781', 'Anticipo 50%');
        $this->stages->changeStage($o2, PipelineStage::Confirmed);
        $o2->update(['census_date' => today()->addDays(5)]);
        Task::create(['title' => 'Confirmar hospedaje y logística con Juan', 'due_date' => today(), 'opportunity_id' => $o2->id, 'assigned_to' => $admin->id]);
        Comment::create(['opportunity_id' => $o2->id, 'body' => 'Juan pide que el vuelo sea al amanecer; el ganado estará en el potrero norte.']);

        // 3. En seguimiento, cotización enviada hace 10 días sin actividad ni tarea.
        $o3 = $this->opportunity($palomas, 'representativo', 600, [$species['Venado cola blanca'], $species['Codorniz cotuí']], $ops, PipelineStage::Prospect, tentative: today()->addMonths(2)->toDateString());
        $this->quote($o3, 600, 33000, 9000, true, issuedDaysAgo: 11, sentDaysAgo: 10);
        $this->stages->changeStage($o3, PipelineStage::FollowUp);
        $this->backdate($o3, lastContact: today()->subDays(12));

        // 4. Pendiente anticipo, operación sin IVA (pago en efectivo), sin pagos.
        $o4 = $this->opportunity($sanIsidro, 'completo', 4500, [...$deer, $species['Borrego berberisco (aoudad)']], $admin, PipelineStage::Prospect, tentative: today()->addMonths(2)->startOfMonth()->addDays(9)->toDateString());
        $q4 = $this->quote($o4, 4500, 198000, 26000, false, issuedDaysAgo: 8, sentDaysAgo: 8, acceptedDaysAgo: 4, notes: 'Operación en efectivo, sin IVA.');
        Task::create(['title' => 'Recordar a Roberto el anticipo para apartar fecha', 'due_date' => today()->subDay(), 'opportunity_id' => $o4->id, 'assigned_to' => $admin->id]);

        // 5. Cotización enviada hace 5 días (requiere seguimiento).
        $o5 = $this->opportunity($escondida, 'localizacion', 3200, [$species['Borrego berberisco (aoudad)'], $species['Venado bura']], $ops, PipelineStage::Prospect);
        $this->quote($o5, 3200, 64000, 24000, true, issuedDaysAgo: 5, sentDaysAgo: 5);
        $this->backdate($o5, lastContact: today()->subDays(6));

        // 6. Prospecto sin cotizar con seguimiento vencido.
        $o6 = $this->opportunity($encinos, 'representativo', 900, [$species['Venado cola blanca'], $species['Guajolote silvestre']], $ops, PipelineStage::Prospect, tentative: today()->addMonths(3)->toDateString());
        Task::create(['title' => 'Llamar a Mauricio para enviar cotización', 'due_date' => today()->subDays(2), 'opportunity_id' => $o6->id, 'assigned_to' => $ops->id]);
        Comment::create(['opportunity_id' => $o6->id, 'body' => 'Busca un censo representativo antes de la temporada de caza.']);

        // 7. Confirmado en 25 días con anticipo parcial.
        $o7 = $this->opportunity($mezquital, 'completo', 6000, [...$deer, $species['Antílope nilgai']], $admin, PipelineStage::Prospect, tentative: today()->addDays(25)->toDateString());
        $this->quote($o7, 6000, 252000, 28000, true, issuedDaysAgo: 40, sentDaysAgo: 39, acceptedDaysAgo: 30);
        $this->pay($o7, 100000, $transfer, 28, 'SPEI 9034', 'Anticipo');
        $this->stages->changeStage($o7, PipelineStage::Confirmed);
        $o7->update(['census_date' => today()->addDays(25)]);
        Task::create(['title' => 'Solicitar liquidación antes del censo', 'due_date' => today()->addDays(7), 'opportunity_id' => $o7->id, 'assigned_to' => $admin->id]);

        // 8. Perdido.
        $o8 = $this->opportunity($santaElena, 'representativo', 500, [$species['Venado cola blanca']], $ops, PipelineStage::Prospect);
        $q8 = $this->quote($o8, 500, 27500, 6000, true, issuedDaysAgo: 70, sentDaysAgo: 69);
        $this->quotes->reject($q8);
        $this->stages->changeStage($o8, PipelineStage::Lost, 'Pospuso hasta el próximo año por la sequía.');

        // 9. Confirmado en 12 días, sin IVA, liquidado.
        $o9 = $this->opportunity($herradura, 'completo', 2100, $deer, $admin, PipelineStage::Prospect, tentative: today()->addDays(12)->toDateString());
        $this->quote($o9, 2100, 94500, 10000, false, issuedDaysAgo: 45, sentDaysAgo: 45, acceptedDaysAgo: 40, notes: 'Pago en efectivo.');
        $this->pay($o9, 52250, $cash, 38, null, 'Anticipo en efectivo');
        $this->pay($o9, 52250, $cash, 5, null, 'Liquidación');
        $this->stages->changeStage($o9, PipelineStage::Confirmed);
        $o9->update(['census_date' => today()->addDays(12)]);

        // 10. Prospecto para el próximo año.
        $o10 = $this->opportunity($herradura, 'localizacion', 2100, [$species['Venado cola blanca']], $ops, PipelineStage::Prospect, tentative: ($year + 1).'-02-10');
        Task::create(['title' => 'Contactar el 15 de enero para confirmar fechas', 'due_date' => ($year + 1).'-01-15', 'opportunity_id' => $o10->id, 'assigned_to' => $ops->id]);
        Comment::create(['opportunity_id' => $o10->id, 'body' => 'Cliente comentó que lo busquemos nuevamente en enero.']);

        // 11. Censo realizado el año pasado en Coahuila.
        $o11 = $this->opportunity($sanIsidro, 'completo', 4500, $deer, $admin, PipelineStage::CensusDone, census: today()->subMonths(9)->subDays(10)->toDateString());
        $this->quote($o11, 4500, 189000, 26000, true, issuedDaysAgo: 320, sentDaysAgo: 318, acceptedDaysAgo: 310);
        $this->pay($o11, 249400, $transfer, 290, 'SPEI 3321', 'Pago único');

        // 12. Confirmado con fecha pasada (falta marcar como realizado) y saldo.
        $o12 = $this->opportunity($palomas, 'localizacion', 1200, [$species['Venado cola blanca']], $admin, PipelineStage::Prospect);
        $this->quote($o12, 1200, 36000, 9000, true, issuedDaysAgo: 35, sentDaysAgo: 34, acceptedDaysAgo: 25);
        $this->pay($o12, 26100, $transfer, 20, 'SPEI 5512', 'Anticipo 50%');
        $this->stages->changeStage($o12, PipelineStage::Confirmed);
        $o12->update(['census_date' => today()->subDays(3)]);

        // Tarea general sin servicio.
        Task::create(['title' => 'Llamar a Eduardo para ofrecer censo de verano', 'due_date' => today()->addDays(3), 'client_id' => $eduardo->id, 'assigned_to' => $admin->id]);

        Auth::forgetUser();
    }

    private function ranch(Client $client, string $name, string $municipality, ?int $state, float $hectares, string $fence, float $km, ?string $maps, ?string $notes = null): Ranch
    {
        $ranch = new Ranch([
            'name' => $name,
            'municipality' => $municipality,
            'state_id' => $state,
            'total_hectares' => $hectares,
            'fence_type' => $fence,
            'km_round_trip' => $km,
            'maps_url' => $maps,
            'notes' => $notes,
        ]);
        $ranch->client_id = $client->id;
        $ranch->save();

        return $ranch;
    }

    private function opportunity(Ranch $ranch, string $type, float $hectares, array $species, User $owner, PipelineStage $stage, ?string $tentative = null, ?string $census = null): Opportunity
    {
        $opportunity = Opportunity::create([
            'ranch_id' => $ranch->id,
            'service_type' => $type,
            'quoted_hectares' => $hectares,
            'stage' => $stage,
            'owner_id' => $owner->id,
            'tentative_census_date' => $tentative ?? $census,
            'census_date' => $census,
            'last_contact_at' => today()->subDays(rand(1, 20)),
        ]);
        $opportunity->species()->sync($species);

        return $opportunity;
    }

    private function quote(Opportunity $opportunity, float $hectares, float $service, float $logistics, bool $vat, int $issuedDaysAgo, ?int $sentDaysAgo = null, ?int $acceptedDaysAgo = null, ?string $notes = null): Quote
    {
        $quote = $this->quotes->create($opportunity, [
            'issued_at' => today()->subDays($issuedDaysAgo),
            'hectares' => $hectares,
            'service_amount' => $service,
            'logistics_amount' => $logistics,
            'apply_vat' => $vat,
            'notes' => $notes,
        ]);

        if ($sentDaysAgo !== null) {
            $this->quotes->markSent($quote);
            $quote->forceFill(['sent_at' => now()->subDays($sentDaysAgo)])->saveQuietly();
        }

        if ($acceptedDaysAgo !== null) {
            $this->quotes->accept($quote);
            $quote->forceFill(['accepted_at' => now()->subDays($acceptedDaysAgo)])->saveQuietly();
        }

        return $quote;
    }

    private function pay(Opportunity $opportunity, float $amount, int $method, int $daysAgo, ?string $reference, string $notes): void
    {
        $payment = $this->payments->register($opportunity, [
            'paid_at' => today()->subDays($daysAgo),
            'amount' => $amount,
            'payment_method_id' => $method,
            'reference' => $reference,
            'notes' => $notes,
        ]);
        $payment->forceFill(['created_at' => now()->subDays($daysAgo)])->saveQuietly();
    }

    private function backdate(Opportunity $opportunity, $lastContact): void
    {
        $opportunity->forceFill(['last_contact_at' => $lastContact])->saveQuietly();
    }
}
