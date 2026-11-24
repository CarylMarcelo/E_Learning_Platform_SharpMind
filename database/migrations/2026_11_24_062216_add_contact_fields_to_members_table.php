<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->char('phone_country', 2)->nullable()->after('phone');
            $table->string('street_name')->nullable()->after('address');
            $table->string('city', 120)->nullable()->after('street_name');
            $table->string('state', 120)->nullable()->after('city');
            $table->string('zip_code', 20)->nullable()->after('state');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['phone_country', 'street_name', 'city', 'state', 'zip_code']);
        });
    }
};
