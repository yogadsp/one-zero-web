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
        Schema::table('questions', function (Blueprint $table) {
            $table->tinyInteger('option_1_point')->nullable();
            $table->tinyInteger('option_2_point')->nullable();
            $table->tinyInteger('option_3_point')->nullable();
            $table->tinyInteger('option_4_point')->nullable();
            $table->tinyInteger('option_5_point')->nullable();
            $table->tinyInteger('option_has_point')->default(0)->after('section');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('option_1_point');
            $table->dropColumn('option_2_point');
            $table->dropColumn('option_3_point');
            $table->dropColumn('option_4_point');
            $table->dropColumn('option_5_point');
            $table->dropColumn('option_has_point');
        });
    }
};
