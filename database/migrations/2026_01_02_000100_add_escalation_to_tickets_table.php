<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->timestamp('escalated_at')->nullable()->after('resolved_within_target');
            $table->unsignedSmallInteger('escalation_count')->default(0)->after('escalated_at');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropColumn(['escalated_at', 'escalation_count']);
        });
    }
};
