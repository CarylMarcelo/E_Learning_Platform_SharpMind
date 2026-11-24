<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membership_transactions', function (Blueprint $table): void {
            $table->foreignId('membership_category_id')->nullable()->constrained('membership_categories')->nullOnDelete();
            $table->foreignId('membership_level_id')->nullable()->constrained('membership_levels')->nullOnDelete();
            $table->string('category_name')->nullable();
            $table->string('level_name')->nullable();
            $table->decimal('base_fee', 10, 2)->nullable();
            $table->decimal('discount_percent', 5, 2)->nullable();
            $table->unsignedInteger('validity_days')->nullable();
            $table->text('benefits')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_legacy')->default(false);
        });

        DB::table('membership_transactions as transactions')
            ->leftJoin('members', 'members.id', '=', 'transactions.member_id')
            ->leftJoin('membership_categories as categories', 'categories.id', '=', 'members.membership_category_id')
            ->leftJoin('membership_levels as levels', 'levels.id', '=', 'members.membership_level_id')
            ->select(['transactions.id', 'transactions.status', 'transactions.updated_at', 'transactions.created_at', 'members.membership_category_id', 'members.membership_level_id', 'categories.name as category_name', 'categories.discount_percent', 'levels.name as level_name', 'levels.fee as base_fee', 'levels.validity_days', 'levels.benefits'])
            ->chunkById(100, function (Collection $transactions): void {
                foreach ($transactions as $transaction) {
                    $approvedAt = $transaction->status === 'Approved'
                        ? Carbon::parse($transaction->updated_at ?? $transaction->created_at)
                        : null;

                    DB::table('membership_transactions')->where('id', $transaction->id)->update([
                        'membership_category_id' => $transaction->membership_category_id,
                        'membership_level_id' => $transaction->membership_level_id,
                        'category_name' => $transaction->category_name ?? 'Unrecorded category',
                        'level_name' => $transaction->level_name ?? 'Unrecorded membership',
                        'base_fee' => $transaction->base_fee,
                        'discount_percent' => $transaction->discount_percent ?? 0,
                        'validity_days' => $transaction->validity_days ?? 365,
                        'benefits' => $transaction->benefits,
                        'approved_at' => $approvedAt,
                        'expires_at' => $approvedAt?->copy()->addDays((int) ($transaction->validity_days ?? 365)),
                        'is_legacy' => true,
                    ]);
                }
            }, 'transactions.id', 'id');
    }

    public function down(): void
    {
        Schema::table('membership_transactions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('membership_category_id');
            $table->dropConstrainedForeignId('membership_level_id');
            $table->dropColumn(['category_name', 'level_name', 'base_fee', 'discount_percent', 'validity_days', 'benefits', 'approved_at', 'expires_at', 'is_legacy']);
        });
    }
};
