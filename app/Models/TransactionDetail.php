<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionDetail extends Model
{
    protected $fillable = [
        'transaction_id', 'product_id', 'quantity', 'price_at_sale', 'discount_per_item', 'tax_rate'
    ];

    protected $casts = [
        'price_at_sale' => 'decimal:2',
        'discount_per_item' => 'decimal:2',
        'tax_rate' => 'decimal:2'
    ];

    // Relationships
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
