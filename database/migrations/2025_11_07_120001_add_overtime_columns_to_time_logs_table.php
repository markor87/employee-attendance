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
        Schema::table('TimeLogs', function (Blueprint $table) {
            $table->boolean('overtime_auto_checkout')->default(false)->after('DateUpdated');
            $table->text('overtime_notes')->nullable()->after('overtime_auto_checkout');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('TimeLogs', function (Blueprint $table) {
            $table->dropColumn(['overtime_auto_checkout', 'overtime_notes']);
        });
    }
};
