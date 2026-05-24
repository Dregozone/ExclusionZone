<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('description');
            $table->timestamps();
        });

        Schema::create('role_task', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'task_id']);
        });

        Schema::create('role_change_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('target_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('old_role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->foreignId('new_role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_change_audits');
        Schema::dropIfExists('role_task');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('roles');
    }
};
