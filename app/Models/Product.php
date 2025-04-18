<?php

namespace App\Models;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'category_id', 'supplier_id', 'name', 'slug', 'image', 'price', 'cost_price', 'stock'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'stock' => 'integer'
    ];

    protected static function boot()
    {
         parent::boot();
             static::saving(function ($product)
             {
                 if (empty($product->slug)) {
                     $product->slug = Str::slug($product->name);
                 }
                 else {
                     $originalName = $product->getOriginal('name');
                     if ($originalName !== $product->name) {
                         $product->slug = Str::slug($product->name);
                     }
                 }
             });
    }
    
    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? Storage::url($this->image) : null;
    }

    // Relationships
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function transactions()
    {
        return $this->hasMany(TransactionDetail::class);
    }

    public function alerts()
    {
        return $this->hasMany(ProductAlert::class);
    }

    public function inventoryHistories()
    {
        return $this->hasMany(InventoryHistory::class);
    }
}
