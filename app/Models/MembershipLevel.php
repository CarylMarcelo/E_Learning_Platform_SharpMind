<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipLevel extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'fee', 'validity_days', 'benefits'];

    /** @return list<string> */
    public function benefitItems(): array
    {
        $lines = preg_split('/\R/u', trim($this->benefits ?? '')) ?: [];
        $lines = array_values(array_filter(array_map('trim', $lines), fn (string $line): bool => $line !== ''));

        if ($lines === []) {
            return ['Standard membership access'];
        }

        $bulletPrefix = '/^(?:[-*•]|\d+[.)])(?:\s+|$)/u';
        $hasExplicitList = count($lines) > 1 || preg_match($bulletPrefix, $lines[0]) === 1;
        $parts = $hasExplicitList ? $lines : (preg_split('/,(?!\d)/u', $lines[0]) ?: []);
        $isLegacyList = ! $hasExplicitList && count($parts) > 1;
        $items = [];

        foreach ($parts as $part) {
            $item = preg_replace($bulletPrefix, '', trim($part)) ?? trim($part);

            if ($isLegacyList) {
                $item = preg_replace('/^(?:and|&)(?:\s+|$)/iu', '', $item) ?? $item;
            }

            $item = trim($item);

            if ($item !== '') {
                $items[] = ucfirst($item);
            }
        }

        return $items === [] ? ['Standard membership access'] : $items;
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(MembershipTransaction::class);
    }
}
