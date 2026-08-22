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
        Schema::table('reports', function (Blueprint $table) {
            $table->boolean('reminder_sent_30')->default(false)->after('status');
            $table->boolean('reminder_sent_14')->default(false)->after('reminder_sent_30');
            $table->boolean('reminder_sent_7')->default(false)->after('reminder_sent_14');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn(['reminder_sent_30', 'reminder_sent_14', 'reminder_sent_7']);
        });
    }
};
