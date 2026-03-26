<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->enum('reference_type', ['purchase', 'sale', 'adjustment']);
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->enum('movement_type', ['in', 'out']);
            $table->integer('quantity');
            $table->integer('balance_after');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('product_id');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_ledgers');
    }
};