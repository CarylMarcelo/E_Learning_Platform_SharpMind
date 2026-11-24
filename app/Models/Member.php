<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueReference;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Member extends Model
{
    use HasFactory, HasUniqueReference;

    protected $fillable = [
        'membership_number',
        'first_name',
        'last_name',
        'email',
        'phone',
        'phone_country',
        'address',
        'street_name',
        'city',
        'state',
        'zip_code',
        'latitude',
        'longitude',
        'membership_category_id',
        'membership_level_id',
        'status',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(MembershipCategory::class, 'membership_category_id');
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(MembershipLevel::class, 'membership_level_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(MembershipTransaction::class);
    }

    public function latestApprovedTransaction(): HasOne
    {
        return $this->hasOne(MembershipTransaction::class)->ofMany([
            'approved_at' => 'max',
            'id' => 'max',
        ], fn (Builder $query): Builder => $query->where('status', 'Approved'));
    }

    public function getEffectiveFee(): float
    {
        $baseFee = (float) ($this->level->fee ?? 0);
        $discount = (float) ($this->category->discount_percent ?? 0);

        return round($baseFee * (1 - ($discount / 100)), 2);
    }

    protected static function referenceNumberColumn(): string
    {
        return 'membership_number';
    }

    protected static function referenceNumberPrefix(): string
    {
        return 'MEM';
    }
}
