<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bill extends Model
{
    /**
     * $fillable adalah "whitelist" kolom yang boleh diisi via Mass Assignment.
     */
    protected $fillable = [
        'user_id',
        'subtotal',
        'discount',
        'delivery_fee',
        'service_fee',
        'packaging_fee',
        'tax_amount',
        'grand_total',
        'split_method',
        'qr_image_path',
        'payment_type',
        'bank_name',
        'account_number',
        'account_name' // Nggak ada yang double lagi, udah rapi!
    ];

    /**
     * $casts memastikan tipe data dikembalikan dengan benar di JSON response.
     */
    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'service_fee' => 'decimal:2',
        'packaging_fee' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'payment_type' => 'string',
        'bank_name' => 'string',
        'account_number' => 'string',
        'account_name' => 'string'
    ];

    /**
     * RELASI: Satu Bill MEMILIKI BANYAK Participants (One-to-Many).
     */
    protected $appends = ['qr_image_url'];

    public function getQrImageUrlAttribute()
    {
        return $this->qr_image_path ? asset('storage/' . $this->qr_image_path) : null;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }
}