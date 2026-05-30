<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_bills_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Buat struktur tabel bills di database.
     */
    public function up(): void
    {
        Schema::create('bills', function (Blueprint $table) {
            $table->id();                                          // BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY

            // ── Komponen Biaya ──────────────────────────────────────
            $table->decimal('subtotal', 12, 2);                   // Harga item sebelum fee/diskon
            $table->decimal('discount', 12, 2)->default(0);       // Potongan harga
            $table->decimal('delivery_fee', 12, 2)->default(0);   // Ongkos kirim
            $table->decimal('service_fee', 12, 2)->default(0);    // Biaya layanan
            $table->decimal('packaging_fee', 12, 2)->default(0);  // Biaya kemasan
            $table->decimal('tax_amount', 12, 2)->default(0);     // Pajak (misal PPN 11%)
            $table->decimal('grand_total', 12, 2);                // Total akhir yang harus dibayar

            // ── Metode Split ─────────────────────────────────────────
            // 'rata' = dibagi rata semua peserta
            // 'custom' = setiap peserta punya porsi berbeda (prorata berdasarkan item)
            $table->enum('split_method', ['rata', 'custom']);

            $table->timestamps(); // created_at + updated_at
        });
    }

    /**
     * Rollback: hapus tabel jika migration di-undo.
     */
    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};