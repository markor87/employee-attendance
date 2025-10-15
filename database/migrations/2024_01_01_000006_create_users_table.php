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
        Schema::create('Users', function (Blueprint $table) {
            $table->id('UserID');
            $table->string('FirstName', 50);
            $table->string('LastName', 50);
            $table->string('Email', 100)->unique()->nullable();
            $table->string('PasswordHash', 255);
            $table->enum('Role', ['SuperAdmin', 'Admin', 'Zaposleni', 'Kadrovik', 'Rukovodilac']);
            $table->unsignedBigInteger('sector_id')->nullable();
            $table->enum('Status', ['Prijavljen', 'Odjavljen'])->default('Odjavljen')->nullable();
            $table->boolean('PasswordNeedsChange')->default(false);
            $table->string('PasswordHashAlgorithm', 10)->default('SHA256');
            $table->string('Remember_token', 100)->nullable();
            $table->timestamp('DateCreated')->default(DB::raw('CURRENT_TIMESTAMP'))->nullable();
            $table->timestamp('DateUpdated')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'))->nullable();

            // Foreign key constraint
            $table->foreign('sector_id')->references('id')->on('sectors')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Users');
    }
};
