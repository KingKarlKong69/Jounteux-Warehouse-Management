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
        // 1. Drop FK constraint on products.category_id
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
        });

        // 2. Drop old categories table entirely and recreate with varchar id
        Schema::dropIfExists('categories');

        Schema::create('categories', function (Blueprint $table) {
            $table->string('id', 10)->primary(); // VARCHAR PK, e.g. "AG", "CC"
            $table->string('name')->unique();
            $table->timestamps();

            $table->index('name');
        });

        // 3. Change products.category_id from unsignedBigInteger to string
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('category_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('category_id', 10)->nullable()->after('description');
            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });

        Schema::dropIfExists('categories');

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
            $table->index('name');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('description')->constrained('categories')->nullOnDelete();
        });
    }
};
