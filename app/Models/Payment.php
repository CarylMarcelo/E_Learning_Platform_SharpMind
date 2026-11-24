<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory, HasUniqueReference;

    protected $fillable = [
        'membership_transaction_id',
        'payment_reference',
        'amount_paid',
        'payment_method',
        'payment_date',
    ];

    protected function casts(): array
    {
        return ['amount_paid' => 'decimal:2', 'payment_date' => 'datetime'];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(MembershipTransaction::class, 'membership_transaction_id');
    }

    protected static function referenceNumberColumn(): string
    {
        return 'payment_reference';
    }

    protected static function referenceNumberPrefix(): string
    {
        return 'PAY';
    }
}
