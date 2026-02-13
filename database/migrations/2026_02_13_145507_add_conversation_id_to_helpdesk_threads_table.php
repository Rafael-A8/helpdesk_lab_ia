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
        Schema::table('helpdesk_threads', function (Blueprint $table) {
            $table->string('conversation_id', 36)->nullable()->index()->after('agent_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('helpdesk_threads', function (Blueprint $table) {
            $table->dropColumn('conversation_id');
        });
    }
};
