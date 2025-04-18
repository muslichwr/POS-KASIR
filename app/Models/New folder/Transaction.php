<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = ['user_id', 'costumer_id', 'transaction_date', 'total_amount'];

    public function user()
    {
        return $this->beLongsto('user');
    }

    public function costumer()
    {
        return $this->beLongto('costumer');
    }
}
