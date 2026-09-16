<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attribute_changes', function (Blueprint $table): void {
            $table->id();
            $table->morphs('recordable');
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('attribute');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['recordable_type', 'recordable_id', 'created_at'], 'attribute_changes_subject_index');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_changes');
    }
};
