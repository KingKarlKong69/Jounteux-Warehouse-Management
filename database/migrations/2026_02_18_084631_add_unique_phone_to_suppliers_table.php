<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Sanitize existing phone data: strip non-digits, remove leading 0
        DB::table('suppliers')->orderBy('id')->each(function ($supplier) {
            $digits = preg_replace('/\D/', '', $supplier->phone);
            $digits = ltrim($digits, '0');
            DB::table('suppliers')->where('id', $supplier->id)->update(['phone' => $digits]);
        });

        // Handle duplicates: append row id to make phone unique before constraint
        $duplicates = DB::table('suppliers')
            ->select('phone', DB::raw('COUNT(*) as cnt'))
            ->groupBy('phone')
            ->having('cnt', '>', 1)
            ->pluck('phone');

        foreach ($duplicates as $phone) {
            $rows = DB::table('suppliers')->where('phone', $phone)->orderBy('id')->get();
            // Keep first row as-is, modify the rest
            foreach ($rows->skip(1) as $row) {
                DB::table('suppliers')->where('id', $row->id)->update([
                    'phone' => substr($phone, 0, 7) . str_pad($row->id, 3, '0', STR_PAD_LEFT),
                ]);
            }
        }

        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('phone', 10)->change();
            $table->unique('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropUnique(['phone']);
            $table->string('phone')->change();
        });
    }
};
