<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'location_short',
        'location_full'
    ];
    
    public function books(){
        return $this->hasMany(Material::class);
    }
}

