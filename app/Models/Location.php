<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use HasFactory, SoftDeletes;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'location_short';
    protected $fillable = [
        'location_short',
        'location_full'
    ];

    public function books(){
        return $this->hasMany(Material::class);
    }
}

