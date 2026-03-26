<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Expand status enum from 3 to 5 values and add new columns
        DB::statement("ALTER TABLE sales_orders MODIFY COLUMN status ENUM('draft','for_processing','for_shipment','completed','rejected') DEFAULT 'draft'");

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->date('delivery_date')->nullable()->after('order_date');
            $table->text('notes')->nullable()->after('total_amount');
            $table->index('status');
        });
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE sales_orders MODIFY COLUMN status ENUM('pending','completed','cancelled') DEFAULT 'pending'");

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['delivery_date', 'notes']);
        });
    }
};
