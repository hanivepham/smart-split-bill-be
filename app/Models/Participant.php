<?php

// app/Models/Participant.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Participant extends Model
{
    protected $fillable = [
        'bill_id',    // Wajib ada di fillable agar createMany() bisa mengisinya
        'name',
        'amount_due',
    ];

    protected $casts = [
        'amount_due' => 'decimal:2',
    ];

    /**
     * RELASI: Setiap Participant DIMILIKI OLEH satu Bill (kebalikan dari hasMany).
     * 
     * Penggunaan: $participant->bill   → objek Bill induk dari peserta ini.
     */
    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }
}