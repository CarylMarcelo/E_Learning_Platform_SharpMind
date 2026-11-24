<?php

namespace App\Models\Concerns;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

trait HasUniqueReference
{
    abstract protected static function referenceNumberColumn(): string;

    abstract protected static function referenceNumberPrefix(): string;

    /** @param array<string, mixed> $attributes */
    public static function createWithUniqueReference(array $attributes): static
    {
        $column = static::referenceNumberColumn();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $reference = static::referenceNumberPrefix().'-'.now(config('mail.membership_timezone'))->format('Y').'-'.Str::upper(Str::random(5));

            if (static::where($column, $reference)->exists()) {
                continue;
            }

            try {
                return DB::transaction(fn (): static => static::create([...$attributes, $column => $reference]));
            } catch (UniqueConstraintViolationException $exception) {
                if (! static::where($column, $reference)->exists()) {
                    throw $exception;
                }
            }
        }

        throw ValidationException::withMessages([
            $column => 'A unique '.str_replace('_', ' ', $column).' could not be generated. Please try again.',
        ]);
    }
}
