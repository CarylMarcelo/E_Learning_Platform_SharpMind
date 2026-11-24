<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membership_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('membership_categories', 'discount_percent')) {
                $table->decimal('discount_percent', 5, 2)->default(0)->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('membership_categories', function (Blueprint $table) {
            $table->dropColumn('discount_percent');
        });
    }
};
