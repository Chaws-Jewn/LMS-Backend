<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Locker extends Model
{
    use HasFactory, SoftDeletes;


    public function locker()
    {
        return $this->belongsTo(user::class);
    }

    public function user()
{
    return $this->belongsTo(User::class);
}

    protected $fillable = [
        'locker_number',
        'remarks',
        'status'
    ];

    public function programs()
    {
        return $this->belongsTo(User::class);
    }

    public function departments()
    {
        return $this->belongsTo(User::class);
    }
}
