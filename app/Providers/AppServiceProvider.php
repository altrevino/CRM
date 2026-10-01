<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\Comment;
use App\Models\Opportunity;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\Ranch;
use App\Models\Task;
use App\Models\User;
use App\Observers\ClientObserver;
use App\Observers\CommentObserver;
use App\Observers\OpportunityObserver;
use App\Observers\PaymentObserver;
use App\Observers\QuoteObserver;
use App\Observers\RanchObserver;
use App\Observers\TaskObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Carbon::setLocale('es');

        // En desarrollo registra en el log las consultas N+1 (sin romper la pantalla).
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation) {
            Log::warning('N+1: carga diferida de '.$model::class."::{$relation}");
        });

        Relation::enforceMorphMap([
            'user' => User::class,
            'client' => Client::class,
            'ranch' => Ranch::class,
            'opportunity' => Opportunity::class,
            'quote' => Quote::class,
            'payment' => Payment::class,
            'task' => Task::class,
            'comment' => Comment::class,
        ]);

        Client::observe(ClientObserver::class);
        Ranch::observe(RanchObserver::class);
        Opportunity::observe(OpportunityObserver::class);
        Quote::observe(QuoteObserver::class);
        Payment::observe(PaymentObserver::class);
        Task::observe(TaskObserver::class);
        Comment::observe(CommentObserver::class);

        Gate::define('manage-settings', fn (User $user) => $user->hasPermission('settings.manage'));
        Gate::define('delete-records', fn (User $user) => $user->hasPermission('records.delete'));
    }
}
