<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Book extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['id', 'call_number', 'title', 'authors', 'publisher',
                            'location_id', 'copyright', 'volume', 'edition', 'remarks',
                            'pages', 'content', 'remarks', 'acquired_date', 'source_of_fund',
                            'price', 'available', 'status'];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function Book(){
        return $this->belongsTo(Book::class);
    }
}
