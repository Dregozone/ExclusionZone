<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')->insert([
            ['id' => 1, 'key' => 'admin', 'name' => 'Administrator', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'key' => 'moderator', 'name' => 'Moderator', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'key' => 'premium', 'name' => 'Premium', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'key' => 'user', 'name' => 'User', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'key' => 'guest', 'name' => 'Guest', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('tasks')->insert([
            ['id' => 1, 'key' => 'view_public_pages', 'description' => 'View public pages', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'key' => 'register_account', 'description' => 'Register account', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'key' => 'login', 'description' => 'Authenticate', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'key' => 'chat_send', 'description' => 'Send chat message', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'key' => 'trade_create', 'description' => 'Create trade', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'key' => 'city_action_perform', 'description' => 'Perform city action', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'key' => 'combat_initiate', 'description' => 'Start combat', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'key' => 'equip_cosmetic', 'description' => 'Equip cosmetic outfit/theme', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 9, 'key' => 'mute_user_temporary', 'description' => 'Temporarily mute user', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 10, 'key' => 'role_change_user', 'description' => 'Change another user role', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'key' => 'moderate_chat_messages', 'description' => 'Moderate chat messages', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 12, 'key' => 'view_admin_dashboard', 'description' => 'Access admin controls', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('role_task')->insert([
            ['role_id' => 5, 'task_id' => 1], ['role_id' => 5, 'task_id' => 2], ['role_id' => 5, 'task_id' => 3],
            ['role_id' => 4, 'task_id' => 1], ['role_id' => 4, 'task_id' => 3], ['role_id' => 4, 'task_id' => 4], ['role_id' => 4, 'task_id' => 5], ['role_id' => 4, 'task_id' => 6], ['role_id' => 4, 'task_id' => 7],
            ['role_id' => 3, 'task_id' => 1], ['role_id' => 3, 'task_id' => 3], ['role_id' => 3, 'task_id' => 4], ['role_id' => 3, 'task_id' => 5], ['role_id' => 3, 'task_id' => 6], ['role_id' => 3, 'task_id' => 7], ['role_id' => 3, 'task_id' => 8],
            ['role_id' => 2, 'task_id' => 1], ['role_id' => 2, 'task_id' => 3], ['role_id' => 2, 'task_id' => 4], ['role_id' => 2, 'task_id' => 5], ['role_id' => 2, 'task_id' => 6], ['role_id' => 2, 'task_id' => 7], ['role_id' => 2, 'task_id' => 9], ['role_id' => 2, 'task_id' => 11],
            ['role_id' => 1, 'task_id' => 1], ['role_id' => 1, 'task_id' => 3], ['role_id' => 1, 'task_id' => 4], ['role_id' => 1, 'task_id' => 5], ['role_id' => 1, 'task_id' => 6], ['role_id' => 1, 'task_id' => 7], ['role_id' => 1, 'task_id' => 9], ['role_id' => 1, 'task_id' => 10], ['role_id' => 1, 'task_id' => 11], ['role_id' => 1, 'task_id' => 12],
        ]);
    }

    public function down(): void
    {
        DB::table('role_task')->delete();
        DB::table('tasks')->whereIn('key', [
            'view_public_pages',
            'register_account',
            'login',
            'chat_send',
            'trade_create',
            'city_action_perform',
            'combat_initiate',
            'equip_cosmetic',
            'mute_user_temporary',
            'role_change_user',
            'moderate_chat_messages',
            'view_admin_dashboard',
        ])->delete();
        DB::table('roles')->whereIn('key', ['admin', 'moderator', 'premium', 'user', 'guest'])->delete();
    }
};
