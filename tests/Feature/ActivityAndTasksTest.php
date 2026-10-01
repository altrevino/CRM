<?php

namespace Tests\Feature;

use App\Enums\PipelineStage;
use App\Livewire\Forms\ClientForm;
use App\Livewire\Opportunities\Show;
use App\Models\Comment;
use App\Models\Opportunity;
use App\Models\Task;
use App\Services\AttentionService;
use App\Services\QuoteService;
use Livewire\Livewire;
use LogicException;
use Tests\TestCase;

class ActivityAndTasksTest extends TestCase
{
    public function test_completar_tarea_queda_en_historial(): void
    {
        $user = $this->actingAsUser();
        $task = Task::factory()->create();

        $task->complete();

        $this->assertDatabaseHas('activity_logs', ['event' => 'task_completed', 'user_id' => $user->id, 'opportunity_id' => $task->opportunity_id]);
        $this->assertSame(today()->toDateString(), $task->opportunity->fresh()->last_contact_at->toDateString());
    }

    public function test_tarea_toma_cliente_y_rancho_de_la_oportunidad(): void
    {
        $this->actingAsUser();
        $task = Task::factory()->create();

        $this->assertSame($task->opportunity->ranch_id, $task->ranch_id);
        $this->assertSame($task->opportunity->ranch->client_id, $task->client_id);
    }

    public function test_seguimientos_vencidos_hoy_y_proximos(): void
    {
        $this->actingAsUser();
        Task::factory()->create(['due_date' => today()->subDays(2)]);
        Task::factory()->create(['due_date' => today()]);
        Task::factory()->create(['due_date' => today()->addDays(4)]);

        $this->assertSame(1, Task::overdue()->count());
        $this->assertSame(1, Task::dueToday()->count());
        $this->assertSame(1, Task::upcoming()->count());
    }

    public function test_comentarios_se_registran_y_no_se_pueden_modificar(): void
    {
        $this->actingAsUser();
        $opportunity = Opportunity::factory()->create();

        Livewire::test(Show::class, ['opportunity' => $opportunity])
            ->set('comment', 'Cliente comentó que lo busquemos en enero.')
            ->call('addComment')
            ->assertHasNoErrors();

        $comment = Comment::first();
        $this->assertDatabaseHas('activity_logs', ['event' => 'comment_added', 'subject_id' => $comment->id]);

        $this->expectException(LogicException::class);
        $comment->update(['body' => 'otro texto']);
    }

    public function test_detecta_cotizacion_enviada_sin_seguimiento(): void
    {
        $this->actingAsUser();
        $service = app(QuoteService::class);
        $opportunity = Opportunity::factory()->create(['last_contact_at' => today()->subDays(10)]);
        $quote = $service->create($opportunity, ['issued_at' => today(), 'hectares' => 100, 'service_amount' => 1000, 'apply_vat' => false]);
        $service->markSent($quote);

        $attention = app(AttentionService::class);
        $this->assertNotContains($quote->id, $attention->staleQuoteIds(), 'Recién enviada no requiere seguimiento');

        $quote->forceFill(['sent_at' => now()->subDays(4)])->saveQuietly();
        $this->assertContains($quote->id, $attention->staleQuoteIds());

        Task::create(['title' => 'Llamar', 'due_date' => today()->addDay(), 'opportunity_id' => $opportunity->id]);
        $this->assertNotContains($quote->id, $attention->staleQuoteIds(), 'Una tarea posterior cuenta como seguimiento');
    }

    public function test_alerta_pendiente_anticipo_sin_pago(): void
    {
        $this->actingAsUser();
        Opportunity::factory()->stage(PipelineStage::PendingDeposit)->create();

        $section = app(AttentionService::class)->sections()->firstWhere('key', 'deposit_no_payment');
        $this->assertSame(1, $section['count']);
    }

    public function test_cliente_valida_telefono_y_avisa_duplicados(): void
    {
        $this->actingAsUser();

        Livewire::test(ClientForm::class)
            ->call('open')
            ->set('name', 'Juan Pérez')
            ->set('phone', '12345')
            ->call('save')
            ->assertHasErrors('phone');

        Livewire::test(ClientForm::class)
            ->call('open')
            ->set('name', 'Juan Pérez Garza')
            ->set('phone', '+52 81 1234 5678')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('clients', ['name' => 'Juan Pérez Garza', 'phone' => '8112345678']);

        $form = Livewire::test(ClientForm::class)->call('open')->set('name', 'Juan Perez Garsa');
        $this->assertCount(1, $form->instance()->duplicates);
    }
}
