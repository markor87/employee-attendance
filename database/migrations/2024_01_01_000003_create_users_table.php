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
            $table->string('Email', 100)->nullable()->unique();
            $table->string('PasswordHash', 255);
            $table->enum('Role', ['SuperAdmin', 'Admin', 'Zaposleni', 'Kadrovik']);
            $table->enum('Status', ['Prijavljen', 'Odjavljen'])->default('Odjavljen')->nullable();
            $table->boolean('PasswordNeedsChange')->default(0);
            $table->string('PasswordHashAlgorithm', 10)->default('SHA256');
            $table->string('Remember_token', 100)->nullable();
            $table->timestamp('DateCreated')->nullable()->useCurrent();
            $table->timestamp('DateUpdated')->nullable()->useCurrent()->useCurrentOnUpdate();
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
