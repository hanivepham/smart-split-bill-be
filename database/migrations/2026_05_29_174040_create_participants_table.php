<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_participants_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Buat struktur tabel participants di database.
     */
    public function up(): void
    {
        Schema::create('participants', function (Blueprint $table) {
            $table->id();

            // ── Relasi ke Tabel bills ────────────────────────────────
            // foreignId() = shorthand untuk kolom BIGINT UNSIGNED
            // constrained('bills') = tambahkan FOREIGN KEY constraint ke tabel bills
            // onDelete('cascade') = jika bill dihapus, participants ikut terhapus otomatis
            $table->foreignId('bill_id')
                ->constrained('bills')
                ->onDelete('cascade');

            // ── Data Peserta ─────────────────────────────────────────
            $table->string('name', 100);            // Nama peserta, max 100 karakter
            $table->decimal('amount_due', 12, 2);   // Jumlah yang harus dibayar peserta ini

            $table->timestamps();
        });
    }

    /**
     * Rollback: hapus tabel participants terlebih dahulu (karena ada FK ke bills).
     */
    public function down(): void
    {
        Schema::dropIfExists('participants');
    }
};