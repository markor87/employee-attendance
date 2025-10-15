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
        Schema::create('reasons', function (Blueprint $table) {
            $table->id('ReasonID');
            $table->string('ReasonName', 100);
            $table->enum('ReasonType', ['Dolazak', 'Odlazak']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reasons');
    }
};
