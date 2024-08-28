<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    use HasFactory;

    protected $primaryKey = 'accession';
    protected $keyType = 'string';
    
    protected $fillable = ['accession', 'material_type', 'title', 'authors', 'publisher', 'image_url',
                            'location', 'volume', 'edition', 'pages', 'remarks', 'acquired_date', 
                            'date_published', 'remarks', 'copyright', 
                            
                            // BOOKS
                            'call_number', 'author_number', 'source_of_fund', 'price', 'status',
                        
                            // PERIODICALS
                            'periodical_type', 'language', 'issue',

                            // ARTICLES
                            'subject', 'abstract'];

    public function book_location() {
        return $this->belongsTo(Location::class, 'location', 'location_short');
    }
}
