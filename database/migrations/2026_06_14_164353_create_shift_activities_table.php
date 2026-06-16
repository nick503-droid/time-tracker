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
        Schema::create('shift_activities', function (Blueprint $table) {
        $table->id();
        $table->foreignId('shift_id')->constrained()->onDelete('cascade');
        // Estados limpios: solo trabajo y descansos oficiales
        $table->string('activity_type');
        $table->dateTime('started_at');
        $table->dateTime('ended_at')->nullable();
        $table->integer('duration_seconds')->nullable();
        $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_activities');
    }
};
