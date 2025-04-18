<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $fillable = ['name', 'is_active'];

    // Relationships
    public function payments()
    {
        return $this->hasMany(TransactionPayment::class);
    }
}