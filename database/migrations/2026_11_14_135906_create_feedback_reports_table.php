<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('title', 120);
            $table->text('details');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'resolved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_reports');
    }
};
