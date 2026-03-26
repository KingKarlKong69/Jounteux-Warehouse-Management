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
        Schema::table('users', function (Blueprint $table) {
            // Contact number for user management
            $table->string('contact_number', 30)->nullable()->after('email');

            // Block reason (enum string) for user management
            $table->string('block_reason', 50)->nullable()->after('blocked_at');

            // Indexes for fast filtering
            $table->index('role');
            $table->index('is_blocked');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['contact_number', 'block_reason']);
            $table->dropIndex(['role']);
            $table->dropIndex(['is_blocked']);
            $table->dropIndex(['created_at']);
        });
    }
};
