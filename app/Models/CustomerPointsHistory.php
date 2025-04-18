<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerPointsHistory extends Model
{
    protected $fillable = ['customer_id', 'points_change', 'source'];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}