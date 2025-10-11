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
        Schema::create('timelogs', function (Blueprint $table) {
            $table->id('LogID');
            $table->unsignedBigInteger('UserID');
            $table->integer('PerformedByPrijava');
            $table->integer('PerformedByOdjava')->nullable();
            $table->dateTime('VremePrijave')->nullable();
            $table->dateTime('VremeOdjave')->nullable();
            $table->date('RadniDatum')->nullable();
            $table->string('RazlogPrijave', 255)->nullable();
            $table->string('RazlogOdjave', 255)->nullable();
            $table->string('IpAdresaPrijave', 45);
            $table->string('IpAdresaOdjave', 45)->nullable();
            $table->mediumText('Napomena')->nullable();
            $table->timestamp('DateCreated')->useCurrent();
            $table->timestamp('DateUpdated')->useCurrent()->useCurrentOnUpdate();

            // Foreign key constraint
            $table->foreign('UserID')->references('UserID')->on('Users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timelogs');
    }
};
