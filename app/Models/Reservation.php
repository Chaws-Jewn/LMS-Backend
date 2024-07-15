<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'borrow_materials';

    protected $fillable = [
        'user_id',
        'book_id',
        'reserve_date',
        'reserve_expiration',
        'price',
        'status'
    ];

    protected $casts = [
        'reserve_date' => 'datetime',
        'reserve_expiration' => 'datetime'
    ];

    // Define relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function book()
    {
        return $this->belongsTo(Material::class);
    }
}
