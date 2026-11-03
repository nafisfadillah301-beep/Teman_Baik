<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaduans', function (Blueprint $table) {
            $table->text('balasan_instansi')->nullable()->after('jawaban');
            $table->timestamp('balasan_instansi_at')->nullable()->after('balasan_instansi');
        });
    }

    public function down(): void
    {
        Schema::table('pengaduans', function (Blueprint $table) {
            $table->dropColumn(['balasan_instansi', 'balasan_instansi_at']);
        });
    }
};
