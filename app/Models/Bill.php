<?php

// app/Models/Bill.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bill extends Model
{
    /**
     * $fillable adalah "whitelist" kolom yang boleh diisi via Mass Assignment.
     * 
     * Mass Assignment = mengisi model dari array sekaligus, contoh: Bill::create([...])
     * Tanpa $fillable, Laravel MENOLAK semua Mass Assignment sebagai proteksi keamanan
     * (mencegah user mengirim kolom berbahaya seperti 'is_admin' secara sembunyii).
     */
    protected $fillable = [
        'subtotal',
        'discount',
        'delivery_fee',
        'service_fee',
        'packaging_fee',
        'tax_amount',
        'grand_total',
        'split_method',
    ];

    /**
     * $casts memastikan tipe data dikembalikan dengan benar di JSON response.
     * 
     * Tanpa ini, nilai decimal dari MySQL bisa dikembalikan sebagai string biasa
     * seperti "120000" (tanpa format). Dengan 'decimal:2', nilainya menjadi "120000.00"
     * yang konsisten dan mudah diproses oleh Frontend.
     */
    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'service_fee' => 'decimal:2',
        'packaging_fee' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    /**
     * RELASI: Satu Bill MEMILIKI BANYAK Participants (One-to-Many).
     * 
     * Eloquent otomatis mencari Foreign Key 'bill_id' di tabel participants.
     * Penggunaan: $bill->participants   → koleksi semua peserta bill ini.
     */
    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }
}