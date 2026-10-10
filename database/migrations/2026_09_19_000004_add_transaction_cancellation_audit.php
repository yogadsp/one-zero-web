<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->uuid('deleted_by')->nullable()->after('deleted_at');
            $table->string('deletion_source', 16)->nullable()->after('deleted_by');
            $table->index(['deletion_source', 'deleted_at'], 'transactions_deletion_audit_index');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_deletion_audit_index');
            $table->dropColumn(['deleted_by', 'deletion_source']);
        });
    }
};
