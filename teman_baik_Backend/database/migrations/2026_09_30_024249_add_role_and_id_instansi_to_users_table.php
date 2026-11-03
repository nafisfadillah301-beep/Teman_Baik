<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['super_admin', 'admin_instansi'])->default('super_admin')->after('email');
            $table->foreignId('id_instansi')->nullable()->constrained('instansis')->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['id_instansi']);
            $table->dropColumn(['role', 'id_instansi']);
        });
    }
};
