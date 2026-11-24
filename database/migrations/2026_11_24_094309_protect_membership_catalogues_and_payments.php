<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            $table->dropForeign(['membership_category_id']);
            $table->dropForeign(['membership_level_id']);
            $table->foreign('membership_category_id')->references('id')->on('membership_categories')->restrictOnDelete();
            $table->foreign('membership_level_id')->references('id')->on('membership_levels')->restrictOnDelete();
        });
        Schema::table('membership_categories', fn (Blueprint $table) => $table->unique('name'));
        Schema::table('membership_levels', fn (Blueprint $table) => $table->unique('name'));
        Schema::table('payments', fn (Blueprint $table) => $table->unique('membership_transaction_id'));
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropForeign(['membership_transaction_id']);
            $table->dropUnique(['membership_transaction_id']);
            $table->foreign('membership_transaction_id')->references('id')->on('membership_transactions')->cascadeOnDelete();
        });
        Schema::table('membership_levels', fn (Blueprint $table) => $table->dropUnique(['name']));
        Schema::table('membership_categories', fn (Blueprint $table) => $table->dropUnique(['name']));
        Schema::table('members', function (Blueprint $table): void {
            $table->dropForeign(['membership_category_id']);
            $table->dropForeign(['membership_level_id']);
            $table->foreign('membership_category_id')->references('id')->on('membership_categories')->cascadeOnDelete();
            $table->foreign('membership_level_id')->references('id')->on('membership_levels')->cascadeOnDelete();
        });
    }
};
