<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAlert extends Model
{
    protected $fillable = ['product_id', 'low_stock_threshold'];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
