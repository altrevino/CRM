<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Ranch;
use App\Support\Phone;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/** Advertencias de posibles duplicados (no bloquea la captura). */
class DuplicateDetector
{
    private const SIMILARITY = 75;

    /** @return Collection<int, array{client: Client, reason: string}> */
    public function similarClients(?string $name, ?string $phone, ?string $exceptId = null): Collection
    {
        $matches = collect();
        $normalizedPhone = Phone::normalize($phone);

        if ($normalizedPhone) {
            Client::where('phone', $normalizedPhone)
                ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
                ->limit(5)
                ->get()
                ->each(fn (Client $c) => $matches->put($c->id, ['client' => $c, 'reason' => 'Mismo teléfono']));
        }

        $needle = self::normalize($name);
        if (mb_strlen($needle) >= 4) {
            $this->candidates(Client::query(), 'name', $needle)
                ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
                ->limit(50)
                ->get()
                ->filter(fn (Client $c) => self::isSimilar($needle, $c->name))
                ->each(fn (Client $c) => $matches->has($c->id) ?: $matches->put($c->id, ['client' => $c, 'reason' => 'Nombre similar']));
        }

        return $matches->values()->take(5);
    }

    /** @return Collection<int, Ranch> */
    public function similarRanches(?string $clientId, ?string $name, ?string $exceptId = null): Collection
    {
        $needle = self::normalize(preg_replace('/^rancho\s+/i', '', (string) $name));
        if (! $clientId || mb_strlen($needle) < 3) {
            return collect();
        }

        return Ranch::where('client_id', $clientId)
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
            ->get()
            ->filter(fn (Ranch $r) => self::isSimilar($needle, preg_replace('/^rancho\s+/i', '', $r->name)))
            ->values();
    }

    private function candidates($query, string $column, string $needle)
    {
        $tokens = collect(explode(' ', $needle))->filter(fn ($t) => mb_strlen($t) >= 3)->take(3);

        return $query->where(function ($q) use ($tokens, $column) {
            foreach ($tokens as $token) {
                $q->orWhere($column, 'like', '%'.mb_substr($token, 0, 4).'%');
            }
        });
    }

    public static function normalize(?string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', Str::lower(Str::ascii((string) $value))));
    }

    public static function isSimilar(string $needle, string $candidate): bool
    {
        $candidate = self::normalize($candidate);
        if ($candidate === $needle || str_contains($candidate, $needle) || str_contains($needle, $candidate)) {
            return true;
        }

        similar_text($needle, $candidate, $percent);

        return $percent >= self::SIMILARITY;
    }
}
