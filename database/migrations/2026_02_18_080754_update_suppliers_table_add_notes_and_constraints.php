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
        Schema::table('suppliers', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('address');
            $table->string('contact_person')->nullable()->change();
            $table->text('address')->nullable()->change();
            $table->unique('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->dropColumn('notes');
            $table->string('contact_person')->nullable(false)->change();
            $table->text('address')->nullable(false)->change();
        });
    }
};
