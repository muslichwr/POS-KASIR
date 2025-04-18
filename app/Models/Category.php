<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'image', 'parent_id'];

    protected static function boot()
    {
         parent::boot();
             static::saving(function ($category)
             {
                 if (empty($category->slug)) {
                     $category->slug = Str::slug($category->name);
                 }
                 else {
                     $originalName = $category->getOriginal('name');
                     if ($originalName !== $category->name) {
                         $category->slug = Str::slug($category->name);
                     }
                 }
             });
    }
    
    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? Storage::url($this->image) : null;
    }

    public function parent()
    {
        return $this->belongsTo(Category::class);
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products()
    {
         return $this->hasMany(Product::class);
    }



    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }


}
