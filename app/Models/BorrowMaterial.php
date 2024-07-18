<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class BorrowMaterial extends Model
{
    use HasFactory;
    protected $fillable = [
        
        'user_id',
        'book_id',
        'borrow_date',
        'borrow_expiration',
        'fine',
        




        
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

    ];

        public function material() {
            return $this->belongsTo(Material::class, 'book_id', 'accession');
        }

        public function user(){
            return $this->belongsTo(User::class, 'user_id');
        }
}
