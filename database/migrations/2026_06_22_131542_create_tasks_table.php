<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'done', 'cancelled'])
                ->default('pending');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->date('due_date')->nullable();

            // Siapa yang membuat tugas (admin)
            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            // Siapa yang mengerjakan tugas (user)
            $table->foreignId('assigned_to')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->softDeletes();   // Admin bisa "hapus" tanpa benar-benar menghapus
            $table->timestamps();

            // Index untuk query umum
            $table->index(['assigned_to', 'status']);
            $table->index(['status', 'priority']);
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
