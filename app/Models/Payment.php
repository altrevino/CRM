<?php

namespace App\Models;

use App\Models\Concerns\HasCreator;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasCreator, HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'opportunity_id',
        'quote_id',
        'paid_at',
        'amount',
        'payment_method_id',
        'reference',
        'notes',
    ];

    protected $attributes = [
        'currency' => 'MXN',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class)->withTrashed();
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class)->withTrashed();
    }

    public function method(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }
}
