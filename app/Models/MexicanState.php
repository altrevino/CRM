<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class MexicanState extends Model
{
    protected $fillable = ['name', 'abbreviation'];

    /** @return array<int, string> */
    public static function options(): array
    {
        return Cache::remember('mexican_states.options', 3600, fn () => static::orderBy('name')->pluck('name', 'id')->all());
    }
}
