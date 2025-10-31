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
        Schema::table('students', function (Blueprint $table) {
            // Add department_id column to students table
            $table->uuid('department_id')->nullable()->after('village_id');
            // Add foreign key constraint to department_id
            $table->foreign('department_id')
                ->references('id')
                ->on('departement')
                ->onDelete('set null') // Set to null if department is deleted
                ->onUpdate('cascade'); // Update if department id changes
            // Ensure that the department_id is indexed for performance
            $table->index('department_id', 'idx_students_department_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
        });
    }
};
