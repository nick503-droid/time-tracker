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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            // Quién hizo el cambio
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            // A qué empleado se lo hizo
            $table->foreignId('affected_user_id')->constrained('users')->onDelete('cascade');

            $table->string('action'); // Ej: "Modificación manual de tiempo de break"
            $table->json('old_value')->nullable(); // Guardamos cómo estaba antes
            $table->json('new_value')->nullable(); // Guardamos cómo quedó después
            $table->text('reason'); // El porqué del cambio

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
