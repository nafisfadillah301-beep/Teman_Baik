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
        Schema::create('pengaduan_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pengaduan')->constrained('pengaduans')->cascadeOnDelete();
            $table->foreignId('id_status')->constrained('statuses');
            $table->text('catatan')->nullable(); // mis. "Diteruskan ke Dinas PUPR"
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengaduan_status_histories');
    }
};
