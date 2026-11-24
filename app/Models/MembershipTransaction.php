<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MembershipTransaction extends Model
{
    use HasFactory, HasUniqueReference;

    protected $fillable = [
        'reference_number', 'member_id', 'type', 'amount', 'status',
        'membership_category_id', 'membership_level_id', 'category_name', 'level_name',
        'base_fee', 'discount_percent', 'validity_days', 'benefits', 'approved_at', 'expires_at', 'is_legacy',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2', 'base_fee' => 'decimal:2', 'discount_percent' => 'decimal:2',
            'validity_days' => 'integer', 'approved_at' => 'datetime', 'expires_at' => 'datetime', 'is_legacy' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (MembershipTransaction $transaction): void {
            $member = $transaction->member;
            $level = $transaction->level ?? $member->level;
            $category = $transaction->category ?? $member->category;
            $attributes = $transaction->getAttributes();

            foreach (static::purchaseSnapshot($member, $level, $category) as $field => $value) {
                if (! array_key_exists($field, $attributes)) {
                    $transaction->setAttribute($field, $value);
                }
            }

            if ($transaction->status === 'Approved') {
                $transaction->approved_at ??= $transaction->updated_at ?? now();
                $transaction->expires_at ??= $transaction->approved_at->copy()->addDays($transaction->validity_days);
            }
        });
    }

    /** @return array{membership_category_id: int, membership_level_id: int, category_name: string, level_name: string, base_fee: float, discount_percent: float, validity_days: int, benefits: ?string} */
    public static function purchaseSnapshot(Member $member, MembershipLevel $level, ?MembershipCategory $category = null): array
    {
        $category ??= $member->category;

        return [
            'membership_category_id' => $category->id,
            'membership_level_id' => $level->id,
            'category_name' => $category->name,
            'level_name' => $level->name,
            'base_fee' => (float) $level->fee,
            'discount_percent' => (float) ($category->discount_percent ?? 0),
            'validity_days' => (int) $level->validity_days,
            'benefits' => $level->benefits,
        ];
    }

    /** @return list<string> */
    public function benefitItems(): array
    {
        if ($this->level_name === null) {
            return $this->member->level?->benefitItems() ?? ['Standard membership access'];
        }

        return (new MembershipLevel(['benefits' => $this->benefits]))->benefitItems();
    }

    public function isCurrentActiveMembership(): bool
    {
        return $this->status === 'Approved'
            && $this->member->status === 'Active'
            && $this->member->latestApprovedTransaction?->is($this) === true;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['Pending', 'Paid'], true) || $this->isCurrentActiveMembership();
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(MembershipLevel::class, 'membership_level_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MembershipCategory::class, 'membership_category_id');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    protected static function referenceNumberColumn(): string
    {
        return 'reference_number';
    }

    protected static function referenceNumberPrefix(): string
    {
        return 'TRX';
    }
}
