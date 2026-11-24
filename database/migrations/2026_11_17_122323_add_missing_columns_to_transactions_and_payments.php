<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membership_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('membership_transactions', 'reference_number')) {
                $table->string('reference_number')->unique()->after('id');
            }
            if (! Schema::hasColumn('membership_transactions', 'member_id')) {
                $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            }
            if (! Schema::hasColumn('membership_transactions', 'type')) {
                $table->enum('type', ['Application', 'Renewal'])->default('Application');
            }
            if (! Schema::hasColumn('membership_transactions', 'amount')) {
                $table->decimal('amount', 10, 2)->default(0);
            }
            if (! Schema::hasColumn('membership_transactions', 'status')) {
                $table->enum('status', ['Pending', 'Paid', 'Approved', 'Expired', 'Cancelled'])->default('Pending');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'membership_transaction_id')) {
                $table->foreignId('membership_transaction_id')->constrained('membership_transactions')->onDelete('cascade');
            }
            if (! Schema::hasColumn('payments', 'payment_reference')) {
                $table->string('payment_reference')->unique();
            }
            if (! Schema::hasColumn('payments', 'amount_paid')) {
                $table->decimal('amount_paid', 10, 2)->default(0);
            }
            if (! Schema::hasColumn('payments', 'payment_method')) {
                $table->string('payment_method')->default('Cash');
            }
            if (! Schema::hasColumn('payments', 'payment_date')) {
                $table->timestamp('payment_date')->useCurrent();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('membership_transaction_id');
            $table->dropUnique(['payment_reference']);
            $table->dropColumn(['payment_reference', 'amount_paid', 'payment_method', 'payment_date']);
        });
        Schema::table('membership_transactions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('member_id');
            $table->dropUnique(['reference_number']);
            $table->dropColumn(['reference_number', 'type', 'amount', 'status']);
        });
    }
};
