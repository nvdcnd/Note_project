<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\PaymentFactory;

class Payment extends Model
{
    use HasFactory;
    protected $table = 'payments';
    protected $fillable = [
        'userID',
        'amount',
        'status',
        'point'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'point' => 'decimal:2'
    ];

    public function user(){
        return $this->belongsTo(User::class, 'userID');
    }
}
