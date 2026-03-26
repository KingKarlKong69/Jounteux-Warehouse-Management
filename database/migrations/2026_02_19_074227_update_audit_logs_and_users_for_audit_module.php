<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update audit_logs table: change action from enum to string, add metadata columns
        Schema::table('audit_logs', function (Blueprint $table) {
            // Add denormalized user info for fast display without joins
            $table->string('user_name')->nullable()->after('user_id');
            $table->string('user_role')->nullable()->after('user_name');

            // Add metadata JSON column for future-proofing
            $table->json('metadata')->nullable()->after('ip_address');
        });

        // Change action column from enum to varchar to support extensible action types
        DB::statement("ALTER TABLE audit_logs MODIFY COLUMN action VARCHAR(50) NOT NULL DEFAULT 'created'");

        // 2. Add failed login tracking + blocking to users table
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('failed_login_attempts')->default(0)->after('remember_token');
            $table->boolean('is_blocked')->default(false)->after('failed_login_attempts');
            $table->timestamp('blocked_at')->nullable()->after('is_blocked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['failed_login_attempts', 'is_blocked', 'blocked_at']);
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn(['user_name', 'user_role', 'metadata']);
        });

        DB::statement("ALTER TABLE audit_logs MODIFY COLUMN action ENUM('created','updated','deleted','login','logout','test') NOT NULL DEFAULT 'created'");
    }
};
