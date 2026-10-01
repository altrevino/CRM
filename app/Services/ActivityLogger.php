<?php

namespace App\Services;

use App\Enums\ActivityEvent;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Comment;
use App\Models\Opportunity;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\Ranch;
use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/** Escribe el historial con el contexto cliente/rancho/oportunidad del registro. */
class ActivityLogger
{
    public static function log(ActivityEvent $event, ?Model $subject, string $description, array $properties = []): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => Auth::id(),
            'event' => $event,
            'description' => Str::limit($description, 497),
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            ...self::context($subject),
            'properties' => $properties ?: null,
        ]);
    }

    /** @return array{client_id: ?string, ranch_id: ?string, opportunity_id: ?string} */
    private static function context(?Model $subject): array
    {
        $context = ['client_id' => null, 'ranch_id' => null, 'opportunity_id' => null];

        $opportunity = match (true) {
            $subject instanceof Opportunity => $subject,
            $subject instanceof Quote, $subject instanceof Payment, $subject instanceof Comment => $subject->opportunity,
            $subject instanceof Task => $subject->opportunity,
            default => null,
        };

        if ($opportunity) {
            $context['opportunity_id'] = $opportunity->id;
            $context['ranch_id'] = $opportunity->ranch_id;
            $context['client_id'] = $opportunity->ranch?->client_id;

            return $context;
        }

        if ($subject instanceof Ranch) {
            $context['ranch_id'] = $subject->id;
            $context['client_id'] = $subject->client_id;
        } elseif ($subject instanceof Client) {
            $context['client_id'] = $subject->id;
        } elseif ($subject instanceof Task) {
            $context['ranch_id'] = $subject->ranch_id;
            $context['client_id'] = $subject->client_id;
        }

        return $context;
    }
}
