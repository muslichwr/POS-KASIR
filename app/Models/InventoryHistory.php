<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class InventoryHistory extends Model
{
    protected $fillable = [
        'product_id', 'user_id', 'type', 'quantity_change', 'cost_price', 'notes'
    ];

    protected $casts = [
        'quantity_change' => 'integer',
        'cost_price' => 'decimal:2'
    ];
    
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->user_id)) {
                $model->user_id = Auth::id() ?? 1; // Default ke user ID 1 jika tidak ada user yang login
            }
        });
    }

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