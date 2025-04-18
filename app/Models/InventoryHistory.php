<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryHistory extends Model
{
    protected $fillable = [
        'product_id', 'user_id', 'type', 'quantity_change', 'cost_price', 'notes'
    ];

    protected $casts = [
        'quantity_change' => 'integer',
        'cost_price' => 'decimal:2'
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}