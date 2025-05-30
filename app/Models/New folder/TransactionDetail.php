<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionDetail extends Model
{
    protected $fillable = ['transaction_id', 'product_id', 'quantity', 'price_at_sale', 'discount_per_item', 'tax_rate'];
}
