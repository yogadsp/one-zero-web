<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_materials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('exam_id');
            $table->enum('type', ['video', 'live_class']);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('url', 2048);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('exam_id')
                ->references('id')
                ->on('exams')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->index(['exam_id', 'type', 'is_published'], 'learning_materials_catalog_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_materials');
    }
};
