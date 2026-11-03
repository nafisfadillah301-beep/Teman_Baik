<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaduan_lampirans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pengaduan')->constrained('pengaduans')->cascadeOnDelete();
            $table->enum('jenis', ['dokumentasi', 'berita_acara']);
            $table->string('label')->nullable();           // mis. "Sesudah"
            $table->date('tanggal')->nullable();           // tanggal foto diambil
            $table->string('nama_file');                   // nama file di storage/app/lampiran/
            $table->unsignedInteger('ukuran')->nullable(); // dalam byte
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaduan_lampirans');
    }
};
