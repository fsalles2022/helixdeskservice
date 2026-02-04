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
        Schema::create('failed_events', function (Blueprint $table) {
            $table->id();
            $table->string('exchange')->nullable();
            $table->string('routing_key')->nullable();
            $table->json('payload');
            $table->string('error')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('failed_events');
    }
};
