<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class BorrowMaterial extends Model
{
    use HasFactory;
    protected $fillable = [
        
        'user_id',
        'accession',
        'borrow_date',
        'borrow_expiration',
        'fine'

    ];

        public function material() {
            return $this->belongsTo(material::class, 'book_id', 'accession');
        }

        public function user(){
            return $this->belongsTo(User::class, 'user_id');
        }

}


// 'name',
        // 'patron_type',
        // 'department',
        // 'reason',
        // 'accession_number',
        // 'title',
        // 'location',
        // 'author',
        // 'time',
        // 'num_material',
        // 'gender',
        // 'name_staff',
        // 'position',
        // 'user_fine',
        // 'date_of_request',
        
        // 'fine',
        // 'due',
        // 'status',

