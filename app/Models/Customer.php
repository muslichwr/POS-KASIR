<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = ['name', 'phone', 'email'];

    // Relationships
    public function pointsHistory()
    {
        return $this->hasMany(CustomerPointsHistory::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
